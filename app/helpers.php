<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return $base === '/' || $base === '.' ? '' : rtrim($base, '/');
}

function url(string $route = '/'): string
{
    return base_url() . '/index.php?route=' . rawurlencode($route);
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . $path;
    return base_url() . '/assets/' . $path . '?v=' . (is_file($file) ? filemtime($file) : '1');
}

function number_es(float $value, int $decimals = 2): string
{
    return number_format($value, $decimals, ',', '.');
}

