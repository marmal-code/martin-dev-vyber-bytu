#!/usr/bin/env python3
"""
Generátor elements/MapaOkoli/element.php (element „Marmal – Mapa okolí“).

Ovládací prvky jsou v jednom seznamu níže – element.php se NEUPRAVUJE ručně ani v Element Studiu,
změna = úprava tohoto skriptu a nové spuštění:  python3 tools/okoli_element.py
Používá jen ověřené typy prvků Breakdance 2.8: number, toggle, color, unit (i s breakpointy),
section a presety typography_with_effects_and_align, spacing_padding_all, borders, spacing_margin_y.
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / 'elements' / 'MapaOkoli' / 'element.php'


def q(s):
    return '"' + s.replace('\\', '\\\\').replace('"', '\\"') + '"'


def c(slug, label, children=None, opts="['type' => 'section']", responsive=False):
    kids = ', '.join(children) if children else ''
    return (f'c(\n        {q(slug)},\n        {q(label)},\n        [{kids}],\n        {opts},\n'
            f'        {"true" if responsive else "false"},\n        false,\n        [],\n      )')


def color(slug, label):
    return c(slug, label, None, "['type' => 'color', 'layout' => 'inline']")


def unit(slug, label, responsive=False):
    return c(slug, label, None, "['type' => 'unit', 'layout' => 'inline']", responsive)


def toggle(slug, label):
    return c(slug, label, None, "['type' => 'toggle', 'layout' => 'vertical']")


def number(slug, label, mn=1):
    return c(slug, label, None, f"['type' => 'number', 'layout' => 'vertical', 'rangeOptions' => ['min' => {mn}, 'step' => 1]]")


def preset(name, label, slug):
    return (f'getPresetSection(\n      "EssentialElements\\\\{name}",\n      {q(label)},\n      {q(slug)},\n'
            f"       ['type' => 'popout']\n     )")


def section(slug, label, children):
    return c(slug, label, children, "['type' => 'section']")


CONTENT = [
    c('mapa', 'Mapa okolí', [
        number('projekt_id', 'ID projektu'),
        number('mapa', 'Pořadí mapy v projektu (1 = první)'),
        toggle('seznam_vlevo', 'Seznam vlevo od mapy'),
        toggle('seznam_pod', 'Seznam pod mapou'),
        toggle('dva_sloupce', 'Seznam ve 2 sloupcích'),
        toggle('bez_ztlumeni', 'Neztlumovat ostatní body při najetí'),
    ], "['type' => 'section', 'layout' => 'vertical']"),
]

CATEGORIES = [
    ('skola', 'Školství'), ('obchod', 'Obchody'), ('gastro', 'Gastro'), ('zdravi', 'Zdraví'),
    ('sport', 'Sport'), ('priroda', 'Příroda'), ('doprava', 'Doprava'), ('sluzby', 'Služby'), ('kultura', 'Kultura'),
]

DESIGN = [
    c('container', 'Container', [
        unit('width', 'Width', True),
        unit('height', 'Height', True),
        c('background', 'Background', None, "['type' => 'color', 'layout' => 'inline', 'colorOptions' => ['type' => 'solidAndGradient']]"),
        preset('spacing_padding_all', 'Padding', 'padding'),
        preset('borders', 'Borders', 'borders'),
    ]),
    preset('typography_with_effects_and_align', 'Typography', 'typography'),
    preset('spacing_margin_y', 'Spacing', 'spacing'),
    section('znacky', 'Značky', [
        color('barva', 'Barva značek (prázdné = Brand barva)'),
        color('text', 'Barva čísla / ikony (prázdné = automaticky)'),
        unit('velikost', 'Velikost značek v mapě', True),
        color('obrys', 'Obrys aktivní značky'),
        color('stitek_pozadi', 'Pozadí čísla u ikony'),
        color('stitek_text', 'Barva čísla u ikony'),
    ]),
    section('hlavni_bod', 'Hlavní bod (projekt)', [
        color('barva', 'Barva hlavního bodu'),
        unit('velikost', 'Velikost hlavního bodu', True),
        toggle('bez_pulzu', 'Vypnout pulzování'),
    ]),
    section('kategorie', 'Barvy kategorií', [color(k, n) for k, n in CATEGORIES]),
    section('mapa', 'Mapa', [
        unit('zaobleni', 'Zaoblení rohů mapy'),
        unit('mezera', 'Mezera mezi mapou a seznamem', True),
        unit('sirka_seznamu', 'Šířka seznamu vedle mapy'),
    ]),
    section('bublina', 'Bublina', [
        color('pozadi', 'Pozadí'),
        color('text', 'Text'),
        unit('zaobleni', 'Zaoblení'),
    ]),
    section('seznam', 'Seznam', [
        preset('typography_with_effects_and_align', 'Název bodu', 'nazev'),
        preset('typography_with_effects_and_align', 'Popis bodu', 'popis'),
        color('vzdalenost', 'Barva vzdálenosti'),
        color('cary', 'Čáry mezi řádky'),
        color('zvyrazneni', 'Zvýraznění řádku po najetí'),
        unit('cislo', 'Velikost čísla v seznamu'),
    ]),
    section('filtr', 'Filtr kategorií', [
        preset('typography_with_effects_and_align', 'Písmo tlačítek', 'tlacitko'),
        color('pozadi', 'Pozadí tlačítka'),
        color('text', 'Text tlačítka'),
        color('ramecek', 'Rámeček tlačítka'),
        color('aktivni_pozadi', 'Pozadí aktivního tlačítka'),
        color('aktivni_text', 'Text aktivního tlačítka'),
        unit('mezera', 'Mezera pod filtrem'),
    ]),
]

TEMPLATE = '''<?php

namespace MartinDV;

use function Breakdance\\Elements\\c;
use function Breakdance\\Elements\\PresetSections\\getPresetSection;

/*
 * VYGENEROVÁNO skriptem tools/okoli_element.py – neupravovat ručně ani v Element Studiu.
 * Výstup elementu je v ssr.php (render_okoli()), vzhled v css.twig.
 */

\\Breakdance\\ElementStudio\\registerElementForEditing(
    "MartinDV\\\\MapaOkoli",
    \\Breakdance\\Util\\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class MapaOkoli extends \\Breakdance\\Elements\\Element
{
    static function uiIcon()
    {
        return 'BracketsIcon';
    }

    static function tag()
    {
        return 'div';
    }

    static function tagOptions()
    {
        return ['div', 'section', 'aside'];
    }

    static function tagControlPath()
    {
        return false;
    }

    static function name()
    {
        return 'Marmal – Mapa okolí';
    }

    static function className()
    {
        return 'martin-dv-okoli-element';
    }

    static function category()
    {
        return 'dynamic';
    }

    static function badge()
    {
        return false;
    }

    static function slug()
    {
        return __CLASS__;
    }

    static function template()
    {
        return file_get_contents(__DIR__ . '/html.twig');
    }

    static function defaultCss()
    {
        return file_get_contents(__DIR__ . '/default.css');
    }

    static function defaultProperties()
    {
        return false;
    }

    static function defaultChildren()
    {
        return false;
    }

    static function cssTemplate()
    {
        $template = file_get_contents(__DIR__ . '/css.twig');
        return $template;
    }

    static function designControls()
    {
        return [%DESIGN%];
    }

    static function contentControls()
    {
        return [%CONTENT%];
    }

    static function settingsControls()
    {
        return [];
    }

    static function dependencies()
    {
        return false;
    }

    static function settings()
    {
        return false;
    }

    static function addPanelRules()
    {
        return false;
    }

    static public function actions()
    {
        return false;
    }

    static function nestingRule()
    {
        return ['type' => 'final'];
    }

    static function spacingBars()
    {
        return [['location' => 'outside-top', 'cssProperty' => 'margin-top', 'affectedPropertyPath' => 'design.spacing.margin_top.%%BREAKPOINT%%'], ['location' => 'outside-bottom', 'cssProperty' => 'margin-bottom', 'affectedPropertyPath' => 'design.spacing.margin_bottom.%%BREAKPOINT%%']];
    }

    static function attributes()
    {
        return false;
    }

    static function experimental()
    {
        return false;
    }

    static function availableIn()
    {
        return ['breakdance', 'oxygen'];
    }

    static function order()
    {
        return 101;
    }

    static function dynamicPropertyPaths()
    {
        return false;
    }

    static function additionalClasses()
    {
        return false;
    }

    static function projectManagement()
    {
        return ['optionsWork' => 'yes', 'dynamicBehaviorWorks' => 'yes', 'optionsGood' => 'yes'];
    }

    static function propertyPathsToWhitelistInFlatProps()
    {
        return false;
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return ['content.mapa'];
    }
}
'''

if __name__ == '__main__':
    php = TEMPLATE.replace('%DESIGN%', ', '.join(DESIGN)).replace('%CONTENT%', ', '.join(CONTENT))
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(php, encoding='utf-8')
    print('Zapsáno', OUT.relative_to(ROOT))
