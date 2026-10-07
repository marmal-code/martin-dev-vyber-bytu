<?php
/**
 * Saving location pro Element Studio (Breakdance 2.8).
 * Element „Výběr bytů“ se vytvoří v Element Studiu a uloží do složky elements/ tohoto pluginu.
 * Postup je v docs/breakdance-element.md.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

// Priorita MUSÍ být < 10, Breakdance načítá elementy na breakdance_loaded s prioritou 10.
add_action('breakdance_loaded', function () {
    if (!function_exists('\Breakdance\ElementStudio\registerSaveLocation')
        || !function_exists('\Breakdance\Util\getDirectoryPathRelativeToPluginFolder')) {
        return;
    }

    \Breakdance\ElementStudio\registerSaveLocation(
        \Breakdance\Util\getDirectoryPathRelativeToPluginFolder(dirname(MARTIN_DV_FILE)) . '/elements',
        'MartinDV',
        'element',
        'Marmal – Výběr bytů',
        false
    );
}, 9);
