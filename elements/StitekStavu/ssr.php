<?php
/**
 * Server-side render elementu „Štítek stavu“ (MartinDV\StitekStavu).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_status_badge')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['stitek'] ?? [];
$martin_dv_html = \MartinDV\render_status_badge(absint($martin_dv_c['jednotka_id'] ?? 0));
if (!empty($martin_dv_c['plny'])) {
    $martin_dv_html = str_replace('class="martin-dv-badge"', 'class="martin-dv-badge martin-dv-badge--solid"', $martin_dv_html);
}
// render_status_badge() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
