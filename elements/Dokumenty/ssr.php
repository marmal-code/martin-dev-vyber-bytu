<?php
/**
 * Server-side render elementu „Dokumenty“ (MartinDV\Dokumenty) – dokumenty jednotky (od 0.16.0) a projektu.
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
    'same_tab'     => !empty($martin_dv_c['stejne_okno']),
]);
// render_documents() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
