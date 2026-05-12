# API interna

Endpoint principal:

```text
/admin/api/index.php?route=/clients
/admin/api/clients
```

Em cPanel, o arquivo `admin/api/.htaccess` reescreve rotas amigaveis para `index.php`. Se a hospedagem nao permitir rewrite, use o parametro `route`.

## Padrao de resposta

Sucesso:

```json
{ "success": true, "message": "OK", "data": {} }
```

Erro:

```json
{ "success": false, "message": "Mensagem", "error": {} }
```

Validacao:

```json
{ "success": false, "message": "Dados invalidos.", "errors": {} }
```

## Rotas iniciais

- `GET /clients`
- `POST /clients`
- `GET /clients/{id}`
- `POST /clients/{id}`
- `GET /leads`
- `POST /leads`
- `GET /leads/{id}`
- `POST /leads/{id}`
- `GET /uploads`
- `POST /uploads`
- `POST /ai/text`
- `POST /ai/visual`
- `GET /calendar`
- `POST /calendar`
- `GET /calendar/{id}`
- `POST /calendar/{id}`
- `POST /kanban/move`
- `GET /products`
- `POST /products`
- `POST /products/{id}/usage`

Requisicoes mutaveis exigem usuario autenticado, permissao compativel e `_csrf_token` no corpo ou `X-CSRF-Token`.
