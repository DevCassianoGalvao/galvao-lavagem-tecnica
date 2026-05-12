<?php

final class SettingsService
{
    private static ?array $runtimeCache = null;

    private const DEFINITIONS = [
        'branding.logo_url' => ['type' => 'string', 'private' => false],
        'branding.favicon_url' => ['type' => 'string', 'private' => false],
        'branding.primary_color' => ['type' => 'string', 'private' => false],
        'branding.accent_color' => ['type' => 'string', 'private' => false],
        'branding.company_name' => ['type' => 'string', 'private' => false],
        'branding.hero_badge' => ['type' => 'string', 'private' => false],
        'landing.meta_pixel_id' => ['type' => 'string', 'private' => true],
        'landing.ga_measurement_id' => ['type' => 'string', 'private' => true],
        'landing.gtm_id' => ['type' => 'string', 'private' => true],
        'landing.custom_head_scripts' => ['type' => 'string', 'private' => true],
        'landing.custom_body_scripts' => ['type' => 'string', 'private' => true],
        'integrations.openai_api_key' => ['type' => 'string', 'private' => true],
        'integrations.google_calendar_client_id' => ['type' => 'string', 'private' => true],
        'integrations.google_calendar_client_secret' => ['type' => 'string', 'private' => true],
        'ai.text_daily_limit' => ['type' => 'number', 'private' => true],
        'ai.image_daily_limit' => ['type' => 'number', 'private' => true],
        'ai.cooldown_seconds' => ['type' => 'number', 'private' => true],
        'ai.watermark_text' => ['type' => 'string', 'private' => false],
        'ai.image_quality' => ['type' => 'string', 'private' => true],
        'system.timezone' => ['type' => 'string', 'private' => false],
        'system.email_from' => ['type' => 'string', 'private' => true],
        'system.company_name' => ['type' => 'string', 'private' => false],
    ];

    private const DEFAULTS = [
        'branding.logo_url' => '../assets/images/logo-galvao.svg',
        'branding.favicon_url' => '',
        'branding.primary_color' => '#C8A95B',
        'branding.accent_color' => '#D4AF37',
        'branding.company_name' => 'Galvao Lavagem Tecnica',
        'branding.hero_badge' => 'Lavagem tecnica premium',
        'landing.meta_pixel_id' => '',
        'landing.ga_measurement_id' => '',
        'landing.gtm_id' => '',
        'landing.custom_head_scripts' => '',
        'landing.custom_body_scripts' => '',
        'integrations.openai_api_key' => '',
        'integrations.google_calendar_client_id' => '',
        'integrations.google_calendar_client_secret' => '',
        'ai.text_daily_limit' => '80',
        'ai.image_daily_limit' => '12',
        'ai.cooldown_seconds' => '90',
        'ai.watermark_text' => 'Simulacao IA - Galvao Lavagem Tecnica',
        'ai.image_quality' => 'standard',
        'system.timezone' => 'America/Sao_Paulo',
        'system.email_from' => 'contato@galvaolavagemtecnica.com.br',
        'system.company_name' => 'Galvao Lavagem Tecnica',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    public static function allowedKeys(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    public function all(bool $maskPrivate = false): array
    {
        if (self::$runtimeCache === null) {
            $settings = self::DEFAULTS;
            $stmt = $this->pdo->query('SELECT setting_key, setting_value, is_private FROM settings WHERE scope = "global"');

            foreach ($stmt->fetchAll() as $row) {
                $key = (string) $row['setting_key'];

                if (!array_key_exists($key, self::DEFINITIONS)) {
                    continue;
                }

                $settings[$key] = (string) $row['setting_value'];
            }

            self::$runtimeCache = $settings;
        }

        if (!$maskPrivate) {
            return self::$runtimeCache;
        }

        $masked = self::$runtimeCache;

        foreach ($masked as $key => $value) {
            if ($this->isSecretKey($key) && $value !== '') {
                $masked[$key] = $this->maskSecret($value);
            }
        }

        return $masked;
    }

    public function saveMany(array $values, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value, value_type, scope, user_id, is_private)
             VALUES (:setting_key, :setting_value, :value_type, "global", NULL, :is_private)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), value_type = VALUES(value_type), is_private = VALUES(is_private)'
        );

        foreach ($values as $key => $value) {
            if (!array_key_exists($key, self::DEFINITIONS)) {
                continue;
            }

            $definition = self::DEFINITIONS[$key];
            $stmt->execute([
                'setting_key' => $key,
                'setting_value' => $this->normalizeValue($key, $value),
                'value_type' => $definition['type'],
                'is_private' => $definition['private'] ? 1 : 0,
            ]);
        }

        self::$runtimeCache = null;
    }

    public function renderBrandingStyles(): string
    {
        $settings = $this->all();
        $primary = $this->hexOrNull($settings['branding.primary_color'] ?? '');
        $accent = $this->hexOrNull($settings['branding.accent_color'] ?? '');

        if (!$primary && !$accent) {
            return '';
        }

        $rules = ':root{';
        $rules .= $primary ? '--color-gold:' . $primary . ';' : '';
        $rules .= $accent ? '--color-gold-strong:' . $accent . ';' : '';
        $rules .= '}';

        return '<style data-galvao-branding>' . $rules . '</style>' . PHP_EOL;
    }

    public function renderLandingHeadScripts(): string
    {
        $settings = $this->all();
        $html = '';

        if ($favicon = $this->safeAssetUrl($settings['branding.favicon_url'] ?? '')) {
            $html .= '<link rel="icon" href="' . $this->escape($favicon) . '">' . PHP_EOL;
        }

        if ($pixelId = $this->trackingId($settings['landing.meta_pixel_id'] ?? '', '/^[0-9]{5,30}$/')) {
            $html .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?"
                . "n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;"
                . "n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;"
                . "t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}"
                . "(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');"
                . "fbq('init','" . $pixelId . "');fbq('track','PageView');</script>" . PHP_EOL;
        }

        if ($gaId = $this->trackingId($settings['landing.ga_measurement_id'] ?? '', '/^G-[A-Z0-9]{6,20}$/')) {
            $html .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $gaId . '"></script>' . PHP_EOL;
            $html .= "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}"
                . "gtag('js',new Date());gtag('config','" . $gaId . "');</script>" . PHP_EOL;
        }

        if ($gtmId = $this->trackingId($settings['landing.gtm_id'] ?? '', '/^GTM-[A-Z0-9]{4,16}$/')) {
            $html .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':"
                . "new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],"
                . "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;"
                . "j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;"
                . "f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . $gtmId . "');</script>" . PHP_EOL;
        }

        $html .= trim((string) ($settings['landing.custom_head_scripts'] ?? '')) . PHP_EOL;

        return $html;
    }

    public function configOverrides(): array
    {
        $settings = $this->all();
        $overrides = [
            'openai_api_key' => $settings['integrations.openai_api_key'] ?? '',
            'ai_text_daily_limit' => $settings['ai.text_daily_limit'] ?? '',
            'ai_image_daily_limit' => $settings['ai.image_daily_limit'] ?? '',
            'ai_cooldown_seconds' => $settings['ai.cooldown_seconds'] ?? '',
            'ai_watermark_text' => $settings['ai.watermark_text'] ?? '',
            'ai_image_quality' => $settings['ai.image_quality'] ?? '',
            'app_timezone' => $settings['system.timezone'] ?? '',
            'mail_from' => $settings['system.email_from'] ?? '',
            'company_name' => $settings['system.company_name'] ?? '',
        ];

        return array_filter($overrides, static fn (string $value): bool => trim($value) !== '');
    }

    public function renderLandingBodyStartScripts(): string
    {
        $settings = $this->all();
        $gtmId = $this->trackingId($settings['landing.gtm_id'] ?? '', '/^GTM-[A-Z0-9]{4,16}$/');

        if (!$gtmId) {
            return '';
        }

        return '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . $gtmId
            . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . PHP_EOL;
    }

    public function renderLandingBodyEndScripts(): string
    {
        $settings = $this->all();

        return trim((string) ($settings['landing.custom_body_scripts'] ?? '')) . PHP_EOL;
    }

    private function normalizeValue(string $key, mixed $value): string
    {
        $value = trim((string) $value);

        if (str_contains($key, 'color')) {
            return $this->hexOrNull($value) ?? '';
        }

        if (self::DEFINITIONS[$key]['type'] === 'number') {
            return (string) max(0, (int) $value);
        }

        if ($this->isSecretKey($key) && str_starts_with($value, '********')) {
            return $this->all()[$key] ?? '';
        }

        return $value;
    }

    private function maskSecret(string $value): string
    {
        $tail = substr($value, -4);

        return '********' . $tail;
    }

    private function isSecretKey(string $key): bool
    {
        return str_contains($key, 'api_key') || str_contains($key, 'secret');
    }

    private function hexOrNull(string $value): ?string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtoupper($value) : null;
    }

    private function trackingId(string $value, string $pattern): ?string
    {
        $value = strtoupper(trim($value));

        return preg_match($pattern, $value) ? $value : null;
    }

    private function safeAssetUrl(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(https?:)?\/\//i', $value) || str_starts_with($value, 'data:')) {
            return null;
        }

        return $value;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
