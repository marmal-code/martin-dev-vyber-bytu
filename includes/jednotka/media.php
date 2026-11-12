<?php
/**
 * Fotky, vizualizace, video a dokumenty jednotky (od 0.16.0).
 *
 * Hlavní fotka = náhledový obrázek jednotky (Featured Image) – žádné nové pole.
 * Ukládá se (bez ACF Pro):
 *   dv_galerie       [ID, …]  fotky jednotky (pole ID obrázků)
 *   dv_vizualizace   [ID, …]  vizualizace
 *   dv_galerie_vse   [ID, …]  fotky + vizualizace dohromady (počítá se při uložení)
 *   dv_video         URL      YouTube / Vimeo / soubor MP4
 *   _dv_dokumenty    JSON     [{nazev, soubor}]
 * Galerie mají názvy bez podtržítka, aby je Galerie Plus (Breakdance Plus) načetla jako „ACF pole“
 * (get_field vrátí uložené pole ID) – v elementu Galerie Plus: Zdroj = ACF pole, název dv_galerie.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

const META_UNIT_DOCS = '_dv_dokumenty';

/** Seznam ID obrázků z knihovny médií (bez duplicit, max. 80). */
function sanitize_image_ids($ids): array
{
    $out = [];
    foreach (array_slice(is_array($ids) ? $ids : [], 0, 80) as $id) {
        $id = absint($id);
        if ($id && !in_array($id, $out, true) && wp_attachment_is_image($id)) {
            $out[] = $id;
        }
    }
    return $out;
}

/** Odkaz na video: jen http(s), jinak prázdné. */
function sanitize_video_url($url): string
{
    $url = esc_url_raw(trim((string) $url), ['http', 'https']);
    return mb_substr($url, 0, 500, 'UTF-8');
}

function sanitize_unit_docs($rows): array
{
    $out = [];
    foreach (array_slice(is_array($rows) ? $rows : [], 0, 30) as $d) {
        if (!is_array($d)) {
            continue;
        }
        $file = clean_attachment($d['soubor'] ?? 0);
        if ($file) {
            $out[] = ['nazev' => clean_text($d['nazev'] ?? '', 120), 'soubor' => $file];
        }
    }
    return $out;
}

function unit_gallery(int $unit_id, string $which = 'dv_galerie'): array
{
    if (!in_array($which, ['dv_galerie', 'dv_vizualizace', 'dv_galerie_vse'], true)) {
        return [];
    }
    $ids = get_post_meta($unit_id, $which, true);
    return sanitize_image_ids(is_array($ids) ? $ids : []);
}

function unit_video(int $unit_id): string
{
    return sanitize_video_url(get_post_meta($unit_id, 'dv_video', true));
}

/**
 * Dokumenty jednotky pro výpis: nejdřív karta jednotky (ACF pole dv_karta), pak vlastní dokumenty.
 * Vrací [['nazev' => …, 'soubor' => ID přílohy | 0, 'url' => …], …].
 */
function unit_documents(int $unit_id): array
{
    $out   = [];
    $karta = get_post_meta($unit_id, 'dv_karta', true); // ACF file ukládá ID přílohy
    $kid   = clean_attachment($karta);
    if ($kid) {
        $out[] = ['nazev' => __('Karta jednotky', 'martin-dev-vyber-bytu'), 'soubor' => $kid];
    }
    foreach (sanitize_unit_docs(read_json_meta($unit_id, META_UNIT_DOCS)) as $d) {
        if ($d['soubor'] !== $kid) {
            $out[] = $d;
        }
    }
    return $out;
}

/** Uloží média a dokumenty jednotky (sdílí editor i budoucí CSV import). Neposlaný klíč = beze změny. */
function save_unit_media(int $post_id, array $data): void
{
    foreach (['galerie' => 'dv_galerie', 'vizualizace' => 'dv_vizualizace'] as $in => $key) {
        if (!array_key_exists($in, $data)) {
            continue;
        }
        $ids = sanitize_image_ids($data[$in]);
        if ($ids) {
            update_post_meta($post_id, $key, $ids);
        } else {
            delete_post_meta($post_id, $key);
        }
    }
    $all = array_values(array_unique(array_merge(unit_gallery($post_id, 'dv_galerie'), unit_gallery($post_id, 'dv_vizualizace'))));
    if ($all) {
        update_post_meta($post_id, 'dv_galerie_vse', $all);
    } else {
        delete_post_meta($post_id, 'dv_galerie_vse');
    }
    if (array_key_exists('video', $data)) {
        $video = sanitize_video_url($data['video']);
        if ($video !== '') {
            update_post_meta($post_id, 'dv_video', $video);
        } else {
            delete_post_meta($post_id, 'dv_video');
        }
    }
    if (array_key_exists('dokumenty', $data)) {
        $docs = sanitize_unit_docs($data['dokumenty']);
        if ($docs) {
            update_post_meta($post_id, META_UNIT_DOCS, wp_slash(wp_json_encode($docs, JSON_UNESCAPED_UNICODE)));
        } else {
            delete_post_meta($post_id, META_UNIT_DOCS);
        }
    }
}

/* ---------- Administrace: box „Fotky, video a dokumenty“ ---------- */

add_action('add_meta_boxes_' . PT_UNIT, function () {
    add_meta_box('martin-dv-media-box', __('Fotky, vizualizace, video a dokumenty', 'martin-dev-vyber-bytu'), __NAMESPACE__ . '\\media_box', PT_UNIT, 'normal', 'default');
});

function media_box(\WP_Post $post): void
{
    $id = (int) $post->ID;
    wp_nonce_field('martin_dv_media_save', 'martin_dv_media_nonce');
    $data = [
        'galerie'     => unit_gallery($id, 'dv_galerie'),
        'vizualizace' => unit_gallery($id, 'dv_vizualizace'),
        'video'       => unit_video($id),
        'dokumenty'   => sanitize_unit_docs(read_json_meta($id, META_UNIT_DOCS)),
    ];
    echo '<input type="hidden" id="martin-dv-media-data" name="martin_dv_media_data" value="' . esc_attr(wp_json_encode($data, JSON_UNESCAPED_UNICODE)) . '">';
    echo '<div id="martin-dv-media-editor"><p>' . esc_html__('Načítám…', 'martin-dev-vyber-bytu') . '</p></div>';
}

add_action('admin_enqueue_scripts', function ($hook) {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== PT_UNIT || !in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    global $post;
    $id    = $post instanceof \WP_Post ? (int) $post->ID : 0;
    $files = [];
    $ids   = array_merge(unit_gallery($id, 'dv_galerie'), unit_gallery($id, 'dv_vizualizace'), array_column(sanitize_unit_docs(read_json_meta($id, META_UNIT_DOCS)), 'soubor'));
    foreach (array_unique($ids) as $aid) {
        $thumb       = wp_get_attachment_image_src($aid, 'thumbnail', true);
        $files[$aid] = ['file' => wp_basename((string) get_attached_file($aid)), 'thumb' => $thumb ? $thumb[0] : ''];
    }
    wp_enqueue_media();
    wp_enqueue_script('martin-dv-media-editor', MARTIN_DV_URL . 'assets/admin/jednotka-media.js', ['media-editor'], VERSION, true);
    $cfg = [
        'files' => (object) $files,
        'i18n'  => [
            'mainPhoto'   => __('Hlavní fotka jednotky = Náhledový obrázek (vpravo v postranním panelu).', 'martin-dev-vyber-bytu'),
            'gallery'     => __('Fotky', 'martin-dev-vyber-bytu'),
            'galleryHelp' => __('Na webu přes Galerii Plus: Zdroj = ACF pole, název dv_galerie (jen vizualizace: dv_vizualizace, obojí: dv_galerie_vse).', 'martin-dev-vyber-bytu'),
            'viz'         => __('Vizualizace', 'martin-dev-vyber-bytu'),
            'addImages'   => __('Přidat obrázky', 'martin-dev-vyber-bytu'),
            'video'       => __('Video', 'martin-dev-vyber-bytu'),
            'videoPh'     => __('https://www.youtube.com/watch?v=… nebo Vimeo / odkaz na MP4', 'martin-dev-vyber-bytu'),
            'videoHelp'   => __('Na webu dynamická data „Developerské projekty → Jednotka → Video“ v elementu Video.', 'martin-dev-vyber-bytu'),
            'docs'        => __('Dokumenty jednotky', 'martin-dev-vyber-bytu'),
            'docsHelp'    => __('Karta jednotky (pole výše) se do seznamu přidá sama jako první. Na webu element „Marmal – Dokumenty“.', 'martin-dev-vyber-bytu'),
            'docNamePh'   => __('např. Technický list', 'martin-dev-vyber-bytu'),
            'chooseFile'  => __('Vybrat soubor', 'martin-dev-vyber-bytu'),
            'changeFile'  => __('Změnit', 'martin-dev-vyber-bytu'),
            'addDoc'      => __('Přidat dokument', 'martin-dev-vyber-bytu'),
            'left'        => __('Posunout doleva', 'martin-dev-vyber-bytu'),
            'right'       => __('Posunout doprava', 'martin-dev-vyber-bytu'),
            'up'          => __('Posunout nahoru', 'martin-dev-vyber-bytu'),
            'down'        => __('Posunout dolů', 'martin-dev-vyber-bytu'),
            'remove'      => __('Odebrat', 'martin-dev-vyber-bytu'),
            'empty'       => __('Zatím nic.', 'martin-dev-vyber-bytu'),
        ],
    ];
    wp_add_inline_script('martin-dv-media-editor', 'window.martinDvMediaEditor = ' . wp_json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';', 'before');
});

add_action('save_post_' . PT_UNIT, function ($post_id) {
    if (!isset($_POST['martin_dv_media_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['martin_dv_media_nonce'])), 'martin_dv_media_save')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['martin_dv_media_data'])) {
        return;
    }
    $raw  = wp_unslash($_POST['martin_dv_media_data']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- JSON se sanitizuje v save_unit_media()
    $data = json_decode((string) $raw, true);
    if (is_array($data)) {
        save_unit_media((int) $post_id, $data);
    }
});
