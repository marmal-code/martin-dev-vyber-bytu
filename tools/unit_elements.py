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
