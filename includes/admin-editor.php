<?php
/**
 * Editor projektu v administraci: pohledy, podlaží a kreslení oblastí.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes_' . PT_PROJECT, function () {
    add_meta_box('martin-dv-editor-box', __('Editor výběru', 'martin-dev-vyber-bytu'), __NAMESPACE__ . '\\editor_box', PT_PROJECT, 'normal', 'high');
    add_meta_box('martin-dv-embed-box', __('Vložení na web', 'martin-dev-vyber-bytu'), __NAMESPACE__ . '\\embed_box', PT_PROJECT, 'side', 'default');
});

function editor_box(\WP_Post $post): void
{
    wp_nonce_field('martin_dv_save', 'martin_dv_nonce');
    $json = wp_json_encode(get_project_data($post->ID), JSON_UNESCAPED_UNICODE);
    echo '<input type="hidden" id="martin-dv-data" name="martin_dv_data" value="' . esc_attr($json) . '">';
    echo '<div id="martin-dv-editor"><p>' . esc_html__('Načítám editor…', 'martin-dev-vyber-bytu') . '</p></div>';
}

function embed_box(\WP_Post $post): void
{
    $id = (int) $post->ID;
    echo '<p><strong>' . esc_html__('ID projektu:', 'martin-dev-vyber-bytu') . '</strong> <code>' . $id . '</code></p>';
    echo '<p>' . esc_html__('Breakdance: Add → element „Výběr bytů“ → Content → ID projektu.', 'martin-dev-vyber-bytu') . '</p>';
    echo '<p>' . esc_html__('Shortcode:', 'martin-dev-vyber-bytu') . '<br><code>[martin_vyber_bytu id="' . $id . '"]</code></p>';
    echo '<p><a href="' . esc_url(admin_url('post-new.php?post_type=' . PT_UNIT . '&martin_dv_projekt=' . $id)) . '">+ ' . esc_html__('Přidat jednotku do projektu', 'martin-dev-vyber-bytu') . '</a></p>';
}

/** Data pro editor: doplní URL obrázků. */
function editor_payload(int $post_id): array
{
    $data = get_project_data($post_id);
    foreach (['views', 'floors'] as $group) {
        foreach ($data[$group] as &$item) {
            $src           = image_src($item['image']);
            $item['image'] = $src ? ['id' => $item['image']['id'], 'url' => $src['url'], 'w' => $src['w'], 'h' => $src['h']] : null;
        }
        unset($item);
    }
    return $data;
}

function editor_units(int $project_id): array
{
    $posts = get_posts([
        'post_type'   => PT_UNIT,
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'numberposts' => -1,
        'meta_key'    => 'dv_projekt', // phpcs:ignore WordPress.DB.SlowDBQuery
        'meta_value'  => $project_id,  // phpcs:ignore WordPress.DB.SlowDBQuery
        'orderby'     => 'title',
        'order'       => 'ASC',
    ]);
    $types = unit_types();
    $sts   = statuses();
    $out   = [];
    foreach ($posts as $p) {
        $num    = trim((string) get_post_meta($p->ID, 'dv_cislo', true));
        $type   = $types[get_post_meta($p->ID, 'dv_typ', true)] ?? $types['byt'];
        $disp   = (string) get_post_meta($p->ID, 'dv_dispozice', true);
        $status = $sts[get_post_meta($p->ID, 'dv_stav', true)] ?? '';
        $short  = $num !== '' ? $num : get_the_title($p);
        $label  = ($num !== '' ? $type . ' ' . $num : get_the_title($p))
            . ($disp ? ' · ' . $disp : '')
            . ($status ? ' · ' . $status : '')
            . ($p->post_status !== 'publish' ? ' (' . __('nepublikováno', 'martin-dev-vyber-bytu') . ')' : '');
        $out[] = [
            'id'    => $p->ID,
            'short' => $short,
            'label' => $label,
            'edit'  => get_edit_post_link($p->ID, 'raw'),
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
    wp_enqueue_media();
    wp_enqueue_style('martin-dv-editor', MARTIN_DV_URL . 'assets/admin/editor.css', [], VERSION);
    wp_enqueue_script('martin-dv-editor', MARTIN_DV_URL . 'assets/admin/editor.js', [], VERSION, true);

    $cfg = [
        'data'       => editor_payload((int) $post->ID),
        'defaults'   => default_settings(),
        'units'      => editor_units((int) $post->ID),
        'newUnitUrl' => admin_url('post-new.php?post_type=' . PT_UNIT . '&martin_dv_projekt=' . (int) $post->ID),
        'projectId'  => (int) $post->ID,
        'i18n'       => [
            'views'        => __('Pohledy', 'martin-dev-vyber-bytu'),
            'floors'       => __('Podlaží', 'martin-dev-vyber-bytu'),
            'project'      => __('Projekt', 'martin-dev-vyber-bytu'),
            'settings'     => __('Nastavení zobrazení', 'martin-dev-vyber-bytu'),
            'addView'      => __('Přidat pohled', 'martin-dev-vyber-bytu'),
            'addFloor'     => __('Přidat podlaží', 'martin-dev-vyber-bytu'),
            'defaultView'  => __('Pohled na dům', 'martin-dev-vyber-bytu'),
            'newViewName'  => __('Nový pohled', 'martin-dev-vyber-bytu'),
            'newFloorName' => __('Nové podlaží', 'martin-dev-vyber-bytu'),
            'start'        => __('úvodní', 'martin-dev-vyber-bytu'),
            'view'         => __('Pohled', 'martin-dev-vyber-bytu'),
            'floor'        => __('Podlaží', 'martin-dev-vyber-bytu'),
            'chooseImage'  => __('Vybrat obrázek', 'martin-dev-vyber-bytu'),
            'changeImage'  => __('Změnit obrázek', 'martin-dev-vyber-bytu'),
            'useImage'     => __('Použít obrázek', 'martin-dev-vyber-bytu'),
            'noImage'      => __('Vyber obrázek z knihovny médií (fotka, vizualizace nebo půdorys).', 'martin-dev-vyber-bytu'),
            'modeSelect'   => __('Upravit body', 'martin-dev-vyber-bytu'),
            'modeDraw'     => __('Kreslit oblast', 'martin-dev-vyber-bytu'),
            'finish'       => __('Dokončit oblast', 'martin-dev-vyber-bytu'),
            'cancel'       => __('Zrušit', 'martin-dev-vyber-bytu'),
            'newArea'      => __('Nová oblast', 'martin-dev-vyber-bytu'),
            'hintDraw'     => __('Klikáním přidávej body (%d). Oblast uzavřeš kliknutím na zelený první bod nebo klávesou Enter. Backspace smaže poslední bod, Esc zruší.', 'martin-dev-vyber-bytu'),
            'hintSelect'   => __('Klikni na oblast v obrázku nebo ve výpisu pod ním. Novou oblast přidáš tlačítkem „Kreslit oblast“.', 'martin-dev-vyber-bytu'),
            'hintDrag'     => __('Táhni body pro úpravu tvaru.', 'martin-dev-vyber-bytu'),
            'target'       => __('Po kliknutí otevřít', 'martin-dev-vyber-bytu'),
            'chooseTarget' => __('— vyber podlaží, pohled nebo jednotku —', 'martin-dev-vyber-bytu'),
            'units'        => __('Jednotky (rodinné a řadové domy – klik vede rovnou na detail)', 'martin-dev-vyber-bytu'),
            'noTarget'     => __('bez cíle', 'martin-dev-vyber-bytu'),
            'unit'         => __('Jednotka', 'martin-dev-vyber-bytu'),
            'chooseUnit'   => __('— vyber jednotku —', 'martin-dev-vyber-bytu'),
            'noUnit'       => __('bez jednotky', 'martin-dev-vyber-bytu'),
            'noUnits'      => __('Projekt zatím nemá žádné jednotky. Přidej je přes odkaz níže, ulož projekt a obnov stránku.', 'martin-dev-vyber-bytu'),
            'editUnit'     => __('Upravit jednotku', 'martin-dev-vyber-bytu'),
            'newUnit'      => __('Nová jednotka v projektu', 'martin-dev-vyber-bytu'),
            'reloadNote'   => __('Nově přidanou jednotku uvidíš v seznamu po uložení projektu a obnovení stránky.', 'martin-dev-vyber-bytu'),
            'mezonet'      => __('Mezonet: obkresli jednotku v obou podlažích a vyber stejnou jednotku.', 'martin-dev-vyber-bytu'),
            'points'       => __('Bodů: %d', 'martin-dev-vyber-bytu'),
            'redraw'       => __('Překreslit', 'martin-dev-vyber-bytu'),
            'delArea'      => __('Smazat oblast', 'martin-dev-vyber-bytu'),
            'name'         => __('Název', 'martin-dev-vyber-bytu'),
            'isStart'      => __('Úvodní pohled (zobrazí se jako první)', 'martin-dev-vyber-bytu'),
            'delView'      => __('Smazat pohled', 'martin-dev-vyber-bytu'),
            'delFloor'     => __('Smazat podlaží', 'martin-dev-vyber-bytu'),
            'confirmArea'  => __('Smazat tuto oblast?', 'martin-dev-vyber-bytu'),
            'confirmObj'   => __('Smazat celé toto místo včetně všech oblastí?', 'martin-dev-vyber-bytu'),
            'lastView'     => __('Projekt musí mít aspoň jeden pohled.', 'martin-dev-vyber-bytu'),
            'up'           => __('Posunout nahoru', 'martin-dev-vyber-bytu'),
            'down'         => __('Posunout dolů', 'martin-dev-vyber-bytu'),
            'viewHelp'     => __('Pohled = fotka nebo vizualizace domu, celého areálu nebo jedné strany budovy. Oblasti v pohledu vedou na podlaží, na další pohled (např. areál → budova A), nebo přímo na jednotku (rodinný či řadový dům).', 'martin-dev-vyber-bytu'),
            'floorHelp'    => __('Podlaží = půdorys, ve kterém obkreslíš jednotky. Pořadí podlaží v levém seznamu (shora dolů) se použije i na webu.', 'martin-dev-vyber-bytu'),
            'setTable'     => __('Pod výběrem zobrazit tabulku jednotek s filtrem', 'martin-dev-vyber-bytu'),
            'setSold'      => __('Prodané jednotky jde otevřít (detail zůstává na webu)', 'martin-dev-vyber-bytu'),
            'setOutlines'  => __('Ukázat obrysy oblastí v pohledech i bez najetí myší', 'martin-dev-vyber-bytu'),
            'setTip'       => __('V bublině u jednotky zobrazit', 'martin-dev-vyber-bytu'),
            'tipArea'      => __('Plochu', 'martin-dev-vyber-bytu'),
            'tipOutdoor'   => __('Balkon / terasu / předzahrádku', 'martin-dev-vyber-bytu'),
            'tipPrice'     => __('Cenu', 'martin-dev-vyber-bytu'),
            'tipStatus'    => __('Stav', 'martin-dev-vyber-bytu'),
            'buttonText'   => __('Text tlačítka u jednotky', 'martin-dev-vyber-bytu'),
            'buttonPh'     => __('Detail jednotky', 'martin-dev-vyber-bytu'),
            'hintView'     => __('Text nápovědy nad seznamem podlaží', 'martin-dev-vyber-bytu'),
            'hintViewPh'   => __('Najeďte na část domu a kliknutím ji otevřete.', 'martin-dev-vyber-bytu'),
            'hintFloor'    => __('Text nápovědy v kartě podlaží / jednotky', 'martin-dev-vyber-bytu'),
            'hintFloorPh'  => __('Najeďte myší na jednotku. Na telefonu klepněte jednou pro údaje, podruhé pro otevření detailu.', 'martin-dev-vyber-bytu'),
            'hintHelp'     => __('Prázdné = výchozí text (šedě v poli). Písmo a barvu nastavíš v elementu → Design → Seznam vedle obrázku.', 'martin-dev-vyber-bytu'),
            'colors'       => __('Barvy', 'martin-dev-vyber-bytu'),
            'accent'       => __('Barva zvýraznění a volných jednotek', 'martin-dev-vyber-bytu'),
            'accentHelp'   => __('Prázdné = Brand barva z Breakdance Global Settings. Lze zadat i var(--…).', 'martin-dev-vyber-bytu'),
            'reserved'     => __('Rezervované', 'martin-dev-vyber-bytu'),
            'sold'         => __('Prodané', 'martin-dev-vyber-bytu'),
            'unnamed'      => __('bez názvu', 'martin-dev-vyber-bytu'),
        ],
    ];
    wp_add_inline_script('martin-dv-editor', 'window.martinDvEditor = ' . wp_json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';', 'before');
});

add_action('save_post_' . PT_PROJECT, function ($post_id) {
    if (!isset($_POST['martin_dv_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['martin_dv_nonce'])), 'martin_dv_save')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['martin_dv_data'])) {
        return;
    }
    $raw  = wp_unslash($_POST['martin_dv_data']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- JSON se sanitizuje níže
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        return;
    }
    $clean = sanitize_project_data($data);
    update_post_meta($post_id, META_DATA, wp_slash(wp_json_encode($clean, JSON_UNESCAPED_UNICODE)));
});
