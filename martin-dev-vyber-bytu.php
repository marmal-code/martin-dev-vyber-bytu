<?php
/**
 * Plugin Name:       Martin – Výběr bytů
 * Description:       Interaktivní výběr podlaží a jednotek (byty, sklepy, garáže) na obrázku domu a půdorysech. CPT Jednotky s ACF poli, Breakdance element a shortcode.
 * Version:           0.3.0
 * Author:            Martin
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

const VERSION    = '0.3.0';
const PT_UNIT    = 'martin_dv_jednotka';
const PT_PROJECT = 'martin_dv_projekt';
const META_DATA  = '_martin_dv_data';

define('MARTIN_DV_FILE', __FILE__);
define('MARTIN_DV_DIR', plugin_dir_path(__FILE__));
define('MARTIN_DV_URL', plugin_dir_url(__FILE__));

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
