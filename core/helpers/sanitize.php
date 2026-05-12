<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function clean_text(?string $value): string
{
    return trim(strip_tags((string) $value));
}
