<?php
/**
 * Server-side render elementu „Předchozí / další jednotka“ (MartinDV\PredchoziDalsi) – od 0.17.0.
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_prev_next')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['navigace'] ?? [];
$martin_dv_html = \MartinDV\render_prev_next(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'prev_text' => sanitize_text_field((string) ($martin_dv_c['text_predchozi'] ?? '')),
    'next_text' => sanitize_text_field((string) ($martin_dv_c['text_dalsi'] ?? '')),
    'hide_dir'  => !empty($martin_dv_c['skryt_texty']),
]);
// Funkce vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
