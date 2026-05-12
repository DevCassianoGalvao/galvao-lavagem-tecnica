<?php

require_once __DIR__ . '/../core/bootstrap.php';

$baseUrl = rtrim((string) ($config['app_url'] ?? ''), '/');
$baseUrl = $baseUrl !== '' ? $baseUrl : 'https://galvaolavagemtecnica.com.br';
$today = date('Y-m-d');

$urls = [
    ['loc' => $baseUrl . '/public/landing/', 'priority' => '1.0', 'changefreq' => 'weekly'],
];

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
  <url>
    <loc><?= e($url['loc']); ?></loc>
    <lastmod><?= e($today); ?></lastmod>
    <changefreq><?= e($url['changefreq']); ?></changefreq>
    <priority><?= e($url['priority']); ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
