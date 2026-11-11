<?php

namespace MartinDV;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;

/*
 * VYGENEROVÁNO skriptem tools/okoli_element.py – neupravovat ručně ani v Element Studiu.
 * Výstup elementu je v ssr.php (render_okoli()), vzhled v css.twig.
 */

\Breakdance\ElementStudio\registerElementForEditing(
    "MartinDV\\MapaOkoli",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class MapaOkoli extends \Breakdance\Elements\Element
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
        return [c(
        "container",
        "Container",
        [c(
        "width",
        "Width",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "height",
        "Height",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "background",
        "Background",
        [],
        ['type' => 'color', 'layout' => 'inline', 'colorOptions' => ['type' => 'solidAndGradient']],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\spacing_padding_all",
      "Padding",
      "padding",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\borders",
      "Borders",
      "borders",
       ['type' => 'popout']
     )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Typography",
      "typography",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\spacing_margin_y",
      "Spacing",
      "spacing",
       ['type' => 'popout']
     ), c(
        "znacky",
        "Značky",
        [c(
        "barva",
        "Barva značek (prázdné = Brand barva)",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "text",
        "Barva čísla / ikony (prázdné = automaticky)",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "velikost",
        "Velikost značek v mapě",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "obrys",
        "Obrys aktivní značky",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "stitek_pozadi",
        "Pozadí čísla u ikony",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "stitek_text",
        "Barva čísla u ikony",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "hlavni_bod",
        "Hlavní bod (projekt)",
        [c(
        "barva",
        "Barva hlavního bodu",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "velikost",
        "Velikost hlavního bodu",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "bez_pulzu",
        "Vypnout pulzování",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "kategorie",
        "Barvy kategorií",
        [c(
        "skola",
        "Školství",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "obchod",
        "Obchody",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "gastro",
        "Gastro",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "zdravi",
        "Zdraví",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "sport",
        "Sport",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "priroda",
        "Příroda",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "doprava",
        "Doprava",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "sluzby",
        "Služby",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "kultura",
        "Kultura",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "mapa",
        "Mapa",
        [c(
        "zaobleni",
        "Zaoblení rohů mapy",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "mezera",
        "Mezera mezi mapou a seznamem",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "sirka_seznamu",
        "Šířka seznamu vedle mapy",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "bublina",
        "Bublina",
        [c(
        "pozadi",
        "Pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "text",
        "Text",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "zaobleni",
        "Zaoblení",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "seznam",
        "Seznam",
        [getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Název bodu",
      "nazev",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Popis bodu",
      "popis",
       ['type' => 'popout']
     ), c(
        "vzdalenost",
        "Barva vzdálenosti",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "cary",
        "Čáry mezi řádky",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "zvyrazneni",
        "Zvýraznění řádku po najetí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "cislo",
        "Velikost čísla v seznamu",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "filtr",
        "Filtr kategorií",
        [getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Písmo tlačítek",
      "tlacitko",
       ['type' => 'popout']
     ), c(
        "pozadi",
        "Pozadí tlačítka",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "text",
        "Text tlačítka",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "ramecek",
        "Rámeček tlačítka",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "aktivni_pozadi",
        "Pozadí aktivního tlačítka",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "aktivni_text",
        "Text aktivního tlačítka",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "mezera",
        "Mezera pod filtrem",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      )];
    }

    static function contentControls()
    {
        return [c(
        "mapa",
        "Mapa okolí",
        [c(
        "projekt_id",
        "ID projektu",
        [],
        ['type' => 'number', 'layout' => 'vertical', 'rangeOptions' => ['min' => 1, 'step' => 1]],
        false,
        false,
        [],
      ), c(
        "mapa",
        "Pořadí mapy v projektu (1 = první)",
        [],
        ['type' => 'number', 'layout' => 'vertical', 'rangeOptions' => ['min' => 1, 'step' => 1]],
        false,
        false,
        [],
      ), c(
        "seznam_vlevo",
        "Seznam vlevo od mapy",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "seznam_pod",
        "Seznam pod mapou",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "dva_sloupce",
        "Seznam ve 2 sloupcích",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "bez_ztlumeni",
        "Neztlumovat ostatní body při najetí",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'layout' => 'vertical'],
        false,
        false,
        [],
      )];
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
