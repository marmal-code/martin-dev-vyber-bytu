<?php
/**
 * Server-side render elementu „Výběr bytů“ (MartinDV\Vyberbytu).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * Content → projekt → projekt_id (number), skryt_tabulku (toggle)
 * Design  → barvy   → akcent (color)
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_project')) {
    echo '<p>Plugin „Martin – Výběr bytů“ není aktivní.</p>';
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
