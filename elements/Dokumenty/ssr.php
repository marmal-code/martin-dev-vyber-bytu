<?php
/**
 * Server-side render elementu „Dokumenty“ (MartinDV\Dokumenty).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_documents')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['dokumenty'] ?? [];
$martin_dv_html = \MartinDV\render_documents(absint($martin_dv_c['projekt_id'] ?? 0), [
    'hide_unit'    => !empty($martin_dv_c['skryt_jednotky']),
    'hide_project' => !empty($martin_dv_c['skryt_projektu']),
    'hide_meta'    => !empty($martin_dv_c['skryt_info']),
    'show_size'    => !empty($martin_dv_c['zobrazit_velikost']),
    'same_tab'     => !empty($martin_dv_c['stejne_okno']),
    'button_text'  => sanitize_text_field((string) ($martin_dv_c['text_tlacitka'] ?? '')),
    'layout'       => sanitize_key((string) ($martin_dv_c['rozlozeni'] ?? 'auto')),
]);
// render_documents() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
