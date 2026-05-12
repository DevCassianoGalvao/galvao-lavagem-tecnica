# Galvao Lavagem Tecnica

Estrutura inicial proprietaria para uma plataforma premium de lavagem tecnica com landing page, quiz inteligente, CRM interno, kanban, calendario, upload de imagens, IA visual, IA textual e dashboard administrativo.

## Stack

- HTML5
- CSS3
- JavaScript Vanilla
- PHP
- MySQL

## Como rodar localmente

1. Coloque a pasta do projeto dentro do ambiente local PHP, como `htdocs` no XAMPP.
2. Copie `.env.example` para `.env`.
3. Ajuste as credenciais do banco MySQL e URLs no arquivo `.env`.
4. Instale o banco:

```bash
php tools/install-database.php
```

Ou, no phpMyAdmin, importe `core/database/schema.sql` e depois `core/database/seed-admin.sql`.

5. Acesse:
   - Landing: `/public/landing/`
   - Admin: `/admin/`

Login local inicial:

- E-mail: `admin@galvao.local`
- Senha: `Admin@12345`

## Deploy

A estrutura suporta `local`, `staging` e `production` por meio de `.env` e arquivos em `core/config/environments`.

Comandos uteis:

```bash
php tools/prepare-deploy.php
php tools/build-assets.php
php tools/deploy-check.php
```

Veja o guia completo em `DEPLOY.md`.

## Estrutura

```text
public/      Landing page e ativos publicos
admin/       Area administrativa, views, componentes, ajax e api
core/        Configuracoes, database, helpers, services, security e auth
storage/     Uploads, thumbnails, imagens IA e temporarios
logs/        Logs da aplicacao
vendor/      Dependencias futuras
```

## Observacoes

Este esqueleto foi criado sem frameworks frontend para facilitar hospedagem em cPanel e evolucao progressiva.
