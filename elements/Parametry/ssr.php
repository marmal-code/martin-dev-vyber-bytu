<?php
/**
 * Server-side render elementu „Parametry jednotky“ (MartinDV\Parametry).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_params')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['parametry'] ?? [];
$martin_dv_html = \MartinDV\render_params(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'hide_basic'  => !empty($martin_dv_c['skryt_zakladni']),
    'hide_custom' => !empty($martin_dv_c['skryt_vlastni']),
    'show_price'  => !empty($martin_dv_c['zobrazit_cenu']),
    'cols'        => !empty($martin_dv_c['dva_sloupce']) ? 2 : 1,
]);
// render_params() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
