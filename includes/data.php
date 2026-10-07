<?php
/**
 * Číselníky, čtení a sanitizace dat projektu, údaje o jednotkách.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

/* ---------- Číselníky ---------- */

function unit_types(): array
{
    return [
        'byt'      => __('Byt', 'martin-dev-vyber-bytu'),
        'rodinny_dum' => __('Rodinný dům', 'martin-dev-vyber-bytu'),
        'dvojdum'  => __('Dvojdům', 'martin-dev-vyber-bytu'),
        'radovy_dum' => __('Řadový dům', 'martin-dev-vyber-bytu'),
        'atelier'  => __('Ateliér', 'martin-dev-vyber-bytu'),
        'nebytovy' => __('Nebytový prostor', 'martin-dev-vyber-bytu'),
        'sklep'    => __('Sklep', 'martin-dev-vyber-bytu'),
        'garaz'    => __('Garáž', 'martin-dev-vyber-bytu'),
        'stani'    => __('Parkovací stání', 'martin-dev-vyber-bytu'),
    ];
}

function statuses(): array
{
    return [
        'volny'       => __('Volný', 'martin-dev-vyber-bytu'),
        'rezervovany' => __('Rezervovaný', 'martin-dev-vyber-bytu'),
        'prodany'     => __('Prodaný', 'martin-dev-vyber-bytu'),
    ];
}

function outdoor_types(): array
{
    return [
        'balkon'       => __('Balkon', 'martin-dev-vyber-bytu'),
        'lodzie'       => __('Lodžie', 'martin-dev-vyber-bytu'),
        'terasa'       => __('Terasa', 'martin-dev-vyber-bytu'),
        'predzahradka' => __('Předzahrádka', 'martin-dev-vyber-bytu'),
        'zahrada'      => __('Zahrada', 'martin-dev-vyber-bytu'),
        'pozemek'      => __('Pozemek', 'martin-dev-vyber-bytu'),
    ];
}

/** Volitelné příslušenství k dokoupení (zaškrtává se u každé jednotky zvlášť). */
function unit_accessories(): array
{
    return apply_filters('martin_dv_accessories', [
        'parkovaci_stani' => __('Parkovací stání', 'martin-dev-vyber-bytu'),
        'garaz'           => __('Garáž', 'martin-dev-vyber-bytu'),
        'sklep'           => __('Sklepní kóje', 'martin-dev-vyber-bytu'),
    ]);
}

function dispositions(): array
{
    $list = ['1+kk', '1+1', '2+kk', '2+1', '3+kk', '3+1', '4+kk', '4+1', '5+kk', '5+1', '6+kk', '6+1', '7+kk', '7+1'];
    return apply_filters('martin_dv_dispositions', array_combine($list, $list));
}

function default_settings(): array
{
    return [
        'show_table'     => true,
        'sold_clickable' => false,
        'show_outlines'  => false,
        'tip_area'       => true,
        'tip_outdoor'    => true,
        'tip_price'      => true,
        'tip_status'     => true,
        'button_text'    => '',
        'hint_view'      => '',
        'hint_floor'     => '',
        'color_accent'   => '',
        'color_reserved' => '#d39b2a',
        'color_sold'     => '#9aa0a8',
    ];
}

/* ---------- Čtení a sanitizace dat projektu ---------- */

function get_project_data(int $project_id): array
{
    $raw  = get_post_meta($project_id, META_DATA, true);
    $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    return sanitize_project_data(is_array($data) ? $data : []);
}

function clean_id($value): string
{
    return substr(preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)), 0, 40);
}

function clean_image($img): array
{
    $img = is_array($img) ? $img : [];
    return [
        'id' => absint($img['id'] ?? 0),
        'w'  => absint($img['w'] ?? 0),
        'h'  => absint($img['h'] ?? 0),
    ];
}

/** Body polygonu v procentech (0–100), min. 3 body. */
function clean_poly($poly): array
{
    if (!is_array($poly)) {
        return [];
    }
    $out = [];
    foreach (array_slice($poly, 0, 300) as $p) {
        if (!is_array($p) || count($p) < 2 || !is_numeric($p[0]) || !is_numeric($p[1])) {
            continue;
        }
        $out[] = [
            round(min(100, max(0, (float) $p[0])), 2),
            round(min(100, max(0, (float) $p[1])), 2),
        ];
    }
    return count($out) >= 3 ? $out : [];
}

/** Barva: hex, var(--…), rgb()/hsl(). Jinak prázdný řetězec. */
function sanitize_css_color($color): string
{
    $color = trim((string) $color);
    if ($color === '') {
        return '';
    }
    if (preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $color)
        || preg_match('/^var\(--[a-z0-9_-]+\)$/i', $color)
        || preg_match('/^(rgb|rgba|hsl|hsla)\([0-9.,%\s\/]+\)$/i', $color)) {
        return $color;
    }
    return '';
}

function sanitize_project_data(array $in): array
{
    $out = [
        'version'  => 1,
        'start'    => '',
        'views'    => [],
        'floors'   => [],
        'settings' => default_settings(),
    ];

    foreach (array_slice((array) ($in['floors'] ?? []), 0, 200) as $f) {
        if (!is_array($f) || !($id = clean_id($f['id'] ?? ''))) {
            continue;
        }
        $floor = [
            'id'    => $id,
            'name'  => sanitize_text_field($f['name'] ?? ''),
            'image' => clean_image($f['image'] ?? []),
            'areas' => [],
        ];
        foreach (array_slice((array) ($f['areas'] ?? []), 0, 500) as $a) {
            $aid  = is_array($a) ? clean_id($a['id'] ?? '') : '';
            $poly = is_array($a) ? clean_poly($a['poly'] ?? []) : [];
            if ($aid && $poly) {
                $floor['areas'][] = ['id' => $aid, 'unit' => absint($a['unit'] ?? 0), 'poly' => $poly];
            }
        }
        $out['floors'][] = $floor;
    }

    foreach (array_slice((array) ($in['views'] ?? []), 0, 50) as $v) {
        if (!is_array($v) || !($id = clean_id($v['id'] ?? ''))) {
            continue;
        }
        $view = [
            'id'    => $id,
            'name'  => sanitize_text_field($v['name'] ?? ''),
            'image' => clean_image($v['image'] ?? []),
            'areas' => [],
        ];
        foreach (array_slice((array) ($v['areas'] ?? []), 0, 200) as $a) {
            $aid  = is_array($a) ? clean_id($a['id'] ?? '') : '';
            $poly = is_array($a) ? clean_poly($a['poly'] ?? []) : [];
            if (!$aid || !$poly) {
                continue;
            }
            $target = null;
            if (!empty($a['target']) && is_array($a['target'])
                && in_array($a['target']['type'] ?? '', ['floor', 'view', 'unit'], true)
                && ($tid = clean_id($a['target']['id'] ?? ''))) {
                $target = ['type' => $a['target']['type'], 'id' => $tid];
            }
            $view['areas'][] = ['id' => $aid, 'target' => $target, 'poly' => $poly];
        }
        $out['views'][] = $view;
    }

    $start    = clean_id($in['start'] ?? '');
    $view_ids = array_column($out['views'], 'id');
    $out['start'] = in_array($start, $view_ids, true) ? $start : ($view_ids[0] ?? '');

    $s = is_array($in['settings'] ?? null) ? $in['settings'] : [];
    foreach (['show_table', 'sold_clickable', 'show_outlines', 'tip_area', 'tip_outdoor', 'tip_price', 'tip_status'] as $key) {
        if (array_key_exists($key, $s)) {
            $out['settings'][$key] = (bool) $s[$key];
        }
    }
    $out['settings']['button_text']    = sanitize_text_field($s['button_text'] ?? '');
    $out['settings']['hint_view']      = sanitize_text_field($s['hint_view'] ?? '');
    $out['settings']['hint_floor']     = sanitize_text_field($s['hint_floor'] ?? '');
    $out['settings']['color_accent']   = sanitize_css_color($s['color_accent'] ?? '');
    $out['settings']['color_reserved'] = sanitize_css_color($s['color_reserved'] ?? '') ?: '#d39b2a';
    $out['settings']['color_sold']     = sanitize_css_color($s['color_sold'] ?? '') ?: '#9aa0a8';

    return $out;
}

/* ---------- Jednotky ---------- */

/** Překlad ID jednotky do aktuálního jazyka (Polylang / WPML). Bez nich vrací původní ID. */
function translate_id(int $id): int
{
    if (function_exists('pll_get_post')) {
        $t = pll_get_post($id);
        if ($t) {
            return (int) $t;
        }
    }
    $t = apply_filters('wpml_object_id', $id, PT_UNIT, true);
    return $t ? (int) $t : $id;
}

function fmt_num($n): string
{
    $n = (float) $n;
    return number_format_i18n($n, (floor($n) == $n) ? 0 : 1);
}

function fmt_area($n): string
{
    /* translators: %s: plocha v metrech čtverečních */
    return sprintf(__('%s m²', 'martin-dev-vyber-bytu'), fmt_num($n));
}

/**
 * Údaje jednotky pro výpis. Čte post meta přímo (ACF je ukládá pod stejným názvem),
 * takže render funguje i bez načteného ACF.
 */
function unit_info(int $id, array $settings): ?array
{
    $pid  = translate_id($id);
    $post = get_post($pid);
    if (!$post || $post->post_type !== PT_UNIT || $post->post_status !== 'publish') {
        return null;
    }
    $m = function ($key) use ($pid) {
        return get_post_meta($pid, $key, true);
    };

    $types  = unit_types();
    $sts    = statuses();
    $outs   = outdoor_types();
    $type   = isset($types[$m('dv_typ')]) ? $m('dv_typ') : 'byt';
    $status = isset($sts[$m('dv_stav')]) ? $m('dv_stav') : 'volny';
    $num    = trim((string) $m('dv_cislo'));
    $label  = $num !== '' ? $types[$type] . ' ' . $num : get_the_title($pid);
    $area   = (float) $m('dv_plocha');
    $outT   = isset($outs[$m('dv_venkovni_typ')]) ? $m('dv_venkovni_typ') : '';
    $outA   = (float) $m('dv_venkovni_plocha');
    $price  = (int) $m('dv_cena');

    if ($status === 'prodany') {
        $price_txt = __('Prodáno', 'martin-dev-vyber-bytu');
    } elseif ($m('dv_cena_na_dotaz')) {
        $price_txt = __('Cena na dotaz', 'martin-dev-vyber-bytu');
    } elseif ($price > 0) {
        /* translators: %s: cena */
        $price_txt = sprintf(__('%s Kč', 'martin-dev-vyber-bytu'), number_format_i18n($price));
    } else {
        $price_txt = '';
    }

    $acc = unit_accessory_list($pid);

    return [
        'acc'         => $acc,
        'id'          => $pid,
        'num'         => $num !== '' ? $num : get_the_title($pid),
        'label'       => $label,
        'type'        => $type,
        'typeLabel'   => $types[$type],
        'disp'        => (string) $m('dv_dispozice'),
        'area'        => $area,
        'areaTxt'     => $area > 0 ? fmt_area($area) : '',
        'outLabel'    => $outT ? $outs[$outT] : '',
        'outAreaTxt'  => ($outT && $outA > 0) ? fmt_area($outA) : '',
        'outTxt'      => $outT ? trim($outs[$outT] . ' ' . ($outA > 0 ? fmt_area($outA) : '')) : '',
        'price'       => ($status === 'volny' && !$m('dv_cena_na_dotaz')) ? $price : 0,
        'priceTxt'    => $price_txt,
        'status'      => $status,
        'statusLabel' => $sts[$status],
        'floorTxt'    => (string) $m('dv_podlazi'),
        'url'         => get_permalink($pid),
        'clickable'   => $status !== 'prodany' || !empty($settings['sold_clickable']),
    ];
}

/** URL, šířka a výška obrázku z knihovny médií. */
function image_src(array $image): ?array
{
    if (empty($image['id'])) {
        return null;
    }
    $src = wp_get_attachment_image_src($image['id'], 'full');
    if (!$src) {
        return null;
    }
    $w = (int) ($src[1] ?: $image['w']);
    $h = (int) ($src[2] ?: $image['h']);
    return ['url' => $src[0], 'w' => $w, 'h' => $h];
}

/** Cena příslušenství: čisté číslo se naformátuje („350000“ → „350 000 Kč“), jinak se vypíše, jak je napsaná. */
function format_accessory_price(string $price): string
{
    $price = trim($price);
    if (preg_match('/^\d[\d\s.]*$/u', $price)) {
        /* translators: %s: cena */
        return sprintf(__('%s Kč', 'martin-dev-vyber-bytu'), number_format_i18n((int) preg_replace('/\D/', '', $price)));
    }
    return $price;
}

/**
 * Seznam příslušenství k dokoupení: [['label' => 'Garáž', 'price' => '350 000 Kč'], …]
 * Položka se zobrazí, jen když je zaškrtnutá (u vlastních vyplněný název) A má cenu.
 */
function unit_accessory_list(int $post_id): array
{
    $out = [];
    foreach (unit_accessories() as $key => $label) {
        $price = trim((string) get_post_meta($post_id, 'dv_acc_' . $key . '_cena', true));
        if (get_post_meta($post_id, 'dv_acc_' . $key, true) && $price !== '') {
            $out[] = ['label' => $label, 'price' => format_accessory_price($price)];
        }
    }

    // ACF Pro opakovač: počet řádků je v hlavním meta poli, hodnoty v dv_acc_vlastni_{i}_nazev / _cena.
    $rows = (int) get_post_meta($post_id, 'dv_acc_vlastni', true);
    for ($i = 0; $i < min($rows, 50); $i++) {
        $name  = trim((string) get_post_meta($post_id, 'dv_acc_vlastni_' . $i . '_nazev', true));
        $price = trim((string) get_post_meta($post_id, 'dv_acc_vlastni_' . $i . '_cena', true));
        if ($name !== '' && $price !== '') {
            $out[] = ['label' => $name, 'price' => format_accessory_price($price)];
        }
    }

    // ACF free: 3 pevné řádky.
    for ($i = 1; $i <= 3; $i++) {
        $name  = trim((string) get_post_meta($post_id, 'dv_acc_slot' . $i . '_nazev', true));
        $price = trim((string) get_post_meta($post_id, 'dv_acc_slot' . $i . '_cena', true));
        if ($name !== '' && $price !== '') {
            $out[] = ['label' => $name, 'price' => format_accessory_price($price)];
        }
    }

    return $out;
}
