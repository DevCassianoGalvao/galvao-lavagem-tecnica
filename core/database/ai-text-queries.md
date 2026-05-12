# IA Textual

## Processar lead sem regenerar desnecessariamente

```php
$service = new AiTextService($pdo, $config);
$result = $service->analyzeLead($leadId, false);
```

Use `force=true` apenas quando o lead, notas, superficies ou sujeiras mudarem de forma relevante e voce quiser ignorar o cache por `prompt_hash`.

## Enfileirar processamento assincrono

```php
$jobs = new AiJobService($pdo);
$jobId = $jobs->enqueueLeadAnalysis($leadId);
```

## Executar um job pendente

```php
$worker = new AiQueueWorker($pdo, $config);
$worker->runOnce();
```

## Cron em cPanel

```bash
php /home/usuario/public_html/core/workers/ai-text-worker.php
```

## Tabelas envolvidas

- `ai_summaries`: resumo elegante do cliente/lead.
- `ai_tag_suggestions`: tags sugeridas, aprovadas ou rejeitadas.
- `ai_classifications`: classificacao simples, medio ou pesado.
- `ai_jobs`: fila para processamento assincrono.
- `logs`: trilha de auditoria e falhas de IA.
