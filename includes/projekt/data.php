<?php
/**
 * Údaje na úrovni projektu (od 0.15.0) – převezmou je všechny jednotky projektu.
 *
 * 1) Stavy jednotek: settings.statuses v `_martin_dv_data` (upravuje editor projektu).
 *    Tři základní stavy (volny, rezervovany, prodany) jsou vždy – jde změnit název a barvu.
 *    Vlastní stavy mají pevný klíč (x…) a „chování“ jednoho ze základních (kind) – podle něj
 *    se řídí cena, klikatelnost a filtr „Jen volné“; na webu mají vlastní barvu (--c).
 * 2) Informace o projektu: JSON v meta `_martin_dv_info` (box „Údaje projektu“):
 *    developer, dodavatel, lokalita, termin, predprodej_od/do (Y-m-d), prodejce {jmeno, firma, telefon, email, foto},
 *    texty {cena, plochy}, dokumenty [{nazev, popis (od 0.18.0), soubor}].
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

const META_INFO = '_martin_dv_info';

/* ---------- Stavy ---------- */

/** Výchozí barvy základních stavů (volný = prázdná → barva zvýraznění projektu / Brand). */
function base_status_colors(): array
{
    return ['volny' => '', 'rezervovany' => '#d39b2a', 'prodany' => '#9aa0a8'];
}

/**
 * Sanitizace seznamu stavů. Základní stavy vždy první (pevné pořadí), pak vlastní (max. 12).
 *
 * @param mixed $rows     [{key, label, color, kind}]
 * @param array $fallback barvy základních stavů, když v seznamu chybí (starší data: color_reserved / color_sold)
 */
function sanitize_statuses($rows, array $fallback = []): array
{
    $base   = statuses();
    $colors = array_merge(base_status_colors(), array_filter($fallback));
    $rows   = is_array($rows) ? $rows : [];
    $byKey  = [];
    foreach ($rows as $r) {
        if (is_array($r) && isset($r['key'])) {
            $byKey[(string) $r['key']] = $r;
        }
    }
    $out = [];
    foreach (array_keys($base) as $key) {
        $r     = $byKey[$key] ?? [];
        $label = clean_text($r['label'] ?? '', 40);
        $color = array_key_exists('color', $r) ? sanitize_css_color((string) $r['color']) : $colors[$key];
        if ($key !== 'volny' && $color === '') {
            $color = base_status_colors()[$key];
        }
        // Prázdný název = výchozí přeložený název (doplní project_statuses()), aby fungoval i překlad do EN.
        $out[] = ['key' => $key, 'label' => $label, 'color' => $color, 'kind' => $key];
    }
    $custom = 0;
    foreach ($rows as $r) {
        if (!is_array($r) || isset($base[(string) ($r['key'] ?? '')]) || $custom >= 12) {
            continue;
        }
        $key   = (string) ($r['key'] ?? '');
        $label = clean_text($r['label'] ?? '', 40);
        if ($label === '' || !preg_match('/^x[a-z0-9]{3,12}$/', $key)) {
            continue;
        }
        $kind  = isset($base[$r['kind'] ?? '']) ? $r['kind'] : 'rezervovany';
        $color = sanitize_css_color((string) ($r['color'] ?? '')) ?: '#6b7280';
        $out[] = ['key' => $key, 'label' => $label, 'color' => $color, 'kind' => $kind];
        $custom++;
    }
    return $out;
}

/** Stavy projektu: key => [label, color, kind]. Bez projektu jen základní stavy. */
function project_statuses(int $project_id): array
{
    static $cache = [];
    if (isset($cache[$project_id])) {
        return $cache[$project_id];
    }
    $rows = $project_id ? (get_project_data($project_id)['settings']['statuses'] ?? []) : [];
    if (!$rows) {
        $rows = sanitize_statuses([]);
    }
    $out = [];
    foreach ($rows as $r) {
        $label = $r['label'] !== '' ? $r['label'] : (statuses()[$r['key']] ?? $r['key']);
        $out[$r['key']] = ['label' => $label, 'color' => $r['color'], 'kind' => $r['kind']];
    }
    return $cache[$project_id] = $out;
}

/**
 * Stav jednotky: key (uložená hodnota), kind (chování), label, color, custom (vlastní stav – barva se
 * na webu nastaví inline; základní stavy barví CSS proměnné výběru).
 */
function unit_status(int $unit_id, int $project_id = -1): array
{
    $project_id = $project_id >= 0 ? $project_id : project_of_unit($unit_id);
    $all        = project_statuses($project_id);
    $key        = (string) get_post_meta($unit_id, 'dv_stav', true);
    if (!isset($all[$key])) {
        $key = 'volny';
    }
    $s = $all[$key];
    return [
        'key'    => $key,
        'kind'   => $s['kind'],
        'label'  => $s['label'],
        'color'  => $s['color'],
        'custom' => !isset(statuses()[$key]),
    ];
}

/* ---------- Informace o projektu ---------- */

function info_defaults(): array
{
    return [
        'developer'     => '',
        'dodavatel'     => '',
        'lokalita'      => '',
        'termin'        => '',
        'predprodej_od' => '',
        'predprodej_do' => '',
        'prodejce'      => ['jmeno' => '', 'firma' => '', 'telefon' => '', 'email' => '', 'foto' => 0],
        'texty'         => ['cena' => '', 'plochy' => ''],
        'dokumenty'     => [],
    ];
}

function clean_date($value): string
{
    $value = (string) $value;
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        return '';
    }
    return $value;
}

/** ID přílohy z knihovny médií (jinak 0). */
function clean_attachment($value): int
{
    $id = absint($value);
    return ($id && get_post_type($id) === 'attachment') ? $id : 0;
}

function clean_phone($value): string
{
    return mb_substr(trim(preg_replace('/[^0-9+()\/ \-]/', '', (string) $value)), 0, 30, 'UTF-8');
}

function sanitize_info($in): array
{
    $in  = is_array($in) ? $in : [];
    $out = info_defaults();
    foreach (['developer', 'dodavatel', 'lokalita', 'termin'] as $k) {
        $out[$k] = clean_text($in[$k] ?? '', 120);
    }
    $out['predprodej_od'] = clean_date($in['predprodej_od'] ?? '');
    $out['predprodej_do'] = clean_date($in['predprodej_do'] ?? '');

    $p     = is_array($in['prodejce'] ?? null) ? $in['prodejce'] : [];
    $email = sanitize_email((string) ($p['email'] ?? ''));
    $out['prodejce'] = [
        'jmeno'   => clean_text($p['jmeno'] ?? '', 80),
        'firma'   => clean_text($p['firma'] ?? '', 120),
        'telefon' => clean_phone($p['telefon'] ?? ''),
        'email'   => is_email($email) ? $email : '',
        'foto'    => clean_attachment($p['foto'] ?? 0),
    ];

    $t = is_array($in['texty'] ?? null) ? $in['texty'] : [];
    $out['texty'] = [
        'cena'   => clean_text($t['cena'] ?? '', 200),
        'plochy' => mb_substr(sanitize_textarea_field((string) ($t['plochy'] ?? '')), 0, 800, 'UTF-8'),
    ];

    foreach (array_slice(is_array($in['dokumenty'] ?? null) ? $in['dokumenty'] : [], 0, 30) as $d) {
        if (!is_array($d)) {
            continue;
        }
        $file = clean_attachment($d['soubor'] ?? 0);
        if ($file) {
            $out['dokumenty'][] = ['nazev' => clean_text($d['nazev'] ?? '', 120), 'popis' => clean_text($d['popis'] ?? '', 200), 'soubor' => $file];
        }
    }
    return $out;
}

function project_info(int $project_id): array
{
    static $cache = [];
    if (!$project_id) {
        return info_defaults();
    }
    if (!isset($cache[$project_id])) {
        $cache[$project_id] = sanitize_info(read_json_meta($project_id, META_INFO));
    }
    return $cache[$project_id];
}

/* ---------- Který projekt zobrazit ---------- */

/**
 * Projekt pro aktuální stránku: detail jednotky → její projekt; jinde (Kontakt, globální blok v patičce…)
 * výchozí projekt = první publikovaný (filtr `martin_dv_default_project` ho může určit).
 */
function current_project(int $post_id = 0): int
{
    $post_id = $post_id ?: (int) get_the_ID();
    if ($post_id) {
        $type = get_post_type($post_id);
        if ($type === PT_UNIT) {
            $pid = project_of_unit(translate_id($post_id));
            if ($pid) {
                return $pid;
            }
        } elseif ($type === PT_PROJECT) {
            return $post_id;
        }
    }
    return default_project();
}

function default_project(): int
{
    static $id = null;
    if ($id === null) {
        $ids = get_posts([
            'post_type'      => PT_PROJECT,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => ['menu_order' => 'ASC', 'date' => 'ASC'],
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);
        $id = (int) apply_filters('martin_dv_default_project', $ids ? (int) $ids[0] : 0);
    }
    return $id;
}

/** Projekt se smí zobrazit (publikovaný, nebo ho aktuální uživatel smí upravovat). */
function project_visible(int $project_id): bool
{
    return $project_id && (get_post_status($project_id) === 'publish' || current_user_can('edit_post', $project_id));
}

/* ---------- Formátování ---------- */

/** Datum Y-m-d → „1. 12. 2026“ (podle formátu z nastavení WP, když není česky). */
function fmt_date(string $ymd): string
{
    if ($ymd === '') {
        return '';
    }
    $ts = strtotime($ymd . ' 12:00:00');
    return $ts ? wp_date(apply_filters('martin_dv_date_format', 'j. n. Y'), $ts) : '';
}

/** „od 1. 12. 2026 do 31. 1. 2027“ / „od 1. 12. 2026“ / „do 31. 1. 2027“. */
function fmt_range(string $from, string $to): string
{
    $f = fmt_date($from);
    $t = fmt_date($to);
    if ($f && $t) {
        /* translators: 1: datum od, 2: datum do */
        return sprintf(__('%1$s – %2$s', 'martin-dev-vyber-bytu'), $f, $t);
    }
    if ($f) {
        /* translators: %s: datum */
        return sprintf(__('od %s', 'martin-dev-vyber-bytu'), $f);
    }
    /* translators: %s: datum */
    return $t ? sprintf(__('do %s', 'martin-dev-vyber-bytu'), $t) : '';
}

/** Cena za m² (zaokrouhlená na celé Kč) z ceny a celkové plochy. */
function price_per_m2(int $unit_id): int
{
    $price = (int) get_post_meta($unit_id, 'dv_cena', true);
    $area  = clean_area(get_post_meta($unit_id, 'dv_plocha', true));
    return ($price > 0 && $area > 0) ? (int) round($price / $area) : 0;
}
