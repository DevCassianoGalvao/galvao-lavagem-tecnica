<?php

final class AssetService
{
    public static function public(string $path): string
    {
        return self::versioned(PUBLIC_PATH, '../' . ltrim($path, '/'), ltrim($path, '/'));
    }

    public static function admin(string $path): string
    {
        return self::versioned(ADMIN_PATH, ltrim($path, '/'), ltrim($path, '/'));
    }

    public static function landingBundle(string $type): string
    {
        $bundle = $type === 'css'
            ? 'assets/dist/landing.min.css'
            : 'assets/dist/landing.min.js';

        if (is_file(PUBLIC_PATH . '/' . $bundle)) {
            return self::public($bundle);
        }

        return '';
    }

    public static function adminBundle(string $type): string
    {
        $bundle = $type === 'css'
            ? 'assets/dist/admin.min.css'
            : 'assets/dist/admin.min.js';

        if (is_file(ADMIN_PATH . '/' . $bundle)) {
            return self::admin($bundle);
        }

        return '';
    }

    private static function versioned(string $root, string $url, string $relativePath): string
    {
        $absolute = $root . '/' . $relativePath;
        $version = is_file($absolute) ? (string) filemtime($absolute) : (string) time();
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'v=' . rawurlencode($version);
    }
}
