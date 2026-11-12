<?php
/**
 * Výstup údajů projektu (od 0.15.0):
 *   render_status_badge() – štítek stavu jednotky v barvě stavu   [martin_dv_stav]
 *   render_documents()    – seznam dokumentů projektu ke stažení  [martin_dv_dokumenty]
 * Breakdance elementy (elements/StitekStavu, Dokumenty) volají tyto funkce ze ssr.php.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('martin_dv_stav', function ($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'martin_dv_stav');
    return render_status_badge(absint($atts['id']));
});

add_shortcode('martin_dv_dokumenty', function ($atts) {
    $atts = shortcode_atts(['projekt' => 0, 'info' => 'ano', 'novy' => 'ano'], $atts, 'martin_dv_dokumenty');
    return render_documents(absint($atts['projekt']), [
        'hide_meta'  => $atts['info'] === 'ne',
        'same_tab'   => $atts['novy'] === 'ne',
    ]);
});

/** Štítek stavu jednotky – barva podle stavu v projektu (prázdná barva Volného = Brand). */
function render_status_badge(int $id): string
{
    $unit = resolve_unit($id);
    if (!$unit) {
        return unit_missing_notice(__('Štítek stavu', 'martin-dev-vyber-bytu'));
    }
    $st    = unit_status($unit);
    $color = sanitize_css_color($st['color']);
    $style = '--c:' . ($color !== '' ? $color : 'var(--bde-brand-primary-color, #2f6b4f)');
    return ensure_unit_assets()
        . '<span class="martin-dv-badge" data-status="' . esc_attr($st['kind']) . '" data-status-key="' . esc_attr($st['key']) . '" style="' . esc_attr($style) . '">'
        . esc_html($st['label']) . '</span>';
}

/**
 * Dokumenty projektu ke stažení. Bez ID projektu: projekt aktuální jednotky, jinak výchozí projekt.
 *
 * @param array $args hide_meta (bez typu a velikosti), same_tab (otevřít ve stejném okně)
 */
function render_documents(int $project_id, array $args = []): string
{
    $project_id = $project_id ?: current_project();
    if (!project_visible($project_id)) {
        return editor_notice(__('Dokumenty: žádný publikovaný projekt.', 'martin-dev-vyber-bytu'));
    }
    $docs = project_info($project_id)['dokumenty'];
    if (!$docs) {
        return editor_notice(__('Dokumenty: projekt zatím nemá dokumenty (Projekt → Údaje projektu → Dokumenty).', 'martin-dev-vyber-bytu'));
    }
    $h = ensure_unit_assets() . '<ul class="martin-dv-docs">';
    foreach ($docs as $d) {
        $url = wp_get_attachment_url($d['soubor']);
        if (!$url) {
            continue;
        }
        $path = (string) get_attached_file($d['soubor']);
        $ext  = strtoupper((string) pathinfo($path, PATHINFO_EXTENSION));
        $size = ($path !== '' && file_exists($path)) ? size_format((int) filesize($path), 1) : '';
        $name = $d['nazev'] !== '' ? $d['nazev'] : get_the_title($d['soubor']);
        $meta = empty($args['hide_meta']) ? trim($ext . ($ext && $size ? ', ' : '') . $size) : '';
        $h   .= '<li class="martin-dv-docs__item"><a class="martin-dv-docs__link" href="' . esc_url($url) . '"'
            . (empty($args['same_tab']) ? ' target="_blank" rel="noopener"' : '') . '>'
            . '<svg class="martin-dv-docs__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M14 3v5h5M9 13h6M9 17h6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>'
            . '<span class="martin-dv-docs__name">' . esc_html($name) . '</span>'
            . ($meta !== '' ? '<span class="martin-dv-docs__meta">' . esc_html($meta) . '</span>' : '')
            . '</a></li>';
    }
    return $h . '</ul>';
}
