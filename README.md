# Marmal – Výběr bytů

Autor: Martin Malý – [marmal.cz](https://marmal.cz)

WordPress plugin pro developerské projekty. Návštěvník vybírá na fotce nebo vizualizaci domu podlaží, v půdorysu podlaží pak jednotku (byt, ateliér, sklep, garáž, parkovací stání) a klikem přejde na její detail.

- **Jednotky** jsou CPT `martin_dv_jednotka` s vlastní URL (`/byty/…`) a ACF poli (`dv_cislo`, `dv_stav`, `dv_cena` …). Detail se staví Breakdance šablonou s dynamickými daty.
- **Projekty** (CPT `martin_dv_projekt`) obsahují pohledy (dům, areál, strany budovy) a podlaží s obkreslenými oblastmi.
- Víc budov a pohledů: oblast v pohledu může vést na podlaží nebo na další pohled.
- Mezonety: stejnou jednotku lze obkreslit ve více podlažích.
- Barvy a písmo se berou z Breakdance Global Settings.
- Výstup: Breakdance element **Marmal – Výběr bytů** (Add → Dynamic, slug `MartinDV\Vyberbytu`; vzniklo v Element Studiu, viz `docs/breakdance-element.md`) nebo shortcode `[martin_vyber_bytu id="123"]` (volitelně `tabulka="ne" nazev="ne" popis="ne"`).
- Řetězce jsou připravené k překladu (text domain `martin-dev-vyber-bytu`, např. Loco Translate). Podpora Polylang/WPML pro jednotky.

## Požadavky

- WordPress 6.5+, PHP 7.4+ (doporučeno 8.2/8.3)
- ACF nebo ACF Pro
- Breakdance 2.8+ (pro element; shortcode funguje i bez něj)

## Instalace

1. Nahrát složku `martin-dev-vyber-bytu` do `wp-content/plugins/` a aktivovat.
2. Nastavení → Trvalé odkazy → Uložit.
3. Výběr bytů → Projekty → Přidat projekt.
4. Výběr bytů → Přidat jednotku (u každé vybrat projekt).
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

## Verze

- **0.7.0** – filtr tabulky jako tlačítka (typ / dispozice / podlaží / jen volné), popis projektu (obsah editoru projektu) nad výběrem, typy Rodinný dům / Dvojdům / Řadový dům, venkovní plocha Zahrada / Pozemek, oblast v pohledu může vést přímo na jednotku; Design tab: Úvod projektu, Filtr tabulky; přepínače Skrýt název / popis.
- **0.6.0** – automatické aktualizace z GitHubu (Plugin Update Checker + GitHub Action release).
- **0.5.0** – přejmenování na „Marmal – Výběr bytů“ (autor Martin Malý – marmal.cz), Design tab: barvy štítků stavu (pozadí + text pro volný / rezervovaný / prodaný).
- **0.4.0** – příslušenství: zaškrtávátko + cena u každé položky, vlastní položky (ACF Pro opakovač / 3 řádky v ACF free), shortcode `[martin_dv_prislusenstvi]` pro detail; Design tab: zvýraznění na obrázku, seznam vedle obrázku, tlačítka podlaží, rámečky a zaoblení karty a bubliny.
- **0.3.0** – volitelné příslušenství k dokoupení (ACF `dv_prislusenstvi`, `dv_prislusenstvi_poznamka`), tabulka bez venkovní plochy, Design tab elementu: barvy, nadpisy, karta, bublina, tabulka.
- **0.2.0** – Breakdance element „Vyberbytu“ (slug `MartinDV\Vyberbytu`, kategorie Dynamic) ve složce `elements/`, `ssr.php` napojený na plugin.
- **0.1.0** – první verze: CPT, ACF pole, editor oblastí, frontend, shortcode, saving location pro Element Studio.
