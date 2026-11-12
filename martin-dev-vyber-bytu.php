<?php
/**
 * Plugin Name:       Marmal – Developerské projekty
 * Plugin URI:        https://marmal.cz
 * Description:       Plugin pro weby developerských projektů: interaktivní výběr podlaží a jednotek (byty, domy, sklepy, garáže) na obrázku domu a půdorysech a interaktivní mapa okolí s body. CPT Jednotky s ACF poli, místnostmi, venkovními plochami a parametry, Breakdance elementy a shortcody.
 * Version:           0.16.0
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

const VERSION    = '0.16.0';
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
// Údaje jednotky (od 0.14.0): místnosti, venkovní plochy, parametry, poloha v domě.
require_once MARTIN_DV_DIR . 'includes/jednotka/data.php';
// Údaje projektu (od 0.15.0): stavy s barvou, developer, termíny, prodejce, texty, dokumenty.
require_once MARTIN_DV_DIR . 'includes/projekt/data.php';
require_once MARTIN_DV_DIR . 'includes/post-types.php';
require_once MARTIN_DV_DIR . 'includes/acf-fields.php';
require_once MARTIN_DV_DIR . 'includes/admin-editor.php';
require_once MARTIN_DV_DIR . 'includes/render.php';
require_once MARTIN_DV_DIR . 'includes/breakdance.php';
// Mapa okolí (od 0.13.0) – samostatná složka, aby šla případně přesunout do Breakdance Plus.
require_once MARTIN_DV_DIR . 'includes/okoli/data.php';
require_once MARTIN_DV_DIR . 'includes/okoli/admin.php';
require_once MARTIN_DV_DIR . 'includes/okoli/render.php';
require_once MARTIN_DV_DIR . 'includes/jednotka/admin.php';
require_once MARTIN_DV_DIR . 'includes/jednotka/render.php';
// Fotky, vizualizace, video a dokumenty jednotky (od 0.16.0).
require_once MARTIN_DV_DIR . 'includes/jednotka/media.php';
require_once MARTIN_DV_DIR . 'includes/projekt/admin.php';
require_once MARTIN_DV_DIR . 'includes/projekt/render.php';
require_once MARTIN_DV_DIR . 'includes/dynamic-data.php';

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
