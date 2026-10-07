<?php
/**
 * Plugin Name:       Marmal – Výběr bytů
 * Plugin URI:        https://marmal.cz
 * Description:       Interaktivní výběr podlaží a jednotek (byty, sklepy, garáže) na obrázku domu a půdorysech. CPT Jednotky s ACF poli, Breakdance element a shortcode.
 * Version:           0.8.1
 * Author:            Martin Malý – marmal.cz
 * Author URI:        https://marmal.cz
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Text Domain:       martin-dev-vyber-bytu
 * Domain Path:       /languages
 * License:           GPLv2 or later
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

const VERSION    = '0.8.1';
const PT_UNIT    = 'martin_dv_jednotka';
const PT_PROJECT = 'martin_dv_projekt';
const META_DATA  = '_martin_dv_data';

define('MARTIN_DV_FILE', __FILE__);
define('MARTIN_DV_DIR', plugin_dir_path(__FILE__));
define('MARTIN_DV_URL', plugin_dir_url(__FILE__));

/*
 * Automatické aktualizace z GitHubu (Plugin Update Checker, stejně jako MarMal Effects).
 * Po zvýšení „Version“ a pushi do main vydá GitHub Action release s martin-dev-vyber-bytu.zip
 * (.github/workflows/release.yml). Weby se ptají dvakrát denně a aktualizace se ukáže v Pluginech.
 * Repozitář musí být veřejný, nebo musí mít web ve wp-config.php:
 *     define('MARTIN_DV_GITHUB_TOKEN', 'github_pat_...');   // jen Contents: Read-only
 */
if (!defined('MARTIN_DV_GITHUB_REPO')) {
    define('MARTIN_DV_GITHUB_REPO', 'https://github.com/marmal-code/martin-dev-vyber-bytu/');
}
require_once MARTIN_DV_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

add_action('plugins_loaded', function () {
    if (!class_exists('\YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
        return;
    }
    $checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        MARTIN_DV_GITHUB_REPO,
        MARTIN_DV_FILE,
        'martin-dev-vyber-bytu'
    );
    $checker->getVcsApi()->enableReleaseAssets('/martin-dev-vyber-bytu\.zip($|[?&#])/i');
    if (defined('MARTIN_DV_GITHUB_TOKEN') && MARTIN_DV_GITHUB_TOKEN) {
        $checker->setAuthentication(MARTIN_DV_GITHUB_TOKEN);
    }
});

require_once MARTIN_DV_DIR . 'includes/data.php';
require_once MARTIN_DV_DIR . 'includes/post-types.php';
require_once MARTIN_DV_DIR . 'includes/acf-fields.php';
require_once MARTIN_DV_DIR . 'includes/admin-editor.php';
require_once MARTIN_DV_DIR . 'includes/render.php';
require_once MARTIN_DV_DIR . 'includes/breakdance.php';

add_action('init', function () {
    load_plugin_textdomain('martin-dev-vyber-bytu', false, dirname(plugin_basename(MARTIN_DV_FILE)) . '/languages');
}, 1);

register_activation_hook(__FILE__, function () {
    register_post_types();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
