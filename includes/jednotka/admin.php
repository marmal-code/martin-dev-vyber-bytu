<?php
/**
 * Editor u jednotky (od 0.14.0): místnosti, venkovní plochy, vlastní parametry.
 * Vlastní meta box (bez ACF Pro) – data jdou jako JSON přes skryté pole a ukládají se
 * po kontrole nonce a oprávnění edit_post. Každý řádek se sanitizuje (includes/jednotka/data.php).
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes_' . PT_UNIT, function () {
    add_meta_box(
        'martin-dv-unit-box',
        __('Místnosti, venkovní plochy a parametry', 'martin-dev-vyber-bytu'),
        __NAMESPACE__ . '\\unit_box',
        PT_UNIT,
        'normal',
        'default'
    );
});

function unit_box(\WP_Post $post): void
{
    wp_nonce_field('martin_dv_unit_save', 'martin_dv_unit_nonce');
    $data = [
        'rooms'    => unit_rooms_raw((int) $post->ID),
        'outdoors' => sanitize_outdoors(read_json_meta((int) $post->ID, META_OUTDOORS)),
        'params'   => unit_params((int) $post->ID),
    ];
    echo '<input type="hidden" id="martin-dv-unit-data" name="martin_dv_unit_data" value="' . esc_attr(wp_json_encode($data, JSON_UNESCAPED_UNICODE)) . '">';
    echo '<div id="martin-dv-unit-editor"><p>' . esc_html__('Načítám editor…', 'martin-dev-vyber-bytu') . '</p></div>';
}

/** Místnosti pro editor (i řádky bez plochy, aby se rozepsané neztratily). */
function unit_rooms_raw(int $post_id): array
{
    return sanitize_rooms(read_json_meta($post_id, META_ROOMS));
}

add_action('admin_enqueue_scripts', function ($hook) {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== PT_UNIT || !in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    wp_enqueue_style('martin-dv-unit-editor', MARTIN_DV_URL . 'assets/admin/jednotka-editor.css', [], VERSION);
    wp_enqueue_script('martin-dv-unit-editor', MARTIN_DV_URL . 'assets/admin/jednotka-editor.js', [], VERSION, true);

    $dirs = [];
    foreach (orientations() as $key => $o) {
        $dirs[] = ['key' => $key, 'short' => $o[0], 'long' => $o[1]];
    }
    $cfg = [
        'roomTypes'    => room_types(),
        'outdoorTypes' => outdoor_types(),
        'orientations' => $dirs,
        'suggestions'  => array_values(param_suggestions()),
        'areaLabel'    => area_label(current_admin_project()),
        'i18n'         => [
            'rooms'        => __('Místnosti', 'martin-dev-vyber-bytu'),
            'roomsHelp'    => __('Vnitřní místnosti jednotky. Balkony, terasy a zahrady patří do Venkovních ploch níže.', 'martin-dev-vyber-bytu'),
            'outdoors'     => __('Venkovní plochy', 'martin-dev-vyber-bytu'),
            'outdoorsHelp' => __('Když je seznam vyplněný, má přednost před jednoduchým polem „Venkovní plocha“ výše. Jedna venkovní plocha může zůstat jen ve starém poli.', 'martin-dev-vyber-bytu'),
            'params'       => __('Vlastní parametry', 'martin-dev-vyber-bytu'),
            'paramsHelp'   => __('Cokoli, co se liší projekt od projektu (výška stropů, energetická třída, vytápění…). Prázdné řádky se nezobrazí.', 'martin-dev-vyber-bytu'),
            'name'         => __('Název', 'martin-dev-vyber-bytu'),
            'area'         => __('Plocha (m²)', 'martin-dev-vyber-bytu'),
            'type'         => __('Typ', 'martin-dev-vyber-bytu'),
            'count'        => __('Započítat', 'martin-dev-vyber-bytu'),
            /* translators: %s: název plochy, např. Užitná plocha */
            'countTitle'   => __('Započítat do: %s', 'martin-dev-vyber-bytu'),
            'note'         => __('Poznámka', 'martin-dev-vyber-bytu'),
            'orientation'  => __('Orientace', 'martin-dev-vyber-bytu'),
            'value'        => __('Hodnota', 'martin-dev-vyber-bytu'),
            'addRoom'      => __('Přidat místnost', 'martin-dev-vyber-bytu'),
            'addOutdoor'   => __('Přidat venkovní plochu', 'martin-dev-vyber-bytu'),
            'addParam'     => __('Přidat parametr', 'martin-dev-vyber-bytu'),
            'up'           => __('Posunout nahoru', 'martin-dev-vyber-bytu'),
            'down'         => __('Posunout dolů', 'martin-dev-vyber-bytu'),
            'remove'       => __('Odebrat řádek', 'martin-dev-vyber-bytu'),
            'roomPh'       => __('např. Obývací pokoj s kuchyní', 'martin-dev-vyber-bytu'),
            'notePh'       => __('nepovinné', 'martin-dev-vyber-bytu'),
            'paramPh'      => __('např. Světlá výška stropů', 'martin-dev-vyber-bytu'),
            'valuePh'      => __('např. 2,7 m', 'martin-dev-vyber-bytu'),
            /* translators: %s: název plochy */
            'sumRooms'     => __('%s podle místností', 'martin-dev-vyber-bytu'),
            'sumOther'     => __('Nezapočítané místnosti', 'martin-dev-vyber-bytu'),
            'sumOutdoor'   => __('Venkovní plochy celkem', 'martin-dev-vyber-bytu'),
            /* translators: 1: součet místností, 2: zadaná plocha, 3: rozdíl */
            'mismatch'     => __('Součet místností (%1$s) se liší od zadané plochy (%2$s) o %3$s. Zkontroluj plochy, nebo zaškrtnutí „Započítat“.', 'martin-dev-vyber-bytu'),
            'match'        => __('Součet místností odpovídá zadané ploše.', 'martin-dev-vyber-bytu'),
            'empty'        => __('Zatím žádné řádky.', 'martin-dev-vyber-bytu'),
        ],
    ];
    wp_add_inline_script('martin-dv-unit-editor', 'window.martinDvUnitEditor = ' . wp_json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';', 'before');
});

add_action('save_post_' . PT_UNIT, function ($post_id) {
    if (!isset($_POST['martin_dv_unit_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['martin_dv_unit_nonce'])), 'martin_dv_unit_save')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['martin_dv_unit_data'])) {
        return;
    }
    $raw  = wp_unslash($_POST['martin_dv_unit_data']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- JSON se sanitizuje po řádcích níže
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        return;
    }
    save_unit_lists((int) $post_id, $data);
});

/** Uloží seznamy jednotky (sdílí editor i budoucí CSV import). Prázdný seznam = smazané meta pole. */
function save_unit_lists(int $post_id, array $data): void
{
    $lists = [
        META_ROOMS    => isset($data['rooms']) ? sanitize_rooms($data['rooms']) : null,
        META_OUTDOORS => isset($data['outdoors']) ? sanitize_outdoors($data['outdoors']) : null,
        META_PARAMS   => isset($data['params']) ? sanitize_params($data['params']) : null,
    ];
    foreach ($lists as $key => $rows) {
        if ($rows === null) {
            continue; // seznam nebyl poslán – neměnit
        }
        if ($rows) {
            update_post_meta($post_id, $key, wp_slash(wp_json_encode($rows, JSON_UNESCAPED_UNICODE)));
        } else {
            delete_post_meta($post_id, $key);
        }
    }
}
