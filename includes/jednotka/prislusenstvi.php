<?php
/**
 * Příslušenství jednotky jako seznam (od 0.17.0) – nahrazuje pevné sloty (dv_acc_*), ty zůstávají
 * jako záloha: když je seznam prázdný, výpis použije stará pole.
 *
 * _dv_prislusenstvi  JSON [{typ, nazev, oznaceni, plocha, cena, v_cene, jednotka}]
 *   typ       stani | garaz | sklep | jine (u „jine“ se zobrazí nazev)
 *   jednotka  ID samostatné jednotky (typ Sklep / Garáž / Parkovací stání) – volitelná vazba;
 *             prázdné označení / plocha / cena se převezmou z ní, na ní se uloží zpětný odkaz _dv_patri_k
 *   cena      celé Kč (0 = bez ceny), v_cene = „v ceně jednotky“
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

const META_ACC     = '_dv_prislusenstvi';
const META_PARENT  = '_dv_patri_k';

/** Typy příslušenství (popisky). */
function accessory_types(): array
{
    return [
        'stani' => __('Parkovací stání', 'martin-dev-vyber-bytu'),
        'garaz' => __('Garáž', 'martin-dev-vyber-bytu'),
        'sklep' => __('Sklep', 'martin-dev-vyber-bytu'),
        'jine'  => __('Jiné', 'martin-dev-vyber-bytu'),
    ];
}

/** Typy jednotek, které jsou příslušenstvím (nepatří do navigace bytů ani do Podobných). */
function accessory_unit_types(): array
{
    return apply_filters('martin_dv_accessory_unit_types', ['sklep', 'garaz', 'stani']);
}

function clean_price($value): int
{
    $value = preg_replace('/[^\d]/', '', (string) $value);
    return $value === '' ? 0 : (int) min(2000000000, (int) $value);
}

function sanitize_accessories($rows): array
{
    $out   = [];
    $types = accessory_types();
    foreach (array_slice(is_array($rows) ? $rows : [], 0, 30) as $r) {
        if (!is_array($r)) {
            continue;
        }
        $unit = absint($r['jednotka'] ?? 0);
        if ($unit && get_post_type($unit) !== PT_UNIT) {
            $unit = 0;
        }
        $row = [
            'typ'      => isset($types[$r['typ'] ?? '']) ? $r['typ'] : 'jine',
            'nazev'    => clean_text($r['nazev'] ?? '', 80),
            'oznaceni' => clean_text($r['oznaceni'] ?? '', 40),
            'plocha'   => clean_area($r['plocha'] ?? 0),
            'cena'     => clean_price($r['cena'] ?? 0),
            'v_cene'   => !empty($r['v_cene']),
            'jednotka' => $unit,
        ];
        if ($row['typ'] === 'jine' && $row['nazev'] === '' && !$unit) {
            continue; // „Jiné“ bez názvu nemá smysl
        }
        $out[] = $row;
    }
    return $out;
}

function unit_accessories_raw(int $unit_id): array
{
    return sanitize_accessories(read_json_meta($unit_id, META_ACC));
}

/**
 * Příslušenství pro výpis (nový seznam s doplněním z navázané jednotky):
 * [['label', 'typ', 'oznaceni', 'plocha', 'areaTxt', 'cena', 'price' (text), 'unit', 'url'], …]
 */
function unit_accessory_rows(int $unit_id): array
{
    $types = accessory_types();
    $out   = [];
    foreach (unit_accessories_raw($unit_id) as $r) {
        $linked = $r['jednotka'] && get_post_status($r['jednotka']) === 'publish' ? $r['jednotka'] : 0;
        $code   = $r['oznaceni'] !== '' ? $r['oznaceni'] : ($linked ? (trim((string) get_post_meta($linked, 'dv_cislo', true)) ?: get_the_title($linked)) : '');
        $area   = $r['plocha'] > 0 ? $r['plocha'] : ($linked ? clean_area(get_post_meta($linked, 'dv_plocha', true)) : 0);
        $price  = $r['cena'] > 0 ? $r['cena'] : ($linked ? (int) get_post_meta($linked, 'dv_cena', true) : 0);
        $name   = $r['typ'] === 'jine' ? $r['nazev'] : $types[$r['typ']];
        if ($r['v_cene']) {
            $price_txt = __('v ceně', 'martin-dev-vyber-bytu');
        } elseif ($price > 0) {
            /* translators: %s: cena */
            $price_txt = sprintf(__('%s Kč', 'martin-dev-vyber-bytu'), num($price));
        } else {
            $price_txt = '';
        }
        $out[] = [
            'label'    => trim($name . ($code !== '' ? ' ' . $code : '')),
            'typ'      => $r['typ'],
            'oznaceni' => $code,
            'plocha'   => $area,
            'areaTxt'  => $area > 0 ? fmt_area_exact($area) : '',
            'cena'     => $r['v_cene'] ? 0 : $price,
            'price'    => $price_txt,
            'unit'     => $linked,
            'url'      => $linked ? (string) get_permalink($linked) : '',
        ];
    }
    return $out;
}

/** Ke které jednotce patří (zpětný odkaz u sklepa / garáže / stání). 0 = k žádné. */
function unit_parent(int $unit_id): int
{
    $pid = absint(get_post_meta($unit_id, META_PARENT, true));
    return ($pid && get_post_type($pid) === PT_UNIT) ? $pid : 0;
}

/**
 * Uloží seznam příslušenství a zpětné odkazy na navázaných jednotkách. Zpětný odkaz se mění jen
 * u jednotek, které smí uživatel upravovat.
 */
function save_unit_accessories(int $post_id, $rows): void
{
    $old_links = array_filter(array_column(unit_accessories_raw($post_id), 'jednotka'));
    $rows      = sanitize_accessories($rows);
    $rows      = array_values(array_filter($rows, function ($r) use ($post_id) {
        return $r['jednotka'] !== $post_id;
    }));
    foreach ($rows as &$r) {
        if ($r['jednotka'] && !current_user_can('edit_post', $r['jednotka'])) {
            $r['jednotka'] = 0;
        }
    }
    unset($r);
    if ($rows) {
        update_post_meta($post_id, META_ACC, wp_slash(wp_json_encode($rows, JSON_UNESCAPED_UNICODE)));
    } else {
        delete_post_meta($post_id, META_ACC);
    }
    $new_links = array_filter(array_column($rows, 'jednotka'));
    foreach (array_diff($old_links, $new_links) as $gone) {
        if ((int) get_post_meta($gone, META_PARENT, true) === $post_id && current_user_can('edit_post', $gone)) {
            delete_post_meta($gone, META_PARENT);
        }
    }
    foreach ($new_links as $linked) {
        update_post_meta($linked, META_PARENT, $post_id);
    }
}

/** Jednotky typu Sklep / Garáž / Stání pro výběr vazby v editoru (z projektu, jinak všechny). */
function accessory_unit_choices(int $project_id, int $exclude = 0): array
{
    $args = [
        'post_type'      => PT_UNIT,
        'post_status'    => ['publish', 'draft', 'private'],
        'posts_per_page' => 300,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'meta_query'     => [['key' => 'dv_typ', 'value' => accessory_unit_types(), 'compare' => 'IN']], // phpcs:ignore WordPress.DB.SlowDBQuery
    ];
    if ($project_id) {
        $args['meta_query'][] = ['key' => 'dv_projekt', 'value' => $project_id]; // phpcs:ignore WordPress.DB.SlowDBQuery
    }
    $types = unit_types();
    $out   = [];
    foreach (get_posts($args) as $p) {
        if ((int) $p->ID === $exclude) {
            continue;
        }
        $num    = trim((string) get_post_meta($p->ID, 'dv_cislo', true));
        $parent = unit_parent((int) $p->ID);
        $out[]  = [
            'id'     => (int) $p->ID,
            'label'  => ($types[get_post_meta($p->ID, 'dv_typ', true)] ?? '') . ' ' . ($num !== '' ? $num : get_the_title($p)),
            'parent' => ($parent && $parent !== $exclude) ? get_the_title($parent) : '',
        ];
    }
    return $out;
}

/* ---------- Pořadí jednotek projektu: předchozí / další, podobné ---------- */

/**
 * Publikované jednotky projektu ve stejném pořadí jako tabulka výběru: od nejnižšího podlaží,
 * uvnitř přirozeně podle čísla. Bez příslušenství (sklep, garáž, stání), pokud o ně nejde.
 */
function project_units_sorted(int $project_id, bool $accessories = false): array
{
    static $cache = [];
    $ck = $project_id . ($accessories ? 'a' : 'u');
    if (isset($cache[$ck])) {
        return $cache[$ck];
    }
    $ids = get_posts([
        'post_type'      => PT_UNIT,
        'post_status'    => 'publish',
        'posts_per_page' => 500,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_key'       => 'dv_projekt',   // phpcs:ignore WordPress.DB.SlowDBQuery
        'meta_value'     => $project_id,    // phpcs:ignore WordPress.DB.SlowDBQuery
    ]);
    $acc  = accessory_unit_types();
    $rows = [];
    foreach ($ids as $id) {
        $is_acc = in_array((string) get_post_meta($id, 'dv_typ', true), $acc, true);
        if ($is_acc !== $accessories) {
            continue;
        }
        $num    = trim((string) get_post_meta($id, 'dv_cislo', true));
        $rows[] = ['id' => (int) $id, 'floor' => unit_floor_number((int) $id), 'num' => $num !== '' ? $num : get_the_title($id)];
    }
    usort($rows, function ($a, $b) {
        if ($a['floor'] !== $b['floor']) {
            if ($a['floor'] === null) {
                return 1;
            }
            if ($b['floor'] === null) {
                return -1;
            }
            return $a['floor'] <=> $b['floor'];
        }
        return strnatcasecmp($a['num'], $b['num']);
    });
    return $cache[$ck] = array_column($rows, 'id');
}

/** Předchozí a další jednotka projektu: ['prev' => ID|0, 'next' => ID|0]. */
function unit_neighbors(int $unit_id): array
{
    $project = project_of_unit($unit_id);
    if (!$project) {
        return ['prev' => 0, 'next' => 0];
    }
    $is_acc = in_array((string) get_post_meta($unit_id, 'dv_typ', true), accessory_unit_types(), true);
    $list   = project_units_sorted($project, $is_acc);
    $i      = array_search($unit_id, $list, true);
    if ($i === false) {
        return ['prev' => 0, 'next' => 0];
    }
    return ['prev' => $list[$i - 1] ?? 0, 'next' => $list[$i + 1] ?? 0];
}

/**
 * Podobné jednotky: stejný projekt, bez aktuální a bez příslušenství. Pořadí: stejná dispozice
 * a podlaží → stejná dispozice → stejné podlaží → ostatní; uvnitř podle rozdílu plochy.
 *
 * @param array $args count (počet), with_sold (i prodané), only_match (jen se shodou dispozice nebo podlaží)
 */
function similar_units(int $unit_id, array $args = []): array
{
    $project = project_of_unit($unit_id);
    if (!$project) {
        return [];
    }
    $count = max(1, min(12, (int) ($args['count'] ?? 3)));
    $disp  = (string) get_post_meta($unit_id, 'dv_dispozice', true);
    $floor = unit_floor_number($unit_id);
    $area  = clean_area(get_post_meta($unit_id, 'dv_plocha', true));
    $cands = [];
    foreach (project_units_sorted($project) as $id) {
        if ($id === $unit_id) {
            continue;
        }
        if (empty($args['with_sold']) && unit_status($id, $project)['kind'] === 'prodany') {
            continue;
        }
        $same_disp  = $disp !== '' && (string) get_post_meta($id, 'dv_dispozice', true) === $disp;
        $same_floor = $floor !== null && unit_floor_number($id) === $floor;
        if (!empty($args['only_match']) && !$same_disp && !$same_floor) {
            continue;
        }
        $score   = ($same_disp ? 2 : 0) + ($same_floor ? 1 : 0);
        $cands[] = ['id' => $id, 'score' => $score, 'diff' => abs(clean_area(get_post_meta($id, 'dv_plocha', true)) - $area)];
    }
    usort($cands, function ($a, $b) {
        return [$b['score'], $a['diff']] <=> [$a['score'], $b['diff']];
    });
    return array_column(array_slice($cands, 0, $count), 'id');
}
