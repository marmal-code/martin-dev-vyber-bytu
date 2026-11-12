<?php
/**
 * ACF pole pro jednotky – registrovaná v kódu, takže jsou verzovaná v Gitu
 * a na všech webech stejná. Funguje s ACF i ACF Pro.
 * V Breakdance je najdeš v dynamických datech pod ACF.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_notices', function () {
    if (class_exists('ACF') || !current_user_can('activate_plugins')) {
        return;
    }
    echo '<div class="notice notice-warning"><p>'
        . esc_html__('Plugin Marmal – Developerské projekty potřebuje Advanced Custom Fields (ACF nebo ACF Pro) pro úpravu údajů jednotek.', 'martin-dev-vyber-bytu')
        . '</p></div>';
});

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'      => 'group_martin_dv_jednotka',
        'title'    => __('Údaje jednotky', 'martin-dev-vyber-bytu'),
        'position' => 'acf_after_title',
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => PT_UNIT]]],
        'fields'   => [
            [
                'key' => 'field_martin_dv_projekt', 'name' => 'dv_projekt', 'label' => __('Projekt', 'martin-dev-vyber-bytu'),
                'type' => 'post_object', 'post_type' => [PT_PROJECT], 'return_format' => 'id', 'required' => 1, 'wrapper' => ['width' => '34'],
            ],
            [
                'key' => 'field_martin_dv_typ', 'name' => 'dv_typ', 'label' => __('Typ', 'martin-dev-vyber-bytu'),
                'type' => 'select', 'choices' => unit_types(), 'default_value' => 'byt', 'return_format' => 'label', 'wrapper' => ['width' => '33'],
            ],
            [
                'key' => 'field_martin_dv_cislo', 'name' => 'dv_cislo', 'label' => __('Číslo jednotky', 'martin-dev-vyber-bytu'),
                'type' => 'text', 'instructions' => __('Např. 201, A2.04, G12', 'martin-dev-vyber-bytu'), 'wrapper' => ['width' => '33'],
            ],
            [
                'key' => 'field_martin_dv_stav', 'name' => 'dv_stav', 'label' => __('Stav', 'martin-dev-vyber-bytu'),
                'type' => 'select', 'choices' => statuses(), 'default_value' => 'volny', 'return_format' => 'label', 'wrapper' => ['width' => '34'],
            ],
            [
                'key' => 'field_martin_dv_dispozice', 'name' => 'dv_dispozice', 'label' => __('Dispozice', 'martin-dev-vyber-bytu'),
                'type' => 'select', 'choices' => dispositions(), 'allow_null' => 1, 'return_format' => 'value', 'wrapper' => ['width' => '33'],
            ],
            [
                'key' => 'field_martin_dv_podlazi', 'name' => 'dv_podlazi', 'label' => __('Podlaží (text)', 'martin-dev-vyber-bytu'),
                'type' => 'text', 'instructions' => __('Např. „2. NP“ nebo u mezonetu „3.–4. NP“. U domů nech prázdné. Prázdné = doplní se podle výkresu.', 'martin-dev-vyber-bytu'),
                'wrapper' => ['width' => '33'],
            ],
            [
                'key' => 'field_martin_dv_plocha', 'name' => 'dv_plocha', 'label' => __('Podlahová plocha (m²)', 'martin-dev-vyber-bytu'), // popisek přepíše nastavení projektu (area_label)
                'type' => 'number', 'step' => 0.1, 'min' => 0, 'wrapper' => ['width' => '34'],
            ],
            [
                'key' => 'field_martin_dv_venkovni_typ', 'name' => 'dv_venkovni_typ', 'label' => __('Venkovní plocha', 'martin-dev-vyber-bytu'),
                'type' => 'select', 'choices' => outdoor_types(), 'allow_null' => 1, 'return_format' => 'label', 'wrapper' => ['width' => '33'],
            ],
            [
                'key' => 'field_martin_dv_venkovni_plocha', 'name' => 'dv_venkovni_plocha', 'label' => __('Venkovní plocha / pozemek (m²)', 'martin-dev-vyber-bytu'),
                'type' => 'number', 'step' => 0.1, 'min' => 0, 'wrapper' => ['width' => '33'],
            ],
            [
                'key' => 'field_martin_dv_cena', 'name' => 'dv_cena', 'label' => __('Cena (Kč)', 'martin-dev-vyber-bytu'),
                'type' => 'number', 'step' => 1, 'min' => 0, 'wrapper' => ['width' => '34'],
            ],
            [
                'key' => 'field_martin_dv_cena_na_dotaz', 'name' => 'dv_cena_na_dotaz', 'label' => __('Cena na dotaz', 'martin-dev-vyber-bytu'),
                'type' => 'true_false', 'ui' => 1, 'wrapper' => ['width' => '33'],
            ],
            // Od 0.17.0: platnost ceny (na webu) a rezervace do (jen interně – sloupec v přehledu jednotek).
            [
                'key' => 'field_martin_dv_cena_od', 'name' => 'dv_cena_od', 'label' => __('Cena platná od', 'martin-dev-vyber-bytu'),
                'type' => 'date_picker', 'display_format' => 'j. n. Y', 'return_format' => 'j. n. Y', 'first_day' => 1, 'wrapper' => ['width' => '50'],
            ],
            [
                'key' => 'field_martin_dv_rezervace_do', 'name' => 'dv_rezervace_do', 'label' => __('Rezervace do (interní)', 'martin-dev-vyber-bytu'),
                'type' => 'date_picker', 'display_format' => 'j. n. Y', 'return_format' => 'j. n. Y', 'first_day' => 1, 'wrapper' => ['width' => '50'],
                'instructions' => __('Jen pro tebe – na webu se nezobrazí. V přehledu jednotek se po termínu zvýrazní.', 'martin-dev-vyber-bytu'),
            ],
            ...position_fields(),
            ...accessory_fields(),
            [
                'key' => 'field_martin_dv_pudorys', 'name' => 'dv_pudorys', 'label' => __('Půdorys jednotky', 'martin-dev-vyber-bytu'),
                'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'wrapper' => ['width' => '50'],
            ],
            [
                'key' => 'field_martin_dv_karta', 'name' => 'dv_karta', 'label' => __('Karta jednotky (PDF)', 'martin-dev-vyber-bytu'),
                'type' => 'file', 'return_format' => 'url', 'mime_types' => 'pdf', 'wrapper' => ['width' => '50'],
            ],
        ],
    ]);
});

/** Předvyplnění projektu u nové jednotky, když přijdeš z editoru projektu. */
add_filter('acf/load_value/name=dv_projekt', function ($value) {
    if (empty($value) && is_admin() && isset($_GET['martin_dv_projekt'])) { // phpcs:ignore WordPress.Security.NonceVerification
        return absint($_GET['martin_dv_projekt']); // phpcs:ignore WordPress.Security.NonceVerification
    }
    return $value;
});

/**
 * Příslušenství k dokoupení: u každé pevné položky zaškrtávátko + cena vedle,
 * pod tím vlastní položky (název + cena). Zobrazí se jen položka se zaškrtnutím i cenou.
 * Vlastní položky: s ACF Pro opakovač (libovolný počet), s ACF free 3 pevné řádky.
 */
function accessory_fields(): array
{
    $fields = [[
        'key'     => 'field_martin_dv_acc_nadpis',
        'name'    => '',
        'label'   => __('Volitelné příslušenství k dokoupení', 'martin-dev-vyber-bytu'),
        'type'    => 'message',
        'message' => __('Starší způsob: zaškrtni, co si kupující může připlatit, a doplň cenu. Nově raději box „Místnosti, venkovní plochy a parametry“ → Příslušenství (označení, plocha, vazba na sklep / garáž) – když je tam vyplněný, má přednost a tato pole se nepoužijí.', 'martin-dev-vyber-bytu'),
    ]];

    foreach (unit_accessories() as $key => $label) {
        $fields[] = [
            'key'     => 'field_martin_dv_acc_' . $key,
            'name'    => 'dv_acc_' . $key,
            'label'   => $label,
            'type'    => 'true_false',
            'ui'      => 1,
            'wrapper' => ['width' => '30'],
        ];
        $fields[] = [
            'key'               => 'field_martin_dv_acc_' . $key . '_cena',
            'name'              => 'dv_acc_' . $key . '_cena',
            'label'             => sprintf(/* translators: %s: název příslušenství */ __('%s – cena', 'martin-dev-vyber-bytu'), $label),
            'type'              => 'text',
            'placeholder'       => __('např. 350 000 Kč', 'martin-dev-vyber-bytu'),
            'wrapper'           => ['width' => '70'],
            'conditional_logic' => [[['field' => 'field_martin_dv_acc_' . $key, 'operator' => '==', 'value' => '1']]],
        ];
    }

    $has_repeater = class_exists('acf_field_repeater') || (function_exists('acf_get_field_type') && acf_get_field_type('repeater'));

    if ($has_repeater) {
        $fields[] = [
            'key'          => 'field_martin_dv_acc_vlastni',
            'name'         => 'dv_acc_vlastni',
            'label'        => __('Další příslušenství', 'martin-dev-vyber-bytu'),
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => __('Přidat příslušenství', 'martin-dev-vyber-bytu'),
            'sub_fields'   => [
                [
                    'key' => 'field_martin_dv_acc_vlastni_nazev', 'name' => 'nazev', 'label' => __('Název', 'martin-dev-vyber-bytu'),
                    'type' => 'text', 'placeholder' => __('např. Nabíječka pro elektroauto', 'martin-dev-vyber-bytu'), 'wrapper' => ['width' => '50'],
                ],
                [
                    'key' => 'field_martin_dv_acc_vlastni_cena', 'name' => 'cena', 'label' => __('Cena / popis', 'martin-dev-vyber-bytu'),
                    'type' => 'text', 'placeholder' => __('např. 35 000 Kč', 'martin-dev-vyber-bytu'), 'wrapper' => ['width' => '50'],
                ],
            ],
        ];
    } else {
        for ($i = 1; $i <= 3; $i++) {
            $fields[] = [
                'key' => 'field_martin_dv_acc_slot' . $i . '_nazev', 'name' => 'dv_acc_slot' . $i . '_nazev',
                /* translators: %d: pořadí */
                'label' => sprintf(__('Další příslušenství %d – název', 'martin-dev-vyber-bytu'), $i),
                'type' => 'text', 'placeholder' => __('např. Nabíječka pro elektroauto', 'martin-dev-vyber-bytu'), 'wrapper' => ['width' => '30'],
            ];
            $fields[] = [
                'key' => 'field_martin_dv_acc_slot' . $i . '_cena', 'name' => 'dv_acc_slot' . $i . '_cena',
                'label' => __('Cena / popis', 'martin-dev-vyber-bytu'),
                'type' => 'text', 'placeholder' => __('např. 35 000 Kč', 'martin-dev-vyber-bytu'), 'wrapper' => ['width' => '70'],
            ];
        }
    }

    return $fields;
}

/**
 * Poloha v domě (od 0.14.0). Jednoduchá pole v ACF free – v Breakdance jsou i v ACF dynamických datech.
 * Budova / vchod se vybírá ze seznamu v nastavení projektu (pole se skryje, když projekt žádné budovy nemá).
 */
function position_fields(): array
{
    $dirs = [];
    foreach (orientations() as $key => $o) {
        $dirs[$key] = $o[0];
    }
    return [
        [
            'key'     => 'field_martin_dv_poloha_nadpis',
            'name'    => '',
            'label'   => __('Poloha v domě', 'martin-dev-vyber-bytu'),
            'type'    => 'message',
            'message' => __('Vše nepovinné – na webu se zobrazí jen vyplněné údaje. Číslo podlaží nemusíš vyplňovat, když je jednotka obkreslená v půdorysu podlaží (doplní se samo).', 'martin-dev-vyber-bytu'),
        ],
        [
            'key' => 'field_martin_dv_budova', 'name' => 'dv_budova', 'label' => __('Budova / vchod', 'martin-dev-vyber-bytu'),
            'type' => 'select', 'choices' => [], 'allow_null' => 1, 'return_format' => 'value', 'wrapper' => ['width' => '34'],
        ],
        [
            'key' => 'field_martin_dv_podlazi_cislo', 'name' => 'dv_podlazi_cislo', 'label' => __('Číslo podlaží', 'martin-dev-vyber-bytu'),
            'type' => 'number', 'step' => 1, 'min' => -10, 'max' => 200,
            'instructions' => __('1 = 1. NP, -1 = 1. PP, 0 = přízemí', 'martin-dev-vyber-bytu'), 'wrapper' => ['width' => '33'],
        ],
        [
            'key' => 'field_martin_dv_umisteni', 'name' => 'dv_umisteni', 'label' => __('Umístění na podlaží', 'martin-dev-vyber-bytu'),
            'type' => 'text', 'placeholder' => __('např. východní roh domu', 'martin-dev-vyber-bytu'), 'wrapper' => ['width' => '33'],
        ],
        [
            'key' => 'field_martin_dv_orientace', 'name' => 'dv_orientace', 'label' => __('Orientace oken', 'martin-dev-vyber-bytu'),
            'type' => 'checkbox', 'choices' => $dirs, 'layout' => 'horizontal', 'return_format' => 'label', 'wrapper' => ['width' => '50'],
        ],
        [
            'key' => 'field_martin_dv_vyhled', 'name' => 'dv_vyhled', 'label' => __('Výhled', 'martin-dev-vyber-bytu'),
            'type' => 'text', 'placeholder' => __('např. do zeleně a na Brdy', 'martin-dev-vyber-bytu'), 'wrapper' => ['width' => '50'],
        ],
    ];
}

/** ID projektu jednotky, kterou právě upravuješ (i u nové jednotky z odkazu v projektu). */
function current_admin_project(): int
{
    global $post;
    if ($post instanceof \WP_Post && $post->post_type === PT_UNIT) {
        $pid = project_of_unit((int) $post->ID);
        if ($pid) {
            return $pid;
        }
    }
    $from_url = isset($_GET['martin_dv_projekt']) ? absint($_GET['martin_dv_projekt']) : 0; // phpcs:ignore WordPress.Security.NonceVerification
    return ($from_url && get_post_type($from_url) === PT_PROJECT) ? $from_url : 0;
}

/** `dv_plocha` = celková plocha jednotky (včetně příček a zdí); popisek podle nastavení projektu. */
add_filter('acf/prepare_field/name=dv_plocha', function ($field) {
    if (is_array($field)) {
        /* translators: %s: název celkové plochy, např. Celková plocha */
        $field['label']        = sprintf(__('%s (m²)', 'martin-dev-vyber-bytu'), total_label(current_admin_project()));
        $field['instructions'] = __('Celková plocha jednotky včetně příček a zdí. Součet místností (níže) může být stejný nebo menší.', 'martin-dev-vyber-bytu');
    }
    return $field;
});

/** Budova / vchod: nabídka ze seznamu v projektu; bez budov se pole skryje (pokud už nemá hodnotu). */
add_filter('acf/prepare_field/name=dv_budova', function ($field) {
    if (!is_array($field)) {
        return $field;
    }
    $buildings = project_buildings(current_admin_project());
    $current   = is_string($field['value'] ?? null) ? $field['value'] : '';
    if ($current !== '' && !in_array($current, $buildings, true)) {
        $buildings[] = $current; // přejmenovaná / smazaná budova – hodnota se neztratí
    }
    if (!$buildings) {
        return false;
    }
    $field['choices']      = array_combine($buildings, $buildings);
    $field['instructions'] = __('Seznam budov upravíš v projektu → Nastavení zobrazení.', 'martin-dev-vyber-bytu');
    return $field;
});

/** Stav jednotky: nabídka stavů projektu (základní s přejmenováním + vlastní stavy, od 0.15.0). */
add_filter('acf/prepare_field/name=dv_stav', function ($field) {
    if (!is_array($field)) {
        return $field;
    }
    $choices = [];
    foreach (project_statuses(current_admin_project()) as $key => $st) {
        $choices[$key] = $st['label'];
    }
    $current = is_string($field['value'] ?? null) ? $field['value'] : '';
    if ($current !== '' && !isset($choices[$current])) {
        $choices[$current] = $current; // stav smazaný v projektu – hodnota se neztratí, jen ji změň
    }
    $field['choices'] = $choices;
    return $field;
});

/** Hodnota stavu v ACF dynamických datech (Breakdance) = název stavu podle projektu jednotky. */
add_filter('acf/format_value/name=dv_stav', function ($value, $post_id) {
    $id = is_numeric($post_id) ? (int) $post_id : 0;
    return ($id && get_post_type($id) === PT_UNIT) ? unit_status($id)['label'] : $value;
}, 10, 2);
