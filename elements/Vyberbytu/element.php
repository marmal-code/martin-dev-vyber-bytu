<?php

namespace MartinDV;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "MartinDV\\Vyberbytu",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class Vyberbytu extends \Breakdance\Elements\Element
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
        return ['div', 'span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'footer', 'header', 'nav', 'aside'];
    }

    static function tagControlPath()
    {
        return false;
    }

    static function name()
    {
        return 'Marmal – Výběr bytů';
    }

    static function className()
    {
        return 'martin-dv-element';
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
        "uvod",
        "Úvod projektu",
        [getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Název projektu",
        "nazev",
        ['type' => 'popout']
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Popis projektu",
        "popis",
        ['type' => 'popout']
      ), c(
        "sirka",
        "Max. šířka popisu",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "mezera",
        "Mezera pod úvodem",
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
        "barvy",
        "Barvy",
        [c(
        "akcent",
        "Barva zvýraznění a volných",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "rezervovany",
        "Rezervované",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "prodany",
        "Prodané",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "text",
        "Barva textu",
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
        "oblasti",
        "Zvýraznění na obrázku",
        [c(
        "barva",
        "Barva zvýraznění pater",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "pruhlednost",
        "Výplň po najetí (%)",
        [],
        ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 0, 'max' => 100, 'step' => 1]],
        false,
        false,
        [],
      ), c(
        "obrys",
        "Barva obrysu po najetí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "obrys_sirka",
        "Tloušťka obrysu",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "jednotky_pruhlednost",
        "Výplň bytů v půdorysu (%)",
        [],
        ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 0, 'max' => 100, 'step' => 1]],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "nadpisy",
        "Nadpisy",
        [getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Nadpis pohledu / podlaží",
        "panel",
        ['type' => 'popout']
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Nadpis tabulky",
        "tabulka",
        ['type' => 'popout']
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "seznam",
        "Seznam vedle obrázku",
        [getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Text nápovědy",
        "napoveda",
        ['type' => 'popout']
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Název (např. 1. NP)",
        "nazev",
        ['type' => 'popout']
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Popis (počet jednotek, cena)",
        "popis",
        ['type' => 'popout']
      ), c(
        "pozadi",
        "Pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "ramecek",
        "Barva rámečku",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "zaobleni",
        "Zaoblení rohů",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "pozadi_hover",
        "Pozadí po najetí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "ramecek_hover",
        "Rámeček po najetí",
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
        "tlacitka",
        "Tlačítka podlaží a Zpět",
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
        "ramecek",
        "Rámeček",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "aktivni_pozadi",
        "Aktivní podlaží – pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "aktivni_text",
        "Aktivní podlaží – text",
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
        "karta",
        "Karta jednotky",
        [c(
        "pozadi",
        "Pozadí karty",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "ramecek",
        "Barva rámečku",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "zaobleni",
        "Zaoblení rohů",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Malý popisek nahoře",
        "popisek",
        ['type' => 'popout']
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Číslo jednotky / podlaží",
        "nadpis",
        ['type' => 'popout']
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Údaje a texty",
        "text",
        ['type' => 'popout']
      ), c(
        "tlacitko_pozadi",
        "Tlačítko – pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "tlacitko_text",
        "Tlačítko – text",
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
        "ramecek",
        "Barva rámečku",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "zaobleni",
        "Zaoblení rohů",
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
        "stavy",
        "Štítky stavu",
        [c(
        "volny_pozadi",
        "Volný – pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "volny_text",
        "Volný – text",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "rezervovany_pozadi",
        "Rezervovaný – pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "rezervovany_text",
        "Rezervovaný – text",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "prodany_pozadi",
        "Prodaný – pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "prodany_text",
        "Prodaný – text",
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
        "filtr",
        "Filtr tabulky",
        [c(
        "box_pozadi",
        "Rámeček filtru – pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "box_ramecek",
        "Rámeček filtru – čára",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "box_zaobleni",
        "Rámeček filtru – zaoblení",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Tlačítka – písmo",
        "tlacitko",
        ['type' => 'popout']
      ), c(
        "pozadi",
        "Tlačítka – pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "ramecek",
        "Tlačítka – rámeček",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "aktivni_pozadi",
        "Aktivní – pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "aktivni_text",
        "Aktivní – text",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "zaobleni",
        "Tlačítka – zaoblení",
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
        "tabulka",
        "Tabulka",
        [getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Záhlaví sloupců",
        "hlavicka",
        ['type' => 'popout']
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Buňky",
        "bunky",
        ['type' => 'popout']
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Jednotka (odkaz)",
        "jednotka",
        ['type' => 'popout']
      ), c(
        "jednotka_hover",
        "Jednotka – barva po najetí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "ramecek",
        "Barva čar a rámečku",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "radek_hover",
        "Řádek – pozadí po najetí",
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
      )];
    }

    static function contentControls()
    {
        return [c(
        "projekt",
        "Projekt",
        [c(
        "projekt_id",
        "ID projektu",
        [],
        ['type' => 'number', 'layout' => 'vertical', 'rangeOptions' => ['min' => 1, 'step' => 1]],
        false,
        false,
        [],
        
      ), c(
        "skryt_tabulku",
        "Skrýt tabulku jednotek",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
        
      ), c(
        "skryt_nazev",
        "Skrýt název projektu",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "skryt_popis",
        "Skrýt popis projektu",
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
        return 100;
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
        return ['content.projekt'];
    }
}
