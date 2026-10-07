<?php
/**
 * Výstup na frontendu: render_project() + shortcode [martin_vyber_bytu id="…"].
 * Breakdance element volá render_project() ze svého ssr.php.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

/* ---------- Assety ---------- */

add_action('init', function () {
    wp_register_style('martin-dv', MARTIN_DV_URL . 'assets/front/vyber-bytu.css', [], VERSION);
    wp_register_script('martin-dv', MARTIN_DV_URL . 'assets/front/vyber-bytu.js', [], VERSION, ['in_footer' => true, 'strategy' => 'defer']);
}, 20);

/**
 * Když víme dopředu, že stránka výběr obsahuje, načteme CSS do <head> (bez probliknutí).
 * Jinak se CSS doplní přímo u komponenty (viz ensure_assets).
 */
add_action('wp_enqueue_scripts', function () {
    $load = (bool) apply_filters('martin_dv_load_everywhere', false);
    if (!$load && is_singular()) {
        $id      = get_queried_object_id();
        $bd      = (string) get_post_meta($id, '_breakdance_data', true);
        $content = (string) get_post_field('post_content', $id);
        // Slug Breakdance elementu je MartinDV\Vyberbytu, shortcode martin_vyber_bytu.
        $needles = apply_filters('martin_dv_detect_needles', ['Vyberbytu', 'martin_vyber_bytu']);
        foreach ($needles as $needle) {
            if (stripos($bd, $needle) !== false || stripos($content, $needle) !== false) {
                $load = true;
                break;
            }
        }
    }
    if ($load) {
        wp_enqueue_style('martin-dv');
        wp_enqueue_script('martin-dv');
    }
});

function ensure_assets(): string
{
    wp_enqueue_script('martin-dv');
    if (wp_style_is('martin-dv', 'done')) {
        return '';
    }
    if (!did_action('wp_head') || doing_action('wp_head')) {
        wp_enqueue_style('martin-dv');
        return '';
    }
    // Hlavička už je venku (typické u Breakdance a v náhledu builderu) → link přímo u komponenty.
    wp_styles()->done[] = 'martin-dv';
    return '<link rel="stylesheet" id="martin-dv-css" href="' . esc_url(add_query_arg('ver', VERSION, MARTIN_DV_URL . 'assets/front/vyber-bytu.css')) . '" media="all">';
}

/* ---------- Shortcode ---------- */

add_shortcode('martin_vyber_bytu', function ($atts) {
    $atts = shortcode_atts(['id' => 0, 'tabulka' => ''], $atts, 'martin_vyber_bytu');
    $args = [];
    if ($atts['tabulka'] === 'ne') {
        $args['hide_table'] = true;
    }
    return render_project(absint($atts['id']), $args);
});

/* ---------- Render ---------- */

/** Hláška jen pro přihlášené editory (návštěvník nevidí nic). */
function editor_notice(string $msg): string
{
    if (!current_user_can('edit_posts')) {
        return '';
    }
    return '<div class="martin-dv-notice" style="padding:16px;border:1px dashed #c33;color:#c33;font:14px/1.4 sans-serif">' . esc_html($msg) . '</div>';
}

function poly_points(array $poly): string
{
    return implode(' ', array_map(function ($p) {
        return $p[0] . ',' . $p[1];
    }, $poly));
}

/**
 * @param int   $project_id ID projektu (CPT martin_dv_projekt)
 * @param array $args       hide_table (bool), accent (CSS barva – přebije Global Settings)
 */
function render_project(int $project_id, array $args = []): string
{
    if (!$project_id || get_post_type($project_id) !== PT_PROJECT) {
        return editor_notice(__('Výběr bytů: zadejte platné ID projektu (Výběr bytů → Projekty).', 'martin-dev-vyber-bytu'));
    }

    $data = get_project_data($project_id);
    $s    = $data['settings'];
    if (!empty($args['hide_table'])) {
        $s['show_table'] = false;
    }
    if ($s['button_text'] === '') {
        $s['button_text'] = __('Detail jednotky', 'martin-dev-vyber-bytu');
    }
    $accent = sanitize_css_color($args['accent'] ?? '') ?: $s['color_accent'];

    /* --- obrázky, platné pohledy a podlaží --- */
    $views = [];
    foreach ($data['views'] as $v) {
        if ($img = image_src($v['image'])) {
            $v['src']          = $img;
            $views[$v['id']]   = $v;
        }
    }
    $floors = [];
    foreach ($data['floors'] as $f) {
        if ($img = image_src($f['image'])) {
            $f['src']           = $img;
            $floors[$f['id']]   = $f;
        }
    }
    if (!$views) {
        return editor_notice(__('Výběr bytů: projekt zatím nemá žádný pohled s obrázkem.', 'martin-dev-vyber-bytu'));
    }
    $start = isset($views[$data['start']]) ? $data['start'] : array_key_first($views);

    /* --- jednotky --- */
    $units       = [];   // id => info
    $floor_units = [];   // floor id => [unit ids]
    $unit_floors = [];   // unit id => [floor ids]
    foreach ($floors as $fid => $f) {
        $floor_units[$fid] = [];
        foreach ($f['areas'] as $a) {
            if (!$a['unit']) {
                continue;
            }
            $tid = translate_id($a['unit']);
            if (!isset($units[$tid])) {
                $info = unit_info($a['unit'], $s);
                if (!$info) {
                    continue;
                }
                $units[$tid] = $info;
            }
            if (!in_array($tid, $floor_units[$fid], true)) {
                $floor_units[$fid][] = $tid;
            }
            if (!in_array($fid, $unit_floors[$tid] ?? [], true)) {
                $unit_floors[$tid][] = $fid;
            }
        }
    }
    foreach ($units as $id => &$u) {
        if ($u['floorTxt'] === '') {
            $u['floorTxt'] = implode(', ', array_map(function ($fid) use ($floors) {
                return $floors[$fid]['name'];
            }, $unit_floors[$id] ?? []));
        }
    }
    unset($u);

    /* --- souhrny pro bubliny podlaží a pohledů --- */
    $stats = function (array $ids) use ($units) {
        $free = array_filter($ids, function ($id) use ($units) {
            return $units[$id]['status'] === 'volny';
        });
        $prices = array_filter(array_map(function ($id) use ($units) {
            return $units[$id]['price'];
        }, $free));
        $lines = [
            /* translators: 1: počet jednotek, 2: počet volných */
            sprintf(__('Jednotky: %1$d · volné: %2$d', 'martin-dev-vyber-bytu'), count($ids), count($free)),
        ];
        if ($prices) {
            /* translators: %s: nejnižší cena */
            $lines[] = sprintf(__('od %s Kč', 'martin-dev-vyber-bytu'), number_format_i18n(min($prices)));
        }
        return $lines;
    };

    $keys = [];
    foreach ($floors as $fid => $f) {
        $keys['floor:' . $fid] = ['title' => $f['name'], 'lines' => $stats($floor_units[$fid])];
    }
    $view_floors = [];
    foreach ($views as $vid => $v) {
        $view_floors[$vid] = [];
        $ids               = [];
        foreach ($v['areas'] as $a) {
            if ($a['target'] && $a['target']['type'] === 'floor' && isset($floors[$a['target']['id']])) {
                $fid = $a['target']['id'];
                if (!in_array($fid, $view_floors[$vid], true)) {
                    $view_floors[$vid][] = $fid;
                }
                $ids = array_merge($ids, $floor_units[$fid]);
            }
        }
        // Pořadí podlaží podle seznamu v editoru (shora dolů).
        usort($view_floors[$vid], function ($a, $b) use ($floors) {
            return array_search($a, array_keys($floors), true) <=> array_search($b, array_keys($floors), true);
        });
        $keys['view:' . $vid] = ['title' => $v['name'], 'lines' => $ids ? $stats(array_values(array_unique($ids))) : []];
    }

    $uid = wp_unique_id('martin-dv-');
    $h   = ensure_assets();

    $style = '--martin-dv-reserved:' . $s['color_reserved'] . ';--martin-dv-sold:' . $s['color_sold'] . ';';
    if ($accent) {
        $style .= '--martin-dv-accent:' . $accent . ';';
    }

    $h .= '<div class="martin-dv' . ($s['show_outlines'] ? ' martin-dv--outlines' : '') . '" id="' . esc_attr($uid) . '" data-martin-dv style="' . esc_attr($style) . '">';

    /* legenda */
    $present = array_unique(array_column($units, 'status'));
    if ($present) {
        $h .= '<div class="martin-dv__legend">';
        foreach (statuses() as $key => $label) {
            if (in_array($key, $present, true)) {
                $h .= '<span data-status="' . esc_attr($key) . '"><i></i>' . esc_html($label) . '</span>';
            }
        }
        $h .= '</div>';
    }

    $back = '<button type="button" class="martin-dv__back" data-dv-back hidden>← ' . esc_html__('Zpět', 'martin-dev-vyber-bytu') . '</button>';

    /* pohledy */
    foreach ($views as $vid => $v) {
        $areas = '';
        $list  = '';
        $seen  = [];
        foreach ($v['areas'] as $a) {
            $t = $a['target'];
            if (!$t || ($t['type'] === 'floor' && !isset($floors[$t['id']])) || ($t['type'] === 'view' && !isset($views[$t['id']]))) {
                continue;
            }
            $key   = $t['type'] . ':' . $t['id'];
            $label = $t['type'] === 'floor' ? $floors[$t['id']]['name'] : $views[$t['id']]['name'];
            $href  = '#' . $uid . '-' . $t['type'] . '-' . $t['id'];
            $areas .= '<a class="martin-dv__area martin-dv__area--nav" href="' . esc_attr($href) . '" data-dv-go="' . esc_attr($key) . '" data-dv-key="' . esc_attr($key) . '" aria-label="' . esc_attr($label) . '">'
                . '<polygon points="' . esc_attr(poly_points($a['poly'])) . '"></polygon></a>';
            if (!isset($seen[$key])) {
                $seen[$key] = [$href, $label, $keys[$key]['lines'] ?? []];
            }
        }
        // Seznam: podlaží v pořadí editoru, pak další pohledy.
        uksort($seen, function ($a, $b) use ($floors) {
            $ia = strpos($a, 'floor:') === 0 ? array_search(substr($a, 6), array_keys($floors), true) : 9999;
            $ib = strpos($b, 'floor:') === 0 ? array_search(substr($b, 6), array_keys($floors), true) : 9999;
            return $ia <=> $ib;
        });
        foreach ($seen as $key => [$href, $label, $lines]) {
            $list .= '<a class="martin-dv__target" href="' . esc_attr($href) . '" data-dv-go="' . esc_attr($key) . '" data-dv-key="' . esc_attr($key) . '">'
                . '<span><strong>' . esc_html($label) . '</strong>' . ($lines ? '<small>' . esc_html(implode(' · ', $lines)) . '</small>' : '') . '</span>'
                . '<span aria-hidden="true">→</span></a>';
        }

        $h .= '<section class="martin-dv__panel" data-dv-panel="view:' . esc_attr($vid) . '" id="' . esc_attr($uid . '-view-' . $vid) . '"' . ($vid === $start ? '' : ' hidden') . '>'
            . '<div class="martin-dv__bar">' . $back . '<h3 class="martin-dv__title">' . esc_html($v['name']) . '</h3></div>'
            . '<div class="martin-dv__layout">'
            . stage_html($v, $areas, $vid === $start)
            . '<div class="martin-dv__side"><p class="martin-dv__hint">' . esc_html__('Najeďte na část domu a kliknutím ji otevřete.', 'martin-dev-vyber-bytu') . '</p>' . $list . '</div>'
            . '</div></section>';
    }

    /* podlaží */
    foreach ($floors as $fid => $f) {
        $areas = '';
        foreach ($f['areas'] as $a) {
            if (!$a['unit']) {
                continue;
            }
            $tid = translate_id($a['unit']);
            if (!isset($units[$tid])) {
                continue;
            }
            $u     = $units[$tid];
            $key   = 'unit:' . $tid;
            $aria  = trim($u['label'] . ', ' . implode(', ', array_filter([$u['disp'], $u['areaTxt'], $u['statusLabel']])), ', ');
            $poly  = '<polygon points="' . esc_attr(poly_points($a['poly'])) . '"></polygon>';
            if ($u['clickable']) {
                $areas .= '<a class="martin-dv__area martin-dv__area--unit" href="' . esc_url($u['url']) . '" data-dv-key="' . esc_attr($key) . '" data-status="' . esc_attr($u['status']) . '" aria-label="' . esc_attr($aria) . '">' . $poly . '</a>';
            } else {
                $areas .= '<g class="martin-dv__area martin-dv__area--unit is-disabled" data-dv-key="' . esc_attr($key) . '" data-status="' . esc_attr($u['status']) . '" role="img" aria-label="' . esc_attr($aria) . '">' . $poly . '</g>';
            }
        }
        $lines   = $keys['floor:' . $fid]['lines'];
        $summary = '<p class="martin-dv__kicker">' . esc_html__('Podlaží', 'martin-dev-vyber-bytu') . '</p>'
            . '<h4 class="martin-dv__card-title">' . esc_html($f['name']) . '</h4>';
        foreach ($lines as $line) {
            $summary .= '<p>' . esc_html($line) . '</p>';
        }
        $summary .= '<p class="martin-dv__hint">' . esc_html__('Najeďte myší na jednotku v půdorysu. Na telefonu klepněte jednou pro údaje, podruhé pro otevření detailu.', 'martin-dev-vyber-bytu') . '</p>';

        $h .= '<section class="martin-dv__panel" data-dv-panel="floor:' . esc_attr($fid) . '" id="' . esc_attr($uid . '-floor-' . $fid) . '" hidden>'
            . '<div class="martin-dv__bar">' . $back . '<h3 class="martin-dv__title">' . esc_html($f['name']) . '</h3><div class="martin-dv__pills" data-dv-pills></div></div>'
            . '<div class="martin-dv__layout">'
            . stage_html($f, $areas, false)
            . '<div class="martin-dv__side"><div class="martin-dv__card" data-dv-card>' . $summary . '</div></div>'
            . '</div></section>';
    }

    /* tabulka */
    if ($s['show_table'] && $units) {
        $h .= table_html($units, $floors, $floor_units, $unit_floors);
    }

    $h .= '<div class="martin-dv__tip" aria-hidden="true" hidden></div>';

    /* data pro JS */
    $json = [
        'start'    => 'view:' . $start,
        'units'    => (object) $units,
        'keys'     => (object) $keys,
        'views'    => (object) array_map(function ($fl) {
            return ['floors' => $fl];
        }, $view_floors),
        'floors'   => (object) array_map(function ($f) {
            return ['name' => $f['name']];
        }, $floors),
        'settings' => [
            'tip_area'    => $s['tip_area'],
            'tip_outdoor' => $s['tip_outdoor'],
            'tip_price'   => $s['tip_price'],
            'tip_status'  => $s['tip_status'],
            'button_text' => $s['button_text'],
        ],
        'i18n'     => [
            'clickArea'  => __('Klikněte pro zobrazení', 'martin-dev-vyber-bytu'),
            'clickUnit'  => __('Klikněte pro detail', 'martin-dev-vyber-bytu'),
            'area'       => __('Plocha', 'martin-dev-vyber-bytu'),
            'disp'       => __('Dispozice', 'martin-dev-vyber-bytu'),
            'floor'      => __('Podlaží', 'martin-dev-vyber-bytu'),
            'price'      => __('Cena', 'martin-dev-vyber-bytu'),
            'soldHint'   => __('Tato jednotka je prodaná.', 'martin-dev-vyber-bytu'),
            'tableAll'   => __('Přehled jednotek', 'martin-dev-vyber-bytu'),
            /* translators: %s: název podlaží */
            'tableFloor' => __('Jednotky – %s', 'martin-dev-vyber-bytu'),
        ],
    ];
    $h .= '<script type="application/json" class="martin-dv__data">' . wp_json_encode($json, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . '</script>';
    $h .= '<noscript><style>#' . esc_attr($uid) . ' [data-dv-panel][hidden]{display:block!important}</style></noscript>';
    $h .= '</div>';

    return $h;
}

function stage_html(array $item, string $areas, bool $eager): string
{
    $src    = $item['src'];
    $aspect = ($src['w'] && $src['h']) ? $src['w'] . ' / ' . $src['h']
        : (($item['image']['w'] && $item['image']['h']) ? $item['image']['w'] . ' / ' . $item['image']['h'] : '3 / 2');

    $img = wp_get_attachment_image($item['image']['id'], 'full', false, [
        'class'    => 'martin-dv__img',
        'alt'      => $item['name'],
        'loading'  => $eager ? 'eager' : 'lazy',
        'decoding' => 'async',
        'sizes'    => '(max-width: 900px) 100vw, 900px',
    ]);

    return '<div class="martin-dv__stage" style="aspect-ratio:' . esc_attr($aspect) . '">'
        . $img
        . '<svg class="martin-dv__svg" viewBox="0 0 100 100" preserveAspectRatio="none">' . $areas . '</svg>'
        . '</div>';
}

function table_html(array $units, array $floors, array $floor_units, array $unit_floors): string
{
    // Pořadí řádků: podle podlaží shora dolů, jednotka jen jednou (mezonet).
    $order = [];
    foreach ($floor_units as $ids) {
        foreach ($ids as $id) {
            $order[$id] = true;
        }
    }

    $types = $disps = $sts = [];
    foreach (array_keys($order) as $id) {
        $types[$units[$id]['type']] = $units[$id]['typeLabel'];
        if ($units[$id]['disp'] !== '') {
            $disps[$units[$id]['disp']] = $units[$id]['disp'];
        }
        $sts[$units[$id]['status']] = $units[$id]['statusLabel'];
    }
    ksort($disps);

    $select = function ($name, $all, $options) {
        if (count($options) < 2) {
            return '';
        }
        $o = '<option value="">' . esc_html($all) . '</option>';
        foreach ($options as $val => $label) {
            $o .= '<option value="' . esc_attr($val) . '">' . esc_html($label) . '</option>';
        }
        return '<select data-dv-filter="' . esc_attr($name) . '" aria-label="' . esc_attr($all) . '">' . $o . '</select>';
    };

    $h = '<div class="martin-dv__table">'
        . '<div class="martin-dv__table-head"><h3 class="martin-dv__table-title" data-dv-table-title>' . esc_html__('Přehled jednotek', 'martin-dev-vyber-bytu') . '</h3>'
        . '<div class="martin-dv__filters">'
        . $select('type', __('Všechny typy', 'martin-dev-vyber-bytu'), $types)
        . $select('disp', __('Všechny dispozice', 'martin-dev-vyber-bytu'), $disps)
        . $select('status', __('Všechny stavy', 'martin-dev-vyber-bytu'), $sts)
        . '</div></div>'
        . '<div class="martin-dv__table-wrap"><table><thead><tr>'
        . '<th>' . esc_html__('Jednotka', 'martin-dev-vyber-bytu') . '</th>'
        . '<th>' . esc_html__('Podlaží', 'martin-dev-vyber-bytu') . '</th>'
        . '<th>' . esc_html__('Dispozice', 'martin-dev-vyber-bytu') . '</th>'
        . '<th>' . esc_html__('Plocha', 'martin-dev-vyber-bytu') . '</th>'
        . '<th>' . esc_html__('Venkovní plocha', 'martin-dev-vyber-bytu') . '</th>'
        . '<th>' . esc_html__('Cena', 'martin-dev-vyber-bytu') . '</th>'
        . '<th>' . esc_html__('Stav', 'martin-dev-vyber-bytu') . '</th>'
        . '</tr></thead><tbody>';

    foreach (array_keys($order) as $id) {
        $u    = $units[$id];
        $name = $u['clickable'] ? '<a href="' . esc_url($u['url']) . '">' . esc_html($u['label']) . '</a>' : esc_html($u['label']);
        $h   .= '<tr data-dv-key="unit:' . (int) $id . '" data-type="' . esc_attr($u['type']) . '" data-disp="' . esc_attr($u['disp']) . '" data-status="' . esc_attr($u['status']) . '" data-floors="' . esc_attr(implode(' ', $unit_floors[$id] ?? [])) . '">'
            . '<td class="martin-dv__cell-name">' . $name . '</td>'
            . '<td>' . esc_html($u['floorTxt']) . '</td>'
            . '<td>' . esc_html($u['disp'] ?: '—') . '</td>'
            . '<td>' . esc_html($u['areaTxt'] ?: '—') . '</td>'
            . '<td>' . esc_html($u['outTxt'] ?: '—') . '</td>'
            . '<td>' . esc_html($u['priceTxt'] ?: '—') . '</td>'
            . '<td><span class="martin-dv__status" data-status="' . esc_attr($u['status']) . '">' . esc_html($u['statusLabel']) . '</span></td>'
            . '</tr>';
    }
    $h .= '<tr class="martin-dv__empty-row" hidden><td colspan="7">' . esc_html__('Filtru neodpovídá žádná jednotka.', 'martin-dev-vyber-bytu') . '</td></tr>';
    $h .= '</tbody></table></div></div>';

    return $h;
}
