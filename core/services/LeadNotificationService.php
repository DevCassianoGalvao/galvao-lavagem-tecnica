<?php

final class LeadNotificationService
{
    public function __construct(private array $config)
    {
    }

    public function notifyNewLead(array $lead): void
    {
        if (empty($this->config['brevo_enabled'])) {
            return;
        }

        $apiKey = trim((string) ($this->config['brevo_api_key'] ?? ''));
        $toEmail = trim((string) ($this->config['lead_notification_email'] ?? ''));
        $fromEmail = trim((string) ($this->config['brevo_from_email'] ?? ''));
        $fromName = trim((string) ($this->config['brevo_from_name'] ?? 'Galvão Lavagem Técnica'));

        if ($toEmail === '') {
            $toEmail = trim((string) ($this->config['admin_email'] ?? ''));
        }

        if ($apiKey === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('Lead notification skipped: Brevo not configured.');
            return;
        }

        $appUrl = rtrim((string) ($this->config['app_url'] ?? ''), '/');
        $leadUrl = $appUrl !== '' && isset($lead['id'])
            ? $appUrl . '/admin/index.php?page=lead&id=' . (int) $lead['id']
            : '';

        $payload = [
            'sender' => [
                'name' => $fromName,
                'email' => $fromEmail,
            ],
            'to' => [
                ['email' => $toEmail],
            ],
            'subject' => 'Novo contato recebido - Galvão Lavagem Técnica',
            'htmlContent' => $this->html($lead, $leadUrl),
            'textContent' => $this->text($lead, $leadUrl),
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');

        if ($ch === false) {
            error_log('Lead notification failed: curl_init unavailable.');
            return;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'api-key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 12,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            error_log('Lead notification failed: HTTP ' . $status . ' ' . ($error ?: (string) $response));
        }
    }

    private function html(array $lead, string $leadUrl): string
    {
        $rows = [
            'Nome' => $lead['name'] ?? '',
            'WhatsApp' => $lead['phone'] ?? '',
            'Bairro' => $lead['neighborhood'] ?? '',
            'Endereço' => $lead['address'] ?? '',
            'Superfícies' => implode(', ', $lead['surfaces'] ?? []),
            'Sujeira' => implode(', ', $lead['dirt'] ?? []),
            'Observações' => $lead['notes'] ?? '',
        ];

        $htmlRows = '';

        foreach ($rows as $label => $value) {
            $htmlRows .= '<tr><td style="padding:8px 0;color:#9b8f72;">' . $this->escape($label) . '</td><td style="padding:8px 0;color:#111111;font-weight:600;">' . nl2br($this->escape((string) $value)) . '</td></tr>';
        }

        $button = $leadUrl !== ''
            ? '<p style="margin:24px 0 0;"><a href="' . $this->escape($leadUrl) . '" style="display:inline-block;background:#d8b84f;color:#111111;text-decoration:none;font-weight:700;padding:14px 22px;border-radius:999px;">Abrir lead no painel</a></p>'
            : '';

        return '<div style="font-family:Arial,sans-serif;background:#f7f3e8;padding:28px;">
            <div style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:18px;padding:28px;border:1px solid #e1d4a6;">
                <p style="margin:0 0 8px;color:#b39535;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">Novo contato</p>
                <h1 style="margin:0 0 18px;color:#111111;font-size:26px;">Um novo orçamento chegou pelo site</h1>
                <table style="width:100%;border-collapse:collapse;">' . $htmlRows . '</table>
                ' . $button . '
            </div>
        </div>';
    }

    private function text(array $lead, string $leadUrl): string
    {
        $lines = [
            'Novo contato recebido pelo site.',
            '',
            'Nome: ' . (string) ($lead['name'] ?? ''),
            'WhatsApp: ' . (string) ($lead['phone'] ?? ''),
            'Bairro: ' . (string) ($lead['neighborhood'] ?? ''),
            'Endereço: ' . (string) ($lead['address'] ?? ''),
            'Superfícies: ' . implode(', ', $lead['surfaces'] ?? []),
            'Sujeira: ' . implode(', ', $lead['dirt'] ?? []),
            'Observações: ' . (string) ($lead['notes'] ?? ''),
        ];

        if ($leadUrl !== '') {
            $lines[] = '';
            $lines[] = 'Abrir no painel: ' . $leadUrl;
        }

        return implode("\n", $lines);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
