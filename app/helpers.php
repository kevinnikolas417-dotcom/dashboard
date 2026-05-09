<?php
function esc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money_br($value): string
{
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function num_br($value): string
{
    return number_format((int)$value, 0, ',', '.');
}

function clean_number($value): float
{
    $value = trim((string)$value);

    if ($value === '' || $value === '-' || $value === '–') {
        return 0.0;
    }

    $value = str_replace(['R$', 'BRL', "\xc2\xa0", ' '], '', $value);

    if (strpos($value, ',') !== false) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }

    return is_numeric($value) ? (float)$value : 0.0;
}

function clean_int($value): int
{
    return (int)round(clean_number($value));
}

function mysql_date($value): ?string
{
    $value = trim((string)$value);

    if ($value === '' || $value === '-' || $value === '–') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    $time = strtotime($value);

    return $time ? date('Y-m-d', $time) : null;
}

function normalize_header($value): string
{
    $value = trim((string)$value);
    return preg_replace('/^\xEF\xBB\xBF/', '', $value);
}

function pick_col(array $row, array $names, $default = '')
{
    foreach ($names as $name) {
        if (array_key_exists($name, $row)) {
            return $row[$name];
        }
    }

    return $default;
}

function result_meaning($type): string
{
    $text = function_exists('mb_strtolower')
        ? mb_strtolower((string)$type, 'UTF-8')
        : strtolower((string)$type);

    if (strpos($text, 'conversas por mensagem') !== false) {
        return 'Quantidade de conversas iniciadas por mensagem a partir da campanha.';
    }

    if (strpos($text, 'alcance') !== false) {
        return 'Quantidade de contas únicas alcançadas pela campanha.';
    }

    if (strpos($text, 'thruplay') !== false) {
        return 'Reproduções qualificadas de vídeo, conforme métrica ThruPlay da Meta.';
    }

    if (strpos($text, 'visitas ao perfil') !== false) {
        return 'Quantidade de visitas ao perfil do Instagram geradas pela campanha.';
    }

    if (strpos($text, 'engajamentos com o post') !== false) {
        return 'Quantidade de engajamentos no post, como reações, comentários e compartilhamentos.';
    }

    if (strpos($text, 'cliques no link') !== false) {
        return 'Quantidade de cliques no link gerados pela campanha.';
    }

    if (strpos($text, 'interações') !== false) {
        return 'Quantidade de interações registradas pela campanha.';
    }

    return 'Resultado principal definido pela Meta conforme o objetivo da campanha.';
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');

    return $text ?: ('item-' . time());
}
