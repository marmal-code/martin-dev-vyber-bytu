<?php
/**
 * Server-side render elementu „Schéma podlaží“ (MartinDV\SchemaPodlazi).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_schema')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['schema'] ?? [];
$martin_dv_html = \MartinDV\render_schema(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'hide_caption' => !empty($martin_dv_c['skryt_nazev']),
    'hide_others'  => !empty($martin_dv_c['skryt_ostatni']),
]);
// render_schema() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr), obrázek přes wp_get_attachment_image().
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
