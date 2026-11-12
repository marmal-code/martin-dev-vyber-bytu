<?php
/**
 * Server-side render elementu „Příslušenství“ (MartinDV\Prislusenstvi) – od 0.17.0.
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_accessories')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['prislusenstvi'] ?? [];
$martin_dv_html = \MartinDV\render_accessories(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'hide_area'  => !empty($martin_dv_c['skryt_plochu']),
    'hide_price' => !empty($martin_dv_c['skryt_cenu']),
    'no_links'   => !empty($martin_dv_c['bez_odkazu']),
]);
// Funkce vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
