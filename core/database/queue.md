# Filas e processamento assincrono

O modulo de filas usa `queue_jobs` como fonte unica de verdade e `queue_job_logs` para auditoria de execucao. Os payloads devem guardar referencias por ID, nunca blobs ou copias de imagens.

## Tipos de fila

- `ai_text`: resumo, tags automaticas e classificacao operacional.
- `ai_visual`: simulacao visual por IA a partir de um upload existente.
- `thumbnail`: geracao de miniaturas.
- `compression`: otimizacao de imagem original.
- `notification`: alertas internos, follow-ups e retornos preventivos.

## Worker local

Execute em cron/cPanel ou terminal local:

```bash
php core/workers/queue-worker.php 5
```

O numero final define quantos jobs serao processados por execucao. Em hospedagem compartilhada, prefira rodadas curtas e frequentes para nao bloquear recursos.
