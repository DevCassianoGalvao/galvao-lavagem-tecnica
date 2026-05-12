# Logs e auditoria

O modulo de auditoria usa a tabela `logs` como trilha unica para:

- `admin`: login, logout, usuarios, configuracoes, uploads e alteracoes administrativas.
- `ai`: geracao textual, geracao visual, erros, fallback e limites.
- `security`: CSRF, falhas de login, rate limit e eventos suspeitos.
- `operational`: kanban, calendario, produtos, observacoes e rotinas de atendimento.
- `backup`: geracao, falha, limpeza e download de backups.

Indices relevantes adicionados ao schema:

- `idx_logs_channel_created`
- `idx_logs_action_created`
- `idx_logs_ip_created`
- `idx_logs_user_created`

A tela `admin/?page=auditoria` permite filtrar por usuario, data, categoria, nivel e busca livre.
