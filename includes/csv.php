<?php
/**
 * Import a export jednotek z/do CSV (od 0.18.0): Developerské projekty → Import / export.
 *
 * Dva soubory (oba se dají otevřít v Excelu, oddělovač středník, UTF-8 s BOM):
 *   jednotky   – jeden řádek = jedna jednotka (seznamy venkovních ploch, parametrů a příslušenství v jedné buňce)
 *   mistnosti  – jeden řádek = jedna místnost (import nahradí místnosti uvedených jednotek)
 * Import ve dvou krocích: nahrání → náhled změn → potvrzení. Mění se jen sloupce, které v souboru jsou;
 * prázdná buňka hodnotu smaže. Jednotka se páruje podle id, jinak podle projektu + čísla, jinak se založí nová.
 *
 * Bezpečnost: jen uživatel s oprávněním `manage_options` (filtr martin_dv_import_capability), nonce u každé
 * akce, max. 2 MB / 2000 řádků, každá hodnota prochází stejnou sanitizací jako editor, u každé jednotky
 * kontrola edit_post. Export chrání buňky proti „CSV injection“ (vzorce v Excelu).
 */

namespace MartinDV;

if (!defined('ABSPATH')) {
    exit;
}

function csv_capability(): string
{
    return (string) apply_filters('martin_dv_import_capability', 'manage_options');
}

/** Sloupce souboru jednotek (pořadí exportu). */
function csv_unit_columns(): array
{
    return ['id', 'projekt', 'cislo', 'nazev', 'publikovano', 'typ', 'stav', 'dispozice', 'podlazi', 'podlazi_cislo', 'budova',
        'umisteni', 'orientace', 'vyhled', 'plocha', 'venkovni_typ', 'venkovni_plocha', 'venkovni', 'cena', 'cena_na_dotaz',
        'cena_od', 'rezervace_do', 'parametry', 'prislusenstvi'];
}

function csv_room_columns(): array
{
    return ['id', 'projekt', 'cislo', 'mistnost', 'plocha', 'typ', 'zapocitat', 'poznamka'];
}

/* ---------- Formát buněk ---------- */

function csv_num($n): string
{
    $n = round((float) $n, 2);
    return $n == 0 ? '' : str_replace('.', ',', (string) $n);
}

function csv_bool($v): string
{
    return $v ? 'ano' : 'ne';
}

function csv_parse_bool(string $v): bool
{
    return in_array(mb_strtolower(trim($v), 'UTF-8'), ['ano', 'a', '1', 'yes', 'y', 'true', 'x'], true);
}

/** Ymd / Y-m-d → „1. 12. 2026“; zpět na Ymd. */
function csv_date_out(string $ymd): string
{
    if (preg_match('/^(\d{4})-?(\d{2})-?(\d{2})$/', $ymd, $m)) {
        return (int) $m[3] . '. ' . (int) $m[2] . '. ' . $m[1];
    }
    return '';
}

function csv_date_in(string $v): ?string
{
    $v = trim($v);
    if ($v === '') {
        return '';
    }
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $v, $m)) {
        [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
    } elseif (preg_match('/^(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})$/', $v, $m)) {
        [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
    } else {
        return null;
    }
    return checkdate($mo, $d, $y) ? sprintf('%04d%02d%02d', $y, $mo, $d) : null;
}

/** Klíč z číselníku podle klíče nebo popisku (bez ohledu na velikost písmen). Null = nenalezeno. */
function csv_key(string $v, array $choices): ?string
{
    $v = trim($v);
    if ($v === '') {
        return '';
    }
    if (isset($choices[$v])) {
        return $v;
    }
    foreach ($choices as $k => $label) {
        $label = is_array($label) ? ($label['label'] ?? '') : $label;
        if (mb_strtolower((string) $label, 'UTF-8') === mb_strtolower($v, 'UTF-8')) {
            return (string) $k;
        }
    }
    return null;
}

function csv_split(string $v, string $sep = '|'): array
{
    return array_values(array_filter(array_map('trim', explode($sep, $v)), 'strlen'));
}

function csv_orient_out(array $keys): string
{
    return orientation_text($keys);
}

function csv_orient_in(string $v): array
{
    $map = [];
    foreach (orientations() as $k => $o) {
        $map[mb_strtolower($k, 'UTF-8')]    = $k;
        $map[mb_strtolower($o[0], 'UTF-8')] = $k;
        $map[mb_strtolower($o[1], 'UTF-8')] = $k;
    }
    $out = [];
    foreach (preg_split('/[\s,;\/]+/u', mb_strtolower($v, 'UTF-8')) as $t) {
        if ($t !== '' && isset($map[$t])) {
            $out[] = $map[$t];
        }
    }
    return clean_orientations($out);
}

/** Venkovní plochy: „Balkon: 4,2: J JZ | Terasa: 10“. */
function csv_outdoors_out(array $rows): string
{
    $types = outdoor_types();
    return implode(' | ', array_map(function ($o) use ($types) {
        return rtrim($types[$o['typ']] . ': ' . csv_num($o['plocha']) . ': ' . orientation_text($o['orientace']), ': ');
    }, $rows));
}

function csv_outdoors_in(string $v, array &$warn): array
{
    $rows = [];
    foreach (csv_split($v) as $part) {
        $p   = array_map('trim', explode(':', $part));
        $typ = csv_key($p[0] ?? '', outdoor_types());
        if (!$typ) {
            /* translators: %s: text z buňky */
            $warn[] = sprintf(__('neznámý typ venkovní plochy „%s“', 'martin-dev-vyber-bytu'), $p[0] ?? '');
            continue;
        }
        $rows[] = ['typ' => $typ, 'plocha' => clean_area($p[1] ?? 0), 'orientace' => csv_orient_in($p[2] ?? '')];
    }
    return sanitize_outdoors($rows);
}

/** Parametry: „Výška stropů = 2,7 m | Energetická třída = B“. */
function csv_params_out(array $rows): string
{
    return implode(' | ', array_map(function ($p) {
        return $p['nazev'] . ' = ' . $p['hodnota'];
    }, $rows));
}

function csv_params_in(string $v): array
{
    $rows = [];
    foreach (csv_split($v) as $part) {
        $p      = explode('=', $part, 2);
        $rows[] = ['nazev' => trim($p[0]), 'hodnota' => trim($p[1] ?? '')];
    }
    return sanitize_params($rows);
}

/** Příslušenství: „Garáž: G3: 18: 450000 | Parkovací stání: P7: : 150000 | Nabíječka: : : v ceně“. */
function csv_acc_out(array $rows): string
{
    $types = accessory_types();
    return implode(' | ', array_map(function ($r) use ($types) {
        $name  = $r['typ'] === 'jine' ? $r['nazev'] : $types[$r['typ']];
        $price = $r['v_cene'] ? 'v ceně' : ($r['cena'] ? (string) $r['cena'] : '');
        return rtrim($name . ': ' . $r['oznaceni'] . ': ' . csv_num($r['plocha']) . ': ' . $price, ': ');
    }, $rows));
}

/** Import příslušenství; vazby na samostatné jednotky se zachovají podle typu + označení. */
function csv_acc_in(string $v, array $old): array
{
    $types = accessory_types();
    unset($types['jine']);
    $links = [];
    foreach ($old as $r) {
        if ($r['jednotka']) {
            $links[$r['typ'] . '|' . mb_strtolower($r['oznaceni'], 'UTF-8')] = $r['jednotka'];
        }
    }
    $rows = [];
    foreach (csv_split($v) as $part) {
        $p     = array_map('trim', explode(':', $part));
        $typ   = csv_key($p[0] ?? '', $types) ?: 'jine';
        $price = $p[3] ?? '';
        $code  = $p[1] ?? '';
        $rows[] = [
            'typ'      => $typ,
            'nazev'    => $typ === 'jine' ? ($p[0] ?? '') : '',
            'oznaceni' => $code,
            'plocha'   => $p[2] ?? 0,
            'cena'     => $price,
            'v_cene'   => mb_strtolower($price, 'UTF-8') === 'v ceně' || mb_strtolower($price, 'UTF-8') === 'v cene',
            'jednotka' => $links[$typ . '|' . mb_strtolower($code, 'UTF-8')] ?? 0,
        ];
    }
    return sanitize_accessories($rows);
}

/* ---------- Export ---------- */

/** Řádek CSV jednotky (hodnoty jako text). */
function csv_unit_row(int $id): array
{
    $m       = function (string $k) use ($id) {
        return get_post_meta($id, $k, true);
    };
    $project = project_of_unit($id);
    $types   = unit_types();
    $orient  = $m('dv_orientace');
    $outT    = (string) $m('dv_venkovni_typ');
    return [
        'id'              => (string) $id,
        'projekt'         => $project ? get_the_title($project) : '',
        'cislo'           => (string) $m('dv_cislo'),
        'nazev'           => get_the_title($id),
        'publikovano'     => csv_bool(get_post_status($id) === 'publish'),
        'typ'             => $types[$m('dv_typ')] ?? '',
        'stav'            => unit_status($id)['label'],
        'dispozice'       => (string) $m('dv_dispozice'),
        'podlazi'         => (string) $m('dv_podlazi'),
        'podlazi_cislo'   => is_numeric($m('dv_podlazi_cislo')) ? (string) (int) $m('dv_podlazi_cislo') : '',
        'budova'          => (string) $m('dv_budova'),
        'umisteni'        => (string) $m('dv_umisteni'),
        'orientace'       => csv_orient_out(is_array($orient) ? $orient : []),
        'vyhled'          => (string) $m('dv_vyhled'),
        'plocha'          => csv_num($m('dv_plocha')),
        'venkovni_typ'    => outdoor_types()[$outT] ?? '',
        'venkovni_plocha' => csv_num($m('dv_venkovni_plocha')),
        'venkovni'        => csv_outdoors_out(sanitize_outdoors(read_json_meta($id, META_OUTDOORS))),
        'cena'            => (int) $m('dv_cena') ? (string) (int) $m('dv_cena') : '',
        'cena_na_dotaz'   => csv_bool((bool) $m('dv_cena_na_dotaz')),
        'cena_od'         => csv_date_out((string) $m('dv_cena_od')),
        'rezervace_do'    => csv_date_out((string) $m('dv_rezervace_do')),
        'parametry'       => csv_params_out(unit_params($id)),
        'prislusenstvi'   => csv_acc_out(unit_accessories_raw($id)),
    ];
}

/** Ochrana proti vzorcům v Excelu: buňka začínající = + - @ tab CR dostane apostrof (import ho zase odebere). */
function csv_safe(string $v): string
{
    return ($v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v;
}

function csv_units_for_export(int $project_id): array
{
    $args = [
        'post_type'      => PT_UNIT,
        'post_status'    => ['publish', 'draft', 'private', 'pending'],
        'posts_per_page' => 2000,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];
    if ($project_id) {
        $args['meta_key']   = 'dv_projekt';   // phpcs:ignore WordPress.DB.SlowDBQuery
        $args['meta_value'] = $project_id;    // phpcs:ignore WordPress.DB.SlowDBQuery
    }
    $ids = array_map('intval', get_posts($args));
    // Pořadí jako tabulka výběru (podlaží, číslo), ostatní na konec.
    $order = [];
    if ($project_id) {
        $order = array_flip(array_merge(project_units_sorted($project_id), project_units_sorted($project_id, true)));
    }
    usort($ids, function ($a, $b) use ($order) {
        return [$order[$a] ?? PHP_INT_MAX, $a] <=> [$order[$b] ?? PHP_INT_MAX, $b];
    });
    return array_values(array_filter($ids, function ($id) {
        return current_user_can('edit_post', $id);
    }));
}

/** Vytvoří obsah CSV (string) – středník, UTF-8 s BOM. */
function csv_build(array $columns, array $rows): string
{
    $fh = fopen('php://temp', 'r+');
    fwrite($fh, "\xEF\xBB\xBF");
    fputcsv($fh, $columns, ';');
    foreach ($rows as $r) {
        fputcsv($fh, array_map(function ($c) use ($r) {
            return csv_safe((string) ($r[$c] ?? ''));
        }, $columns), ';');
    }
    rewind($fh);
    $out = stream_get_contents($fh);
    fclose($fh);
    return (string) $out;
}

function csv_export(string $kind, int $project_id): string
{
    $rows = [];
    foreach (csv_units_for_export($project_id) as $id) {
        if ($kind === 'mistnosti') {
            $base = ['id' => (string) $id, 'projekt' => ($p = project_of_unit($id)) ? get_the_title($p) : '', 'cislo' => (string) get_post_meta($id, 'dv_cislo', true)];
            foreach (unit_rooms($id) as $r) {
                $rows[] = $base + [
                    'mistnost'  => $r['nazev'],
                    'plocha'    => csv_num($r['plocha']),
                    'typ'       => room_types()[$r['typ']],
                    'zapocitat' => csv_bool($r['zapocitat']),
                    'poznamka'  => $r['poznamka'],
                ];
            }
        } else {
            $rows[] = csv_unit_row($id);
        }
    }
    return csv_build($kind === 'mistnosti' ? csv_room_columns() : csv_unit_columns(), $rows);
}

add_action('admin_post_martin_dv_export', function () {
    if (!current_user_can(csv_capability())) {
        wp_die(esc_html__('Nemáš oprávnění.', 'martin-dev-vyber-bytu'), 403);
    }
    check_admin_referer('martin_dv_export');
    $kind    = (isset($_GET['co']) && $_GET['co'] === 'mistnosti') ? 'mistnosti' : 'jednotky';
    $project = isset($_GET['projekt']) ? absint($_GET['projekt']) : 0;
    $name    = $kind . ($project ? '-' . sanitize_title(get_the_title($project)) : '') . '-' . wp_date('Y-m-d') . '.csv';
    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    echo csv_export($kind, $project); // phpcs:ignore WordPress.Security.EscapeOutput -- soubor CSV, buňky ošetřené csv_safe()
    exit;
});

/* ---------- Import: čtení souboru ---------- */

/**
 * Přečte CSV (středník nebo čárka, UTF-8 nebo Windows-1250). Vrací ['head' => […], 'rows' => [[sloupec => hodnota], …]]
 * nebo WP_Error.
 */
function csv_read(string $content)
{
    if (strlen($content) > 2 * MB_IN_BYTES) {
        return new \WP_Error('size', __('Soubor je větší než 2 MB.', 'martin-dev-vyber-bytu'));
    }
    if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
        $content = substr($content, 3);
    } elseif (!mb_check_encoding($content, 'UTF-8')) {
        $converted = function_exists('iconv') ? @iconv('Windows-1250', 'UTF-8//IGNORE', $content) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
        $content   = $converted !== false ? $converted : mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }
    $first = strtok($content, "\n");
    $delim = substr_count((string) $first, ';') >= substr_count((string) $first, ',') ? ';' : ',';
    $fh    = fopen('php://temp', 'r+');
    fwrite($fh, $content);
    rewind($fh);
    $head = fgetcsv($fh, 0, $delim);
    if (!$head) {
        fclose($fh);
        return new \WP_Error('empty', __('Soubor je prázdný.', 'martin-dev-vyber-bytu'));
    }
    $head = array_map(function ($h) {
        return sanitize_key(remove_accents(trim((string) $h)));
    }, $head);
    $rows = [];
    $line = 1;
    while (($cells = fgetcsv($fh, 0, $delim)) !== false) {
        $line++;
        if (count($rows) >= 2000) {
            break;
        }
        if (!array_filter($cells, function ($c) {
            return trim((string) $c) !== '';
        })) {
            continue; // prázdný řádek
        }
        $row = ['_line' => $line];
        foreach ($head as $i => $key) {
            if ($key === '') {
                continue;
            }
            $val       = trim((string) ($cells[$i] ?? ''));
            $row[$key] = (strlen($val) > 1 && $val[0] === "'" && strpos("=+-@", $val[1]) !== false) ? substr($val, 1) : $val;
        }
        $rows[] = $row;
    }
    fclose($fh);
    return ['head' => $head, 'rows' => $rows];
}

/** Projekt podle ID nebo názvu. */
function csv_find_project(string $v): int
{
    $v = trim($v);
    if ($v === '') {
        return 0;
    }
    if (ctype_digit($v) && get_post_type((int) $v) === PT_PROJECT) {
        return (int) $v;
    }
    static $by_title = null;
    if ($by_title === null) {
        $by_title = [];
        foreach (get_posts(['post_type' => PT_PROJECT, 'post_status' => ['publish', 'draft', 'private'], 'posts_per_page' => 200, 'no_found_rows' => true]) as $p) {
            $by_title[mb_strtolower($p->post_title, 'UTF-8')] = (int) $p->ID;
        }
    }
    return $by_title[mb_strtolower($v, 'UTF-8')] ?? 0;
}

/** Jednotka podle ID, jinak podle projektu + čísla. */
function csv_find_unit(array $row, int $project): int
{
    $id = absint($row['id'] ?? 0);
    if ($id && get_post_type($id) === PT_UNIT) {
        return $id;
    }
    $num = trim((string) ($row['cislo'] ?? ''));
    if ($num === '' || !$project) {
        return 0;
    }
    $found = get_posts([
        'post_type'      => PT_UNIT,
        'post_status'    => ['publish', 'draft', 'private', 'pending'],
        'posts_per_page' => 2,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery
            ['key' => 'dv_projekt', 'value' => $project],
            ['key' => 'dv_cislo', 'value' => $num],
        ],
    ]);
    return count($found) === 1 ? (int) $found[0] : (count($found) > 1 ? -1 : 0);
}

/* ---------- Import: plán změn ---------- */

/**
 * Z řádků souboru jednotek sestaví plán: [['line', 'unit' (ID|0 = nová), 'label', 'set' => [pole => hodnota], 'changes' => [sloupec => [staré, nové]], 'errors' => [], 'warnings' => []], …]
 */
function csv_plan_units(array $data): array
{
    $plan  = [];
    $cols  = array_intersect($data['head'], csv_unit_columns());
    $seen  = [];
    foreach ($data['rows'] as $row) {
        $item = ['line' => $row['_line'], 'unit' => 0, 'label' => '', 'set' => [], 'changes' => [], 'errors' => [], 'warnings' => []];

        $project = in_array('projekt', $cols, true) ? csv_find_project($row['projekt'] ?? '') : 0;
        if (in_array('projekt', $cols, true) && ($row['projekt'] ?? '') !== '' && !$project) {
            /* translators: %s: název projektu */
            $item['errors'][] = sprintf(__('projekt „%s“ neexistuje', 'martin-dev-vyber-bytu'), $row['projekt']);
        }
        $unit = csv_find_unit($row, $project ?: (absint($row['id'] ?? 0) ? project_of_unit(absint($row['id'])) : 0));
        if ($unit === -1) {
            $item['errors'][] = __('v projektu je víc jednotek se stejným číslem', 'martin-dev-vyber-bytu');
            $unit = 0;
        }
        if ($unit && !current_user_can('edit_post', $unit)) {
            $item['errors'][] = __('jednotku nesmíš upravovat', 'martin-dev-vyber-bytu');
        }
        if (!$unit && !$project) {
            $item['errors'][] = __('nová jednotka potřebuje sloupec projekt', 'martin-dev-vyber-bytu');
        }
        if ($unit && isset($seen[$unit])) {
            /* translators: %d: číslo řádku */
            $item['errors'][] = sprintf(__('stejná jednotka už je na řádku %d', 'martin-dev-vyber-bytu'), $seen[$unit]);
        }
        $item['unit'] = $unit;
        if ($unit) {
            $seen[$unit] = $row['_line'];
        }
        $target_project = $project ?: ($unit ? project_of_unit($unit) : 0);
        $old            = $unit ? csv_unit_row($unit) : array_fill_keys(csv_unit_columns(), '');
        $item['label']  = trim(($row['nazev'] ?? '') ?: (($row['cislo'] ?? '') ?: $old['nazev']));

        foreach ($cols as $c) {
            if (in_array($c, ['id'], true)) {
                continue;
            }
            $v    = (string) ($row[$c] ?? '');
            $w    = [];
            $skip = false;
            switch ($c) {
                case 'projekt':
                    if (!$project) {
                        $skip = true; // prázdný / neznámý projekt = beze změny (chyba už je hlášená)
                        break;
                    }
                    $new = get_the_title($project);
                    $set = $project;
                    break;
                case 'dispozice':
                    $set = clean_text($v, 40);
                    if ($set !== '' && !isset(dispositions()[$set])) {
                        /* translators: %s: dispozice */
                        $w[] = sprintf(__('dispozice „%s“ není v seznamu – v administraci se nezobrazí ve výběru', 'martin-dev-vyber-bytu'), $set);
                    }
                    $new = $set;
                    break;
                case 'cislo':
                case 'podlazi':
                case 'budova':
                case 'umisteni':
                case 'vyhled':
                    $set = clean_text($v, 120);
                    $new = $set;
                    break;
                case 'nazev':
                    $set = clean_text($v, 200);
                    $new = $set === '' ? $old['nazev'] : $set; // prázdný název = beze změny
                    break;
                case 'publikovano':
                    $set = csv_parse_bool($v);
                    $new = csv_bool($set);
                    break;
                case 'typ':
                    $set = csv_key($v, unit_types());
                    if ($set === null) {
                        /* translators: %s: hodnota */
                        $w[] = sprintf(__('neznámý typ „%s“', 'martin-dev-vyber-bytu'), $v);
                        $skip = true;
                        break;
                    }
                    $new = $set === '' ? '' : unit_types()[$set];
                    break;
                case 'stav':
                    $st  = project_statuses($target_project);
                    $set = csv_key($v, $st);
                    if ($set === null) {
                        /* translators: %s: hodnota */
                        $w[] = sprintf(__('neznámý stav „%s“ (stavy jsou podle projektu)', 'martin-dev-vyber-bytu'), $v);
                        $skip = true;
                        break;
                    }
                    $set = $set === '' ? 'volny' : $set;
                    $new = $st[$set]['label'];
                    break;
                case 'podlazi_cislo':
                    $set = ($v === '' || !is_numeric(str_replace(',', '.', $v))) ? '' : (string) (int) $v;
                    if ($v !== '' && $set === '') {
                        $w[] = __('číslo podlaží není číslo', 'martin-dev-vyber-bytu');
                    }
                    $new = $set;
                    break;
                case 'orientace':
                    $set = csv_orient_in($v);
                    $new = csv_orient_out($set);
                    break;
                case 'plocha':
                case 'venkovni_plocha':
                    $set = clean_area($v);
                    $new = csv_num($set);
                    break;
                case 'venkovni_typ':
                    $set = csv_key($v, outdoor_types());
                    if ($set === null) {
                        /* translators: %s: hodnota */
                        $w[] = sprintf(__('neznámý typ venkovní plochy „%s“', 'martin-dev-vyber-bytu'), $v);
                        $skip = true;
                        break;
                    }
                    $new = $set === '' ? '' : outdoor_types()[$set];
                    break;
                case 'venkovni':
                    $set = csv_outdoors_in($v, $w);
                    $new = csv_outdoors_out($set);
                    break;
                case 'cena':
                    $set = clean_price($v);
                    $new = $set ? (string) $set : '';
                    break;
                case 'cena_na_dotaz':
                    $set = csv_parse_bool($v);
                    $new = csv_bool($set);
                    break;
                case 'cena_od':
                case 'rezervace_do':
                    $set = csv_date_in($v);
                    if ($set === null) {
                        /* translators: %s: hodnota */
                        $w[] = sprintf(__('datum „%s“ nemá tvar 1. 12. 2026', 'martin-dev-vyber-bytu'), $v);
                        $skip = true;
                        break;
                    }
                    $new = csv_date_out($set);
                    break;
                case 'parametry':
                    $set = csv_params_in($v);
                    $new = csv_params_out($set);
                    break;
                case 'prislusenstvi':
                    $set = csv_acc_in($v, $unit ? unit_accessories_raw($unit) : []);
                    $new = csv_acc_out($set);
                    break;
                default:
                    $skip = true;
            }
            $item['warnings'] = array_merge($item['warnings'], $w);
            if ($skip) {
                continue;
            }
            $item['set'][$c]  = $set;
            if ((string) $old[$c] !== (string) $new) {
                $item['changes'][$c] = [(string) $old[$c], (string) $new];
            }
        }
        if (!$unit && !isset($item['set']['typ'])) {
            $item['set']['typ'] = 'byt';
        }
        $plan[] = $item;
    }
    return $plan;
}

/** Plán pro soubor místností: místnosti seskupené podle jednotky (nahradí stávající). */
function csv_plan_rooms(array $data): array
{
    $groups = [];
    foreach ($data['rows'] as $row) {
        $project = csv_find_project($row['projekt'] ?? '');
        $unit    = csv_find_unit($row, $project);
        $key     = $unit > 0 ? 'u' . $unit : 'x' . $row['_line'];
        if (!isset($groups[$key])) {
            $groups[$key] = ['line' => $row['_line'], 'unit' => max(0, $unit), 'label' => ($row['cislo'] ?? '') ?: ($row['id'] ?? ''), 'rooms' => [], 'changes' => [], 'errors' => [], 'warnings' => []];
            if ($unit <= 0) {
                $groups[$key]['errors'][] = $unit === -1 ? __('v projektu je víc jednotek se stejným číslem', 'martin-dev-vyber-bytu') : __('jednotka nenalezena (vyplň id, nebo projekt + číslo)', 'martin-dev-vyber-bytu');
            } elseif (!current_user_can('edit_post', $unit)) {
                $groups[$key]['errors'][] = __('jednotku nesmíš upravovat', 'martin-dev-vyber-bytu');
            }
        }
        $typ = csv_key((string) ($row['typ'] ?? ''), room_types());
        if ($typ === null) {
            /* translators: 1: číslo řádku, 2: hodnota */
            $groups[$key]['warnings'][] = sprintf(__('řádek %1$d: neznámý typ „%2$s“ → obytná', 'martin-dev-vyber-bytu'), $row['_line'], $row['typ']);
        }
        $groups[$key]['rooms'][] = [
            'nazev'     => $row['mistnost'] ?? '',
            'plocha'    => $row['plocha'] ?? 0,
            'typ'       => $typ ?: 'obytna',
            'zapocitat' => !isset($row['zapocitat']) || $row['zapocitat'] === '' || csv_parse_bool($row['zapocitat']),
            'poznamka'  => $row['poznamka'] ?? '',
        ];
    }
    foreach ($groups as &$g) {
        $g['rooms'] = sanitize_rooms($g['rooms']);
        if ($g['unit']) {
            $old = count(unit_rooms($g['unit']));
            /* translators: 1: počet místností před, 2: počet po */
            $g['changes']['mistnosti'] = [sprintf(_n('%d místnost', '%d místností', $old, 'martin-dev-vyber-bytu'), $old), sprintf(_n('%d místnost', '%d místností', count($g['rooms']), 'martin-dev-vyber-bytu'), count($g['rooms']))];
            $g['label'] = get_the_title($g['unit']);
        }
    }
    unset($g);
    return array_values($groups);
}

/* ---------- Import: provedení ---------- */

/** Provede plán (jen položky bez chyb). Vrací ['created' => n, 'updated' => n, 'skipped' => n]. */
function csv_apply(string $kind, array $plan): array
{
    $res = ['created' => 0, 'updated' => 0, 'skipped' => 0];
    foreach ($plan as $item) {
        if ($item['errors']) {
            $res['skipped']++;
            continue;
        }
        if ($kind === 'mistnosti') {
            if ($item['unit'] && current_user_can('edit_post', $item['unit'])) {
                save_unit_lists((int) $item['unit'], ['rooms' => $item['rooms']]);
                $res['updated']++;
            }
            continue;
        }
        $set  = $item['set'];
        $unit = (int) $item['unit'];
        if (!$unit) {
            if (!current_user_can(get_post_type_object(PT_UNIT)->cap->create_posts)) {
                $res['skipped']++;
                continue;
            }
            $types = unit_types();
            $title = ($set['nazev'] ?? '') !== '' ? $set['nazev'] : trim(($types[$set['typ'] ?? 'byt'] ?? '') . ' ' . ($set['cislo'] ?? ''));
            $can_publish = current_user_can(get_post_type_object(PT_UNIT)->cap->publish_posts);
            $unit        = (int) wp_insert_post([
                'post_type'   => PT_UNIT,
                'post_title'  => $title,
                'post_status' => (!empty($set['publikovano']) && $can_publish) ? 'publish' : 'draft',
            ], false);
            if (!$unit) {
                $res['skipped']++;
                continue;
            }
            $res['created']++;
        } else {
            if (!$item['changes']) {
                continue;
            }
            if (!current_user_can('edit_post', $unit)) { // oprávnění znovu při provedení (náhled mohl být starší)
                $res['skipped']++;
                continue;
            }
            $post = [];
            if (($set['nazev'] ?? '') !== '' && $set['nazev'] !== get_the_title($unit)) {
                $post['post_title'] = $set['nazev'];
            }
            if (array_key_exists('publikovano', $set) && ($set['publikovano'] ? 'publish' : 'draft') !== get_post_status($unit)
                && (!$set['publikovano'] || current_user_can('publish_post', $unit))) {
                $post['post_status'] = $set['publikovano'] ? 'publish' : 'draft';
            }
            if ($post) {
                wp_update_post(['ID' => $unit] + $post);
            }
            $res['updated']++;
        }
        csv_apply_unit_meta($unit, $set);
    }
    return $res;
}

function csv_apply_unit_meta(int $unit, array $set): void
{
    $meta = [
        'projekt' => 'dv_projekt', 'cislo' => 'dv_cislo', 'typ' => 'dv_typ', 'stav' => 'dv_stav', 'dispozice' => 'dv_dispozice',
        'podlazi' => 'dv_podlazi', 'podlazi_cislo' => 'dv_podlazi_cislo', 'budova' => 'dv_budova', 'umisteni' => 'dv_umisteni',
        'vyhled' => 'dv_vyhled', 'plocha' => 'dv_plocha', 'venkovni_typ' => 'dv_venkovni_typ', 'venkovni_plocha' => 'dv_venkovni_plocha',
        'cena' => 'dv_cena', 'cena_od' => 'dv_cena_od', 'rezervace_do' => 'dv_rezervace_do',
    ];
    foreach ($meta as $col => $key) {
        if (!array_key_exists($col, $set)) {
            continue;
        }
        $v = $set[$col];
        if ($v === '' || $v === 0 || $v === 0.0) {
            delete_post_meta($unit, $key);
        } else {
            update_post_meta($unit, $key, is_float($v) ? (string) $v : $v);
        }
    }
    if (array_key_exists('orientace', $set)) {
        $set['orientace'] ? update_post_meta($unit, 'dv_orientace', $set['orientace']) : delete_post_meta($unit, 'dv_orientace');
    }
    if (array_key_exists('cena_na_dotaz', $set)) {
        update_post_meta($unit, 'dv_cena_na_dotaz', $set['cena_na_dotaz'] ? 1 : 0);
    }
    $lists = [];
    if (array_key_exists('venkovni', $set)) {
        $lists['outdoors'] = $set['venkovni'];
    }
    if (array_key_exists('parametry', $set)) {
        $lists['params'] = $set['parametry'];
    }
    if ($lists) {
        save_unit_lists($unit, $lists);
    }
    if (array_key_exists('prislusenstvi', $set)) {
        save_unit_accessories($unit, $set['prislusenstvi']);
    }
}

/* ---------- Stránka Import / export ---------- */

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=' . PT_UNIT,
        __('Import / export', 'martin-dev-vyber-bytu'),
        __('Import / export', 'martin-dev-vyber-bytu'),
        csv_capability(),
        'martin-dv-import',
        __NAMESPACE__ . '\\import_page'
    );
});

function import_transient_key(): string
{
    return 'martin_dv_import_' . get_current_user_id();
}

/** Nahrání souboru → plán do transientu (30 min) → přesměrování na náhled. */
add_action('admin_post_martin_dv_import_upload', function () {
    if (!current_user_can(csv_capability())) {
        wp_die(esc_html__('Nemáš oprávnění.', 'martin-dev-vyber-bytu'), 403);
    }
    check_admin_referer('martin_dv_import_upload');
    $back = admin_url('edit.php?post_type=' . PT_UNIT . '&page=martin-dv-import');
    $file = $_FILES['soubor'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- kontrola níže
    if (!$file || !empty($file['error']) || !is_uploaded_file($file['tmp_name'])
        || strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        wp_safe_redirect(add_query_arg('chyba', 'soubor', $back));
        exit;
    }
    $data = csv_read((string) file_get_contents($file['tmp_name'])); // phpcs:ignore WordPress.WP.AlternativeFunctions
    if (is_wp_error($data)) {
        wp_safe_redirect(add_query_arg('chyba', $data->get_error_code(), $back));
        exit;
    }
    $kind = in_array('mistnost', $data['head'], true) ? 'mistnosti' : 'jednotky';
    if ($kind === 'jednotky' && !in_array('id', $data['head'], true) && !in_array('cislo', $data['head'], true)) {
        wp_safe_redirect(add_query_arg('chyba', 'sloupce', $back));
        exit;
    }
    $plan = $kind === 'mistnosti' ? csv_plan_rooms($data) : csv_plan_units($data);
    set_transient(import_transient_key(), ['kind' => $kind, 'plan' => $plan, 'file' => sanitize_file_name((string) $file['name'])], 30 * MINUTE_IN_SECONDS);
    wp_safe_redirect(add_query_arg('nahled', 1, $back));
    exit;
});

add_action('admin_post_martin_dv_import_apply', function () {
    if (!current_user_can(csv_capability())) {
        wp_die(esc_html__('Nemáš oprávnění.', 'martin-dev-vyber-bytu'), 403);
    }
    check_admin_referer('martin_dv_import_apply');
    $back  = admin_url('edit.php?post_type=' . PT_UNIT . '&page=martin-dv-import');
    $stash = get_transient(import_transient_key());
    delete_transient(import_transient_key());
    if (!is_array($stash) || empty($stash['plan'])) {
        wp_safe_redirect(add_query_arg('chyba', 'vyprselo', $back));
        exit;
    }
    $res = csv_apply($stash['kind'] === 'mistnosti' ? 'mistnosti' : 'jednotky', $stash['plan']);
    wp_safe_redirect(add_query_arg(['hotovo' => 1, 'nove' => $res['created'], 'zmeneno' => $res['updated'], 'preskoceno' => $res['skipped']], $back));
    exit;
});

function import_page(): void
{
    if (!current_user_can(csv_capability())) {
        return;
    }
    // phpcs:disable WordPress.Security.NonceVerification -- jen zobrazení stavu z přesměrování
    $errors = [
        'soubor'   => __('Vyber soubor .csv.', 'martin-dev-vyber-bytu'),
        'size'     => __('Soubor je větší než 2 MB.', 'martin-dev-vyber-bytu'),
        'empty'    => __('Soubor je prázdný.', 'martin-dev-vyber-bytu'),
        'sloupce'  => __('V souboru chybí sloupec id nebo cislo – použij soubor z exportu jako vzor.', 'martin-dev-vyber-bytu'),
        'vyprselo' => __('Náhled vypršel (30 minut). Nahraj soubor znovu.', 'martin-dev-vyber-bytu'),
    ];
    echo '<div class="wrap"><h1>' . esc_html__('Import / export jednotek', 'martin-dev-vyber-bytu') . '</h1>';
    if (isset($_GET['chyba'], $errors[$_GET['chyba']])) {
        echo '<div class="notice notice-error"><p>' . esc_html($errors[sanitize_key($_GET['chyba'])]) . '</p></div>';
    }
    if (isset($_GET['hotovo'])) {
        printf(
            '<div class="notice notice-success"><p>%s</p></div>',
            esc_html(sprintf(
                /* translators: 1: nové, 2: změněné, 3: přeskočené */
                __('Import hotový: nových %1$d, změněných %2$d, přeskočených (s chybou) %3$d.', 'martin-dev-vyber-bytu'),
                absint($_GET['nove'] ?? 0),
                absint($_GET['zmeneno'] ?? 0),
                absint($_GET['preskoceno'] ?? 0)
            ))
        );
    }
    if (isset($_GET['nahled'])) {
        import_preview();
        echo '</div>';
        return;
    }
    // phpcs:enable

    $projects = get_posts(['post_type' => PT_PROJECT, 'post_status' => ['publish', 'draft', 'private'], 'posts_per_page' => 200, 'no_found_rows' => true, 'orderby' => 'title', 'order' => 'ASC']);
    echo '<h2>' . esc_html__('Export', 'martin-dev-vyber-bytu') . '</h2>'
        . '<form method="get" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="martin_dv_export">';
    wp_nonce_field('martin_dv_export');
    echo '<p><select name="projekt"><option value="0">' . esc_html__('Všechny projekty', 'martin-dev-vyber-bytu') . '</option>';
    foreach ($projects as $p) {
        echo '<option value="' . (int) $p->ID . '">' . esc_html($p->post_title) . '</option>';
    }
    echo '</select> <select name="co"><option value="jednotky">' . esc_html__('Jednotky (1 řádek = 1 jednotka)', 'martin-dev-vyber-bytu') . '</option>'
        . '<option value="mistnosti">' . esc_html__('Místnosti (1 řádek = 1 místnost)', 'martin-dev-vyber-bytu') . '</option></select> ';
    submit_button(__('Stáhnout CSV', 'martin-dev-vyber-bytu'), 'secondary', 'submit', false);
    echo '</p></form>';

    echo '<h2>' . esc_html__('Import', 'martin-dev-vyber-bytu') . '</h2>'
        . '<ol class="description" style="max-width:820px">'
        . '<li>' . esc_html__('Stáhni export jako vzor a uprav ho v Excelu (ulož jako CSV UTF-8, oddělovač středník).', 'martin-dev-vyber-bytu') . '</li>'
        . '<li>' . esc_html__('Jednotka se najde podle sloupce id, jinak podle projektu + čísla; jinak se založí nová (jako koncept, pokud publikovano ≠ ano).', 'martin-dev-vyber-bytu') . '</li>'
        . '<li>' . esc_html__('Změní se jen sloupce, které v souboru jsou. Prázdná buňka hodnotu smaže (u názvu ne).', 'martin-dev-vyber-bytu') . '</li>'
        . '<li>' . esc_html__('Seznamy v jedné buňce oddělené svislítkem |: venkovni „Balkon: 4,2: J JZ | Terasa: 10“, parametry „Výška stropů = 2,7 m | PENB = B“, prislusenstvi „Garáž: G3: 18: 450000 | Nabíječka: : : v ceně“.', 'martin-dev-vyber-bytu') . '</li>'
        . '<li>' . esc_html__('Soubor místností (sloupec mistnost) nahradí všechny místnosti uvedených jednotek.', 'martin-dev-vyber-bytu') . '</li>'
        . '<li>' . esc_html__('Před importem uvidíš náhled všech změn a teprve pak je potvrdíš. Doporučení: před velkým importem zálohuj databázi.', 'martin-dev-vyber-bytu') . '</li>'
        . '</ol><form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="martin_dv_import_upload">';
    wp_nonce_field('martin_dv_import_upload');
    echo '<p><input type="file" name="soubor" accept=".csv,text/csv" required> ';
    submit_button(__('Nahrát a zobrazit náhled', 'martin-dev-vyber-bytu'), 'primary', 'submit', false);
    echo '</p></form></div>';
}

function import_preview(): void
{
    $stash = get_transient(import_transient_key());
    if (!is_array($stash) || empty($stash['plan'])) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Náhled vypršel nebo soubor neobsahoval žádné řádky.', 'martin-dev-vyber-bytu') . '</p></div>';
        return;
    }
    $plan = $stash['plan'];
    $ok   = count(array_filter($plan, function ($i) {
        return !$i['errors'];
    }));
    echo '<h2>' . esc_html(sprintf(
        /* translators: 1: název souboru, 2: počet položek, 3: počet bez chyb */
        __('Náhled importu %1$s – %2$d položek, %3$d bez chyb', 'martin-dev-vyber-bytu'),
        $stash['file'],
        count($plan),
        $ok
    )) . '</h2>';
    echo '<table class="widefat striped"><thead><tr><th>' . esc_html__('Řádek', 'martin-dev-vyber-bytu') . '</th><th>' . esc_html__('Jednotka', 'martin-dev-vyber-bytu') . '</th><th>' . esc_html__('Akce', 'martin-dev-vyber-bytu') . '</th><th>' . esc_html__('Změny', 'martin-dev-vyber-bytu') . '</th></tr></thead><tbody>';
    foreach ($plan as $i) {
        if ($i['errors']) {
            $act = '<strong style="color:#b32d2e">' . esc_html__('chyba – přeskočí se', 'martin-dev-vyber-bytu') . '</strong>';
        } elseif (!$i['unit']) {
            $act = '<strong style="color:#00703c">' . esc_html__('nová', 'martin-dev-vyber-bytu') . '</strong>';
        } elseif ($i['changes']) {
            $act = esc_html__('změna', 'martin-dev-vyber-bytu');
        } else {
            $act = '<span style="color:#757575">' . esc_html__('beze změny', 'martin-dev-vyber-bytu') . '</span>';
        }
        $ch = '';
        foreach ($i['changes'] as $col => [$from, $to]) {
            $ch .= '<div><code>' . esc_html($col) . '</code>: ' . ($i['unit'] ? '<del>' . esc_html($from !== '' ? $from : '—') . '</del> → ' : '') . esc_html($to !== '' ? $to : '—') . '</div>';
        }
        foreach ($i['errors'] as $e) {
            $ch .= '<div style="color:#b32d2e">⚠ ' . esc_html($e) . '</div>';
        }
        foreach ($i['warnings'] as $w) {
            $ch .= '<div style="color:#996800">• ' . esc_html($w) . '</div>';
        }
        echo '<tr><td>' . (int) $i['line'] . '</td><td>' . esc_html($i['label']) . ($i['unit'] ? ' <small>#' . (int) $i['unit'] . '</small>' : '') . '</td><td>' . $act . '</td><td>' . $ch . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $act a $ch složené z escapovaných částí
    }
    echo '</tbody></table>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin-top:16px"><input type="hidden" name="action" value="martin_dv_import_apply">';
    wp_nonce_field('martin_dv_import_apply');
    submit_button(__('Potvrdit a importovat', 'martin-dev-vyber-bytu'), 'primary', 'submit', false, $ok ? [] : ['disabled' => 'disabled']);
    echo ' <a class="button" href="' . esc_url(admin_url('edit.php?post_type=' . PT_UNIT . '&page=martin-dv-import')) . '">' . esc_html__('Zrušit', 'martin-dev-vyber-bytu') . '</a></form>';
}
