# Backups operacionais

Tabela adicionada ao `schema.sql`:

- `backup_logs`: registra frequencia, arquivo, hash, tamanho, status, duracao, usuario e erro.

Rotinas recomendadas no cPanel Cron:

```bash
/usr/local/bin/php /home/USUARIO/public_html/core/workers/backup-worker.php daily 1
/usr/local/bin/php /home/USUARIO/public_html/core/workers/backup-worker.php weekly 1
```

Sugestao:

- diario: uma vez por madrugada;
- semanal: domingo de madrugada;
- manter `storage/backups` fora de `public_html` sempre que possivel.

O servico usa `ZipArchive` quando disponivel e cai para `.tar.gz` com `PharData` quando ZIP nao estiver ativo.
