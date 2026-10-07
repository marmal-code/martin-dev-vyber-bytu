<?php
/**
 * ssr.php pro Breakdance element „Výběr bytů“ (Element Studio, Breakdance 2.8).
 * Zkopíruj obsah do ssr.php elementu (viz docs/breakdance-element.md).
 *
 * Cesty odpovídají ovládacím prvkům:
 *   content → sekce "projekt" → "projekt_id" (Number), "skryt_tabulku" (Toggle)
 *   design  → sekce "barvy"   → "akcent" (Color)
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_project')) {
    echo '<p>Plugin „Marmal – Výběr bytů“ není aktivní.</p>';
    return;
}

$martin_dv_args = [
    'hide_table' => !empty($propertiesData['content']['projekt']['skryt_tabulku']),
    'accent'     => (string) ($propertiesData['design']['barvy']['akcent'] ?? ''),
];

echo \MartinDV\render_project(
    absint($propertiesData['content']['projekt']['projekt_id'] ?? 0),
    $martin_dv_args
);
