<?php
/**
 * Rozšířené údaje jednotky (od 0.14.0): místnosti, venkovní plochy, vlastní parametry, poloha v domě.
 *
 * Opakovače se ukládají jako JSON do vlastních meta polí (bez ACF Pro):
 *   _dv_mistnosti  [{nazev, plocha, typ: obytna|vedlejsi, zapocitat: bool, poznamka}]
 *   _dv_venkovni   [{typ, plocha, orientace: [s, sv, …]}]
 *   _dv_parametry  [{nazev, hodnota}]
 * Jednoduchá pole polohy jsou ACF (dv_budova, dv_podlazi_cislo, dv_umisteni, dv_orientace, dv_vyhled),
 * takže jsou v Breakdance i v ACF dynamických datech.
 * Čísla se ukládají jako čísla, formát (55,5 m²) až při výpisu.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

const META_ROOMS    = '_dv_mistnosti';
const META_OUTDOORS = '_dv_venkovni';
const META_PARAMS   = '_dv_parametry';

/* ---------- Číselníky ---------- */

function room_types(): array
{
    return [
        'obytna'   => __('Obytná', 'martin-dev-vyber-bytu'),
        'vedlejsi' => __('Vedlejší', 'martin-dev-vyber-bytu'),
    ];
}

/** Světové strany: klíč => [zkratka, celý název]. */
function orientations(): array
{
    return [
        's'  => [__('S', 'martin-dev-vyber-bytu'), __('sever', 'martin-dev-vyber-bytu')],
        'sv' => [__('SV', 'martin-dev-vyber-bytu'), __('severovýchod', 'martin-dev-vyber-bytu')],
        'v'  => [__('V', 'martin-dev-vyber-bytu'), __('východ', 'martin-dev-vyber-bytu')],
        'jv' => [__('JV', 'martin-dev-vyber-bytu'), __('jihovýchod', 'martin-dev-vyber-bytu')],
        'j'  => [__('J', 'martin-dev-vyber-bytu'), __('jih', 'martin-dev-vyber-bytu')],
        'jz' => [__('JZ', 'martin-dev-vyber-bytu'), __('jihozápad', 'martin-dev-vyber-bytu')],
        'z'  => [__('Z', 'martin-dev-vyber-bytu'), __('západ', 'martin-dev-vyber-bytu')],
        'sz' => [__('SZ', 'martin-dev-vyber-bytu'), __('severozápad', 'martin-dev-vyber-bytu')],
    ];
}

/** Návrhy názvů vlastních parametrů (jen nápověda v editoru, lze psát cokoli). */
function param_suggestions(): array
{
    return apply_filters('martin_dv_param_suggestions', [
        __('Světlá výška stropů', 'martin-dev-vyber-bytu'),
        __('Energetická třída (PENB)', 'martin-dev-vyber-bytu'),
        __('Vytápění', 'martin-dev-vyber-bytu'),
        __('Podlahové topení', 'martin-dev-vyber-bytu'),
        __('Předsazené žaluzie', 'martin-dev-vyber-bytu'),
        __('Příprava na klimatizaci', 'martin-dev-vyber-bytu'),
        __('Rekuperace', 'martin-dev-vyber-bytu'),
        __('Okna', 'martin-dev-vyber-bytu'),
    ]);
}

/* ---------- Sanitizace ---------- */

/** Plocha v m²: nezáporné číslo, max. 2 desetinná místa. Přijímá i „55,5“. */
function clean_area($value): float
{
    if (is_string($value)) {
        $value = str_replace([' ', "\u{00a0}", ','], ['', '', '.'], $value);
    }
    if (!is_numeric($value)) {
        return 0.0;
    }
    return round(min(100000, max(0, (float) $value)), 2);
}

function clean_text($value, int $max = 200): string
{
    return mb_substr(sanitize_text_field((string) $value), 0, $max, 'UTF-8');
}

/** Seznam světových stran jen z pevného číselníku, bez duplicit, v pořadí S → SZ. */
function clean_orientations($value): array
{
    $all = array_keys(orientations());
    $in  = is_array($value) ? array_map('strval', $value) : [];
    return array_values(array_intersect($all, $in));
}

function sanitize_rooms($rows): array
{
    $out   = [];
    $types = room_types();
    foreach (array_slice(is_array($rows) ? $rows : [], 0, 60) as $r) {
        if (!is_array($r)) {
            continue;
        }
        $row = [
            'nazev'     => clean_text($r['nazev'] ?? '', 120),
            'plocha'    => clean_area($r['plocha'] ?? 0),
            'typ'       => isset($types[$r['typ'] ?? '']) ? $r['typ'] : 'obytna',
            'zapocitat' => !isset($r['zapocitat']) || (bool) $r['zapocitat'],
            'poznamka'  => clean_text($r['poznamka'] ?? '', 200),
        ];
        if ($row['nazev'] !== '' || $row['plocha'] > 0) {
            $out[] = $row;
        }
    }
    return $out;
}

function sanitize_outdoors($rows): array
{
    $out   = [];
    $types = outdoor_types();
    foreach (array_slice(is_array($rows) ? $rows : [], 0, 20) as $r) {
        if (!is_array($r) || !isset($types[$r['typ'] ?? ''])) {
            continue;
        }
        $out[] = [
            'typ'       => $r['typ'],
            'plocha'    => clean_area($r['plocha'] ?? 0),
            'orientace' => clean_orientations($r['orientace'] ?? []),
        ];
    }
    return $out;
}

function sanitize_params($rows): array
{
    $out = [];
    foreach (array_slice(is_array($rows) ? $rows : [], 0, 60) as $r) {
        if (!is_array($r)) {
            continue;
        }
        $name  = clean_text($r['nazev'] ?? '', 120);
        $value = clean_text($r['hodnota'] ?? '', 300);
        if ($name !== '' && $value !== '') {
            $out[] = ['nazev' => $name, 'hodnota' => $value];
        }
    }
    return $out;
}

/* ---------- Čtení ---------- */

function read_json_meta(int $post_id, string $key): array
{
    $raw  = get_post_meta($post_id, $key, true);
    $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    return is_array($data) ? $data : [];
}

function unit_rooms(int $post_id): array
{
    return sanitize_rooms(read_json_meta($post_id, META_ROOMS));
}

function unit_params(int $post_id): array
{
    return sanitize_params(read_json_meta($post_id, META_PARAMS));
}

/**
 * Venkovní plochy jednotky. Vyplněný seznam má přednost; jinak se použije
 * původní jednoduché pole (dv_venkovni_typ + dv_venkovni_plocha).
 */
function unit_outdoors(int $post_id): array
{
    $rows = sanitize_outdoors(read_json_meta($post_id, META_OUTDOORS));
    if ($rows) {
        return $rows;
    }
    $type = (string) get_post_meta($post_id, 'dv_venkovni_typ', true);
    if (isset(outdoor_types()[$type])) {
        return [[
            'typ'       => $type,
            'plocha'    => clean_area(get_post_meta($post_id, 'dv_venkovni_plocha', true)),
            'orientace' => [],
        ]];
    }
    return [];
}

/** Součty: započítaná plocha místností, nezapočítané, venkovní. */
function unit_area_sums(int $post_id): array
{
    $counted = 0.0;
    $other   = 0.0;
    foreach (unit_rooms($post_id) as $r) {
        if ($r['zapocitat']) {
            $counted += $r['plocha'];
        } else {
            $other += $r['plocha'];
        }
    }
    $outdoor = 0.0;
    foreach (unit_outdoors($post_id) as $o) {
        $outdoor += $o['plocha'];
    }
    return ['rooms' => round($counted, 2), 'other' => round($other, 2), 'outdoor' => round($outdoor, 2)];
}

/* ---------- Nastavení projektu (popisky, budovy) ---------- */

function project_of_unit(int $post_id): int
{
    $pid = (int) get_post_meta($post_id, 'dv_projekt', true);
    return ($pid && get_post_type($pid) === PT_PROJECT) ? $pid : 0;
}

/** Název hlavní plochy podle projektu (Podlahová plocha / Užitná plocha / …). */
function area_label(int $project_id = 0): string
{
    $label = '';
    if ($project_id) {
        $label = (string) (get_project_data($project_id)['settings']['label_area'] ?? '');
    }
    return $label !== '' ? $label : __('Podlahová plocha', 'martin-dev-vyber-bytu');
}

/** Seznam budov / vchodů v projektu (názvy). */
function project_buildings(int $project_id): array
{
    if (!$project_id) {
        return [];
    }
    return (array) (get_project_data($project_id)['settings']['buildings'] ?? []);
}

/** Řádky textového pole → názvy budov (bez prázdných a duplicit). */
function sanitize_buildings($value): array
{
    $lines = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string) $value);
    $out   = [];
    foreach (array_slice((array) $lines, 0, 50) as $line) {
        $name = clean_text($line, 80);
        if ($name !== '' && !in_array($name, $out, true)) {
            $out[] = $name;
        }
    }
    return $out;
}

/* ---------- Poloha v domě ---------- */

/**
 * Podlaží, kde je jednotka obkreslená v editoru projektu: [['id' => …, 'name' => …, 'image' => …, 'areas' => …], …]
 * (u mezonetu víc podlaží).
 */
function unit_drawn_floors(int $post_id): array
{
    $project = project_of_unit($post_id);
    if (!$project) {
        return [];
    }
    $out = [];
    foreach (get_project_data($project)['floors'] as $floor) {
        foreach ($floor['areas'] as $a) {
            if ((int) $a['unit'] === $post_id || translate_id((int) $a['unit']) === $post_id) {
                $out[] = $floor;
                break;
            }
        }
    }
    return $out;
}

/** Číslo podlaží: ruční pole → číslo z textu „3. NP“ → podle výkresu. Null = nevíme (např. dům). */
function unit_floor_number(int $post_id): ?int
{
    $manual = get_post_meta($post_id, 'dv_podlazi_cislo', true);
    if ($manual !== '' && $manual !== null && is_numeric($manual)) {
        return (int) $manual;
    }
    $text = trim((string) get_post_meta($post_id, 'dv_podlazi', true));
    if ($text !== '' && ($lvl = floor_level($text)) !== null) {
        return $lvl;
    }
    foreach (unit_drawn_floors($post_id) as $floor) {
        if (($lvl = floor_level((string) $floor['name'])) !== null) {
            return $lvl;
        }
    }
    return null;
}

/** Text podlaží pro výpis: ruční text → z čísla („3. NP“, „1. PP“, „přízemí“) → názvy podlaží z výkresu. */
function unit_floor_text(int $post_id): string
{
    $text = trim((string) get_post_meta($post_id, 'dv_podlazi', true));
    if ($text !== '') {
        return $text;
    }
    $manual = get_post_meta($post_id, 'dv_podlazi_cislo', true);
    if ($manual !== '' && $manual !== null && is_numeric($manual)) {
        return floor_number_label((int) $manual);
    }
    $names = array_filter(array_map(function ($f) {
        return (string) $f['name'];
    }, unit_drawn_floors($post_id)));
    return implode(', ', $names);
}

function floor_number_label(int $n): string
{
    if ($n === 0) {
        return __('přízemí', 'martin-dev-vyber-bytu');
    }
    /* translators: %d: číslo nadzemního / podzemního podlaží */
    return $n > 0 ? sprintf(__('%d. NP', 'martin-dev-vyber-bytu'), $n) : sprintf(__('%d. PP', 'martin-dev-vyber-bytu'), abs($n));
}

/** „J, JZ“ ze seznamu klíčů. */
function orientation_text(array $keys, bool $long = false): string
{
    $all = orientations();
    $out = [];
    foreach (clean_orientations($keys) as $k) {
        $out[] = $all[$k][$long ? 1 : 0];
    }
    return implode(', ', $out);
}

/* ---------- Formát čísel ---------- */

/** Plocha s nejvýš 2 desetinnými místy bez zbytečných nul: 55 → „55 m²“, 55.5 → „55,5 m²“, 12.35 → „12,35 m²“. */
function fmt_area_exact($n): string
{
    $n   = round((float) $n, 2);
    $dec = (round($n) == $n) ? 0 : ((round($n, 1) == $n) ? 1 : 2);
    /* translators: %s: plocha v metrech čtverečních */
    return sprintf(__('%s m²', 'martin-dev-vyber-bytu'), num($n, $dec));
}
