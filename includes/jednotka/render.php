<?php
/**
 * Výstup údajů jednotky na detailu (od 0.14.0):
 *   render_rooms()   – Tabulka místností se součtem      [martin_dv_mistnosti]
 *   render_params()  – Parametry jednotky (prázdné se skryjí) [martin_dv_parametry]
 *   render_schema()  – Schéma podlaží se zvýrazněnou jednotkou [martin_dv_schema]
 * Bez ID se bere aktuální příspěvek (Breakdance šablona detailu jednotky).
 * Breakdance elementy (elements/Mistnosti, Parametry, SchemaPodlazi) volají tyto funkce ze ssr.php.
 * Všechny hodnoty se escapují při výpisu (esc_html / esc_attr / esc_url).
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

/* ---------- Assety ---------- */

add_action('init', function () {
    wp_register_style('martin-dv-jednotka', MARTIN_DV_URL . 'assets/front/jednotka.css', [], asset_ver('assets/front/jednotka.css'));
}, 20);

/** Na detailu jednotky CSS rovnou do <head>; jinde ho doplní ensure_unit_assets(). */
add_action('wp_enqueue_scripts', function () {
    if (is_singular(PT_UNIT) || apply_filters('martin_dv_load_everywhere', false)) {
        wp_enqueue_style('martin-dv-jednotka');
    }
});

function ensure_unit_assets(): string
{
    if (wp_style_is('martin-dv-jednotka', 'done')) {
        return '';
    }
    if (!did_action('wp_head') || doing_action('wp_head')) {
        wp_enqueue_style('martin-dv-jednotka');
        return '';
    }
    wp_styles()->done[] = 'martin-dv-jednotka';
    return '<link rel="stylesheet" id="martin-dv-jednotka-css" href="' . esc_url(add_query_arg('ver', asset_ver('assets/front/jednotka.css'), MARTIN_DV_URL . 'assets/front/jednotka.css')) . '" media="all">';
}

/* ---------- Shortcody ---------- */

add_shortcode('martin_dv_mistnosti', function ($atts) {
    $atts = shortcode_atts(['id' => 0, 'venkovni' => 'ano', 'poznamky' => 'ano', 'soucet' => 'ano'], $atts, 'martin_dv_mistnosti');
    return render_rooms(absint($atts['id']), [
        'hide_outdoor' => $atts['venkovni'] === 'ne',
        'hide_notes'   => $atts['poznamky'] === 'ne',
        'hide_sum'     => $atts['soucet'] === 'ne',
    ]);
});

add_shortcode('martin_dv_parametry', function ($atts) {
    $atts = shortcode_atts(['id' => 0, 'zakladni' => 'ano', 'vlastni' => 'ano', 'cena' => 'ne', 'sloupce' => 1], $atts, 'martin_dv_parametry');
    return render_params(absint($atts['id']), [
        'hide_basic'  => $atts['zakladni'] === 'ne',
        'hide_custom' => $atts['vlastni'] === 'ne',
        'show_price'  => $atts['cena'] === 'ano',
        'cols'        => absint($atts['sloupce']),
    ]);
});

add_shortcode('martin_dv_schema', function ($atts) {
    $atts = shortcode_atts(['id' => 0, 'nazev' => 'ano', 'ostatni' => 'ano'], $atts, 'martin_dv_schema');
    return render_schema(absint($atts['id']), [
        'hide_caption' => $atts['nazev'] === 'ne',
        'hide_others'  => $atts['ostatni'] === 'ne',
    ]);
});

/* ---------- Společné ---------- */

/**
 * ID jednotky k výpisu: zadané ID → aktuální příspěvek. Jen publikovaná jednotka,
 * nebo nepublikovaná pro toho, kdo ji smí upravovat (náhled v builderu).
 */
function resolve_unit(int $id): int
{
    $id = $id ?: (int) get_the_ID();
    $id = $id ? translate_id($id) : 0;
    if (!$id || get_post_type($id) !== PT_UNIT) {
        return 0;
    }
    if (get_post_status($id) !== 'publish' && !current_user_can('edit_post', $id)) {
        return 0;
    }
    return $id;
}

function unit_missing_notice(string $what): string
{
    /* translators: %s: název prvku, např. Tabulka místností */
    return editor_notice(sprintf(__('%s: zobrazí se na detailu jednotky. V šabloně nastav náhled na některou jednotku, nebo zadej ID jednotky.', 'martin-dev-vyber-bytu'), $what));
}

/* ---------- Tabulka místností ---------- */

/**
 * @param array $args hide_outdoor, hide_notes, hide_sum (bool)
 */
function render_rooms(int $id, array $args = []): string
{
    $unit = resolve_unit($id);
    if (!$unit) {
        return unit_missing_notice(__('Tabulka místností', 'martin-dev-vyber-bytu'));
    }
    $rooms    = unit_rooms($unit);
    $outdoors = empty($args['hide_outdoor']) ? unit_outdoors($unit) : [];
    if (!$rooms && !$outdoors) {
        return editor_notice(__('Tabulka místností: jednotka zatím nemá vyplněné místnosti (Upravit jednotku → Místnosti).', 'martin-dev-vyber-bytu'));
    }
    $label  = area_label(project_of_unit($unit));
    $sums   = unit_area_sums($unit);
    $types  = outdoor_types();
    $notes  = empty($args['hide_notes']);
    $h      = ensure_unit_assets() . '<div class="martin-dv-rooms"><table class="martin-dv-rooms__table">';
    $h     .= '<thead><tr><th scope="col">' . esc_html__('Místnost', 'martin-dev-vyber-bytu') . '</th><th scope="col" class="martin-dv-rooms__area">' . esc_html__('Plocha', 'martin-dev-vyber-bytu') . '</th></tr></thead>';

    if ($rooms) {
        $h .= '<tbody class="martin-dv-rooms__inside">';
        foreach ($rooms as $r) {
            $note = $notes ? $r['poznamka'] : '';
            if (!$r['zapocitat']) {
                $note = trim($note . ($note !== '' ? ' · ' : '') . __('nezapočítává se', 'martin-dev-vyber-bytu'));
            }
            $h .= '<tr class="martin-dv-rooms__row martin-dv-rooms__row--' . esc_attr($r['typ']) . ($r['zapocitat'] ? '' : ' is-excluded') . '">'
                . '<th scope="row" class="martin-dv-rooms__name">' . esc_html($r['nazev'])
                . ($note !== '' ? ' <span class="martin-dv-rooms__note">' . esc_html($note) . '</span>' : '') . '</th>'
                . '<td class="martin-dv-rooms__area">' . ($r['plocha'] > 0 ? esc_html(fmt_area_exact($r['plocha'])) : '—') . '</td></tr>';
        }
        $h .= '</tbody>';
        if (empty($args['hide_sum']) && $sums['rooms'] > 0) {
            $h .= '<tbody class="martin-dv-rooms__sum"><tr><th scope="row">' . esc_html($label) . '</th>'
                . '<td class="martin-dv-rooms__area">' . esc_html(fmt_area_exact($sums['rooms'])) . '</td></tr></tbody>';
        }
    }

    if ($outdoors) {
        $h .= '<tbody class="martin-dv-rooms__outside">';
        foreach ($outdoors as $o) {
            $dir = orientation_text($o['orientace']);
            $h  .= '<tr class="martin-dv-rooms__row martin-dv-rooms__row--' . esc_attr($o['typ']) . '">'
                . '<th scope="row" class="martin-dv-rooms__name">' . esc_html($types[$o['typ']])
                . ($dir !== '' ? ' <span class="martin-dv-rooms__note" title="' . esc_attr(orientation_text($o['orientace'], true)) . '">' . esc_html($dir) . '</span>' : '') . '</th>'
                . '<td class="martin-dv-rooms__area">' . ($o['plocha'] > 0 ? esc_html(fmt_area_exact($o['plocha'])) : '—') . '</td></tr>';
        }
        $h .= '</tbody>';
        if (empty($args['hide_sum']) && count($outdoors) > 1 && $sums['outdoor'] > 0) {
            $h .= '<tbody class="martin-dv-rooms__sum"><tr><th scope="row">' . esc_html__('Venkovní plochy celkem', 'martin-dev-vyber-bytu') . '</th>'
                . '<td class="martin-dv-rooms__area">' . esc_html(fmt_area_exact($sums['outdoor'])) . '</td></tr></tbody>';
        }
    }

    return $h . '</table></div>';
}

/* ---------- Parametry jednotky ---------- */

/**
 * Seznam údajů jednotky: [['key' => 'dispozice', 'label' => 'Dispozice', 'value' => '2+kk'], …]
 * Jen vyplněné. Používá render_params() a později i dynamická pole.
 *
 * @param array $args hide_basic, hide_custom, show_price (bool); render_params() i cols (1 | 2)
 */
function unit_param_rows(int $unit, array $args = []): array
{
    $rows = [];
    $add  = function (string $key, string $label, string $value) use (&$rows) {
        if (trim($value) !== '') {
            $rows[] = ['key' => $key, 'label' => $label, 'value' => $value];
        }
    };
    $m = function (string $key) use ($unit) {
        return get_post_meta($unit, $key, true);
    };

    if (empty($args['hide_basic'])) {
        $project = project_of_unit($unit);
        $types   = unit_types();
        $outs    = outdoor_types();
        $area    = clean_area($m('dv_plocha'));

        $add('typ', __('Typ', 'martin-dev-vyber-bytu'), $types[$m('dv_typ')] ?? '');
        $add('dispozice', __('Dispozice', 'martin-dev-vyber-bytu'), (string) $m('dv_dispozice'));
        // `dv_plocha` = celková plocha (s příčkami); součet místností jen když se liší (jinak by se opakoval).
        $add('plocha', total_label($project), $area > 0 ? fmt_area_exact($area) : '');
        $rooms_sum = unit_area_sums($unit)['rooms'];
        if ($rooms_sum > 0 && abs($rooms_sum - $area) > 0.01) {
            $add('uzitna', area_label($project), fmt_area_exact($rooms_sum));
        }
        foreach (unit_outdoors($unit) as $o) {
            $dir = orientation_text($o['orientace']);
            $add('venkovni', $outs[$o['typ']], trim(($o['plocha'] > 0 ? fmt_area_exact($o['plocha']) : '') . ($dir !== '' ? ' (' . $dir . ')' : '')) ?: '✓');
        }
        $add('budova', __('Budova', 'martin-dev-vyber-bytu'), (string) $m('dv_budova'));
        $add('podlazi', __('Podlaží', 'martin-dev-vyber-bytu'), unit_floor_text($unit));
        $add('umisteni', __('Umístění', 'martin-dev-vyber-bytu'), (string) $m('dv_umisteni'));
        $orient = $m('dv_orientace');
        $add('orientace', __('Orientace oken', 'martin-dev-vyber-bytu'), orientation_text(is_array($orient) ? $orient : []));
        $add('vyhled', __('Výhled', 'martin-dev-vyber-bytu'), (string) $m('dv_vyhled'));

        if (!empty($args['show_price'])) {
            $info = unit_info($unit, []);
            if ($info) {
                $add('stav', __('Stav', 'martin-dev-vyber-bytu'), $info['statusLabel']);
                $add('cena', __('Cena', 'martin-dev-vyber-bytu'), $info['priceTxt']);
            }
        }
    }

    if (empty($args['hide_custom'])) {
        foreach (unit_params($unit) as $i => $p) {
            $add('vlastni-' . $i, $p['nazev'], $p['hodnota']);
        }
    }
    return $rows;
}

function render_params(int $id, array $args = []): string
{
    $unit = resolve_unit($id);
    if (!$unit) {
        return unit_missing_notice(__('Parametry jednotky', 'martin-dev-vyber-bytu'));
    }
    $rows = unit_param_rows($unit, $args);
    if (!$rows) {
        return editor_notice(__('Parametry jednotky: žádný vyplněný údaj.', 'martin-dev-vyber-bytu'));
    }
    $cols = (int) ($args['cols'] ?? 1) === 2 ? ' martin-dv-params--2' : '';
    $h    = ensure_unit_assets() . '<dl class="martin-dv-params' . $cols . '">';
    foreach ($rows as $r) {
        $key = preg_replace('/[^a-z0-9-]/', '', $r['key']);
        $h  .= '<div class="martin-dv-params__row martin-dv-params__row--' . esc_attr($key) . '">'
            . '<dt class="martin-dv-params__label">' . esc_html($r['label']) . '</dt>'
            . '<dd class="martin-dv-params__value">' . esc_html($r['value']) . '</dd></div>';
    }
    return $h . '</dl>';
}

/* ---------- Schéma podlaží ---------- */

/**
 * Půdorys podlaží (u mezonetu všech), kde je jednotka obkreslená, se zvýrazněnou jednotkou.
 * Rodinný / řadový dům obkreslený přímo v pohledu → pohled se zvýrazněným domem.
 *
 * @param array $args hide_caption, hide_others (bool)
 */
function render_schema(int $id, array $args = []): string
{
    $unit = resolve_unit($id);
    if (!$unit) {
        return unit_missing_notice(__('Schéma podlaží', 'martin-dev-vyber-bytu'));
    }
    $project = project_of_unit($unit);
    if (!$project) {
        return editor_notice(__('Schéma podlaží: jednotka nemá vybraný projekt.', 'martin-dev-vyber-bytu'));
    }
    // Nepublikovaný projekt (rozpracované půdorysy) vidí jen ten, kdo ho smí upravovat – stejně jako výběr.
    if (get_post_status($project) !== 'publish' && !current_user_can('edit_post', $project)) {
        return '';
    }
    $data   = get_project_data($project);
    $plans  = [];
    foreach ($data['floors'] as $f) {
        $mine = [];
        $rest = [];
        foreach ($f['areas'] as $a) {
            if (translate_id((int) $a['unit']) === $unit || (int) $a['unit'] === $unit) {
                $mine[] = $a['poly'];
            } elseif ((int) $a['unit']) {
                $rest[] = $a['poly'];
            }
        }
        if ($mine) {
            $plans[] = ['name' => $f['name'], 'image' => $f['image'], 'mine' => $mine, 'rest' => $rest];
        }
    }
    if (!$plans) {
        foreach ($data['views'] as $v) {
            $mine = [];
            $rest = [];
            foreach ($v['areas'] as $a) {
                if (!$a['target'] || $a['target']['type'] !== 'unit') {
                    continue;
                }
                $tid = (int) $a['target']['id'];
                if ($tid === $unit || translate_id($tid) === $unit) {
                    $mine[] = $a['poly'];
                } else {
                    $rest[] = $a['poly'];
                }
            }
            if ($mine) {
                $plans[] = ['name' => $v['name'], 'image' => $v['image'], 'mine' => $mine, 'rest' => $rest];
            }
        }
    }
    if (!$plans) {
        return editor_notice(__('Schéma podlaží: jednotka není obkreslená v žádném půdorysu ani pohledu projektu.', 'martin-dev-vyber-bytu'));
    }

    $h = ensure_unit_assets() . '<div class="martin-dv-schema">';
    foreach ($plans as $plan) {
        $src = image_src($plan['image']);
        if (!$src) {
            continue;
        }
        $alt = sprintf(
            /* translators: 1: název podlaží, 2: označení jednotky */
            __('%1$s – umístění jednotky %2$s', 'martin-dev-vyber-bytu'),
            $plan['name'],
            get_the_title($unit)
        );
        $h .= '<figure class="martin-dv-schema__plan">'
            . '<div class="martin-dv-schema__stage" style="aspect-ratio:' . esc_attr($src['w'] . ' / ' . $src['h']) . '">'
            . wp_get_attachment_image((int) $plan['image']['id'], 'large', false, ['class' => 'martin-dv-schema__img', 'alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async'])
            . '<svg class="martin-dv-schema__svg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">';
        if (empty($args['hide_others'])) {
            foreach ($plan['rest'] as $poly) {
                $h .= '<polygon class="martin-dv-schema__other" points="' . esc_attr(poly_points($poly)) . '" vector-effect="non-scaling-stroke"/>';
            }
        }
        foreach ($plan['mine'] as $poly) {
            $h .= '<polygon class="martin-dv-schema__unit" points="' . esc_attr(poly_points($poly)) . '" vector-effect="non-scaling-stroke"/>';
        }
        $h .= '</svg></div>';
        if (empty($args['hide_caption']) && $plan['name'] !== '') {
            $h .= '<figcaption class="martin-dv-schema__caption">' . esc_html($plan['name']) . '</figcaption>';
        }
        $h .= '</figure>';
    }
    return $h . '</div>';
}
