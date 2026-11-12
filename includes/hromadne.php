<?php
/**
 * Rychlá a hromadná úprava stavu a ceny v přehledu jednotek (od 0.18.0).
 *   Rychlé úpravy (Quick Edit): stav + cena jedné jednotky.
 *   Hromadné úpravy (Bulk Edit): stav, nová cena nebo změna ceny o % (se zaokrouhlením) u vybraných jednotek.
 * Vlastní nonce v obou formulářích + edit_post u každé jednotky; stav se nastaví jen tehdy,
 * když existuje v projektu dané jednotky.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

/** Stavy pro výběr: základní + vlastní stavy všech projektů (klíč => popisek). */
function all_status_choices(): array
{
    $out = [];
    foreach (statuses() as $k => $label) {
        $out[$k] = $label;
    }
    $projects = get_posts(['post_type' => PT_PROJECT, 'post_status' => ['publish', 'draft', 'private'], 'posts_per_page' => 100, 'fields' => 'ids', 'no_found_rows' => true]);
    foreach ($projects as $pid) {
        foreach (project_statuses((int) $pid) as $k => $st) {
            if (!isset($out[$k])) {
                $out[$k] = $st['label'] . ' (' . get_the_title($pid) . ')';
            }
        }
    }
    return $out;
}

/** Skrytá data pro rychlé úpravy – vypisují se do sloupce Stav. */
add_action('manage_' . PT_UNIT . '_posts_custom_column', function ($col, $post_id) {
    if ($col !== 'dv_stav') {
        return;
    }
    echo '<span class="martin-dv-qe" hidden data-stav="' . esc_attr(unit_status((int) $post_id)['key']) . '" data-cena="' . esc_attr((string) (int) get_post_meta($post_id, 'dv_cena', true)) . '"></span>';
}, 20, 2);

add_action('quick_edit_custom_box', function ($col, $post_type) {
    if ($post_type !== PT_UNIT || $col !== 'dv_stav') {
        return;
    }
    wp_nonce_field('martin_dv_qe', 'martin_dv_qe_nonce');
    echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col"><input type="hidden" name="martin_dv_qe_mode" value="quick">'
        . '<label><span class="title">' . esc_html__('Stav', 'martin-dev-vyber-bytu') . '</span><select name="martin_dv_stav">';
    foreach (all_status_choices() as $k => $label) {
        echo '<option value="' . esc_attr($k) . '">' . esc_html($label) . '</option>';
    }
    echo '</select></label><label><span class="title">' . esc_html__('Cena (Kč)', 'martin-dev-vyber-bytu') . '</span>'
        . '<input type="text" inputmode="numeric" name="martin_dv_cena" value=""></label></div></fieldset>';
}, 10, 2);

add_action('bulk_edit_custom_box', function ($col, $post_type) {
    if ($post_type !== PT_UNIT || $col !== 'dv_stav') {
        return;
    }
    wp_nonce_field('martin_dv_qe', 'martin_dv_qe_nonce');
    echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col"><input type="hidden" name="martin_dv_qe_mode" value="bulk">'
        . '<label><span class="title">' . esc_html__('Stav', 'martin-dev-vyber-bytu') . '</span><select name="martin_dv_stav"><option value="">' . esc_html__('— beze změny —', 'martin-dev-vyber-bytu') . '</option>';
    foreach (all_status_choices() as $k => $label) {
        echo '<option value="' . esc_attr($k) . '">' . esc_html($label) . '</option>';
    }
    echo '</select></label>'
        . '<label><span class="title">' . esc_html__('Nová cena', 'martin-dev-vyber-bytu') . '</span><input type="text" inputmode="numeric" name="martin_dv_cena" value="" placeholder="' . esc_attr__('beze změny', 'martin-dev-vyber-bytu') . '"></label>'
        . '<label><span class="title">' . esc_html__('Cena ± %', 'martin-dev-vyber-bytu') . '</span><input type="text" inputmode="decimal" name="martin_dv_procento" value="" placeholder="' . esc_attr__('např. 3 nebo -5', 'martin-dev-vyber-bytu') . '" style="width:110px"> '
        . '<select name="martin_dv_zaokrouhlit"><option value="1">' . esc_html__('na celé Kč', 'martin-dev-vyber-bytu') . '</option><option value="1000" selected>' . esc_html__('na tisíce', 'martin-dev-vyber-bytu') . '</option><option value="10000">' . esc_html__('na desetitisíce', 'martin-dev-vyber-bytu') . '</option></select></label>'
        . '<p class="description" style="margin:4px 0 0">' . esc_html__('Změna o % se týká jen jednotek, které cenu mají.', 'martin-dev-vyber-bytu') . '</p>'
        . '</div></fieldset>';
}, 10, 2);

/** Nová cena po změně o procenta (zaokrouhlená). */
function price_change(int $price, float $percent, int $round): int
{
    if ($price <= 0) {
        return 0;
    }
    $round = in_array($round, [1, 1000, 10000], true) ? $round : 1;
    $new   = $price * (1 + $percent / 100);
    return (int) max(0, round($new / $round) * $round);
}

add_action('save_post_' . PT_UNIT, function ($post_id) {
    if (!isset($_REQUEST['martin_dv_qe_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['martin_dv_qe_nonce'])), 'martin_dv_qe')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $mode  = sanitize_key(wp_unslash($_REQUEST['martin_dv_qe_mode'] ?? ''));
    $stav  = sanitize_key(wp_unslash($_REQUEST['martin_dv_stav'] ?? ''));
    $cena  = trim(sanitize_text_field(wp_unslash($_REQUEST['martin_dv_cena'] ?? '')));
    $proc  = trim(str_replace(',', '.', sanitize_text_field(wp_unslash($_REQUEST['martin_dv_procento'] ?? ''))));
    $round = absint($_REQUEST['martin_dv_zaokrouhlit'] ?? 1);

    if ($stav !== '' && isset(project_statuses(project_of_unit((int) $post_id))[$stav])) {
        update_post_meta($post_id, 'dv_stav', $stav);
    }
    if ($mode === 'quick') {
        $p = clean_price($cena);
        $p ? update_post_meta($post_id, 'dv_cena', $p) : delete_post_meta($post_id, 'dv_cena');
        return;
    }
    if ($mode === 'bulk') {
        if ($cena !== '') {
            $p = clean_price($cena);
            $p ? update_post_meta($post_id, 'dv_cena', $p) : delete_post_meta($post_id, 'dv_cena');
        } elseif ($proc !== '' && is_numeric($proc) && abs((float) $proc) <= 90) {
            $old = (int) get_post_meta($post_id, 'dv_cena', true);
            if ($old > 0) {
                update_post_meta($post_id, 'dv_cena', price_change($old, (float) $proc, $round));
            }
        }
    }
}, 20);

/** Doplnění hodnot do Rychlých úprav (JS bez závislostí kromě inline-edit-post). */
add_action('admin_enqueue_scripts', function ($hook) {
    $screen = get_current_screen();
    if ($hook !== 'edit.php' || !$screen || $screen->post_type !== PT_UNIT) {
        return;
    }
    wp_add_inline_script('inline-edit-post', <<<'JS'
(function () {
  if (!window.inlineEditPost) return;
  var orig = inlineEditPost.edit;
  inlineEditPost.edit = function (id) {
    orig.apply(this, arguments);
    var postId = typeof id === 'object' ? parseInt(this.getId(id), 10) : parseInt(id, 10);
    if (!postId) return;
    var data = document.querySelector('#post-' + postId + ' .martin-dv-qe');
    var row = document.getElementById('edit-' + postId);
    if (!data || !row) return;
    var sel = row.querySelector('select[name="martin_dv_stav"]');
    var price = row.querySelector('input[name="martin_dv_cena"]');
    if (sel) sel.value = data.getAttribute('data-stav') || 'volny';
    if (price) price.value = data.getAttribute('data-cena') !== '0' ? data.getAttribute('data-cena') : '';
  };
})();
JS
    );
});
