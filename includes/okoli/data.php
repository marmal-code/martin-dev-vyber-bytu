<?php
/**
 * Mapa okolí – číselníky (kategorie, ikony) a sanitizace map uložených v projektu.
 *
 * Mapy jsou součástí dat projektu (meta _martin_dv_data, klíč „maps“):
 * [
 *   'id' => 'm…', 'name' => 'Okolí', 'image' => ['id','w','h'],
 *   'mode' => 'cisla' | 'ikony' | 'kombi', 'cat_colors' => bool, 'filter' => bool,
 *   'proj' => null | ['x','y','name','desc'],
 *   'points' => [['id','name','cat','icon','desc','dist','url','x','y'], …]   // pořadí = čísla 01, 02…
 * ]
 * Kód mapy je záměrně v samostatné složce includes/okoli/ – jde ho později přesunout do Breakdance Plus.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

/** Kategorie bodů: klíč => název, výchozí ikona, výchozí barva (při zapnutých barvách podle kategorie). */
function okoli_categories(): array
{
    return apply_filters('martin_dv_okoli_categories', [
        'skola'   => ['name' => __('Školství', 'martin-dev-vyber-bytu'), 'icon' => 'skola', 'color' => '#e4a853'],
        'obchod'  => ['name' => __('Obchody', 'martin-dev-vyber-bytu'), 'icon' => 'kosik', 'color' => '#7fb3d5'],
        'gastro'  => ['name' => __('Gastro', 'martin-dev-vyber-bytu'), 'icon' => 'gastro', 'color' => '#e08a6d'],
        'zdravi'  => ['name' => __('Zdraví', 'martin-dev-vyber-bytu'), 'icon' => 'zdravi', 'color' => '#d77a9a'],
        'sport'   => ['name' => __('Sport', 'martin-dev-vyber-bytu'), 'icon' => 'sport', 'color' => '#8cc084'],
        'priroda' => ['name' => __('Příroda', 'martin-dev-vyber-bytu'), 'icon' => 'strom', 'color' => '#5fae9e'],
        'doprava' => ['name' => __('Doprava', 'martin-dev-vyber-bytu'), 'icon' => 'autobus', 'color' => '#9aa3b5'],
        'sluzby'  => ['name' => __('Služby', 'martin-dev-vyber-bytu'), 'icon' => 'posta', 'color' => '#b49ad6'],
        'kultura' => ['name' => __('Kultura', 'martin-dev-vyber-bytu'), 'icon' => 'budova', 'color' => '#c7a27c'],
    ]);
}

/**
 * Sada ikon (vnitřek SVG 24×24, tah 2 px, barva currentColor). Pevný seznam – žádné nahrávání vlastních SVG
 * (SVG může obsahovat skript). Filtr martin_dv_okoli_icons umožní doplnit ikony z kódu.
 */
function okoli_icons(): array
{
    return apply_filters('martin_dv_okoli_icons', [
        'bod'       => ['name' => __('Bod', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M12 21s-6.5-6.2-6.5-11a6.5 6.5 0 0 1 13 0c0 4.8-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>'],
        'skolka'    => ['name' => __('Školka', 'martin-dev-vyber-bytu'), 'svg' => '<rect x="3" y="13" width="7" height="7" rx="1"/><rect x="14" y="13" width="7" height="7" rx="1"/><path d="M8.5 10h7L12 4z"/>'],
        'skola'     => ['name' => __('Škola', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M2 9.5 12 4.5l10 5-10 5z"/><path d="M6 11.5v4.5c3 2.2 9 2.2 12 0v-4.5"/>'],
        'kosik'     => ['name' => __('Obchod', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M3 4h2.2l2.3 10.5h10.8L20.5 7.5H6.4"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/>'],
        'gastro'    => ['name' => __('Restaurace', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M7 3v18M4.5 3v5a2.5 2.5 0 0 0 5 0V3"/><path d="M17 21V3c-2.5 2-3 6-3 9h3"/>'],
        'kava'      => ['name' => __('Kavárna', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M4 9h12v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5z"/><path d="M16 10h1.5a2.5 2.5 0 0 1 0 5H16"/><path d="M8 3.5c-.8 1 .8 2 0 3M12 3.5c-.8 1 .8 2 0 3"/>'],
        'pivo'      => ['name' => __('Hospoda', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M5 8h10v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z"/><path d="M15 11h2a2 2 0 0 1 0 4h-2"/><path d="M5 8c0-3.5 10-3.5 10 0"/>'],
        'zdravi'    => ['name' => __('Lékař', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M9.5 4h5v5.5H20v5h-5.5V20h-5v-5.5H4v-5h5.5z"/>'],
        'lekarna'   => ['name' => __('Lékárna', 'martin-dev-vyber-bytu'), 'svg' => '<rect x="3.5" y="8.5" width="17" height="7" rx="3.5" transform="rotate(-45 12 12)"/><path d="m9.5 9.5 5 5"/>'],
        'posta'     => ['name' => __('Pošta', 'martin-dev-vyber-bytu'), 'svg' => '<rect x="3" y="6" width="18" height="12" rx="1.5"/><path d="m3.5 7 8.5 6 8.5-6"/>'],
        'banka'     => ['name' => __('Banka', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M3 9.5 12 4l9 5.5z"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 20h18"/>'],
        'budova'    => ['name' => __('Budova / kultura', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M3 21h18M5 21V10l7-5 7 5v11"/><path d="M9.5 21v-5h5v5"/>'],
        'kostel'    => ['name' => __('Kostel', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M12 2.5v4M10 4.5h4"/><path d="M8 21v-9l4-4 4 4v9"/><path d="M4 21v-6l4-2M20 21v-6l-4-2M3 21h18"/><path d="M11 21v-3h2v3"/>'],
        'sport'     => ['name' => __('Sport', 'martin-dev-vyber-bytu'), 'svg' => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c3 3 3 14 0 17M12 3.5c-3 3-3 14 0 17"/>'],
        'kolo'      => ['name' => __('Cyklostezka', 'martin-dev-vyber-bytu'), 'svg' => '<circle cx="6" cy="16" r="3.5"/><circle cx="18" cy="16" r="3.5"/><path d="m6 16 4-7h5l3 7M10 9l3 7H6M8.5 6h3"/>'],
        'strom'     => ['name' => __('Příroda', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M12 3 6.5 11h3.5L6 16.5h12L14 11h3.5z"/><path d="M12 16.5V21"/>'],
        'park'      => ['name' => __('Park', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M7 4 3.5 11h7z"/><path d="M7 11v8"/><circle cx="16" cy="9" r="4.5"/><path d="M16 13.5V19M3 20h18"/>'],
        'voda'      => ['name' => __('Voda', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M2 10c2.5-2 5-2 7.5 0s5 2 7.5 0 3.5-1.5 5-1"/><path d="M2 15.5c2.5-2 5-2 7.5 0s5 2 7.5 0 3.5-1.5 5-1"/>'],
        'autobus'   => ['name' => __('Autobus', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M6.5 3h11A2.5 2.5 0 0 1 20 5.5V17H4V5.5A2.5 2.5 0 0 1 6.5 3z"/><path d="M4 11h16M7.5 17v3M16.5 17v3"/>'],
        'vlak'      => ['name' => __('Vlak', 'martin-dev-vyber-bytu'), 'svg' => '<rect x="5" y="3" width="14" height="14" rx="3"/><path d="M5 10h14"/><path d="M8.5 13.5h.01M15.5 13.5h.01"/><path d="m8 17-2 4M16 17l2 4"/>'],
        'silnice'   => ['name' => __('Silnice / dálnice', 'martin-dev-vyber-bytu'), 'svg' => '<path d="M8.5 3 5 21M15.5 3 19 21"/><path d="M12 4.5v2.5M12 10.5V13M12 16.5V19"/>'],
        'parkovani' => ['name' => __('Parkování', 'martin-dev-vyber-bytu'), 'svg' => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="M9.5 17V7.5h3.5a3 3 0 0 1 0 6H9.5"/>'],
    ]);
}

/** Celé SVG ikony (obsah je z pevného seznamu v kódu, ne od uživatele). */
function okoli_icon_svg(string $key): string
{
    $icons = okoli_icons();
    if (!isset($icons[$key])) {
        return '';
    }
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $icons[$key]['svg'] . '</svg>';
}

function okoli_modes(): array
{
    return ['cisla', 'ikony', 'kombi'];
}

/** Text zkrácený na max. délku (po sanitize_text_field). */
function okoli_text($value, int $max): string
{
    $text = sanitize_text_field(is_scalar($value) ? (string) $value : '');
    return function_exists('mb_substr') ? mb_substr($text, 0, $max, 'UTF-8') : substr($text, 0, $max);
}

/** Souřadnice v procentech 0–100, nebo null. */
function okoli_coord($value): ?float
{
    if (!is_numeric($value)) {
        return null;
    }
    return round(min(100, max(0, (float) $value)), 2);
}

/** Odkaz jen http(s), jinak prázdný. */
function okoli_url($value): string
{
    $url = trim(is_scalar($value) ? (string) $value : '');
    if ($url === '') {
        return '';
    }
    $url = esc_url_raw($url, ['http', 'https']);
    return strlen($url) <= 500 ? $url : '';
}

function sanitize_maps($maps): array
{
    if (!is_array($maps)) {
        return [];
    }
    $cats  = okoli_categories();
    $icons = okoli_icons();
    $out   = [];
    $seen  = [];

    foreach (array_slice($maps, 0, 20) as $m) {
        if (!is_array($m) || !($id = clean_id($m['id'] ?? '')) || isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $mode      = (string) ($m['mode'] ?? '');

        $map = [
            'id'         => $id,
            'name'       => okoli_text($m['name'] ?? '', 120),
            'image'      => clean_image($m['image'] ?? []),
            'mode'       => in_array($mode, okoli_modes(), true) ? $mode : 'cisla',
            'cat_colors' => !empty($m['cat_colors']),
            'filter'     => !empty($m['filter']),
            'proj'       => null,
            'points'     => [],
        ];

        $p = $m['proj'] ?? null;
        if (is_array($p) && ($px = okoli_coord($p['x'] ?? null)) !== null && ($py = okoli_coord($p['y'] ?? null)) !== null) {
            $map['proj'] = [
                'x'    => $px,
                'y'    => $py,
                'name' => okoli_text($p['name'] ?? '', 120),
                'desc' => okoli_text($p['desc'] ?? '', 160),
            ];
        }

        $pseen = [];
        foreach (array_slice((array) ($m['points'] ?? []), 0, 100) as $pt) {
            if (!is_array($pt) || !($pid = clean_id($pt['id'] ?? '')) || isset($pseen[$pid])) {
                continue;
            }
            $x = okoli_coord($pt['x'] ?? null);
            $y = okoli_coord($pt['y'] ?? null);
            if ($x === null || $y === null) {
                continue;
            }
            $pseen[$pid] = true;
            $cat         = (string) ($pt['cat'] ?? '');
            $cat         = isset($cats[$cat]) ? $cat : (string) array_key_first($cats);
            $icon        = (string) ($pt['icon'] ?? '');
            if (!isset($icons[$icon])) {
                $icon = isset($icons[$cats[$cat]['icon'] ?? '']) ? $cats[$cat]['icon'] : 'bod';
            }
            $map['points'][] = [
                'id'   => $pid,
                'name' => okoli_text($pt['name'] ?? '', 120),
                'cat'  => $cat,
                'icon' => $icon,
                'desc' => okoli_text($pt['desc'] ?? '', 300),
                'dist' => okoli_text($pt['dist'] ?? '', 40),
                'url'  => okoli_url($pt['url'] ?? ''),
                'x'    => $x,
                'y'    => $y,
            ];
        }
        $out[] = $map;
    }
    return $out;
}
