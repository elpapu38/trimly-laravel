<?php

// ============================================================
//  app/helpers.php — Helpers globales usados en las vistas Blade
//  (equivalente a app/Helpers/Helpers.php del sistema original;
//   e(), url() y asset() ya los provee Laravel de forma nativa)
// ============================================================

if (!function_exists('money')) {
    function money(float|int|null $amount, string $currency = 'ARS'): string
    {
        if ($amount === null) return '—';
        $amount = (float) $amount;
        return match ($currency) {
            'ARS' => '$ ' . number_format($amount, 2, ',', '.'),
            'USD' => 'U$S ' . number_format($amount, 2, '.', ','),
            default => number_format($amount, 2),
        };
    }
}

if (!function_exists('fecha')) {
    function fecha(mixed $date, string $format = 'd/m/Y'): string
    {
        if (!$date) return '—';
        $ts = $date instanceof \Carbon\Carbon ? $date->timestamp : strtotime((string) $date);
        return date($format, $ts);
    }
}

if (!function_exists('hora')) {
    function hora(mixed $time): string
    {
        if (!$time) return '—';
        $ts = $time instanceof \Carbon\Carbon ? $time->timestamp : strtotime((string) $time);
        return date('H:i', $ts);
    }
}

if (!function_exists('diasSemana')) {
    function diasSemana(): array
    {
        return ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    }
}

if (!function_exists('stars')) {
    function stars(float $rating, bool $html = true): string
    {
        $full = (int) floor($rating);
        $empty = 5 - $full;
        if (!$html) return str_repeat('★', $full) . str_repeat('☆', $empty);
        return str_repeat('<span class="text-amber-400">★</span>', $full)
             . str_repeat('<span class="text-ink-600">★</span>', $empty);
    }
}

if (!function_exists('truncate')) {
    function truncate(?string $text, int $length = 120): string
    {
        $text = $text ?? '';
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '…' : $text;
    }
}

if (!function_exists('initials')) {
    function initials(?string $name): string
    {
        $parts = array_filter(explode(' ', $name ?? ''));
        $parts = array_values($parts);
        return strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
    }
}

if (!function_exists('duracionTexto')) {
    function duracionTexto(int $minutos): string
    {
        if ($minutos < 60) return "{$minutos} min";
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;
        return $m ? "{$h}h {$m}min" : "{$h}h";
    }
}

if (!function_exists('upload_url')) {
    /** Reemplaza a upload_url() del sistema original: usa el disco público de Laravel. */
    function upload_url(?string $file): string
    {
        if (!$file) return '';
        return \Illuminate\Support\Facades\Storage::url($file);
    }
}
