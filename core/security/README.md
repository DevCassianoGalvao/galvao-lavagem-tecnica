# Camada de Seguranca

## Servicos

- `SecurityService`: headers, metodo HTTP e respostas JSON padronizadas.
- `SessionService`: cookies `HttpOnly`, `SameSite=Lax`, modo estrito, timeout e rotacao de ID.
- `CsrfService`: token unico por sessao e validacao em formularios/APIs.
- `AuthService`: login com `password_verify`, logout seguro e logs.
- `RateLimitService`: limitacao por IP/sessao/acao com fallback em sessao e persistencia em `rate_limits`.
- `UploadSecurityService`: MIME, extensao, tamanho, assinatura de imagem e bloqueio de conteudo malicioso.
- `SecurityLogger`: logs de seguranca em `logs` ou arquivo fallback.

## Regras

- Toda query nova deve usar PDO prepared statements.
- Toda saida HTML deve usar `e()`.
- Toda API mutavel deve validar CSRF e rate limit.
- Uploads devem ser salvos fora de `public` e nunca preservar nome original como caminho.
- Arquivos em `storage` permanecem bloqueados por `.htaccess`.
- Senhas devem usar `AuthService::hashPassword()`.

## Criar usuario admin

Gere um hash seguro com PHP:

```php
echo AuthService::hashPassword('senha-forte-aqui');
```

Depois insira em `users.password_hash`.
