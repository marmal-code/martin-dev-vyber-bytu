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
    $atts = shortcode_atts(['projekt' => 0, 'jednotka' => 0, 'info' => 'ano', 'velikost' => 'ne', 'novy' => 'ano', 'jednotky' => 'ano', 'projektu' => 'ano', 'tlacitko' => ''], $atts, 'martin_dv_dokumenty');
    return render_documents(absint($atts['projekt']), [
        'unit'         => absint($atts['jednotka']),
        'hide_meta'    => $atts['info'] === 'ne',
        'show_size'    => $atts['velikost'] === 'ano',
        'button_text'  => sanitize_text_field($atts['tlacitko']),
        'same_tab'     => $atts['novy'] === 'ne',
        'hide_unit'    => $atts['jednotky'] === 'ne',
        'hide_project' => $atts['projektu'] === 'ne',
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
 * Dokumenty ke stažení: na detailu jednotky nejdřív dokumenty jednotky (karta jednotky + vlastní, od 0.16.0),
 * pak dokumenty projektu. Bez ID projektu: projekt jednotky, jinak výchozí projekt.
 *
 * @param array $args unit (ID jednotky, 0 = aktuální), hide_unit, hide_project, hide_meta (bez štítku s typem souboru),
 *                    show_size (velikost souboru za popisem), same_tab (bez nového okna), button_text (výchozí „Stáhnout“)
 */
function render_documents(int $project_id, array $args = []): string
{
    $docs = [];
    $unit = empty($args['hide_unit']) ? resolve_unit((int) ($args['unit'] ?? 0)) : 0;
    if ($unit) {
        $docs = unit_documents($unit);
    }
    if (empty($args['hide_project'])) {
        $project_id = $project_id ?: ($unit ? project_of_unit($unit) : current_project());
        if (project_visible($project_id)) {
            $docs = array_merge($docs, project_info($project_id)['dokumenty']);
        }
    }
    // Stejný soubor jen jednou (např. ceník u projektu i u jednotky).
    $seen = [];
    $docs = array_values(array_filter($docs, function ($d) use (&$seen) {
        if (isset($seen[$d['soubor']])) {
            return false;
        }
        return $seen[$d['soubor']] = true;
    }));
    if (!$docs) {
        return editor_notice(__('Dokumenty: zatím žádné (Jednotka → Fotky, video a dokumenty, nebo Projekt → Údaje projektu → Dokumenty).', 'martin-dev-vyber-bytu'));
    }
    // Od 0.18.0 každý dokument na celý řádek: vlevo typ souboru, název a popis, vpravo tlačítko Stáhnout.
    $btn_text = trim((string) ($args['button_text'] ?? '')) ?: __('Stáhnout', 'martin-dev-vyber-bytu');
    $icon     = '<svg class="martin-dv-docs__btn-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4v11m0 0-4.5-4.5M12 15l4.5-4.5M5 19h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $h        = ensure_unit_assets() . '<ul class="martin-dv-docs">';
    foreach ($docs as $d) {
        $url = wp_get_attachment_url($d['soubor']);
        if (!$url) {
            continue;
        }
        $path = (string) get_attached_file($d['soubor']);
        $ext  = strtoupper((string) pathinfo($path, PATHINFO_EXTENSION));
        $size = (!empty($args['show_size']) && $path !== '' && file_exists($path)) ? size_format((int) filesize($path), 1) : '';
        $name = $d['nazev'] !== '' ? $d['nazev'] : get_the_title($d['soubor']);
        $desc = trim((string) ($d['popis'] ?? ''));
        $sub  = implode(' · ', array_filter([$desc, $size]));
        $h   .= '<li class="martin-dv-docs__item">'
            . (empty($args['hide_meta']) && $ext !== '' ? '<span class="martin-dv-docs__badge" aria-hidden="true">' . esc_html(mb_substr($ext, 0, 4)) . '</span>' : '')
            . '<span class="martin-dv-docs__text"><span class="martin-dv-docs__name">' . esc_html($name) . '</span>'
            . ($sub !== '' ? '<span class="martin-dv-docs__desc">' . esc_html($sub) . '</span>' : '') . '</span>'
            . '<a class="martin-dv-docs__btn" href="' . esc_url($url) . '" download'
            . (empty($args['same_tab']) ? ' target="_blank" rel="noopener"' : '')
            /* translators: 1: text tlačítka, 2: název dokumentu */
            . ' aria-label="' . esc_attr(sprintf(__('%1$s: %2$s', 'martin-dev-vyber-bytu'), $btn_text, $name) . ($ext !== '' ? ' (' . $ext . ')' : '')) . '">'
            . $icon . '<span class="martin-dv-docs__btn-text">' . esc_html($btn_text) . '</span></a>'
            . '</li>';
    }
    return $h . '</ul>';
}
