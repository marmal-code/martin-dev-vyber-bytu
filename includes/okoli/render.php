<?php
/**
 * Mapa okolí – výstup na frontendu: render_okoli() + shortcode [martin_mapa_okoli id="…" mapa="1"].
 * Breakdance element „Marmal – Mapa okolí“ (MartinDV\MapaOkoli) volá render_okoli() ze svého ssr.php.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

/* ---------- Assety ---------- */

add_action('init', function () {
    wp_register_style('martin-dv-okoli', MARTIN_DV_URL . 'assets/front/okoli.css', [], asset_ver('assets/front/okoli.css'));
    wp_register_script('martin-dv-okoli', MARTIN_DV_URL . 'assets/front/okoli.js', [], asset_ver('assets/front/okoli.js'), ['in_footer' => true, 'strategy' => 'defer']);
}, 20);

/** Když stránka mapu obsahuje, CSS jde do <head> (bez probliknutí). Jinak ho doplní ensure_okoli_assets(). */
add_action('wp_enqueue_scripts', function () {
    $load = (bool) apply_filters('martin_dv_load_everywhere', false);
    if (!$load && is_singular()) {
        $id      = get_queried_object_id();
        $bd      = (string) get_post_meta($id, '_breakdance_data', true);
        $content = (string) get_post_field('post_content', $id);
        foreach (apply_filters('martin_dv_okoli_detect_needles', ['MapaOkoli', 'martin_mapa_okoli']) as $needle) {
            if (stripos($bd, $needle) !== false || stripos($content, $needle) !== false) {
                $load = true;
                break;
            }
        }
    }
    if ($load) {
        wp_enqueue_style('martin-dv-okoli');
        wp_enqueue_script('martin-dv-okoli');
    }
});

function ensure_okoli_assets(): string
{
    wp_enqueue_script('martin-dv-okoli');
    if (wp_style_is('martin-dv-okoli', 'done')) {
        return '';
    }
    if (!did_action('wp_head') || doing_action('wp_head')) {
        wp_enqueue_style('martin-dv-okoli');
        return '';
    }
    // Hlavička už je venku (Breakdance, náhled v builderu) → link přímo u komponenty.
    wp_styles()->done[] = 'martin-dv-okoli';
    return '<link rel="stylesheet" id="martin-dv-okoli-css" href="' . esc_url(add_query_arg('ver', asset_ver('assets/front/okoli.css'), MARTIN_DV_URL . 'assets/front/okoli.css')) . '" media="all">';
}

/* ---------- Shortcode ---------- */

add_shortcode('martin_mapa_okoli', function ($atts) {
    $atts = shortcode_atts([
        'id'        => 0,
        'mapa'      => 1,
        'rozlozeni' => 'vpravo',
        'sloupce'   => 1,
        'ztlumit'   => 'ano',
    ], $atts, 'martin_mapa_okoli');

    $layouts = ['vpravo' => 'right', 'vlevo' => 'left', 'pod' => 'below'];
    return render_okoli(absint($atts['id']), [
        'map'    => absint($atts['mapa']),
        'layout' => $layouts[$atts['rozlozeni']] ?? 'right',
        'cols'   => absint($atts['sloupce']),
        'dim'    => $atts['ztlumit'] !== 'ne',
    ]);
});

/* ---------- Render ---------- */

function okoli_pad(int $n): string
{
    return str_pad((string) $n, 2, '0', STR_PAD_LEFT);
}

/** Obsah značky (stejný v mapě i v seznamu). SVG je z pevné sady ikon v kódu. */
function okoli_mark_inner(string $mode, int $n, string $icon): string
{
    if ($mode === 'ikony') {
        return okoli_icon_svg($icon);
    }
    if ($mode === 'kombi') {
        return okoli_icon_svg($icon) . '<span class="martin-dv-okoli__badge">' . esc_html(okoli_pad($n)) . '</span>';
    }
    return esc_html(okoli_pad($n));
}

/** Barva kategorie jako CSS proměnná: z elementu (--mdo-cat-<klíč>), jinak výchozí barva kategorie. */
function okoli_cat_var(string $cat): string
{
    $cats  = okoli_categories();
    $key   = sanitize_key($cat);
    $color = sanitize_css_color($cats[$cat]['color'] ?? '');
    return '--mdo-cc:var(--mdo-cat-' . $key . ($color ? ',' . $color : '') . ')';
}

/**
 * @param int   $project_id ID projektu (CPT martin_dv_projekt)
 * @param array $args       map (pořadí mapy v projektu, od 1), layout (right|left|below), cols (1|2), dim (bool – ztlumit ostatní body)
 */
function render_okoli(int $project_id, array $args = []): string
{
    if (!$project_id || get_post_type($project_id) !== PT_PROJECT) {
        return editor_notice(__('Mapa okolí: zadejte platné ID projektu (Developerské projekty → Projekty).', 'martin-dev-vyber-bytu'));
    }
    // Nepublikovaný projekt vidí jen ten, kdo ho smí upravovat (stejně jako výběr bytů).
    if (get_post_status($project_id) !== 'publish' && !current_user_can('edit_post', $project_id)) {
        return editor_notice(__('Mapa okolí: projekt není publikovaný.', 'martin-dev-vyber-bytu'));
    }

    $data = get_project_data($project_id);
    $n    = max(1, (int) ($args['map'] ?? 1));
    $map  = $data['maps'][$n - 1] ?? null;
    if (!$map) {
        /* translators: %d: pořadí mapy */
        return editor_notice(sprintf(__('Mapa okolí: projekt nemá mapu č. %d (projekt → Okolí).', 'martin-dev-vyber-bytu'), $n));
    }
    $src = image_src($map['image']);
    if (!$src) {
        return editor_notice(__('Mapa okolí: mapa nemá obrázek (projekt → Okolí → Vybrat obrázek mapy).', 'martin-dev-vyber-bytu'));
    }

    $layout = in_array($args['layout'] ?? '', ['right', 'left', 'below'], true) ? $args['layout'] : 'right';
    $cols   = ((int) ($args['cols'] ?? 1)) === 2 ? 2 : 1;
    $dim    = !array_key_exists('dim', $args) || !empty($args['dim']);
    $mode   = $map['mode'];
    $cats   = okoli_categories();
    $points = array_values(array_filter($map['points'], function ($p) {
        return $p['name'] !== '';
    }));

    $classes = [
        'martin-dv-okoli',
        'martin-dv-okoli--' . $layout,
        'martin-dv-okoli--' . $mode,
    ];
    if ($cols === 2) {
        $classes[] = 'martin-dv-okoli--cols2';
    }
    if ($dim) {
        $classes[] = 'is-dim';
    }
    if ($map['cat_colors']) {
        $classes[] = 'martin-dv-okoli--catcolors';
    }

    $uid = wp_unique_id('martin-dv-okoli-');
    $h   = ensure_okoli_assets();
    $h  .= '<div id="' . esc_attr($uid) . '" class="' . esc_attr(implode(' ', $classes)) . '" data-martin-dv-okoli>';

    /* filtr kategorií */
    $used = array_values(array_unique(array_column($points, 'cat')));
    if ($map['filter'] && count($used) > 1) {
        $h .= '<div class="martin-dv-okoli__filters" role="group" aria-label="' . esc_attr__('Filtr kategorií', 'martin-dev-vyber-bytu') . '">';
        $h .= '<button type="button" class="martin-dv-okoli__chip" data-cat="" aria-pressed="true">' . esc_html__('Vše', 'martin-dev-vyber-bytu') . '</button>';
        foreach ($cats as $key => $cat) {
            if (in_array($key, $used, true)) {
                $h .= '<button type="button" class="martin-dv-okoli__chip" data-cat="' . esc_attr($key) . '" aria-pressed="false" style="' . esc_attr(okoli_cat_var($key)) . '">'
                    . '<i aria-hidden="true"></i>' . esc_html($cat['name']) . '</button>';
            }
        }
        $h .= '</div>';
    }

    $h .= '<div class="martin-dv-okoli__layout">';

    /* mapa */
    $alt = $map['name'] !== '' ? $map['name'] : __('Mapa okolí', 'martin-dev-vyber-bytu');
    $img = wp_get_attachment_image($map['image']['id'], 'full', false, [
        'class'    => 'martin-dv-okoli__img',
        'alt'      => $alt,
        'loading'  => 'lazy',
        'decoding' => 'async',
        'sizes'    => '(max-width: 1200px) 100vw, 1200px',
    ]);
    $h .= '<div class="martin-dv-okoli__map" style="aspect-ratio:' . esc_attr($src['w'] . ' / ' . $src['h']) . '">' . $img;

    foreach ($points as $i => $p) {
        $num   = $i + 1;
        $style = 'left:' . $p['x'] . '%;top:' . $p['y'] . '%;' . okoli_cat_var($p['cat']);
        $h    .= '<button type="button" class="martin-dv-okoli__mk" data-mdo="' . esc_attr((string) $num) . '" data-cat="' . esc_attr($p['cat']) . '"'
            . ' data-name="' . esc_attr(okoli_pad($num) . '  ' . $p['name']) . '" data-sub="' . esc_attr($p['dist']) . '"'
            . ' aria-label="' . esc_attr(okoli_pad($num) . ' ' . $p['name']) . '" style="' . esc_attr($style) . '">'
            . okoli_mark_inner($mode, $num, $p['icon']) . '</button>';
    }

    if ($map['proj']) {
        $pname = $map['proj']['name'] !== '' ? $map['proj']['name'] : get_the_title($project_id);
        $h    .= '<button type="button" class="martin-dv-okoli__mk martin-dv-okoli__mk--proj" data-mdo="proj"'
            . ' data-name="' . esc_attr($pname) . '" data-sub="' . esc_attr($map['proj']['desc']) . '"'
            . ' aria-label="' . esc_attr(trim($pname . ' ' . $map['proj']['desc'])) . '"'
            . ' style="' . esc_attr('left:' . $map['proj']['x'] . '%;top:' . $map['proj']['y'] . '%') . '"></button>';
    }
    $h .= '<div class="martin-dv-okoli__tip" aria-hidden="true" hidden></div>';
    $h .= '</div>';

    /* seznam */
    $home = wp_parse_url(home_url(), PHP_URL_HOST);
    $h   .= '<div class="martin-dv-okoli__side"><ol class="martin-dv-okoli__list">';
    foreach ($points as $i => $p) {
        $num   = $i + 1;
        $inner = '<span class="martin-dv-okoli__num" style="' . esc_attr(okoli_cat_var($p['cat'])) . '">' . okoli_mark_inner($mode, $num, $p['icon']) . '</span>'
            . '<span class="martin-dv-okoli__txt"><span class="martin-dv-okoli__name">' . esc_html($p['name']) . '</span>'
            . ($p['desc'] !== '' ? '<span class="martin-dv-okoli__desc">' . esc_html($p['desc']) . '</span>' : '')
            . '</span>'
            . ($p['dist'] !== '' ? '<span class="martin-dv-okoli__dist">' . esc_html($p['dist']) . '</span>' : '');

        if ($p['url'] !== '') {
            $external = wp_parse_url($p['url'], PHP_URL_HOST) !== $home;
            $row      = '<a class="martin-dv-okoli__row" data-mdo="' . esc_attr((string) $num) . '" href="' . esc_url($p['url']) . '"'
                . ($external ? ' target="_blank" rel="noopener"' : '') . '>' . $inner . '</a>';
        } else {
            $row = '<button type="button" class="martin-dv-okoli__row" data-mdo="' . esc_attr((string) $num) . '">' . $inner . '</button>';
        }
        $h .= '<li class="martin-dv-okoli__li" data-cat="' . esc_attr($p['cat']) . '">' . $row . '</li>';
    }
    $h .= '</ol></div>';

    $h .= '</div></div>';
    return $h;
}
