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
    $atts = shortcode_atts(['id' => 0, 'tabulka' => '', 'nazev' => '', 'popis' => ''], $atts, 'martin_vyber_bytu');
    $args = [
        'hide_table' => $atts['tabulka'] === 'ne',
        'hide_title' => $atts['nazev'] === 'ne',
        'hide_desc'  => $atts['popis'] === 'ne',
    ];
    return render_project(absint($atts['id']), $args);
});

/**
 * [martin_dv_prislusenstvi] – seznam příslušenství k dokoupení na detailu jednotky.
 * Bez atributu bere aktuální příspěvek (Breakdance šablona detailu), jinak id="123".
 * Když jednotka nic nemá, nevypíše nic.
 */
add_shortcode('martin_dv_prislusenstvi', function ($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'martin_dv_prislusenstvi');
    $id   = absint($atts['id']) ?: get_the_ID();
    if (!$id || get_post_type($id) !== PT_UNIT) {
        return '';
    }
    $list = unit_accessory_list($id);
    if (!$list) {
        return '';
    }
    $h = '<ul class="martin-dv-acc">';
    foreach ($list as $item) {
        $h .= '<li><span class="martin-dv-acc__label">' . esc_html($item['label']) . '</span> '
            . '<span class="martin-dv-acc__price">' . esc_html($item['price']) . '</span></li>';
    }
    return $h . '</ul>';
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
    // Jednotky obkreslené přímo v pohledu (rodinné a řadové domy na fotce areálu).
    $view_units = [];   // view id => [unit ids]
    foreach ($views as $vid => $v) {
        $view_units[$vid] = [];
        foreach ($v['areas'] as $a) {
            if (!$a['target'] || $a['target']['type'] !== 'unit') {
                continue;
            }
            $tid = translate_id((int) $a['target']['id']);
            if (!isset($units[$tid])) {
                $info = unit_info((int) $a['target']['id'], $s);
                if (!$info) {
                    continue;
                }
                $units[$tid] = $info;
            }
            if (!in_array($tid, $view_units[$vid], true)) {
                $view_units[$vid][] = $tid;
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
        $ids               = $view_units[$vid];
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

    /* úvod projektu: název + popis (obsah editoru projektu) */
    $intro = '';
    if (empty($args['hide_title'])) {
        $intro .= '<h2 class="martin-dv__project-title">' . esc_html(get_the_title($project_id)) . '</h2>';
    }
    $desc = trim((string) get_post_field('post_content', $project_id));
    if ($desc !== '' && empty($args['hide_desc'])) {
        $intro .= '<div class="martin-dv__project-desc">' . wpautop(wp_kses_post($desc)) . '</div>';
    }
    if ($intro !== '') {
        $h .= '<div class="martin-dv__intro">' . $intro . '</div>';
    }

    /* legenda – v bočním panelu pod seznamem / kartou */
    $legend = '';
    if ($units) {
        $legend = '<div class="martin-dv__legend">';
        foreach (statuses() as $key => $label) {
            $legend .= '<span data-status="' . esc_attr($key) . '"><i></i>' . esc_html($label) . '</span>';
        }
        $legend .= '</div>';
    }

    /* texty nápověd (nastavení projektu → Nastavení zobrazení) */
    $hint_view = $s['hint_view'] !== '' ? $s['hint_view'] : __('Najeďte na část domu a kliknutím ji otevřete.', 'martin-dev-vyber-bytu');
    $hint_unit = $s['hint_floor'] !== '' ? $s['hint_floor'] : __('Najeďte myší na jednotku. Na telefonu klepněte jednou pro údaje, podruhé pro otevření detailu.', 'martin-dev-vyber-bytu');

    $back = '<button type="button" class="martin-dv__back" data-dv-back hidden>← ' . esc_html__('Zpět', 'martin-dev-vyber-bytu') . '</button>';

    /* pohledy */
    foreach ($views as $vid => $v) {
        $areas = '';
        $list  = '';
        $seen  = [];
        foreach ($v['areas'] as $a) {
            $t = $a['target'];
            if ($t && $t['type'] === 'unit') {
                $tid = translate_id((int) $t['id']);
                if (isset($units[$tid])) {
                    $areas .= unit_area_html($units[$tid], $tid, $a['poly']);
                }
                continue;
            }
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
            . '<div class="martin-dv__bar martin-dv__bar--view" hidden>' . $back . '</div>'
            . '<div class="martin-dv__layout">'
            . stage_html($v, $areas, $vid === $start)
            . '<div class="martin-dv__side">'
            . ($list ? '<p class="martin-dv__hint">' . esc_html($hint_view) . '</p><div class="martin-dv__targets">' . $list . '</div>' : '')
            . ($view_units[$vid] ? '<div class="martin-dv__card" data-dv-card>' . summary_card_html(__('Projekt', 'martin-dev-vyber-bytu'), $v['name'], $keys['view:' . $vid]['lines'], $hint_unit) . '</div>' : '')
            . $legend
            . '</div>'
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
            $areas .= unit_area_html($units[$tid], $tid, $a['poly']);
        }
        $summary = summary_card_html(__('Podlaží', 'martin-dev-vyber-bytu'), $f['name'], $keys['floor:' . $fid]['lines'], $hint_unit);

        $h .= '<section class="martin-dv__panel" data-dv-panel="floor:' . esc_attr($fid) . '" id="' . esc_attr($uid . '-floor-' . $fid) . '" hidden>'
            . '<div class="martin-dv__bar">' . $back . '<h3 class="martin-dv__title">' . esc_html($f['name']) . '</h3><div class="martin-dv__pills" data-dv-pills></div></div>'
            . '<div class="martin-dv__layout">'
            . stage_html($f, $areas, false)
            . '<div class="martin-dv__side"><div class="martin-dv__card" data-dv-card>' . $summary . '</div>' . $legend . '</div>'
            . '</div></section>';
    }

    /* tabulka */
    if ($s['show_table'] && $units) {
        $h .= table_html($units, $floors, $floor_units, $unit_floors, $view_units);
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
            'acc'        => __('Příslušenství k dokoupení', 'martin-dev-vyber-bytu'),
            'soldHint'   => __('Tato jednotka je prodaná.', 'martin-dev-vyber-bytu'),
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

/** Plocha polygonu jednotky (odkaz na detail, nebo neaktivní u prodané). */
function unit_area_html(array $u, int $id, array $poly): string
{
    $key  = 'unit:' . $id;
    $aria = trim($u['label'] . ', ' . implode(', ', array_filter([$u['disp'], $u['areaTxt'], $u['statusLabel']])), ', ');
    $pg   = '<polygon points="' . esc_attr(poly_points($poly)) . '"></polygon>';
    if ($u['clickable']) {
        return '<a class="martin-dv__area martin-dv__area--unit" href="' . esc_url($u['url']) . '" data-dv-key="' . esc_attr($key) . '" data-status="' . esc_attr($u['status']) . '" aria-label="' . esc_attr($aria) . '">' . $pg . '</a>';
    }
    return '<g class="martin-dv__area martin-dv__area--unit is-disabled" data-dv-key="' . esc_attr($key) . '" data-status="' . esc_attr($u['status']) . '" role="img" aria-label="' . esc_attr($aria) . '">' . $pg . '</g>';
}

function summary_card_html(string $kicker, string $title, array $lines, string $hint): string
{
    $h = '<p class="martin-dv__kicker">' . esc_html($kicker) . '</p><h4 class="martin-dv__card-title">' . esc_html($title) . '</h4>';
    foreach ($lines as $line) {
        $h .= '<p>' . esc_html($line) . '</p>';
    }
    return $h . '<p class="martin-dv__hint">' . esc_html($hint) . '</p>';
}

/** Společný název pro „všechny“: byty / domy / jednotky podle toho, co projekt obsahuje. */
function collection_labels(array $types): array
{
    $houses = ['rodinny_dum', 'dvojdum', 'radovy_dum'];
    if ($types && !array_diff($types, ['byt'])) {
        return [__('Všechny byty', 'martin-dev-vyber-bytu'), __('Přehled bytů', 'martin-dev-vyber-bytu')];
    }
    if ($types && !array_diff($types, $houses)) {
        return [__('Všechny domy', 'martin-dev-vyber-bytu'), __('Přehled domů', 'martin-dev-vyber-bytu')];
    }
    return [__('Všechny jednotky', 'martin-dev-vyber-bytu'), __('Přehled jednotek', 'martin-dev-vyber-bytu')];
}

function table_html(array $units, array $floors, array $floor_units, array $unit_floors, array $view_units = []): string
{
    // Pořadí řádků: podle podlaží shora dolů, pak jednotky z pohledů; každá jednotka jen jednou (mezonet).
    $order = [];
    foreach ($floor_units as $ids) {
        foreach ($ids as $id) {
            $order[$id] = true;
        }
    }
    foreach ($view_units as $ids) {
        foreach ($ids as $id) {
            $order[$id] = true;
        }
    }
    $ids = array_keys($order);

    $types = $disps = $floor_opts = [];
    $has_sold = $show_floor = false;
    foreach ($ids as $id) {
        $u                    = $units[$id];
        $types[$u['type']]    = $u['typeLabel'];
        if ($u['disp'] !== '') {
            $disps[$u['disp']] = $u['disp'];
        }
        if ($u['status'] !== 'volny') {
            $has_sold = true;
        }
        if ($u['floorTxt'] !== '') {
            $show_floor = true;
        }
    }
    ksort($disps, SORT_NATURAL);
    foreach ($floors as $fid => $f) {
        if (!empty($floor_units[$fid])) {
            $floor_opts[$fid] = trim(explode(' –', $f['name'])[0]);
        }
    }
    [$all_label, $title] = collection_labels(array_keys($types));

    $pill = function (string $group, string $value, string $label, bool $active = false) {
        return '<button type="button" class="martin-dv__fpill' . ($active ? ' is-active' : '') . '" data-dv-fgroup="' . esc_attr($group) . '" data-dv-fvalue="' . esc_attr($value) . '" aria-pressed="' . ($active ? 'true' : 'false') . '">' . esc_html($label) . '</button>';
    };
    $pills = $pill('all', '', $all_label, true);
    if (count($types) > 1) {
        foreach ($types as $val => $label) {
            $pills .= $pill('type', $val, $label);
        }
    }
    if (count($disps) > 1) {
        foreach ($disps as $val => $label) {
            $pills .= $pill('disp', $val, $label);
        }
    }
    if (count($floor_opts) > 1) {
        foreach ($floor_opts as $val => $label) {
            $pills .= $pill('floor', $val, $label);
        }
    }
    if ($has_sold) {
        $pills .= $pill('status', 'volny', __('Jen volné', 'martin-dev-vyber-bytu'));
    }

    $cols = 5 + ($show_floor ? 1 : 0);
    $h    = '<div class="martin-dv__table">'
        . '<h3 class="martin-dv__table-title">' . esc_html($title) . '</h3>'
        . '<div class="martin-dv__filters" role="group" aria-label="' . esc_attr__('Filtr', 'martin-dev-vyber-bytu') . '">' . $pills . '</div>'
        . '<div class="martin-dv__table-wrap"><table><thead><tr>'
        . '<th>' . esc_html__('Jednotka', 'martin-dev-vyber-bytu') . '</th>'
        . ($show_floor ? '<th>' . esc_html__('Podlaží', 'martin-dev-vyber-bytu') . '</th>' : '')
        . '<th>' . esc_html__('Dispozice', 'martin-dev-vyber-bytu') . '</th>'
        . '<th>' . esc_html__('Plocha', 'martin-dev-vyber-bytu') . '</th>'
        . '<th>' . esc_html__('Cena', 'martin-dev-vyber-bytu') . '</th>'
        . '<th>' . esc_html__('Stav', 'martin-dev-vyber-bytu') . '</th>'
        . '</tr></thead><tbody>';

    foreach ($ids as $id) {
        $u    = $units[$id];
        $name = $u['clickable'] ? '<a href="' . esc_url($u['url']) . '">' . esc_html($u['label']) . '</a>' : esc_html($u['label']);
        $h   .= '<tr data-dv-key="unit:' . (int) $id . '" data-type="' . esc_attr($u['type']) . '" data-disp="' . esc_attr($u['disp']) . '" data-status="' . esc_attr($u['status']) . '" data-floors="' . esc_attr(implode(' ', $unit_floors[$id] ?? [])) . '">'
            . '<td class="martin-dv__cell-name">' . $name . '</td>'
            . ($show_floor ? '<td>' . esc_html($u['floorTxt'] ?: '—') . '</td>' : '')
            . '<td>' . esc_html($u['disp'] ?: '—') . '</td>'
            . '<td>' . esc_html($u['areaTxt'] ?: '—') . '</td>'
            . '<td>' . esc_html($u['priceTxt'] ?: '—') . '</td>'
            . '<td><span class="martin-dv__status" data-status="' . esc_attr($u['status']) . '">' . esc_html($u['statusLabel']) . '</span></td>'
            . '</tr>';
    }
    $h .= '<tr class="martin-dv__empty-row" hidden><td colspan="' . $cols . '">' . esc_html__('Filtru neodpovídá žádná jednotka.', 'martin-dev-vyber-bytu') . '</td></tr>';
    $h .= '</tbody></table></div></div>';

    return $h;
}
