<?php

final class BackupService
{
    private const FREQUENCIES = ['daily', 'weekly'];
    private const RETENTION = ['daily' => 14, 'weekly' => 8];

    public function __construct(
        private PDO $pdo,
        private array $config,
        private ?SecurityLogger $logger = null
    ) {
    }

    public function create(string $frequency = 'daily', bool $includeUploads = true, ?int $createdBy = null): array
    {
        $frequency = $this->normalizeFrequency($frequency);
        $this->ensureDirectories();

        if (!class_exists('ZipArchive') && !class_exists('PharData')) {
            throw new RuntimeException('Ative ZipArchive ou PharData no PHP para gerar backups compactados.');
        }

        $startedAt = microtime(true);
        $stamp = date('Ymd-His');
        $token = bin2hex(random_bytes(4));
        $useZip = class_exists('ZipArchive');
        $extension = $useZip ? 'zip' : 'tar.gz';
        $filename = sprintf('galvao-backup-%s-%s-%s.%s', $frequency, $stamp, $token, $extension);
        $targetPath = $this->frequencyDir($frequency) . '/' . $filename;
        $tempSqlPath = STORAGE_PATH . '/backups/temp/database-' . $stamp . '-' . $token . '.sql';
        $tempZipPath = STORAGE_PATH . '/backups/temp/' . $filename;

        $status = 'completed';
        $error = null;

        try {
            $this->writeDatabaseDump($tempSqlPath);

            if ($useZip) {
                $archive = new ZipArchive();

                if ($archive->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new RuntimeException('Nao foi possivel criar o arquivo compactado.');
                }

                $archive->addFile($tempSqlPath, 'database/schema-and-data.sql');
                $archive->addFromString('metadata.json', $this->metadataJson($frequency, $includeUploads));
            } else {
                $tarPath = STORAGE_PATH . '/backups/temp/' . str_replace('.tar.gz', '.tar', $filename);
                @unlink($tarPath);
                @unlink($tarPath . '.gz');
                $archive = new PharData($tarPath);
                $archive->addFile($tempSqlPath, 'database/schema-and-data.sql');
                $archive->addFromString('metadata.json', $this->metadataJson($frequency, $includeUploads));
            }

            if ($includeUploads) {
                $this->addDirectoryToArchive($archive, STORAGE_PATH . '/uploads', 'storage/uploads');
                $this->addDirectoryToArchive($archive, STORAGE_PATH . '/thumbnails', 'storage/thumbnails');
                $this->addDirectoryToArchive($archive, STORAGE_PATH . '/ai-images', 'storage/ai-images');
            }

            if ($archive instanceof ZipArchive) {
                $archive->close();
            } else {
                $archive->compress(Phar::GZ);
                unset($archive);
                @unlink($tarPath);
                $tempZipPath = $tarPath . '.gz';
            }

            rename($tempZipPath, $targetPath);
        } catch (Throwable $exception) {
            $status = 'failed';
            $error = $exception->getMessage();
            @unlink($tempZipPath);
            if (isset($tarPath)) {
                @unlink($tarPath);
                @unlink($tarPath . '.gz');
            }
            throw $exception;
        } finally {
            @unlink($tempSqlPath);
            $this->writeLog($frequency, $filename, $targetPath, $status, $includeUploads, $createdBy, $startedAt, $error);
        }

        $this->cleanup($frequency);

        return $this->fileInfo($targetPath, $frequency);
    }

    private function metadataJson(string $frequency, bool $includeUploads): string
    {
        return json_encode([
                'app' => $this->config['app_name'] ?? 'Galvao Lavagem Tecnica',
                'frequency' => $frequency,
                'created_at' => date(DATE_ATOM),
                'includes_uploads' => $includeUploads,
                'storage' => ['uploads', 'thumbnails', 'ai-images'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    public function cleanup(?string $frequency = null): array
    {
        $frequencies = $frequency ? [$this->normalizeFrequency($frequency)] : self::FREQUENCIES;
        $removed = [];

        foreach ($frequencies as $item) {
            $files = $this->files($item);
            $keep = self::RETENTION[$item];

            foreach (array_slice($files, $keep) as $file) {
                if (@unlink($file['path'])) {
                    $removed[] = $file['name'];
                }
            }
        }

        if ($removed) {
            $this->logger?->log('info', 'backups_cleaned', 'Backups antigos removidos.', ['files' => $removed]);
        }

        return $removed;
    }

    public function list(): array
    {
        return [
            'daily' => $this->files('daily'),
            'weekly' => $this->files('weekly'),
        ];
    }

    public function status(): array
    {
        $list = $this->list();
        $all = array_merge($list['daily'], $list['weekly']);
        $latest = $all[0] ?? null;

        usort($all, static fn (array $a, array $b): int => $b['created_at_ts'] <=> $a['created_at_ts']);
        $latest = $all[0] ?? null;

        return [
            'latest' => $latest,
            'daily_count' => count($list['daily']),
            'weekly_count' => count($list['weekly']),
            'total_size' => array_sum(array_column($all, 'size_bytes')),
            'retention' => self::RETENTION,
        ];
    }

    public function downloadPath(string $frequency, string $filename): string
    {
        $frequency = $this->normalizeFrequency($frequency);
        $filename = basename($filename);

        if (!preg_match('/^galvao-backup-(daily|weekly)-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}\.(zip|tar\.gz)$/', $filename)) {
            throw new InvalidArgumentException('Arquivo de backup invalido.');
        }

        $path = realpath($this->frequencyDir($frequency) . '/' . $filename);
        $root = realpath($this->frequencyDir($frequency));

        if (!$path || !$root || !str_starts_with($path, $root) || !is_file($path)) {
            throw new RuntimeException('Backup nao encontrado.');
        }

        return $path;
    }

    private function writeDatabaseDump(string $path): void
    {
        $database = (string) $this->config['db_name'];
        $handle = fopen($path, 'wb');

        if (!$handle) {
            throw new RuntimeException('Nao foi possivel preparar o dump SQL.');
        }

        fwrite($handle, "-- Backup Galvao Lavagem Tecnica\n");
        fwrite($handle, "-- Gerado em " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $this->pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM);

        foreach ($tables as $row) {
            $table = (string) $row[0];
            $create = $this->pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_ASSOC);
            $createSql = (string) ($create['Create Table'] ?? '');

            fwrite($handle, "DROP TABLE IF EXISTS `" . str_replace('`', '``', $table) . "`;\n");
            fwrite($handle, $createSql . ";\n\n");

            $stmt = $this->pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
            $columns = [];
            $insertPrefix = '';

            while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!$columns) {
                    $columns = array_keys($record);
                    $insertPrefix = 'INSERT INTO `' . str_replace('`', '``', $table) . '` (`'
                        . implode('`, `', array_map(static fn (string $column): string => str_replace('`', '``', $column), $columns))
                        . '`) VALUES ';
                }

                $values = array_map(fn (mixed $value): string => $this->sqlValue($value), array_values($record));
                fwrite($handle, $insertPrefix . '(' . implode(', ', $values) . ");\n");
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    private function addDirectoryToArchive(mixed $archive, string $directory, string $zipRoot): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $path = $file->getRealPath();
            $relative = str_replace('\\', '/', substr($path, strlen(realpath($directory)) + 1));
            $archive->addFile($path, $zipRoot . '/' . $relative);
        }
    }

    private function files(string $frequency): array
    {
        $frequency = $this->normalizeFrequency($frequency);
        $this->ensureDirectories();
        $paths = array_merge(
            glob($this->frequencyDir($frequency) . '/galvao-backup-' . $frequency . '-*.zip') ?: [],
            glob($this->frequencyDir($frequency) . '/galvao-backup-' . $frequency . '-*.tar.gz') ?: []
        );
        $files = array_map(fn (string $path): array => $this->fileInfo($path, $frequency), $paths);

        usort($files, static fn (array $a, array $b): int => $b['created_at_ts'] <=> $a['created_at_ts']);

        return $files;
    }

    private function fileInfo(string $path, string $frequency): array
    {
        return [
            'name' => basename($path),
            'frequency' => $frequency,
            'path' => $path,
            'size_bytes' => is_file($path) ? filesize($path) : 0,
            'size_label' => $this->formatBytes(is_file($path) ? filesize($path) : 0),
            'created_at' => is_file($path) ? date('d/m/Y H:i', filemtime($path)) : '',
            'created_at_ts' => is_file($path) ? filemtime($path) : 0,
            'sha256' => is_file($path) ? hash_file('sha256', $path) : '',
        ];
    }

    private function writeLog(string $frequency, string $filename, string $path, string $status, bool $includeUploads, ?int $createdBy, float $startedAt, ?string $error): void
    {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $size = is_file($path) ? filesize($path) : 0;
        $hash = is_file($path) ? hash_file('sha256', $path) : null;

        try {
            $relativePath = str_replace('\\', '/', substr($path, strlen(STORAGE_PATH) + 1));
            $stmt = $this->pdo->prepare(
                'INSERT INTO backup_logs (
                    created_by, frequency, filename, storage_path, size_bytes, sha256_hash,
                    status, includes_uploads, duration_ms, error_message, created_at
                ) VALUES (
                    :created_by, :frequency, :filename, :storage_path, :size_bytes, :sha256_hash,
                    :status, :includes_uploads, :duration_ms, :error_message, NOW()
                )'
            );
            $stmt->execute([
                'created_by' => $createdBy,
                'frequency' => $frequency,
                'filename' => $filename,
                'storage_path' => $relativePath,
                'size_bytes' => $size,
                'sha256_hash' => $hash,
                'status' => $status,
                'includes_uploads' => $includeUploads ? 1 : 0,
                'duration_ms' => $durationMs,
                'error_message' => $error,
            ]);
        } catch (Throwable) {
            // O arquivo de log cobre ambientes antes da migracao do banco.
        }

        $level = $status === 'completed' ? 'info' : 'error';
        $this->logger?->log($level, 'backup_' . $status, 'Rotina de backup finalizada.', [
            'frequency' => $frequency,
            'filename' => $filename,
            'size_bytes' => $size,
            'duration_ms' => $durationMs,
            'error' => $error,
        ]);

        try {
            (new AuditLogService($this->pdo))->write('backup', $level, 'backup_' . $status, 'Rotina de backup finalizada.', [
                'frequency' => $frequency,
                'filename' => $filename,
                'size_bytes' => $size,
                'duration_ms' => $durationMs,
                'error' => $error,
            ], $createdBy, 'backup', null);
        } catch (Throwable) {
            // SecurityLogger/file fallback above preserves minimum observability.
        }
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $this->pdo->quote((string) $value);
    }

    private function ensureDirectories(): void
    {
        foreach (['daily', 'weekly', 'temp'] as $dir) {
            $path = STORAGE_PATH . '/backups/' . $dir;

            if (!is_dir($path)) {
                mkdir($path, 0775, true);
            }
        }
    }

    private function frequencyDir(string $frequency): string
    {
        return STORAGE_PATH . '/backups/' . $this->normalizeFrequency($frequency);
    }

    private function normalizeFrequency(string $frequency): string
    {
        $frequency = strtolower(trim($frequency));

        if (!in_array($frequency, self::FREQUENCIES, true)) {
            throw new InvalidArgumentException('Tipo de backup invalido.');
        }

        return $frequency;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', '.') . ' KB';
        }

        return $bytes . ' B';
    }
}
