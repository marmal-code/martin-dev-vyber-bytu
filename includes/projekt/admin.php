<?php
/**
 * Box „Údaje projektu“ u projektu (od 0.15.0): developer, dodavatel, lokalita, termín, předprodej,
 * kontakt prodejce s fotkou, texty a dokumenty. Data jako JSON v `_martin_dv_info`, ukládá se
 * po kontrole nonce a oprávnění edit_post; každá hodnota prochází sanitize_info().
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes_' . PT_PROJECT, function () {
    add_meta_box('martin-dv-info-box', __('Údaje projektu', 'martin-dev-vyber-bytu'), __NAMESPACE__ . '\\info_box', PT_PROJECT, 'normal', 'default');
});

function info_box(\WP_Post $post): void
{
    wp_nonce_field('martin_dv_info_save', 'martin_dv_info_nonce');
    $info = project_info((int) $post->ID);
    echo '<input type="hidden" id="martin-dv-info-data" name="martin_dv_info_data" value="' . esc_attr(wp_json_encode($info, JSON_UNESCAPED_UNICODE)) . '">';
    echo '<div id="martin-dv-info-editor"><p>' . esc_html__('Načítám…', 'martin-dev-vyber-bytu') . '</p></div>';
}

/** Náhledy příloh použitých v údajích (fotka prodejce, dokumenty) pro editor. */
function info_attachments(array $info): array
{
    $ids = array_filter(array_merge([(int) $info['prodejce']['foto']], array_column($info['dokumenty'], 'soubor')));
    $out = [];
    foreach (array_unique($ids) as $id) {
        $thumb    = wp_get_attachment_image_src($id, 'thumbnail', true);
        $out[$id] = [
            'title' => get_the_title($id),
            'file'  => wp_basename((string) get_attached_file($id)),
            'thumb' => $thumb ? $thumb[0] : '',
        ];
    }
    return $out;
}

add_action('admin_enqueue_scripts', function ($hook) {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== PT_PROJECT || !in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    global $post;
    $info = project_info($post instanceof \WP_Post ? (int) $post->ID : 0);
    wp_enqueue_media();
    wp_enqueue_style('martin-dv-unit-editor', MARTIN_DV_URL . 'assets/admin/jednotka-editor.css', [], asset_ver('assets/admin/jednotka-editor.css'));
    wp_enqueue_script('martin-dv-info-editor', MARTIN_DV_URL . 'assets/admin/projekt-info.js', ['media-editor'], asset_ver('assets/admin/projekt-info.js'), true);
    $cfg = [
        'files' => (object) info_attachments($info),
        'i18n'  => [
            'project'      => __('Projekt', 'martin-dev-vyber-bytu'),
            'projectHelp'  => __('Převezmou je všechny jednotky projektu – na webu přes dynamická data „Developerské projekty – Projekt“. Vše nepovinné.', 'martin-dev-vyber-bytu'),
            'developer'    => __('Developer', 'martin-dev-vyber-bytu'),
            'dodavatel'    => __('Generální dodavatel stavby', 'martin-dev-vyber-bytu'),
            'lokalita'     => __('Lokalita', 'martin-dev-vyber-bytu'),
            'lokalitaPh'   => __('např. Kamenné Žehrovice', 'martin-dev-vyber-bytu'),
            'termin'       => __('Termín dokončení', 'martin-dev-vyber-bytu'),
            'terminPh'     => __('např. Q4 2027', 'martin-dev-vyber-bytu'),
            'predOd'       => __('Předprodej od', 'martin-dev-vyber-bytu'),
            'predDo'       => __('Předprodej do', 'martin-dev-vyber-bytu'),
            'seller'       => __('Kontakt prodejce', 'martin-dev-vyber-bytu'),
            'sellerHelp'   => __('Pro globální blok s kontaktem (jméno, telefon, e-mail, fotka). Údaje se zobrazí veřejně na webu – jen se souhlasem prodejce.', 'martin-dev-vyber-bytu'),
            'jmeno'        => __('Jméno', 'martin-dev-vyber-bytu'),
            'firma'        => __('Firma', 'martin-dev-vyber-bytu'),
            'telefon'      => __('Telefon', 'martin-dev-vyber-bytu'),
            'email'        => __('E-mail', 'martin-dev-vyber-bytu'),
            'foto'         => __('Fotka', 'martin-dev-vyber-bytu'),
            'choosePhoto'  => __('Vybrat fotku', 'martin-dev-vyber-bytu'),
            'removePhoto'  => __('Odebrat', 'martin-dev-vyber-bytu'),
            'texts'        => __('Texty', 'martin-dev-vyber-bytu'),
            'cena'         => __('Text místo ceny', 'martin-dev-vyber-bytu'),
            'cenaPh'       => __('např. Ceny zveřejníme se zahájením předprodeje', 'martin-dev-vyber-bytu'),
            'cenaHelp'     => __('Zobrazí se u volné jednotky bez ceny (bublina, karta, tabulka, dynamická data „Cena“).', 'martin-dev-vyber-bytu'),
            'plochy'       => __('Upozornění k plochám', 'martin-dev-vyber-bytu'),
            'plochyPh'     => __('např. Plochy jsou orientační, mohou se lišit o ± 3 %.', 'martin-dev-vyber-bytu'),
            'docs'         => __('Dokumenty projektu', 'martin-dev-vyber-bytu'),
            'docsHelp'     => __('Brožura, standardy, platební podmínky… Na webu element „Marmal – Dokumenty“ nebo shortcode [martin_dv_dokumenty]. Soubory v knihovně médií jsou veřejně dostupné.', 'martin-dev-vyber-bytu'),
            'docName'      => __('Název', 'martin-dev-vyber-bytu'),
            'docNamePh'    => __('např. Standardy provedení', 'martin-dev-vyber-bytu'),
            'docDesc'      => __('Popis', 'martin-dev-vyber-bytu'),
            'docDescPh'    => __('např. Materiály, okna, podlahy a koupelny', 'martin-dev-vyber-bytu'),
            'docFile'      => __('Soubor', 'martin-dev-vyber-bytu'),
            'chooseFile'   => __('Vybrat soubor', 'martin-dev-vyber-bytu'),
            'changeFile'   => __('Změnit', 'martin-dev-vyber-bytu'),
            'addDoc'       => __('Přidat dokument', 'martin-dev-vyber-bytu'),
            'noFile'       => __('Bez souboru se dokument neuloží.', 'martin-dev-vyber-bytu'),
            'up'           => __('Posunout nahoru', 'martin-dev-vyber-bytu'),
            'down'         => __('Posunout dolů', 'martin-dev-vyber-bytu'),
            'remove'       => __('Odebrat řádek', 'martin-dev-vyber-bytu'),
            'empty'        => __('Zatím žádné dokumenty.', 'martin-dev-vyber-bytu'),
        ],
    ];
    wp_add_inline_script('martin-dv-info-editor', 'window.martinDvInfoEditor = ' . wp_json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';', 'before');
});

add_action('save_post_' . PT_PROJECT, function ($post_id) {
    if (!isset($_POST['martin_dv_info_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['martin_dv_info_nonce'])), 'martin_dv_info_save')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['martin_dv_info_data'])) {
        return;
    }
    $raw  = wp_unslash($_POST['martin_dv_info_data']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- JSON se sanitizuje v sanitize_info()
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        return;
    }
    update_post_meta($post_id, META_INFO, wp_slash(wp_json_encode(sanitize_info($data), JSON_UNESCAPED_UNICODE)));
});
