<?php
/**
 * Výstup na detailu jednotky (od 0.17.0):
 *   render_accessories() – Příslušenství (stání, garáž, sklep…)       [martin_dv_prislusenstvi]
 *   render_prev_next()   – Předchozí / další jednotka                  [martin_dv_navigace]
 *   render_similar()     – Podobné jednotky (karty)                    [martin_dv_podobne]
 * Breakdance elementy Prislusenstvi, PredchoziDalsi, PodobneJednotky volají tyto funkce ze ssr.php.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('martin_dv_navigace', function ($atts) {
    $atts = shortcode_atts(['id' => 0, 'predchozi' => '', 'dalsi' => ''], $atts, 'martin_dv_navigace');
    return render_prev_next(absint($atts['id']), ['prev_text' => $atts['predchozi'], 'next_text' => $atts['dalsi']]);
});

add_shortcode('martin_dv_podobne', function ($atts) {
    $atts = shortcode_atts(['id' => 0, 'pocet' => 3, 'sloupce' => 3, 'prodane' => 'ne', 'jen_shoda' => 'ne', 'obrazek' => 'ano'], $atts, 'martin_dv_podobne');
    return render_similar(absint($atts['id']), [
        'count'      => absint($atts['pocet']),
        'cols'       => absint($atts['sloupce']),
        'with_sold'  => $atts['prodane'] === 'ano',
        'only_match' => $atts['jen_shoda'] === 'ano',
        'hide_image' => $atts['obrazek'] === 'ne',
    ]);
});

/** Označení jednotky „Byt 1.02“. */
function unit_label(int $unit_id): string
{
    $types = unit_types();
    $num   = trim((string) get_post_meta($unit_id, 'dv_cislo', true));
    $type  = $types[get_post_meta($unit_id, 'dv_typ', true)] ?? $types['byt'];
    return $num !== '' ? $type . ' ' . $num : get_the_title($unit_id);
}

/** Obrázek jednotky: náhledový obrázek → první vizualizace → první fotka (ID přílohy, 0 = žádný). */
function unit_image_id(int $unit_id): int
{
    $thumb = (int) get_post_thumbnail_id($unit_id);
    if ($thumb) {
        return $thumb;
    }
    $all = unit_gallery($unit_id, 'dv_vizualizace') ?: unit_gallery($unit_id, 'dv_galerie');
    return $all ? (int) $all[0] : 0;
}

/* ---------- Příslušenství ---------- */

/**
 * @param array $args hide_area, hide_price, no_links (bez odkazu na samostatnou jednotku)
 */
function render_accessories(int $id, array $args = []): string
{
    $unit = resolve_unit($id);
    if (!$unit) {
        return unit_missing_notice(__('Příslušenství', 'martin-dev-vyber-bytu'));
    }
    $rows = unit_accessory_rows($unit);
    if (!$rows) {
        // Starší pole dv_acc_* (bez plochy a odkazu).
        $rows = array_map(function ($a) {
            return ['label' => $a['label'], 'areaTxt' => '', 'price' => $a['price'], 'url' => '', 'typ' => 'jine'];
        }, unit_accessory_list($unit));
    }
    if (!$rows) {
        return editor_notice(__('Příslušenství: jednotka žádné nemá.', 'martin-dev-vyber-bytu'));
    }
    $h = ensure_unit_assets() . '<ul class="martin-dv-acc">';
    foreach ($rows as $r) {
        $label = esc_html($r['label']);
        if ($r['url'] !== '' && empty($args['no_links'])) {
            $label = '<a href="' . esc_url($r['url']) . '">' . $label . '</a>';
        }
        $h .= '<li class="martin-dv-acc__item martin-dv-acc__item--' . esc_attr($r['typ']) . '">'
            . '<span class="martin-dv-acc__label">' . $label . '</span>'
            . (empty($args['hide_area']) && $r['areaTxt'] !== '' ? '<span class="martin-dv-acc__area">' . esc_html($r['areaTxt']) . '</span>' : '')
            . (empty($args['hide_price']) && $r['price'] !== '' ? '<span class="martin-dv-acc__price">' . esc_html($r['price']) . '</span>' : '')
            . '</li>';
    }
    return $h . '</ul>';
}

/* ---------- Předchozí / další ---------- */

/**
 * @param array $args prev_text, next_text (výchozí „Předchozí“ / „Další“), hide_dir (bez těchto textů)
 */
function render_prev_next(int $id, array $args = []): string
{
    $unit = resolve_unit($id);
    if (!$unit) {
        return unit_missing_notice(__('Předchozí / další', 'martin-dev-vyber-bytu'));
    }
    $n = unit_neighbors($unit);
    if (!$n['prev'] && !$n['next']) {
        return editor_notice(__('Předchozí / další: v projektu není jiná publikovaná jednotka.', 'martin-dev-vyber-bytu'));
    }
    $texts = [
        'prev' => trim((string) ($args['prev_text'] ?? '')) ?: __('Předchozí', 'martin-dev-vyber-bytu'),
        'next' => trim((string) ($args['next_text'] ?? '')) ?: __('Další', 'martin-dev-vyber-bytu'),
    ];
    $h = ensure_unit_assets() . '<nav class="martin-dv-nav" aria-label="' . esc_attr__('Další jednotky projektu', 'martin-dev-vyber-bytu') . '">';
    foreach (['prev', 'next'] as $dir) {
        $to = $n[$dir];
        if (!$to) {
            $h .= '<span class="martin-dv-nav__' . $dir . ' is-empty" aria-hidden="true"></span>';
            continue;
        }
        $meta = implode(' · ', array_filter([(string) get_post_meta($to, 'dv_dispozice', true), dv_unit_value('marmal_dv_jednotka_plocha', $to)]));
        $h   .= '<a class="martin-dv-nav__' . $dir . '" href="' . esc_url(get_permalink($to)) . '" rel="' . $dir . '">'
            . (empty($args['hide_dir']) ? '<span class="martin-dv-nav__dir">' . ($dir === 'prev' ? '← ' : '') . esc_html($texts[$dir]) . ($dir === 'next' ? ' →' : '') . '</span>' : '')
            . '<span class="martin-dv-nav__label">' . esc_html(unit_label($to)) . '</span>'
            . ($meta !== '' ? '<span class="martin-dv-nav__meta">' . esc_html($meta) . '</span>' : '')
            . '</a>';
    }
    return $h . '</nav>';
}

/* ---------- Podobné jednotky ---------- */

/**
 * @param array $args count, cols (1–4), with_sold, only_match, hide_image, hide_price
 */
function render_similar(int $id, array $args = []): string
{
    $unit = resolve_unit($id);
    if (!$unit) {
        return unit_missing_notice(__('Podobné jednotky', 'martin-dev-vyber-bytu'));
    }
    $ids = similar_units($unit, $args);
    if (!$ids) {
        return editor_notice(__('Podobné jednotky: v projektu žádné další nejsou.', 'martin-dev-vyber-bytu'));
    }
    $cols = max(1, min(4, (int) ($args['cols'] ?? 3) ?: 3));
    $h    = ensure_unit_assets() . '<div class="martin-dv-similar" style="--mdj-cols:' . (int) $cols . '">';
    foreach ($ids as $sid) {
        $info  = unit_info($sid, []);
        if (!$info) {
            continue;
        }
        $st    = unit_status($sid);
        $color = sanitize_css_color($st['color']);
        $img   = empty($args['hide_image']) ? unit_image_id($sid) : 0;
        $meta  = implode(' · ', array_filter([$info['disp'], dv_unit_value('marmal_dv_jednotka_plocha', $sid), unit_floor_text($sid)]));
        $h    .= '<a class="martin-dv-similar__card" href="' . esc_url($info['url']) . '">'
            . ($img ? '<span class="martin-dv-similar__img">' . wp_get_attachment_image($img, 'medium_large', false, ['loading' => 'lazy', 'decoding' => 'async', 'alt' => '']) . '</span>' : '')
            . '<span class="martin-dv-similar__body">'
            . '<span class="martin-dv-similar__top"><span class="martin-dv-similar__title">' . esc_html($info['label']) . '</span>'
            . '<span class="martin-dv-badge" data-status="' . esc_attr($st['kind']) . '" style="' . esc_attr('--c:' . ($color !== '' ? $color : 'var(--bde-brand-primary-color, #2f6b4f)')) . '">' . esc_html($st['label']) . '</span></span>'
            . ($meta !== '' ? '<span class="martin-dv-similar__meta">' . esc_html($meta) . '</span>' : '')
            . (empty($args['hide_price']) && $info['priceTxt'] !== '' ? '<span class="martin-dv-similar__price">' . esc_html($info['priceTxt']) . '</span>' : '')
            . '</span></a>';
    }
    return $h . '</div>';
}
