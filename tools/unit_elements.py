#!/usr/bin/env python3
"""
Generátor Breakdance elementů pro detail jednotky (od 0.14.0):
  elements/Mistnosti/      „Marmal – Tabulka místností“
  elements/Parametry/      „Marmal – Parametry jednotky“
  elements/SchemaPodlazi/  „Marmal – Schéma podlaží“

Vychází ze šablony a pomocných funkcí ověřeného elementu Mapa okolí (tools/okoli_element.py),
používá jen ověřené typy prvků Breakdance 2.8 (number, toggle, color, unit, section, presety).
element.php se NEUPRAVUJE ručně ani v Element Studiu – změna = úprava tohoto skriptu a nové spuštění:
    python3 tools/unit_elements.py
ssr.php, css.twig a default.css se generují jen poprvé (když neexistují) – pak se upravují ručně.
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
import okoli_element as base  # noqa: E402
from okoli_element import c, color, unit, toggle, number, preset, section  # noqa: E402

ROOT = base.ROOT


def text(slug, label):
    # Typ „text“ je ověřený v Breakdance 2.8 (viz katalog).
    return c(slug, label, None, "['type' => 'text', 'layout' => 'vertical']")


def container():
    return c('container', 'Container', [
        unit('width', 'Width', True),
        c('background', 'Background', None, "['type' => 'color', 'layout' => 'inline', 'colorOptions' => ['type' => 'solidAndGradient']]"),
        preset('spacing_padding_all', 'Padding', 'padding'),
        preset('borders', 'Borders', 'borders'),
    ])


def common_design(extra):
    return [
        container(),
        preset('typography_with_effects_and_align', 'Typography', 'typography'),
        preset('spacing_margin_y', 'Spacing', 'spacing'),
    ] + extra


ID_HELP = 'ID jednotky (prázdné = aktuální jednotka)'

ELEMENTS = {
    'Mistnosti': {
        'name': 'Marmal – Tabulka místností',
        'class': 'martin-dv-mistnosti-element',
        'section': 'mistnosti',
        'order': 102,
        'content': [
            c('mistnosti', 'Tabulka místností', [
                number('jednotka_id', ID_HELP, 0),
                toggle('skryt_venkovni', 'Skrýt venkovní plochy'),
                toggle('skryt_poznamky', 'Skrýt poznámky u místností'),
                toggle('skryt_soucet', 'Skrýt součty'),
            ], "['type' => 'section', 'layout' => 'vertical']"),
        ],
        'design': common_design([
            section('vzhled', 'Tabulka', [
                preset('typography_with_effects_and_align', 'Písmo součtu', 'soucet_pismo'),
                color('cary', 'Čáry mezi řádky'),
                color('soucet', 'Čára nad součtem (prázdné = Brand)'),
                color('popisky', 'Poznámky a záhlaví'),
                unit('odsazeni', 'Výška řádku (odsazení nahoře a dole)', True),
            ]),
        ]),
    },
    'Parametry': {
        'name': 'Marmal – Parametry jednotky',
        'class': 'martin-dv-parametry-element',
        'section': 'parametry',
        'order': 103,
        'content': [
            c('parametry', 'Parametry jednotky', [
                number('jednotka_id', ID_HELP, 0),
                toggle('skryt_zakladni', 'Skrýt základní údaje (dispozice, plocha, podlaží…)'),
                toggle('skryt_vlastni', 'Skrýt vlastní parametry'),
                toggle('zobrazit_cenu', 'Zobrazit stav a cenu'),
                toggle('dva_sloupce', 'Ve dvou sloupcích (na mobilu jeden)'),
            ], "['type' => 'section', 'layout' => 'vertical']"),
        ],
        'design': common_design([
            section('vzhled', 'Řádky', [
                preset('typography_with_effects_and_align', 'Písmo názvu', 'nazev'),
                preset('typography_with_effects_and_align', 'Písmo hodnoty', 'hodnota'),
                color('cary', 'Čáry mezi řádky'),
                unit('odsazeni', 'Výška řádku (odsazení nahoře a dole)', True),
                unit('mezera', 'Mezera mezi sloupci'),
            ]),
        ]),
    },
    'SchemaPodlazi': {
        'name': 'Marmal – Schéma podlaží',
        'class': 'martin-dv-schema-element',
        'section': 'schema',
        'order': 104,
        'content': [
            c('schema', 'Schéma podlaží', [
                number('jednotka_id', ID_HELP, 0),
                toggle('skryt_nazev', 'Skrýt název podlaží pod obrázkem'),
                toggle('skryt_ostatni', 'Skrýt obrysy ostatních jednotek'),
            ], "['type' => 'section', 'layout' => 'vertical']"),
        ],
        'design': common_design([
            section('vzhled', 'Schéma', [
                color('jednotka', 'Barva jednotky (prázdné = Brand)'),
                number('sytost', 'Sytost výplně jednotky v % (1–100, prázdné = 45)', 1),
                color('ostatni', 'Výplň ostatních jednotek'),
                color('ostatni_obrys', 'Obrys ostatních jednotek'),
                unit('zaobleni', 'Zaoblení rohů obrázku'),
                color('popisek', 'Barva názvu podlaží'),
            ]),
        ]),
    },
    'StitekStavu': {
        'name': 'Marmal – Štítek stavu',
        'class': 'martin-dv-stitek-element',
        'section': 'stitek',
        'order': 105,
        'content': [
            c('stitek', 'Štítek stavu', [
                number('jednotka_id', ID_HELP, 0),
                toggle('plny', 'Plné pozadí v barvě stavu (bílý text)'),
            ], "['type' => 'section', 'layout' => 'vertical']"),
        ],
        'design': common_design([
            section('vzhled', 'Štítek', [
                preset('spacing_padding_all', 'Odsazení', 'odsazeni'),
                unit('zaobleni', 'Zaoblení'),
                color('pozadi', 'Pozadí (prázdné = podle stavu)'),
                color('text', 'Text (prázdné = podle stavu)'),
            ]),
        ]),
    },
    'Dokumenty': {
        'name': 'Marmal – Dokumenty',  # od 0.16.0 i dokumenty jednotky
        'class': 'martin-dv-dokumenty-element',
        'section': 'dokumenty',
        'order': 106,
        'content': [
            c('dokumenty', 'Dokumenty', [
                number('projekt_id', 'ID projektu (prázdné = projekt jednotky / výchozí)', 0),
                toggle('skryt_jednotky', 'Skrýt dokumenty jednotky (karta jednotky…)'),
                toggle('skryt_projektu', 'Skrýt dokumenty projektu'),
                toggle('skryt_info', 'Skrýt typ a velikost souboru'),
                toggle('stejne_okno', 'Otevírat ve stejném okně'),
            ], "['type' => 'section', 'layout' => 'vertical']"),
        ],
        'design': common_design([
            section('vzhled', 'Položky', [
                preset('typography_with_effects_and_align', 'Písmo názvu', 'nazev'),
                preset('spacing_padding_all', 'Odsazení položky', 'odsazeni'),
                unit('mezera', 'Mezera mezi položkami', True),
                unit('zaobleni', 'Zaoblení'),
                color('pozadi', 'Pozadí'),
                color('pozadi_hover', 'Pozadí po najetí'),
                color('ramecek', 'Rámeček'),
                color('zvyrazneni', 'Rámeček po najetí a ikona (prázdné = Brand)'),
                color('info', 'Typ a velikost souboru'),
            ]),
        ]),
    },
    'Prislusenstvi': {
        'name': 'Marmal – Příslušenství',
        'class': 'martin-dv-prislusenstvi-element',
        'section': 'prislusenstvi',
        'order': 107,
        'content': [
            c('prislusenstvi', 'Příslušenství', [
                number('jednotka_id', ID_HELP, 0),
                toggle('skryt_plochu', 'Skrýt plochu'),
                toggle('skryt_cenu', 'Skrýt cenu'),
                toggle('bez_odkazu', 'Bez odkazu na samostatnou jednotku (sklep, garáž)'),
            ], "['type' => 'section', 'layout' => 'vertical']"),
        ],
        'design': common_design([
            section('vzhled', 'Řádky', [
                preset('typography_with_effects_and_align', 'Písmo ceny', 'cena_pismo'),
                color('cary', 'Čáry mezi řádky'),
                color('plocha', 'Barva plochy'),
                unit('odsazeni', 'Výška řádku (odsazení nahoře a dole)', True),
            ]),
        ]),
    },
    'PredchoziDalsi': {
        'name': 'Marmal – Předchozí / další jednotka',
        'class': 'martin-dv-navigace-element',
        'section': 'navigace',
        'order': 108,
        'content': [
            c('navigace', 'Předchozí / další', [
                number('jednotka_id', ID_HELP, 0),
                text('text_predchozi', 'Text „Předchozí“ (prázdné = Předchozí)'),
                text('text_dalsi', 'Text „Další“ (prázdné = Další)'),
                toggle('skryt_texty', 'Skrýt texty Předchozí / Další'),
            ], "['type' => 'section', 'layout' => 'vertical']"),
        ],
        'design': common_design([
            section('vzhled', 'Odkazy', [
                preset('typography_with_effects_and_align', 'Písmo označení', 'nazev'),
                preset('spacing_padding_all', 'Odsazení', 'odsazeni'),
                unit('mezera', 'Mezera mezi odkazy'),
                unit('zaobleni', 'Zaoblení'),
                color('pozadi', 'Pozadí'),
                color('pozadi_hover', 'Pozadí po najetí'),
                color('ramecek', 'Rámeček'),
                color('zvyrazneni', 'Rámeček po najetí a text Předchozí / Další (prázdné = Brand)'),
                color('popis', 'Barva dispozice a plochy'),
            ]),
        ]),
    },
    'PodobneJednotky': {
        'name': 'Marmal – Podobné jednotky',
        'class': 'martin-dv-podobne-element',
        'section': 'podobne',
        'order': 109,
        'content': [
            c('podobne', 'Podobné jednotky', [
                number('jednotka_id', ID_HELP, 0),
                number('pocet', 'Počet jednotek (prázdné = 3)', 1),
                number('sloupce', 'Sloupce na počítači 1–4 (prázdné = 3)', 1),
                toggle('jen_shoda', 'Jen se stejnou dispozicí nebo podlažím'),
                toggle('s_prodanymi', 'Zobrazit i prodané'),
                toggle('bez_obrazku', 'Bez obrázku'),
                toggle('bez_ceny', 'Bez ceny'),
            ], "['type' => 'section', 'layout' => 'vertical']"),
        ],
        'design': common_design([
            section('vzhled', 'Karty', [
                preset('typography_with_effects_and_align', 'Písmo označení', 'nazev'),
                preset('typography_with_effects_and_align', 'Písmo ceny', 'cena'),
                unit('mezera', 'Mezera mezi kartami', True),
                unit('zaobleni', 'Zaoblení karty'),
                preset('spacing_padding_all', 'Odsazení textu', 'odsazeni'),
                color('pozadi', 'Pozadí karty'),
                color('ramecek', 'Rámeček'),
                color('zvyrazneni', 'Rámeček po najetí (prázdné = Brand)'),
                color('popis', 'Barva dispozice, plochy a podlaží'),
            ]),
        ]),
    },
}

SSR = {
    'Mistnosti': '''<?php
/**
 * Server-side render elementu „Tabulka místností“ (MartinDV\\Mistnosti).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\\MartinDV\\render_rooms')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['mistnosti'] ?? [];
$martin_dv_html = \\MartinDV\\render_rooms(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'hide_outdoor' => !empty($martin_dv_c['skryt_venkovni']),
    'hide_notes'   => !empty($martin_dv_c['skryt_poznamky']),
    'hide_sum'     => !empty($martin_dv_c['skryt_soucet']),
]);
// render_rooms() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
''',
    'Parametry': '''<?php
/**
 * Server-side render elementu „Parametry jednotky“ (MartinDV\\Parametry).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\\MartinDV\\render_params')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['parametry'] ?? [];
$martin_dv_html = \\MartinDV\\render_params(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'hide_basic'  => !empty($martin_dv_c['skryt_zakladni']),
    'hide_custom' => !empty($martin_dv_c['skryt_vlastni']),
    'show_price'  => !empty($martin_dv_c['zobrazit_cenu']),
    'cols'        => !empty($martin_dv_c['dva_sloupce']) ? 2 : 1,
]);
// render_params() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
''',
    'SchemaPodlazi': '''<?php
/**
 * Server-side render elementu „Schéma podlaží“ (MartinDV\\SchemaPodlazi).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\\MartinDV\\render_schema')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['schema'] ?? [];
$martin_dv_html = \\MartinDV\\render_schema(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'hide_caption' => !empty($martin_dv_c['skryt_nazev']),
    'hide_others'  => !empty($martin_dv_c['skryt_ostatni']),
]);
// render_schema() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr), obrázek přes wp_get_attachment_image().
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
''',
    'StitekStavu': '''<?php
/**
 * Server-side render elementu „Štítek stavu“ (MartinDV\\StitekStavu).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\\MartinDV\\render_status_badge')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['stitek'] ?? [];
$martin_dv_html = \\MartinDV\\render_status_badge(absint($martin_dv_c['jednotka_id'] ?? 0));
if (!empty($martin_dv_c['plny'])) {
    $martin_dv_html = str_replace('class="martin-dv-badge"', 'class="martin-dv-badge martin-dv-badge--solid"', $martin_dv_html);
}
// render_status_badge() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
''',
    'Dokumenty': '''<?php
/**
 * Server-side render elementu „Dokumenty“ (MartinDV\\Dokumenty).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\\MartinDV\\render_documents')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['dokumenty'] ?? [];
$martin_dv_html = \\MartinDV\\render_documents(absint($martin_dv_c['projekt_id'] ?? 0), [
    'hide_unit'    => !empty($martin_dv_c['skryt_jednotky']),
    'hide_project' => !empty($martin_dv_c['skryt_projektu']),
    'hide_meta'    => !empty($martin_dv_c['skryt_info']),
    'same_tab'     => !empty($martin_dv_c['stejne_okno']),
]);
// render_documents() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
''',
    'Prislusenstvi': '''<?php
/**
 * Server-side render elementu „Příslušenství“ (MartinDV\\Prislusenstvi) – od 0.17.0.
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\\MartinDV\\render_accessories')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['prislusenstvi'] ?? [];
$martin_dv_html = \\MartinDV\\render_accessories(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'hide_area'  => !empty($martin_dv_c['skryt_plochu']),
    'hide_price' => !empty($martin_dv_c['skryt_cenu']),
    'no_links'   => !empty($martin_dv_c['bez_odkazu']),
]);
// Funkce vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
''',
    'PredchoziDalsi': '''<?php
/**
 * Server-side render elementu „Předchozí / další jednotka“ (MartinDV\\PredchoziDalsi) – od 0.17.0.
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\\MartinDV\\render_prev_next')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['navigace'] ?? [];
$martin_dv_html = \\MartinDV\\render_prev_next(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'prev_text' => sanitize_text_field((string) ($martin_dv_c['text_predchozi'] ?? '')),
    'next_text' => sanitize_text_field((string) ($martin_dv_c['text_dalsi'] ?? '')),
    'hide_dir'  => !empty($martin_dv_c['skryt_texty']),
]);
// Funkce vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
''',
    'PodobneJednotky': '''<?php
/**
 * Server-side render elementu „Podobné jednotky“ (MartinDV\\PodobneJednotky) – od 0.17.0.
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * @var array $propertiesData
 */

if (!function_exists('\\MartinDV\\render_similar')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_c = $propertiesData['content']['podobne'] ?? [];
$martin_dv_html = \\MartinDV\\render_similar(absint($martin_dv_c['jednotka_id'] ?? 0), [
    'count'      => absint($martin_dv_c['pocet'] ?? 0) ?: 3,
    'cols'       => absint($martin_dv_c['sloupce'] ?? 0) ?: 3,
    'only_match' => !empty($martin_dv_c['jen_shoda']),
    'with_sold'  => !empty($martin_dv_c['s_prodanymi']),
    'hide_image' => !empty($martin_dv_c['bez_obrazku']),
    'hide_price' => !empty($martin_dv_c['bez_ceny']),
]);
// Funkce vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo $martin_dv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
''',
}

CONTAINER_CSS = '''%%SELECTOR%% {
  width: {{ design.container.width.style }};
  {{ macros.backgroundColor(design.container.background) }}
  {{ macros.borders(design.container.borders) }}
  {{ macros.spacing_padding_all(design.container.padding) }}
  {{ macros.typography(design.typography, globalSettings)  }}
  {{ macros.spacing_margin_y(design.spacing) }}
}
'''

CSS = {
    'Mistnosti': CONTAINER_CSS + '''
{# Vzhled přes CSS proměnné (výchozí hodnoty v assets/front/jednotka.css; prázdný prvek nic nemění) #}
%%SELECTOR%% .martin-dv-rooms {
  {% if design.vzhled.cary %}--mdj-line: {{ design.vzhled.cary }};{% endif %}
  {% if design.vzhled.soucet %}--mdj-sum-line: {{ design.vzhled.soucet }};{% endif %}
  {% if design.vzhled.popisky %}--mdj-muted: {{ design.vzhled.popisky }};{% endif %}
  {% if design.vzhled.odsazeni.style %}--mdj-row-pad: {{ design.vzhled.odsazeni.style }};{% endif %}
}
%%SELECTOR%% .martin-dv-rooms .martin-dv-rooms__sum th,
%%SELECTOR%% .martin-dv-rooms .martin-dv-rooms__sum td {
  {{ macros.typography(design.vzhled.soucet_pismo, globalSettings) }}
}
''',
    'Parametry': CONTAINER_CSS + '''
{# Vzhled přes CSS proměnné (výchozí hodnoty v assets/front/jednotka.css; prázdný prvek nic nemění) #}
%%SELECTOR%% .martin-dv-params {
  {% if design.vzhled.cary %}--mdj-line: {{ design.vzhled.cary }};{% endif %}
  {% if design.vzhled.odsazeni.style %}--mdj-row-pad: {{ design.vzhled.odsazeni.style }};{% endif %}
  {% if design.vzhled.mezera.style %}column-gap: {{ design.vzhled.mezera.style }};{% endif %}
}
%%SELECTOR%% .martin-dv-params .martin-dv-params__label {
  {{ macros.typography(design.vzhled.nazev, globalSettings) }}
}
%%SELECTOR%% .martin-dv-params .martin-dv-params__value {
  {{ macros.typography(design.vzhled.hodnota, globalSettings) }}
}
''',
    'SchemaPodlazi': CONTAINER_CSS + '''
{# Vzhled přes CSS proměnné (výchozí hodnoty v assets/front/jednotka.css; prázdný prvek nic nemění) #}
%%SELECTOR%% .martin-dv-schema {
  {% if design.vzhled.jednotka %}--mdj-accent: {{ design.vzhled.jednotka }};{% endif %}
  {% if design.vzhled.sytost %}--mdj-fill: {{ design.vzhled.sytost }}%;{% endif %}
  {% if design.vzhled.ostatni %}--mdj-other: {{ design.vzhled.ostatni }};{% endif %}
  {% if design.vzhled.ostatni_obrys %}--mdj-other-line: {{ design.vzhled.ostatni_obrys }};{% endif %}
  {% if design.vzhled.zaobleni.style %}--mdj-radius: {{ design.vzhled.zaobleni.style }};{% endif %}
  {% if design.vzhled.popisek %}--mdj-muted: {{ design.vzhled.popisek }};{% endif %}
}
''',
    'StitekStavu': CONTAINER_CSS + '''
{# Vzhled štítku (výchozí hodnoty v assets/front/jednotka.css; prázdný prvek nic nemění) #}
%%SELECTOR%% .martin-dv-badge {
  {{ macros.spacing_padding_all(design.vzhled.odsazeni) }}
  {% if design.vzhled.zaobleni.style %}border-radius: {{ design.vzhled.zaobleni.style }};{% endif %}
  {% if design.vzhled.pozadi %}background: {{ design.vzhled.pozadi }};{% endif %}
  {% if design.vzhled.text %}color: {{ design.vzhled.text }};{% endif %}
}
''',
    'Dokumenty': CONTAINER_CSS + '''
{# Vzhled přes CSS proměnné (výchozí hodnoty v assets/front/jednotka.css; prázdný prvek nic nemění) #}
%%SELECTOR%% .martin-dv-docs {
  {% if design.vzhled.mezera.style %}--mdj-docs-gap: {{ design.vzhled.mezera.style }};{% endif %}
  {% if design.vzhled.zaobleni.style %}--mdj-docs-radius: {{ design.vzhled.zaobleni.style }};{% endif %}
  {% if design.vzhled.pozadi %}--mdj-docs-bg: {{ design.vzhled.pozadi }};{% endif %}
  {% if design.vzhled.pozadi_hover %}--mdj-docs-bg-hover: {{ design.vzhled.pozadi_hover }};{% endif %}
  {% if design.vzhled.ramecek %}--mdj-line: {{ design.vzhled.ramecek }};{% endif %}
  {% if design.vzhled.zvyrazneni %}--mdj-accent: {{ design.vzhled.zvyrazneni }};{% endif %}
  {% if design.vzhled.info %}--mdj-muted: {{ design.vzhled.info }};{% endif %}
}
%%SELECTOR%% .martin-dv-docs .martin-dv-docs__link {
  {{ macros.spacing_padding_all(design.vzhled.odsazeni) }}
}
%%SELECTOR%% .martin-dv-docs .martin-dv-docs__name {
  {{ macros.typography(design.vzhled.nazev, globalSettings) }}
}
''',
    'Prislusenstvi': CONTAINER_CSS + '''
{# Vzhled přes CSS proměnné (výchozí hodnoty v assets/front/jednotka.css; prázdný prvek nic nemění) #}
%%SELECTOR%% .martin-dv-acc {
  {% if design.vzhled.cary %}--mdj-line: {{ design.vzhled.cary }};{% endif %}
  {% if design.vzhled.plocha %}--mdj-muted: {{ design.vzhled.plocha }};{% endif %}
  {% if design.vzhled.odsazeni.style %}--mdj-row-pad: {{ design.vzhled.odsazeni.style }};{% endif %}
}
%%SELECTOR%% .martin-dv-acc .martin-dv-acc__price {
  {{ macros.typography(design.vzhled.cena_pismo, globalSettings) }}
}
''',
    'PredchoziDalsi': CONTAINER_CSS + '''
{# Vzhled přes CSS proměnné (výchozí hodnoty v assets/front/jednotka.css; prázdný prvek nic nemění) #}
%%SELECTOR%% .martin-dv-nav {
  {% if design.vzhled.mezera.style %}--mdj-nav-gap: {{ design.vzhled.mezera.style }};{% endif %}
  {% if design.vzhled.zaobleni.style %}--mdj-nav-radius: {{ design.vzhled.zaobleni.style }};{% endif %}
  {% if design.vzhled.pozadi %}--mdj-nav-bg: {{ design.vzhled.pozadi }};{% endif %}
  {% if design.vzhled.pozadi_hover %}--mdj-nav-bg-hover: {{ design.vzhled.pozadi_hover }};{% endif %}
  {% if design.vzhled.ramecek %}--mdj-line: {{ design.vzhled.ramecek }};{% endif %}
  {% if design.vzhled.zvyrazneni %}--mdj-accent: {{ design.vzhled.zvyrazneni }};{% endif %}
  {% if design.vzhled.popis %}--mdj-muted: {{ design.vzhled.popis }};{% endif %}
}
%%SELECTOR%% .martin-dv-nav a {
  {{ macros.spacing_padding_all(design.vzhled.odsazeni) }}
}
%%SELECTOR%% .martin-dv-nav .martin-dv-nav__label {
  {{ macros.typography(design.vzhled.nazev, globalSettings) }}
}
''',
    'PodobneJednotky': CONTAINER_CSS + '''
{# Vzhled přes CSS proměnné (výchozí hodnoty v assets/front/jednotka.css; prázdný prvek nic nemění) #}
%%SELECTOR%% .martin-dv-similar {
  {% if design.vzhled.mezera.style %}--mdj-sim-gap: {{ design.vzhled.mezera.style }};{% endif %}
  {% if design.vzhled.zaobleni.style %}--mdj-sim-radius: {{ design.vzhled.zaobleni.style }};{% endif %}
  {% if design.vzhled.pozadi %}--mdj-sim-bg: {{ design.vzhled.pozadi }};{% endif %}
  {% if design.vzhled.ramecek %}--mdj-line: {{ design.vzhled.ramecek }};{% endif %}
  {% if design.vzhled.zvyrazneni %}--mdj-accent: {{ design.vzhled.zvyrazneni }};{% endif %}
  {% if design.vzhled.popis %}--mdj-muted: {{ design.vzhled.popis }};{% endif %}
}
%%SELECTOR%% .martin-dv-similar .martin-dv-similar__body {
  {{ macros.spacing_padding_all(design.vzhled.odsazeni) }}
}
%%SELECTOR%% .martin-dv-similar .martin-dv-similar__title {
  {{ macros.typography(design.vzhled.nazev, globalSettings) }}
}
%%SELECTOR%% .martin-dv-similar .martin-dv-similar__price {
  {{ macros.typography(design.vzhled.cena, globalSettings) }}
}
''',
}


def build(cls, spec):
    php = base.TEMPLATE.replace('%DESIGN%', ', '.join(spec['design'])).replace('%CONTENT%', ', '.join(spec['content']))
    php = (php.replace('tools/okoli_element.py', 'tools/unit_elements.py')
              .replace('Výstup elementu je v ssr.php (render_okoli())', 'Výstup elementu je v ssr.php (includes/jednotka/render.php)')
              .replace('MapaOkoli', cls)
              .replace('Marmal – Mapa okolí', spec['name'])
              .replace('martin-dv-okoli-element', spec['class'])
              .replace("return ['content.mapa'];", "return ['content." + spec['section'] + "'];")
              .replace('return 101;', 'return ' + str(spec['order']) + ';'))
    folder = ROOT / 'elements' / cls
    folder.mkdir(parents=True, exist_ok=True)
    (folder / 'element.php').write_text(php, encoding='utf-8')
    defaults = {
        'ssr.php': SSR[cls],
        'css.twig': CSS[cls],
        'default.css': '.' + spec['class'] + ' {\n  max-width: 100%;\n  width: 100%;\n}\n',
        'html.twig': '%%SSR%%',
    }
    for name, content in defaults.items():
        f = folder / name
        if not f.exists():
            f.write_text(content, encoding='utf-8')
    print('Zapsáno', folder.relative_to(ROOT))


if __name__ == '__main__':
    for cls, spec in ELEMENTS.items():
        build(cls, spec)
