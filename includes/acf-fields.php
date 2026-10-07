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
        . esc_html__('Plugin Marmal – Výběr bytů potřebuje Advanced Custom Fields (ACF nebo ACF Pro) pro úpravu údajů jednotek.', 'martin-dev-vyber-bytu')
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
                'type' => 'text', 'instructions' => __('Např. „2. NP“ nebo u mezonetu „3.–4. NP“. Prázdné = doplní se podle výkresu.', 'martin-dev-vyber-bytu'),
                'wrapper' => ['width' => '33'],
            ],
            [
                'key' => 'field_martin_dv_plocha', 'name' => 'dv_plocha', 'label' => __('Podlahová plocha (m²)', 'martin-dev-vyber-bytu'),
                'type' => 'number', 'step' => 0.1, 'min' => 0, 'wrapper' => ['width' => '34'],
            ],
            [
                'key' => 'field_martin_dv_venkovni_typ', 'name' => 'dv_venkovni_typ', 'label' => __('Venkovní plocha', 'martin-dev-vyber-bytu'),
                'type' => 'select', 'choices' => outdoor_types(), 'allow_null' => 1, 'return_format' => 'label', 'wrapper' => ['width' => '33'],
            ],
            [
                'key' => 'field_martin_dv_venkovni_plocha', 'name' => 'dv_venkovni_plocha', 'label' => __('Venkovní plocha (m²)', 'martin-dev-vyber-bytu'),
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
        'message' => __('Zaškrtni, co si kupující k této jednotce může připlatit, a vedle doplň cenu. Na webu se zobrazí jen položky, které jsou zaškrtnuté a mají cenu.', 'martin-dev-vyber-bytu'),
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
