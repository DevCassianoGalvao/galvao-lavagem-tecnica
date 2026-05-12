<?php

function asset(string $path): string
{
    return '/public/assets/' . ltrim($path, '/');
}

function admin_asset(string $path): string
{
    return '/admin/assets/' . ltrim($path, '/');
}

function route_url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}
