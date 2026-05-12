# Simulacao Visual com IA

## Fluxo

1. Frontend comprime a imagem no navegador antes do envio.
2. `admin/api/upload.php` valida CSRF, IP, sessao, lead, cooldown e limite interno.
3. `ImageUploadService` salva a imagem original uma unica vez em `uploads`.
4. `AiVisualService` chama GPT-Image-2 quando `openai_api_key` estiver configurado.
5. Se a API nao estiver disponivel, um fallback visual local e gerado para manter a UX fluida.
6. O resultado recebe watermark leve e e salvo como upload `ai_generated`.
7. `ImageHistoryService` registra `ai_images` e `upload_links`.
8. `AiImageUsageService` grava `ai_image_usages` para controle invisivel de uso.

## Regras

- Antes do quiz: 1 simulacao por IP/sessao.
- Depois do quiz: ate 3 simulacoes totais para o lead.
- A interface nunca exibe creditos, limites ou mensagens agressivas.
- Apos simular, o CTA leva o usuario para o diagnostico tecnico.

## Tabelas

- `uploads`
- `upload_links`
- `ai_images`
- `ai_image_usages`
- `logs`
