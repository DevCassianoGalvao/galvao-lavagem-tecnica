<?php

final class SeoService
{
    public function __construct(private array $config, private array $settings = [])
    {
    }

    public function landing(): array
    {
        $siteName = $this->settings['branding.company_name'] ?? $this->config['app_name'] ?? 'Galvao Lavagem Tecnica';
        $baseUrl = rtrim((string) ($this->config['app_url'] ?? ''), '/');
        $canonical = $baseUrl !== '' ? $baseUrl . '/public/landing/' : '/public/landing/';
        $image = $baseUrl !== '' ? $baseUrl . '/public/assets/images/logo-galvao.png' : '../assets/images/logo-galvao.png';
        $title = $siteName . ' | Lavagem tecnica de alta pressao em Nova Friburgo';
        $description = 'Lavagem tecnica de alta pressao em Nova Friburgo para revitalizacao de areas externas, remocao profissional de lodo e musgo, limpeza de pisos, muros, fachadas, decks e areas molhadas.';

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'image' => $image,
            'site_name' => $siteName,
            'keywords' => 'lavagem tecnica Nova Friburgo, lavagem de alta pressao Nova Friburgo, revitalizacao de areas externas, remocao de lodo, remocao de musgo, limpeza tecnica de fachadas, limpeza de pisos externos, manutencao preventiva externa',
            'schema' => $this->schema($siteName, $canonical, $image, $description),
        ];
    }

    public function renderMeta(array $seo): string
    {
        $tags = [
            ['name' => 'description', 'content' => $seo['description']],
            ['name' => 'keywords', 'content' => $seo['keywords']],
            ['name' => 'robots', 'content' => 'index, follow, max-image-preview:large'],
            ['name' => 'author', 'content' => $seo['site_name']],
            ['name' => 'theme-color', 'content' => '#0F0F0F'],
            ['property' => 'og:type', 'content' => 'website'],
            ['property' => 'og:locale', 'content' => 'pt_BR'],
            ['property' => 'og:site_name', 'content' => $seo['site_name']],
            ['property' => 'og:title', 'content' => $seo['title']],
            ['property' => 'og:description', 'content' => $seo['description']],
            ['property' => 'og:url', 'content' => $seo['canonical']],
            ['property' => 'og:image', 'content' => $seo['image']],
            ['property' => 'og:image:alt', 'content' => 'Galvao Lavagem Tecnica - revitalizacao premium de areas externas'],
            ['name' => 'twitter:card', 'content' => 'summary_large_image'],
            ['name' => 'twitter:title', 'content' => $seo['title']],
            ['name' => 'twitter:description', 'content' => $seo['description']],
            ['name' => 'twitter:image', 'content' => $seo['image']],
        ];

        $html = '<link rel="canonical" href="' . e($seo['canonical']) . '">' . PHP_EOL;

        foreach ($tags as $tag) {
            $attribute = isset($tag['property']) ? 'property="' . e($tag['property']) . '"' : 'name="' . e($tag['name']) . '"';
            $html .= '<meta ' . $attribute . ' content="' . e($tag['content']) . '">' . PHP_EOL;
        }

        return $html;
    }

    public function renderJsonLd(array $seo): string
    {
        return '<script type="application/ld+json">' . json_encode(
            $seo['schema'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) . '</script>' . PHP_EOL;
    }

    private function schema(string $siteName, string $canonical, string $image, string $description): array
    {
        $businessId = $canonical . '#localbusiness';
        $serviceId = $canonical . '#service';

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'LocalBusiness',
                    '@id' => $businessId,
                    'name' => $siteName,
                    'url' => $canonical,
                    'image' => $image,
                    'description' => $description,
                    'priceRange' => '$$$',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => 'Nova Friburgo',
                        'addressRegion' => 'RJ',
                        'addressCountry' => 'BR',
                    ],
                    'areaServed' => [
                        [
                            '@type' => 'City',
                            'name' => 'Nova Friburgo',
                        ],
                    ],
                    'knowsAbout' => [
                        'Lavagem tecnica',
                        'Lavagem de alta pressao',
                        'Revitalizacao de areas externas',
                        'Remocao de lodo e musgo',
                        'Limpeza tecnica de pisos externos',
                        'Limpeza de fachadas e muros',
                        'Manutencao preventiva de superficies externas',
                    ],
                ],
                [
                    '@type' => 'Service',
                    '@id' => $serviceId,
                    'name' => 'Lavagem tecnica de alta pressao e revitalizacao de areas externas',
                    'serviceType' => 'Lavagem tecnica, lavagem de alta pressao e revitalizacao de areas externas',
                    'provider' => ['@id' => $businessId],
                    'areaServed' => 'Nova Friburgo, RJ',
                    'description' => 'Diagnostico, remocao profissional de lodo, musgo e sujeiras aderidas, lavagem de alta pressao, revitalizacao visual e orientacao de manutencao preventiva para pisos, muros, fachadas, decks e areas externas.',
                    'offers' => [
                        '@type' => 'Offer',
                        'availability' => 'https://schema.org/InStock',
                        'url' => $canonical . '#quiz',
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $canonical . '#website',
                    'name' => $siteName,
                    'url' => $canonical,
                    'inLanguage' => 'pt-BR',
                ],
            ],
        ];
    }
}
