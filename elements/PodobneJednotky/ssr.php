<?php
/**
 * Server-side render elementu „Podobné jednotky“ (MartinDV\PodobneJednotky) – od 0.17.0.
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_similar')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['podobne'] ?? [];
$martin_dv_html = \MartinDV\render_similar(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'count'      => absint($martin_dv_c['pocet'] ?? 0) ?: 3,
    'cols'       => absint($martin_dv_c['sloupce'] ?? 0) ?: 3,
    'only_match' => !empty($martin_dv_c['jen_shoda']),
    'with_sold'  => !empty($martin_dv_c['s_prodanymi']),
    'hide_image' => !empty($martin_dv_c['bez_obrazku']),
    'hide_price' => !empty($martin_dv_c['bez_ceny']),
]);
// Funkce vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
