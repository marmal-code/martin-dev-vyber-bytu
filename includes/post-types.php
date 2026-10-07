<?php
/**
 * CPT Jednotky (veřejné, vlastní URL) a CPT Projekty (jen administrace).
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

function register_post_types(): void
{
    register_post_type(PT_UNIT, [
        'labels' => [
            'name'               => __('Jednotky', 'martin-dev-vyber-bytu'),
            'singular_name'      => __('Jednotka', 'martin-dev-vyber-bytu'),
            'menu_name'          => __('Výběr bytů', 'martin-dev-vyber-bytu'),
            'all_items'          => __('Jednotky', 'martin-dev-vyber-bytu'),
            'add_new'            => __('Přidat jednotku', 'martin-dev-vyber-bytu'),
            'add_new_item'       => __('Přidat jednotku', 'martin-dev-vyber-bytu'),
            'edit_item'          => __('Upravit jednotku', 'martin-dev-vyber-bytu'),
            'new_item'           => __('Nová jednotka', 'martin-dev-vyber-bytu'),
            'view_item'          => __('Zobrazit jednotku', 'martin-dev-vyber-bytu'),
            'search_items'       => __('Hledat jednotky', 'martin-dev-vyber-bytu'),
            'not_found'          => __('Žádné jednotky', 'martin-dev-vyber-bytu'),
            'not_found_in_trash' => __('V koši nejsou žádné jednotky', 'martin-dev-vyber-bytu'),
        ],
        'public'        => true,
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-building',
        'menu_position' => 21,
        'supports'      => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
        'has_archive'   => false,
        'rewrite'       => [
            'slug'       => apply_filters('martin_dv_unit_slug', 'byty'),
            'with_front' => false,
        ],
    ]);

    register_post_type(PT_PROJECT, [
        'labels' => [
            'name'          => __('Projekty', 'martin-dev-vyber-bytu'),
            'singular_name' => __('Projekt', 'martin-dev-vyber-bytu'),
            'all_items'     => __('Projekty', 'martin-dev-vyber-bytu'),
            'add_new'       => __('Přidat projekt', 'martin-dev-vyber-bytu'),
            'add_new_item'  => __('Přidat projekt', 'martin-dev-vyber-bytu'),
            'edit_item'     => __('Upravit projekt', 'martin-dev-vyber-bytu'),
            'not_found'     => __('Žádné projekty', 'martin-dev-vyber-bytu'),
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'edit.php?post_type=' . PT_UNIT,
        'supports'     => ['title'],
        'show_in_rest' => false,
    ]);
}
add_action('init', __NAMESPACE__ . '\\register_post_types');

/* ---------- Sloupce v přehledu jednotek ---------- */

add_filter('manage_' . PT_UNIT . '_posts_columns', function ($cols) {
    $new = [];
    foreach ($cols as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['dv_typ']     = __('Typ', 'martin-dev-vyber-bytu');
            $new['dv_disp']    = __('Dispozice', 'martin-dev-vyber-bytu');
            $new['dv_stav']    = __('Stav', 'martin-dev-vyber-bytu');
            $new['dv_projekt'] = __('Projekt', 'martin-dev-vyber-bytu');
        }
    }
    return $new;
});

add_action('manage_' . PT_UNIT . '_posts_custom_column', function ($col, $post_id) {
    switch ($col) {
        case 'dv_typ':
            $t = unit_types();
            echo esc_html($t[get_post_meta($post_id, 'dv_typ', true)] ?? '—');
            break;
        case 'dv_disp':
            echo esc_html(get_post_meta($post_id, 'dv_dispozice', true) ?: '—');
            break;
        case 'dv_stav':
            $s = statuses();
            echo esc_html($s[get_post_meta($post_id, 'dv_stav', true)] ?? '—');
            break;
        case 'dv_projekt':
            $pid = (int) get_post_meta($post_id, 'dv_projekt', true);
            echo $pid ? esc_html(get_the_title($pid)) : '—';
            break;
    }
}, 10, 2);

/* ---------- Sloupce v přehledu projektů ---------- */

add_filter('manage_' . PT_PROJECT . '_posts_columns', function ($cols) {
    $cols['dv_id']        = __('ID projektu', 'martin-dev-vyber-bytu');
    $cols['dv_shortcode'] = __('Shortcode', 'martin-dev-vyber-bytu');
    return $cols;
});

add_action('manage_' . PT_PROJECT . '_posts_custom_column', function ($col, $post_id) {
    if ($col === 'dv_id') {
        echo (int) $post_id;
    }
    if ($col === 'dv_shortcode') {
        echo '<code>[martin_vyber_bytu id="' . (int) $post_id . '"]</code>';
    }
}, 10, 2);
