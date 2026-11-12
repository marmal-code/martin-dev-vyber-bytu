<?php
/**
 * Vlastní dynamická pole Breakdance (od 0.15.0): Add → Dynamic Data → „Developerské projekty“.
 *   Jednotka – údaje aktuální jednotky (detail, Post Loop) v českém formátu (55,5 m², 3 450 000 Kč).
 *   Projekt  – údaje projektu jednotky; mimo detail jednotky výchozí projekt (první publikovaný,
 *              filtr martin_dv_default_project) – např. kontakt prodejce v globálním bloku.
 * Hodnoty počítají funkce dv_unit_value() / dv_project_value() (dají se použít i v PHP).
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

/** Seznam polí: slug => [název, podkategorie, typ (string | url | image)]. */
function dynamic_fields(): array
{
    $u = __('Jednotka', 'martin-dev-vyber-bytu');
    $p = __('Projekt', 'martin-dev-vyber-bytu');
    return [
        'marmal_dv_jednotka_oznaceni'   => [__('Označení (Byt 1.02)', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_cislo'      => [__('Číslo jednotky', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_typ'        => [__('Typ', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_dispozice'  => [__('Dispozice', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_plocha'     => [__('Celková plocha (55,5 m²)', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_plocha_cislo' => [__('Celková plocha – jen číslo (55,5)', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_soucet'     => [__('Součet místností (užitná / podlahová)', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_venkovni'   => [__('Venkovní plochy (Balkon 4,2 m², …)', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_venkovni_celkem' => [__('Venkovní plochy celkem', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_podlazi'    => [__('Podlaží', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_budova'     => [__('Budova / vchod', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_umisteni'   => [__('Umístění na podlaží', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_orientace'  => [__('Orientace oken (J, JZ)', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_orientace_slovy' => [__('Orientace oken slovy (jih, jihozápad)', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_vyhled'     => [__('Výhled', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_cena'       => [__('Cena (nebo Prodáno / Cena na dotaz / text projektu)', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_cena_m2'    => [__('Cena za m²', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_stav'       => [__('Stav', 'martin-dev-vyber-bytu'), $u, 'string'],
        'marmal_dv_jednotka_projekt'    => [__('Název projektu', 'martin-dev-vyber-bytu'), $u, 'string'],

        'marmal_dv_projekt_nazev'       => [__('Název projektu', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_developer'   => [__('Developer', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_dodavatel'   => [__('Generální dodavatel', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_lokalita'    => [__('Lokalita', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_termin'      => [__('Termín dokončení', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_predprodej'  => [__('Předprodej (od – do)', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_predprodej_od' => [__('Předprodej od', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_predprodej_do' => [__('Předprodej do', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_jednotek'    => [__('Počet jednotek', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_volnych'     => [__('Počet volných jednotek', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_cena_od'     => [__('Cena od (nejlevnější volná)', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_text_cena'   => [__('Text místo ceny', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_projekt_text_plochy' => [__('Upozornění k plochám', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_prodejce_jmeno'      => [__('Prodejce – jméno', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_prodejce_firma'      => [__('Prodejce – firma', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_prodejce_telefon'    => [__('Prodejce – telefon', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_prodejce_telefon_odkaz' => [__('Prodejce – telefon (odkaz tel:)', 'martin-dev-vyber-bytu'), $p, 'url'],
        'marmal_dv_prodejce_email'      => [__('Prodejce – e-mail', 'martin-dev-vyber-bytu'), $p, 'string'],
        'marmal_dv_prodejce_email_odkaz' => [__('Prodejce – e-mail (odkaz mailto:)', 'martin-dev-vyber-bytu'), $p, 'url'],
        'marmal_dv_prodejce_foto'       => [__('Prodejce – fotka', 'martin-dev-vyber-bytu'), $p, 'image'],
    ];
}

/** Hodnota pole jednotky (aktuální příspěvek nebo zadané ID). Prázdný řetězec = nic. */
function dv_unit_value(string $slug, int $id = 0): string
{
    $unit = resolve_unit($id);
    if (!$unit) {
        return '';
    }
    $m       = function (string $key) use ($unit) {
        return get_post_meta($unit, $key, true);
    };
    $project = project_of_unit($unit);
    $types   = unit_types();
    $outs    = outdoor_types();
    $key     = substr($slug, strlen('marmal_dv_jednotka_'));

    switch ($key) {
        case 'oznaceni':
            $num  = trim((string) $m('dv_cislo'));
            $type = $types[$m('dv_typ')] ?? $types['byt'];
            return $num !== '' ? $type . ' ' . $num : get_the_title($unit);
        case 'cislo':
            return trim((string) $m('dv_cislo')) ?: get_the_title($unit);
        case 'typ':
            return $types[$m('dv_typ')] ?? '';
        case 'dispozice':
            return (string) $m('dv_dispozice');
        case 'plocha':
            $a = clean_area($m('dv_plocha'));
            return $a > 0 ? fmt_area_exact($a) : '';
        case 'plocha_cislo':
            $a = clean_area($m('dv_plocha'));
            return $a > 0 ? num($a, (round($a) == $a) ? 0 : ((round($a, 1) == $a) ? 1 : 2)) : '';
        case 'soucet':
            $s = unit_area_sums($unit)['rooms'];
            return $s > 0 ? fmt_area_exact($s) : '';
        case 'venkovni':
            return implode(', ', array_map(function ($o) use ($outs) {
                return trim($outs[$o['typ']] . ($o['plocha'] > 0 ? ' ' . fmt_area_exact($o['plocha']) : ''));
            }, unit_outdoors($unit)));
        case 'venkovni_celkem':
            $s = unit_area_sums($unit)['outdoor'];
            return $s > 0 ? fmt_area_exact($s) : '';
        case 'podlazi':
            return unit_floor_text($unit);
        case 'budova':
            return (string) $m('dv_budova');
        case 'umisteni':
            return (string) $m('dv_umisteni');
        case 'orientace':
        case 'orientace_slovy':
            $o = $m('dv_orientace');
            return orientation_text(is_array($o) ? $o : [], $key === 'orientace_slovy');
        case 'vyhled':
            return (string) $m('dv_vyhled');
        case 'cena':
            $info = unit_info($unit, []);
            return $info ? (string) $info['priceTxt'] : '';
        case 'cena_m2':
            $st = unit_status($unit, $project);
            if ($st['kind'] !== 'volny' || $m('dv_cena_na_dotaz')) {
                return '';
            }
            $pm = price_per_m2($unit);
            /* translators: %s: cena za metr čtvereční */
            return $pm ? sprintf(__('%s Kč/m²', 'martin-dev-vyber-bytu'), num($pm)) : '';
        case 'stav':
            return unit_status($unit, $project)['label'];
        case 'projekt':
            return $project ? get_the_title($project) : '';
    }
    return '';
}

/** Statistika jednotek projektu (publikované): počet, volné, nejnižší cena volné. */
function project_unit_stats(int $project_id): array
{
    static $cache = [];
    if (isset($cache[$project_id])) {
        return $cache[$project_id];
    }
    $ids   = get_posts([
        'post_type'      => PT_UNIT,
        'post_status'    => 'publish',
        'posts_per_page' => 500,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_key'       => 'dv_projekt',   // phpcs:ignore WordPress.DB.SlowDBQuery
        'meta_value'     => $project_id,    // phpcs:ignore WordPress.DB.SlowDBQuery
    ]);
    $free  = 0;
    $min   = 0;
    foreach ($ids as $id) {
        if (unit_status((int) $id, $project_id)['kind'] !== 'volny') {
            continue;
        }
        $free++;
        $price = (int) get_post_meta($id, 'dv_cena', true);
        if ($price > 0 && !get_post_meta($id, 'dv_cena_na_dotaz', true) && (!$min || $price < $min)) {
            $min = $price;
        }
    }
    return $cache[$project_id] = ['count' => count($ids), 'free' => $free, 'min' => $min];
}

/** Hodnota pole projektu (projekt aktuální jednotky, jinak výchozí). */
function dv_project_value(string $slug, int $project_id = 0)
{
    $project_id = $project_id ?: current_project();
    if (!project_visible($project_id)) {
        return '';
    }
    $i = project_info($project_id);
    $p = $i['prodejce'];
    switch ($slug) {
        case 'marmal_dv_projekt_nazev':
            return get_the_title($project_id);
        case 'marmal_dv_projekt_developer':
            return $i['developer'];
        case 'marmal_dv_projekt_dodavatel':
            return $i['dodavatel'];
        case 'marmal_dv_projekt_lokalita':
            return $i['lokalita'];
        case 'marmal_dv_projekt_termin':
            return $i['termin'];
        case 'marmal_dv_projekt_predprodej':
            return fmt_range($i['predprodej_od'], $i['predprodej_do']);
        case 'marmal_dv_projekt_predprodej_od':
            return fmt_date($i['predprodej_od']);
        case 'marmal_dv_projekt_predprodej_do':
            return fmt_date($i['predprodej_do']);
        case 'marmal_dv_projekt_jednotek':
            $c = project_unit_stats($project_id)['count'];
            return $c ? (string) $c : '';
        case 'marmal_dv_projekt_volnych':
            return (string) project_unit_stats($project_id)['free'];
        case 'marmal_dv_projekt_cena_od':
            $min = project_unit_stats($project_id)['min'];
            /* translators: %s: nejnižší cena */
            return $min ? sprintf(__('od %s Kč', 'martin-dev-vyber-bytu'), num($min)) : '';
        case 'marmal_dv_projekt_text_cena':
            return $i['texty']['cena'];
        case 'marmal_dv_projekt_text_plochy':
            return $i['texty']['plochy'];
        case 'marmal_dv_prodejce_jmeno':
            return $p['jmeno'];
        case 'marmal_dv_prodejce_firma':
            return $p['firma'];
        case 'marmal_dv_prodejce_telefon':
            return $p['telefon'];
        case 'marmal_dv_prodejce_telefon_odkaz':
            $tel = preg_replace('/[^0-9+]/', '', $p['telefon']);
            return $tel !== '' ? 'tel:' . $tel : '';
        case 'marmal_dv_prodejce_email':
            return $p['email'];
        case 'marmal_dv_prodejce_email_odkaz':
            return $p['email'] !== '' ? 'mailto:' . $p['email'] : '';
        case 'marmal_dv_prodejce_foto':
            return (int) $p['foto'];
    }
    return '';
}

/** Registrace v Breakdance – třídy se načtou až po ověření, že Breakdance běží. */
add_action('init', function () {
    if (!function_exists('\Breakdance\DynamicData\registerField')
        || !class_exists('\Breakdance\DynamicData\StringField')
        || !class_exists('\Breakdance\DynamicData\ImageField')) {
        return;
    }
    require_once __DIR__ . '/dynamic-data/fields.php';
    foreach (dynamic_fields() as $slug => [$label, $sub, $type]) {
        $is_unit = strpos($slug, 'marmal_dv_jednotka_') === 0;
        $cb      = function () use ($slug, $is_unit) {
            return $is_unit ? dv_unit_value($slug) : dv_project_value($slug);
        };
        if ($type === 'image') {
            \Breakdance\DynamicData\registerField(new DynImage($slug, $label, $sub, $cb));
        } else {
            \Breakdance\DynamicData\registerField(new DynText($slug, $label, $sub, $cb, [$type]));
        }
    }
});
