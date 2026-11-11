<?php
/**
 * Mapa okolí – data a texty pro editor projektu (assets/admin/okoli-editor.js).
 * Ukládání jde přes existující uložení projektu (nonce martin_dv_save + edit_post) a sanitize_maps().
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

add_filter('martin_dv_editor_config', function (array $cfg): array {
    $icons = [];
    foreach (okoli_icons() as $key => $icon) {
        $icons[$key] = ['name' => $icon['name'], 'svg' => okoli_icon_svg($key)];
    }

    $cfg['okoli'] = [
        'cats'  => okoli_categories(),
        'icons' => $icons,
        'i18n'  => [
            'section'     => __('Okolí', 'martin-dev-vyber-bytu'),
            'addMap'      => __('Přidat mapu okolí', 'martin-dev-vyber-bytu'),
            'newMapName'  => __('Okolí', 'martin-dev-vyber-bytu'),
            'map'         => __('Mapa okolí', 'martin-dev-vyber-bytu'),
            'mapHelp'     => __('Nahraj stylizovaný obrázek mapy. Body přidáš kliknutím do mapy, pořadí v seznamu pod mapou určuje čísla 01, 02… Na web mapu vložíš elementem „Marmal – Mapa okolí“ nebo shortcodem.', 'martin-dev-vyber-bytu'),
            'mapLicense'  => __('Podklad mapy neobkresluj z Google Map (licence to nedovoluje). Použij OpenStreetMap a na mapě uveď „© přispěvatelé OpenStreetMap“.', 'martin-dev-vyber-bytu'),
            'chooseImage' => __('Vybrat obrázek mapy', 'martin-dev-vyber-bytu'),
            'changeImage' => __('Změnit obrázek', 'martin-dev-vyber-bytu'),
            'useImage'    => __('Použít obrázek', 'martin-dev-vyber-bytu'),
            'noImage'     => __('Vyber obrázek mapy z knihovny médií.', 'martin-dev-vyber-bytu'),
            'modeSelect'  => __('Upravit body', 'martin-dev-vyber-bytu'),
            'modeAdd'     => __('Přidat bod', 'martin-dev-vyber-bytu'),
            'modeProj'    => __('Umístit projekt', 'martin-dev-vyber-bytu'),
            'hintSelect'  => __('Klikni na bod v mapě nebo v seznamu. Tažením bod posuneš.', 'martin-dev-vyber-bytu'),
            'hintAdd'     => __('Klikni do mapy na místo, kam chceš bod přidat.', 'martin-dev-vyber-bytu'),
            'hintProj'    => __('Klikni do mapy na místo stavby – hlavní bod projektu (pulzuje, není v seznamu).', 'martin-dev-vyber-bytu'),
            'newPoint'    => __('Nový bod', 'martin-dev-vyber-bytu'),
            'points'      => __('Body (pořadí = čísla)', 'martin-dev-vyber-bytu'),
            'noPoints'    => __('Zatím žádné body. Přepni na „Přidat bod“ a klikni do mapy.', 'martin-dev-vyber-bytu'),
            'dragHelp'    => __('Pořadí změníš přetažením řádku nebo šipkami.', 'martin-dev-vyber-bytu'),
            'name'        => __('Název', 'martin-dev-vyber-bytu'),
            'mapName'     => __('Název mapy (jen pro administraci a popis obrázku)', 'martin-dev-vyber-bytu'),
            'cat'         => __('Kategorie', 'martin-dev-vyber-bytu'),
            'icon'        => __('Ikona', 'martin-dev-vyber-bytu'),
            'desc'        => __('Popis (nepovinný)', 'martin-dev-vyber-bytu'),
            'descPh'      => __('např. 1.–9. ročník, družina', 'martin-dev-vyber-bytu'),
            'dist'        => __('Vzdálenost (nepovinná)', 'martin-dev-vyber-bytu'),
            'distPh'      => __('např. 350 m nebo 5 min pěšky', 'martin-dev-vyber-bytu'),
            'url'         => __('Odkaz (nepovinný)', 'martin-dev-vyber-bytu'),
            'urlHelp'     => __('Řádek v seznamu se stane odkazem (např. web školy).', 'martin-dev-vyber-bytu'),
            'delPoint'    => __('Smazat bod', 'martin-dev-vyber-bytu'),
            'confirmPt'   => __('Smazat tento bod?', 'martin-dev-vyber-bytu'),
            'proj'        => __('Hlavní bod – projekt', 'martin-dev-vyber-bytu'),
            'projName'    => __('Text v bublině', 'martin-dev-vyber-bytu'),
            'projNamePh'  => __('prázdné = název projektu', 'martin-dev-vyber-bytu'),
            'projDesc'    => __('Druhý řádek bubliny', 'martin-dev-vyber-bytu'),
            'projDescPh'  => __('např. Tady stavíme', 'martin-dev-vyber-bytu'),
            'projNone'    => __('Hlavní bod zatím není umístěný.', 'martin-dev-vyber-bytu'),
            'projDel'     => __('Odebrat hlavní bod', 'martin-dev-vyber-bytu'),
            'markers'     => __('Značky v mapě a v seznamu', 'martin-dev-vyber-bytu'),
            'mCisla'      => __('Čísla', 'martin-dev-vyber-bytu'),
            'mIkony'      => __('Ikony', 'martin-dev-vyber-bytu'),
            'mKombi'      => __('Ikona + číslo', 'martin-dev-vyber-bytu'),
            'catColors'   => __('Barvy značek podle kategorie', 'martin-dev-vyber-bytu'),
            'catColorsH'  => __('Barvy kategorií nastavíš v elementu → Design → Barvy kategorií.', 'martin-dev-vyber-bytu'),
            'filter'      => __('Nad seznamem zobrazit filtr kategorií', 'martin-dev-vyber-bytu'),
            'delMap'      => __('Smazat mapu', 'martin-dev-vyber-bytu'),
            'confirmMap'  => __('Smazat celou mapu včetně všech bodů?', 'martin-dev-vyber-bytu'),
            'embed'       => __('Vložení na web', 'martin-dev-vyber-bytu'),
            'order'       => __('Pořadí mapy v projektu: %d', 'martin-dev-vyber-bytu'),
            'unnamed'     => __('bez názvu', 'martin-dev-vyber-bytu'),
            'up'          => __('Posunout nahoru', 'martin-dev-vyber-bytu'),
            'down'        => __('Posunout dolů', 'martin-dev-vyber-bytu'),
            /* translators: 1: vodorovná pozice v procentech, 2: svislá pozice v procentech */
            'position'    => __('Pozice: zleva %1$s, shora %2$s', 'martin-dev-vyber-bytu'),
        ],
    ];
    return $cfg;
});
