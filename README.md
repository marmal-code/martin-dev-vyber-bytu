# Marmal – Developerské projekty

> Do verze 0.12.0 „Marmal – Výběr bytů“. Složka, repozitář, text domain, prefixy `martin_dv_*` / `MartinDV` a element „Marmal – Výběr bytů“ zůstávají beze změny.

Autor: Martin Malý – [marmal.cz](https://marmal.cz)

WordPress plugin pro weby developerských projektů. Obsahuje dvě funkce: **výběr jednotek** a **mapu okolí**.

### Výběr jednotek

Návštěvník vybírá na fotce nebo vizualizaci domu podlaží, v půdorysu podlaží pak jednotku (byt, ateliér, sklep, garáž, parkovací stání) a klikem přejde na její detail.

- **Jednotky** jsou CPT `martin_dv_jednotka` s vlastní URL (`/byty/…`) a ACF poli (`dv_cislo`, `dv_stav`, `dv_cena` …). Detail se staví Breakdance šablonou s dynamickými daty.
- **Projekty** (CPT `martin_dv_projekt`) obsahují pohledy (dům, areál, strany budovy) a podlaží s obkreslenými oblastmi.
- Víc budov a pohledů: oblast v pohledu může vést na podlaží nebo na další pohled.
- Mezonety: stejnou jednotku lze obkreslit ve více podlažích.
- Barvy a písmo se berou z Breakdance Global Settings.
- Výstup: Breakdance element **Marmal – Výběr bytů** (Add → Dynamic, slug `MartinDV\Vyberbytu`; vzniklo v Element Studiu, viz `docs/breakdance-element.md`) nebo shortcode `[martin_vyber_bytu id="123"]` (volitelně `jen_tabulka="ano" tabulka="ne" nazev="ne" popis="ne"`).

### Mapa okolí (od 0.13.0)

Stylizovaný obrázek mapy s očíslovanými body a seznamem vedle (nebo pod) mapou. Najetí na bod zvýrazní řádek a naopak, bod ukáže bublinu s názvem.

- Zadává se v projektu → **Okolí** → Přidat mapu okolí: obrázek mapy, body kliknutím do mapy (tažením se posunou), pořadí v seznamu = čísla 01, 02…, hlavní bod projektu (pulzuje).
- U bodu: název, kategorie, ikona (pevná sada ~20 ikon), nepovinně popis, vzdálenost a odkaz.
- Volby mapy: značky Čísla / Ikony / Ikona + číslo, barvy podle kategorie, filtr kategorií.
- Výstup: Breakdance element **Marmal – Mapa okolí** (Add → Dynamic, slug `MartinDV\MapaOkoli`) nebo shortcode `[martin_mapa_okoli id="123" mapa="1"]` (volitelně `rozlozeni="vpravo|vlevo|pod" sloupce="2" ztlumit="ne"`).
- Kód je v `includes/okoli/`, `assets/*/okoli*`, `elements/MapaOkoli/`. Ovládací prvky elementu generuje `tools/okoli_element.py` – `element.php` neupravovat ručně ani v Element Studiu.
- Podklad mapy neobkreslovat z Google Map (licence). Použít OpenStreetMap a uvést „© přispěvatelé OpenStreetMap“.

### Údaje jednotky na detailu (od 0.14.0)

- `dv_plocha` = **celková plocha** jednotky včetně příček a zdí. Součet místností je kontrolní – smí být stejný nebo menší.
- U jednotky box **Místnosti, venkovní plochy a parametry** (vlastní editor, nepotřebuje ACF Pro): místnosti (název, plocha, obytná / vedlejší, „započítat“, poznámka) se součtem a upozorněním, když je součet větší než celková plocha (tolerance 0,1 m²); venkovní plochy (typ, plocha, orientace); vlastní parametry (název + hodnota). Ukládá se jako JSON do `_dv_mistnosti`, `_dv_venkovni`, `_dv_parametry`.
- **Poloha v domě** (ACF free): `dv_budova` (výběr ze seznamu budov v projektu), `dv_podlazi_cislo`, `dv_umisteni`, `dv_orientace`, `dv_vyhled`. Číslo podlaží se bez vyplnění vezme z názvu podlaží, ve kterém je jednotka obkreslená.
- V projektu → Nastavení zobrazení → **Údaje jednotek**: název celkové plochy (výchozí Celková plocha), název součtu místností (Podlahová / Užitná …) a seznam budov / vchodů.
- Vyplněný seznam venkovních ploch má přednost před starým polem `dv_venkovni_*` (to zůstává pro jednoduché případy).
- Výstup (detail jednotky): elementy **Marmal – Tabulka místností**, **Marmal – Parametry jednotky**, **Marmal – Schéma podlaží** (Add → Dynamic) nebo shortcody `[martin_dv_mistnosti]`, `[martin_dv_parametry]` (`cena="ano" sloupce="2" zakladni="ne" vlastni="ne"`), `[martin_dv_schema]` (`nazev="ne" ostatni="ne"`). Bez `id` berou aktuální jednotku.
- Kód je v `includes/jednotka/`, `assets/admin/jednotka-editor.*`, `assets/front/jednotka.css`, `elements/Mistnosti|Parametry|SchemaPodlazi/`. Ovládací prvky elementů generuje `tools/unit_elements.py`.

### Společné

- Řetězce jsou připravené k překladu (text domain `martin-dev-vyber-bytu`, např. Loco Translate). Podpora Polylang/WPML pro jednotky.

## Požadavky

- WordPress 6.5+, PHP 7.4+ (doporučeno 8.2/8.3)
- ACF nebo ACF Pro
- Breakdance 2.8+ (pro element; shortcode funguje i bez něj)

## Instalace

1. Nahrát složku `martin-dev-vyber-bytu` do `wp-content/plugins/` a aktivovat.
2. Nastavení → Trvalé odkazy → Uložit.
3. Developerské projekty → Projekty → Přidat projekt.
4. Developerské projekty → Přidat jednotku (u každé vybrat projekt).
5. V projektu: obrázek pohledu → oblasti podlaží → půdorysy → oblasti jednotek.
6. Vytvořit Breakdance element podle `docs/breakdance-element.md`.

## Automatické aktualizace

1. Zvyš `Version:` v hlavičce `martin-dev-vyber-bytu.php` (a konstantu `VERSION`) a pushni do `main`.
2. GitHub Action (`.github/workflows/release.yml`) vydá release `vX.Y.Z` se souborem `martin-dev-vyber-bytu.zip`.
3. Weby se na GitHub ptají dvakrát denně (knihovna Plugin Update Checker v `lib/`). Nová verze se ukáže v Pluginech; se zapnutými automatickými aktualizacemi se nainstaluje sama. Hned vynutíš kontrolu odkazem „Check for updates“ u pluginu.

Repozitář je veřejný. Kdyby byl soukromý, každý web potřebuje ve `wp-config.php` konstantu `MARTIN_DV_GITHUB_TOKEN` (fine-grained token, Contents: Read-only).

## Filtry

| Filtr | Výchozí | Účel |
|---|---|---|
| `martin_dv_unit_slug` | `byty` | URL slug jednotek (po změně uložit trvalé odkazy) |
| `martin_dv_accessories` | stání, garáž, sklepní kóje | pevné položky příslušenství (každá má zaškrtávátko + cenu) |
| `martin_dv_dispositions` | 1+kk … 6+kk | seznam dispozic v ACF |
| `martin_dv_load_everywhere` | `false` | načíst CSS/JS na všech stránkách |
| `martin_dv_detect_needles` | `Vyberbytu`, `martin_vyber_bytu` | podle čeho se pozná stránka s výběrem (CSS do `<head>`) |
| `martin_dv_okoli_categories` | 9 kategorií | kategorie bodů mapy okolí (název, výchozí ikona, barva) |
| `martin_dv_okoli_icons` | ~20 ikon | sada ikon bodů (vnitřek SVG 24×24 z kódu – ne od uživatele) |
| `martin_dv_okoli_detect_needles` | `MapaOkoli`, `martin_mapa_okoli` | podle čeho se pozná stránka s mapou okolí |
| `martin_dv_param_suggestions` | výška stropů, PENB… | návrhy názvů vlastních parametrů v editoru jednotky |
| `martin_dv_editor_config` | – | data pro editor projektu v administraci (používá mapa okolí) |

## Verze

- **0.14.0** – údaje jednotky: místnosti se součtem a kontrolou proti celkové ploše (součet smí být menší – příčky a zdi), seznam venkovních ploch s orientací, vlastní parametry, poloha v domě (budova, číslo podlaží, umístění, orientace, výhled); v projektu název hlavní plochy a seznam budov; nové elementy Tabulka místností, Parametry jednotky, Schéma podlaží a shortcody.
- **0.13.0** – nová funkce **Mapa okolí** (projekt → Okolí, element „Marmal – Mapa okolí“, shortcode `[martin_mapa_okoli]`); plugin přejmenován na „Marmal – Developerské projekty“; oprava: tlačítka v levém panelu editoru se už neroztahují do výšky; menu v administraci „Výběr bytů“ přejmenováno na „Developerské projekty“.
- **0.12.0** – režim „jen tabulka s filtrem“ (bez obrázků domu a půdorysů): v elementu Content → Projekt → Zobrazit jen tabulku s filtrem, v shortcodu `jen_tabulka="ano"`; má přednost před Skrýt tabulku.
- **0.11.0** – při najetí myší na obrázek (pohled i půdorys) se zbytek obrázku ztmaví (výchozí 50 % černá) a klikací oblasti se jemně zvýrazní; prodané jednotky bez odkazu zůstávají ztmavené; Design tab: Zvýraznění na obrázku → Ztmavení okolí při najetí (%), Barva ztmavení, Zvýraznění klikacích oblastí při najetí (%).
- **0.10.0** – řazení tabulky: výchozí od nejnižšího podlaží (číslo z názvu – NP, PP záporně, přízemí 0; bez čísla na konec) a uvnitř přirozeně podle čísla jednotky; mezonet u nejnižšího podlaží; stejné pořadí tlačítek podlaží ve filtru; přepínač Řazení tabulky (automaticky / podle pořadí v editoru).
- **0.9.0** – nastavení tabulky v projektu: výběr a pořadí sloupců (Jednotka, Typ, Podlaží, Dispozice, Plocha, Venkovní plocha, Cena, Stav, Detail), nový sloupec Detail s odkazem na jednotku a vlastním textem; Design tab: Tabulka → Detail (písmo, pozadí, text, hover, zaoblení); bezpečnostní kontrola PHPCS WordPress Security bez nálezů.
- **0.8.2** – bezpečnost: nepublikovaný projekt (koncept, soukromý) se na webu nevykreslí – vidí ho jen ten, kdo ho smí upravovat.
- **0.8.1** – oprava „&nbsp;“ v cenách a plochách v bublině a kartě; cena v bublině tučně s odsazením 8 px; Design tab: Bublina → Cena.
- **0.8.0** – bez nadpisu pohledu nad obrázkem, legenda stavů (vždy všechny) pod seznamem v bočním panelu, texty nápověd v nastavení projektu, tmavé záhlaví tabulky; Design tab: Legenda stavů, Tabulka → pozadí záhlaví.
- **0.7.0** – filtr tabulky jako tlačítka (typ / dispozice / podlaží / jen volné), popis projektu (obsah editoru projektu) nad výběrem, typy Rodinný dům / Dvojdům / Řadový dům, venkovní plocha Zahrada / Pozemek, oblast v pohledu může vést přímo na jednotku; Design tab: Úvod projektu, Filtr tabulky; přepínače Skrýt název / popis.
- **0.6.0** – automatické aktualizace z GitHubu (Plugin Update Checker + GitHub Action release).
- **0.5.0** – přejmenování na „Marmal – Výběr bytů“ (autor Martin Malý – marmal.cz), Design tab: barvy štítků stavu (pozadí + text pro volný / rezervovaný / prodaný).
- **0.4.0** – příslušenství: zaškrtávátko + cena u každé položky, vlastní položky (ACF Pro opakovač / 3 řádky v ACF free), shortcode `[martin_dv_prislusenstvi]` pro detail; Design tab: zvýraznění na obrázku, seznam vedle obrázku, tlačítka podlaží, rámečky a zaoblení karty a bubliny.
- **0.3.0** – volitelné příslušenství k dokoupení (ACF `dv_prislusenstvi`, `dv_prislusenstvi_poznamka`), tabulka bez venkovní plochy, Design tab elementu: barvy, nadpisy, karta, bublina, tabulka.
- **0.2.0** – Breakdance element „Vyberbytu“ (slug `MartinDV\Vyberbytu`, kategorie Dynamic) ve složce `elements/`, `ssr.php` napojený na plugin.
- **0.1.0** – první verze: CPT, ACF pole, editor oblastí, frontend, shortcode, saving location pro Element Studio.
