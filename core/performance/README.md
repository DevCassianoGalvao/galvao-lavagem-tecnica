# Performance

Camada inicial de otimizacao da plataforma Galvao Lavagem Tecnica.

## Assets

Execute antes de publicar em cPanel:

```bash
php tools/build-assets.php
```

O build gera:

- `public/assets/dist/landing.min.css`
- `public/assets/dist/landing.min.js`
- `admin/assets/dist/admin.min.css`
- `admin/assets/dist/admin.min.js`

Os arquivos PHP carregam automaticamente o bundle versionado quando ele existir, com fallback para os arquivos modulares durante desenvolvimento.

## Imagens

Uploads passam pelo `ImageOptimizationService`:

- redimensionamento do original para largura maxima operacional;
- geracao automatica de thumbnail;
- hash SHA-256 para evitar duplicacao;
- entrega via `admin/api/image.php` com `ETag`, `Last-Modified` e cache privado.

## Cache

`CacheService` cria caches JSON em `storage/cache` para dados administrativos de baixa volatilidade. Nao armazene segredos persistentes neste cache.

## Banco

O schema inclui indices para dashboards, calendario, notificacoes, banco visual, uploads, produtos e recorrencia. Em producao, rode `EXPLAIN` nas consultas mais acessadas antes de aumentar volume.
