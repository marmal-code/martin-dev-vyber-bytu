<?php
/**
 * Nastavení pluginu (od 0.18.0): Developerské projekty → Nastavení.
 *   - typy jednotek, které se nabízejí (např. jen Rodinný dům, nebo Kancelář),
 *   - vlastní texty místo výchozích (Jednotka → Byt / Dům / Kancelář …) – přes filtr gettext_{domain},
 *   - adresa (URL) detailu jednotek (výchozí /byty/).
 * Option `martin_dv_settings` (autoload): ['types' => [klíč, …], 'texts' => [původní => nový], 'slug' => '…'].
 * Ukládá se přes options.php (nonce + manage_options), sanitizace sanitize_plugin_settings().
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

const OPTION_SETTINGS = 'martin_dv_settings';

/** Všechny typy jednotek (i vypnuté – kvůli popiskům u uložených jednotek). */
function unit_types_all(): array
{
    return unit_types();
}

/** Typy nabízené v administraci (podle nastavení; prázdné = všechny). */
function enabled_unit_types(): array
{
    $all = unit_types_all();
    $on  = plugin_settings()['types'];
    return $on ? array_intersect_key($all, array_flip($on)) : $all;
}

function plugin_settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $raw   = get_option(OPTION_SETTINGS, []);
        $cache = [
            'types' => is_array($raw['types'] ?? null) ? $raw['types'] : [],
            'texts' => is_array($raw['texts'] ?? null) ? $raw['texts'] : [],
            'slug'  => is_string($raw['slug'] ?? null) ? $raw['slug'] : '',
        ];
    }
    return $cache;
}

/**
 * Texty, které jde v nastavení přepsat (původní český text = klíč překladu). Hlavně slova „jednotka“
 * a typy jednotek – aby plugin šel použít pro byty, domy i kanceláře.
 */
function overridable_texts(): array
{
    return apply_filters('martin_dv_overridable_texts', [
        __('Typy jednotek', 'martin-dev-vyber-bytu') => ['Byt', 'Rodinný dům', 'Dvojdům', 'Řadový dům', 'Ateliér', 'Kancelář', 'Nebytový prostor', 'Sklep', 'Garáž', 'Parkovací stání'],
        __('Administrace', 'martin-dev-vyber-bytu')  => ['Jednotky', 'Jednotka', 'Přidat jednotku', 'Upravit jednotku', 'Nová jednotka', 'Zobrazit jednotku', 'Hledat jednotky', 'Žádné jednotky'],
        __('Výběr a tabulka na webu', 'martin-dev-vyber-bytu') => [
            'Jednotka', 'Detail jednotky', 'Všechny jednotky', 'Přehled jednotek', 'Jednotky: %1$d · volné: %2$d',
            'Tato jednotka je prodaná.', 'Filtru neodpovídá žádná jednotka.', 'Projekt zatím nemá žádné jednotky.', 'Jen volné',
        ],
        __('Detail jednotky', 'martin-dev-vyber-bytu') => ['Místnost', 'Podobné jednotky', 'Předchozí', 'Další', 'Karta jednotky', 'Karta jednotky %s', 'Půdorys, plochy místností a poloha v domě', 'Stáhnout', 'Příslušenství', 'Venkovní plochy celkem'],
    ]);
}

function sanitize_plugin_settings($in): array
{
    $in  = is_array($in) ? $in : [];
    $out = ['types' => [], 'texts' => [], 'slug' => ''];

    $all = array_keys(unit_types_all());
    foreach ((array) ($in['types'] ?? []) as $t) {
        if (in_array($t, $all, true)) {
            $out['types'][] = $t;
        }
    }
    if (count($out['types']) === count($all)) {
        $out['types'] = []; // vše zapnuto = výchozí stav
    }

    $allowed = [];
    foreach (overridable_texts() as $list) {
        foreach ($list as $orig) {
            $allowed[$orig] = true;
        }
    }
    foreach ((array) ($in['texts'] ?? []) as $orig => $new) {
        $orig = (string) $orig;
        $new  = clean_text($new, 120);
        if (!isset($allowed[$orig]) || $new === '' || $new === $orig) {
            continue;
        }
        // Zástupné symboly (%1$d …) musí zůstat stejné, jinak by sprintf spadl.
        preg_match_all('/%(\d+\$)?[sd]/', $orig, $a);
        preg_match_all('/%(\d+\$)?[sd]/', $new, $b);
        sort($a[0]);
        sort($b[0]);
        if ($a[0] !== $b[0]) {
            add_settings_error(OPTION_SETTINGS, 'placeholders', sprintf(
                /* translators: %s: text */
                __('Text „%s“ nebyl uložen – musí obsahovat stejné zástupné symboly (%%1$d, %%2$d …) jako původní.', 'martin-dev-vyber-bytu'),
                $orig
            ));
            continue;
        }
        $out['texts'][$orig] = $new;
    }

    $slug = sanitize_title((string) ($in['slug'] ?? ''));
    $out['slug'] = ($slug === 'byty') ? '' : $slug;
    if ($out['slug'] !== plugin_settings()['slug']) {
        update_option('martin_dv_flush_rewrite', 1, false); // nová adresa → přegenerovat trvalé odkazy
    }
    return $out;
}

/* ---------- Použití ---------- */

/** Vlastní texty místo výchozích (jen texty tohoto pluginu, jen ze seznamu overridable_texts()). */
add_filter('gettext_martin-dev-vyber-bytu', function ($translation, $text) {
    $texts = plugin_settings()['texts'];
    return $texts ? ($texts[$text] ?? $translation) : $translation;
}, 10, 2);

add_filter('martin_dv_unit_slug', function ($slug) {
    $own = plugin_settings()['slug'];
    return $own !== '' ? $own : $slug;
}, 5);

add_action('init', function () {
    if (get_option('martin_dv_flush_rewrite')) {
        delete_option('martin_dv_flush_rewrite');
        flush_rewrite_rules(false);
    }
}, 99);

/* ---------- Stránka nastavení ---------- */

add_action('admin_init', function () {
    register_setting('martin_dv_settings_group', OPTION_SETTINGS, [
        'type'              => 'array',
        'sanitize_callback' => __NAMESPACE__ . '\\sanitize_plugin_settings',
        'default'           => [],
    ]);
});

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=' . PT_UNIT,
        __('Nastavení', 'martin-dev-vyber-bytu'),
        __('Nastavení', 'martin-dev-vyber-bytu'),
        'manage_options',
        'martin-dv-nastaveni',
        __NAMESPACE__ . '\\settings_page'
    );
});

function settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $s       = plugin_settings();
    $enabled = $s['types'] ?: array_keys(unit_types_all());
    echo '<div class="wrap"><h1>' . esc_html__('Developerské projekty – nastavení', 'martin-dev-vyber-bytu') . '</h1>';
    settings_errors(OPTION_SETTINGS);
    echo '<form method="post" action="options.php">';
    settings_fields('martin_dv_settings_group');

    echo '<h2>' . esc_html__('Typy jednotek', 'martin-dev-vyber-bytu') . '</h2><p class="description">'
        . esc_html__('Které typy se nabízejí u jednotky. Již uložené jednotky se nezmění.', 'martin-dev-vyber-bytu') . '</p><p>';
    foreach (unit_types_all() as $key => $label) {
        echo '<label style="display:inline-block;margin:0 18px 8px 0"><input type="checkbox" name="' . esc_attr(OPTION_SETTINGS) . '[types][]" value="' . esc_attr($key) . '"'
            . checked(in_array($key, $enabled, true), true, false) . '> ' . esc_html($label) . '</label>';
    }
    echo '</p>';

    echo '<h2>' . esc_html__('Adresa detailu jednotky', 'martin-dev-vyber-bytu') . '</h2><p><code>' . esc_html(home_url('/')) . '</code>'
        . '<input type="text" name="' . esc_attr(OPTION_SETTINGS) . '[slug]" value="' . esc_attr($s['slug']) . '" placeholder="byty" style="width:160px"><code>/…</code></p>'
        . '<p class="description">' . esc_html__('Např. domy, kancelare, apartmany. Po změně se staré odkazy přestanou otevírat – měň jen před spuštěním webu, nebo nastav přesměrování.', 'martin-dev-vyber-bytu') . '</p>';

    echo '<h2>' . esc_html__('Vlastní texty', 'martin-dev-vyber-bytu') . '</h2><p class="description">'
        . esc_html__('Prázdné = výchozí text. Např. „Jednotka“ → „Byt“, „Detail jednotky“ → „Detail domu“. Platí pro všechny jazyky webu (pro vícejazyčný web raději překlad přes Loco Translate).', 'martin-dev-vyber-bytu') . '</p>';
    foreach (overridable_texts() as $group => $list) {
        echo '<h3>' . esc_html($group) . '</h3><table class="form-table" role="presentation"><tbody>';
        foreach (array_unique($list) as $orig) {
            $id = 'mdv-t-' . md5($orig);
            echo '<tr><th scope="row"><label for="' . esc_attr($id) . '">' . esc_html($orig) . '</label></th><td>'
                . '<input type="text" class="regular-text" id="' . esc_attr($id) . '" name="' . esc_attr(OPTION_SETTINGS) . '[texts][' . esc_attr($orig) . ']" value="' . esc_attr($s['texts'][$orig] ?? '') . '">'
                . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    submit_button();
    echo '</form></div>';
}
