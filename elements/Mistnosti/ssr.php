<?php
/**
 * Server-side render elementu „Tabulka místností“ (MartinDV\Mistnosti).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_rooms')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['mistnosti'] ?? [];
$martin_dv_html = \MartinDV\render_rooms(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'hide_outdoor' => !empty($martin_dv_c['skryt_venkovni']),
    'hide_notes'   => !empty($martin_dv_c['skryt_poznamky']),
    'hide_sum'     => !empty($martin_dv_c['skryt_soucet']),
]);
// render_rooms() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
