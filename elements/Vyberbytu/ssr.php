<?php
/**
 * Server-side render elementu „Výběr bytů“ (MartinDV\Vyberbytu).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * Content → projekt → projekt_id (number), skryt_tabulku (toggle)
 * Barvy a typografie z Design tabu řeší css.twig (přebíjí nastavení projektu).
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_project')) {
    echo '<p>Plugin „Marmal – Výběr bytů“ není aktivní.</p>';
    return;
}

echo \MartinDV\render_project(
    absint($propertiesData['content']['projekt']['projekt_id'] ?? 0),
    ['hide_table' => !empty($propertiesData['content']['projekt']['skryt_tabulku'])]
);
