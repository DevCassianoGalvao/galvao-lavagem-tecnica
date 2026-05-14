# Deploy Galvao Lavagem Tecnica

## Ambientes

- `local`: desenvolvimento em XAMPP ou servidor local.
- `staging`: homologacao com dados controlados.
- `production`: producao em cPanel.

Use `.env` para credenciais reais. Nunca exponha `.env`, `core`, `storage`, `logs` ou `vendor` publicamente.

## Preparacao local

1. Copie `.env.example` para `.env`.
2. Ajuste `APP_URL`, `DB_*` e chaves de API.
3. Rode:

```bash
php tools/prepare-deploy.php
php tools/build-assets.php
php tools/deploy-check.php
```

## Produção cPanel

1. Envie o projeto para uma pasta fora de `public_html`, quando possivel.
2. Aponte o dominio para a pasta `public`, ou mantenha as protecoes `.htaccess` da raiz.
3. Copie `.env.production.example` para `.env`.
4. Configure:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://www.galvaolavagemtecnica.com.br`
   - credenciais MySQL do cPanel
   - `OPENAI_API_KEY`
5. Importe `core/database/schema.sql` no MySQL.
6. Garanta permissao de escrita em:
   - `storage/uploads`
   - `storage/thumbnails`
   - `storage/ai-images`
   - `storage/temp`
   - `storage/backups`
   - `logs/production`
7. Rode `php tools/deploy-check.php`.

## Tarefas agendadas sugeridas

No cron do cPanel:

```bash
php /caminho/do/projeto/core/workers/queue-worker.php 5
php /caminho/do/projeto/core/workers/notifications-worker.php
php /caminho/do/projeto/core/workers/recurrence-worker.php
php /caminho/do/projeto/core/workers/backup-worker.php daily
```

## Segurança

- `.env` fica fora do versionamento.
- `core`, `storage` e `logs` possuem `.htaccess` bloqueando acesso direto.
- Uploads devem ser servidos por proxy controlado, como `admin/api/image.php`.
- Em producao, mantenha `APP_DEBUG=false`.
