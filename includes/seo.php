<?php
/**
 * Napojení na modul SEO pluginu Marmal – Breakdance Plus (od 0.19.0).
 *
 * Modul SEO volá filtr `marmal_seo_data` – tady do něj dodáme titulek, popisek a obrázek
 * jednotky složené z jejích dat. Hodnota vyplněná ručně u jednotky (box „SEO a sdílení“)
 * má vždy přednost. Bez Breakdance Plus se filtr nikdy nezavolá – oba pluginy na sobě nezávisí.
 *
 * Vzory se nastavují v projektu → Údaje projektu → SEO a sdílení jednotek (prázdné = výchozí).
 * Zástupné symboly: viz seo_placeholders(). Část v [hranatých závorkách] se vypustí, když
 * je některý symbol v ní prázdný. %web% a %oddelovac% doplní modul SEO.
 *
 * Pravidla: prodaná jednotka má v popisku „Prodáno“, cena se do textů dostane jen přes %cena%,
 * a jen u volné jednotky s cenou bez „Cena na dotaz“.
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

/** Zástupné symboly pro nápovědu v administraci. */
function seo_placeholders(): array
{
    return [
        '%oznaceni%'      => __('Byt 1.01', 'martin-dev-vyber-bytu'),
        '%typ%'           => __('Byt', 'martin-dev-vyber-bytu'),
        '%dispozice%'     => '2+kk',
        '%celkova_plocha%' => '56,3 m²',
        '%nazev_celkove%' => __('celková plocha', 'martin-dev-vyber-bytu'),
        '%uzitna_plocha%' => __('54,1 m² (součet místností, jen když se liší)', 'martin-dev-vyber-bytu'),
        '%nazev_uzitne%'  => __('užitná plocha', 'martin-dev-vyber-bytu'),
        '%venkovni%'      => __('terasa 19,02 m²', 'martin-dev-vyber-bytu'),
        '%podlazi%'       => __('1. NP', 'martin-dev-vyber-bytu'),
        '%budova%'        => __('Dům A', 'martin-dev-vyber-bytu'),
        '%orientace%'     => __('jih, jihozápad', 'martin-dev-vyber-bytu'),
        '%cena%'          => __('3 450 000 Kč (jen volná s cenou, ne „na dotaz“)', 'martin-dev-vyber-bytu'),
        '%patri_k%'       => __('Byt 1.01 (u sklepa / garáže)', 'martin-dev-vyber-bytu'),
        '%projekt%'       => __('název projektu', 'martin-dev-vyber-bytu'),
        '%lokalita%'      => __('lokalita z Údajů projektu', 'martin-dev-vyber-bytu'),
        '%text_projektu%' => __('věta z pole Text projektu', 'martin-dev-vyber-bytu'),
        '%web%'           => __('název webu (z modulu SEO)', 'martin-dev-vyber-bytu'),
        '%oddelovac%'     => '|',
    ];
}

/** Výchozí vzory: [titulek, popisek] pro bytové a jiné jednotky a pro příslušenství. */
function seo_default_patterns(bool $accessory): array
{
    if ($accessory) {
        return [
            '%oznaceni%[, %celkova_plocha%] %oddelovac% %web%',
            '%oznaceni%[ k jednotce %patri_k%][, %nazev_celkove% %celkova_plocha%][ v %podlazi%]. %text_projektu%',
        ];
    }
    return [
        '%oznaceni%[ – %dispozice%][, %celkova_plocha%] %oddelovac% %web%',
        '%typ%[ %dispozice%][ v %podlazi%][: %nazev_celkove% %celkova_plocha%][, %nazev_uzitne% %uzitna_plocha%][, %venkovni%]. %text_projektu%',
    ];
}

/** Hodnoty zástupných symbolů pro jednotku (prázdný řetězec = není). */
function seo_values(int $unit): array
{
    $project = project_of_unit($unit);
    $info    = project_info($project);
    $lower   = function (string $s): string {
        return $s === '' ? '' : mb_strtolower(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, null, 'UTF-8');
    };

    $total = clean_area(get_post_meta($unit, 'dv_plocha', true));
    $rooms = unit_area_sums($unit)['rooms'];
    // Součet místností jen když se liší od celkové (jinak by v popisku byla dvakrát stejná čísla).
    $useful = ($rooms > 0 && abs($rooms - $total) > 0.05) ? fmt_area_exact($rooms) : '';

    $outs = outdoor_types();
    $venkovni = implode(', ', array_map(function ($o) use ($outs, $lower) {
        return trim($lower((string) ($outs[$o['typ']] ?? '')) . ($o['plocha'] > 0 ? ' ' . fmt_area_exact($o['plocha']) : ''));
    }, unit_outdoors($unit)));

    // Cena: jen volná jednotka s cenou a bez „Cena na dotaz“.
    $cena  = '';
    $price = (int) get_post_meta($unit, 'dv_cena', true);
    if ($price > 0 && !get_post_meta($unit, 'dv_cena_na_dotaz', true) && unit_status($unit, $project)['kind'] === 'volny') {
        /* translators: %s: cena */
        $cena = sprintf(__('%s Kč', 'martin-dev-vyber-bytu'), num($price));
    }

    $o = get_post_meta($unit, 'dv_orientace', true);
    return [
        '%oznaceni%'       => dv_unit_value('marmal_dv_jednotka_oznaceni', $unit),
        '%typ%'            => dv_unit_value('marmal_dv_jednotka_typ', $unit),
        '%dispozice%'      => trim((string) get_post_meta($unit, 'dv_dispozice', true)),
        '%celkova_plocha%' => $total > 0 ? fmt_area_exact($total) : '',
        '%nazev_celkove%'  => $lower(total_label($project)),
        '%uzitna_plocha%'  => $useful,
        '%nazev_uzitne%'   => $lower(area_label($project)),
        '%venkovni%'       => $venkovni,
        '%podlazi%'        => unit_floor_text($unit),
        '%budova%'         => trim((string) get_post_meta($unit, 'dv_budova', true)),
        '%orientace%'      => orientation_text(is_array($o) ? $o : [], true),
        '%cena%'           => $cena,
        '%patri_k%'        => dv_unit_value('marmal_dv_jednotka_patri_k', $unit),
        '%projekt%'        => $project ? wp_strip_all_tags(get_the_title($project)) : '',
        '%lokalita%'       => (string) $info['lokalita'],
        '%text_projektu%'  => (string) ($info['seo']['text'] ?? ''),
    ];
}

/**
 * Složí text ze vzoru: [volitelné části] se vypustí, když je v nich prázdný symbol;
 * %web% a %oddelovac% zůstávají pro modul SEO. Pak se uklidí mezery a interpunkce.
 */
function seo_fill(string $pattern, array $values): string
{
    $keep = ['%web%' => '%web%', '%oddelovac%' => '%oddelovac%', '%nazev%' => '%nazev%', '%popis_webu%' => '%popis_webu%'];
    $text = preg_replace_callback('/\[([^\[\]]*)\]/u', function ($m) use ($values) {
        if (preg_match_all('/%[a-z_]+%/', $m[1], $found)) {
            foreach ($found[0] as $ph) {
                if (isset($values[$ph]) && $values[$ph] === '') {
                    return '';
                }
            }
        }
        return $m[1];
    }, $pattern);
    $text = strtr($text, $values + $keep);
    $text = preg_replace('/%(?!web%|oddelovac%|nazev%|popis_webu%)[a-z_]+%/', '', $text); // neznámé symboly pryč
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = preg_replace('/\s+([,.:;])/u', '$1', $text);   // mezera před interpunkcí
    $text = preg_replace('/([,:;])(?=[.,:;])/u', '', $text); // „, .“ / „: .“
    $text = preg_replace('/\.{2,}/u', '.', $text);
    $text = preg_replace('/^[\s,.:;–-]+/u', '', $text);
    return trim($text);
}

/** Titulek, popisek a obrázek jednotky pro modul SEO. */
function seo_unit_data(int $unit): array
{
    $project   = project_of_unit($unit);
    if (!$project || !project_visible($project)) {
        return [];
    }
    $info      = project_info($project);
    $accessory = in_array((string) get_post_meta($unit, 'dv_typ', true), accessory_unit_types(), true);
    [$def_title, $def_desc] = seo_default_patterns($accessory);
    $values    = seo_values($unit);

    $title = seo_fill(($info['seo']['title'] ?? '') !== '' && !$accessory ? $info['seo']['title'] : $def_title, $values);
    $desc  = seo_fill(($info['seo']['desc'] ?? '') !== '' && !$accessory ? $info['seo']['desc'] : $def_desc, $values);

    if (unit_status($unit, $project)['kind'] === 'prodany') {
        /* translators: %s: popisek jednotky */
        $desc = sprintf(__('Prodáno – %s', 'martin-dev-vyber-bytu'), $desc);
    }

    // Obrázek: hlavní fotka → první vizualizace (půdorys ne – v náhledu na Facebooku je nečitelný).
    $image = (int) get_post_thumbnail_id($unit);
    if (!$image) {
        $viz   = unit_gallery($unit, 'dv_vizualizace');
        $image = $viz ? (int) $viz[0] : 0;
    }
    return ['title' => $title, 'description' => $desc, 'image_id' => $image];
}

add_filter('marmal_seo_data', function ($data, $post_id = 0) {
    $post_id = (int) $post_id;
    if (!is_array($data) || !$post_id || get_post_type($post_id) !== PT_UNIT) {
        return $data;
    }
    $u = seo_unit_data($post_id);
    foreach (['title', 'description', 'image_id'] as $k) {
        if (empty($data[$k]) && !empty($u[$k])) {
            $data[$k] = $u[$k];
        }
    }
    return $data;
}, 10, 2);
