<?php

abstract class ApiController
{
    public function __construct(
        protected PDO $pdo,
        protected array $config,
        protected SecurityLogger $logger,
        protected AuditLogService $audit
    ) {
    }

    protected function limit(ApiRequest $request, int $default = 30, int $max = 120): int
    {
        return max(1, min($max, (int) ($request->query['limit'] ?? $default)));
    }

    protected function cleanPayload(array $data): array
    {
        return array_map(static fn ($value) => is_string($value) ? clean_text($value) : $value, $data);
    }
}
