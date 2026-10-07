# Breakdance element „Vyberbytu“ (Breakdance 2.8)

**Stav:** element je hotový ve `elements/Vyberbytu/` (slug `MartinDV\Vyberbytu`). Postup níže slouží jen jako záznam, jak vznikl. Element Studio neumí upravit `ssr.php` – ten se mění jen v pluginu.

Element se vytváří v Element Studiu a ES ho uloží jako PHP soubory do složky `elements/` tohoto pluginu. Pak je součástí repozitáře a cestuje s pluginem na další weby.

Vycházíme z vestavěného elementu **Shortcode**: už renderuje přes `ssr.php`, takže stačí vyměnit ovládací prvky a obsah `ssr.php`.

## Postup

1. Plugin nahraj a aktivuj na **stagingu**. Zavři builder a znovu ho otevři.
2. V builderu: Settings menu (vlevo nahoře) → **Element Studio**.
3. Najdi vestavěný element **Shortcode** → **Duplicate**. Jako saving location vyber **„Výběr bytů – elementy“**.
4. Záložka **Settings**:
   - Name: `Výběr bytů`
   - Class Name: `martin-dv-element` (nastav hned, později neměnit)
   - Category: vyber z nabídky (např. Dynamic nebo Basic)
   - Nesting rule: `final`
5. Záložka **Controls**: smaž původní prvky Shortcode elementu a přidej:
   - **Content** → sekce, slug `projekt`, label „Projekt“
     - `projekt_id` – **Number**, label „ID projektu“
     - `skryt_tabulku` – **Toggle**, label „Skrýt tabulku jednotek“
   - **Design** → sekce, slug `barvy`, label „Barvy“
     - `akcent` – **Color**, label „Barva zvýraznění (prázdné = Global Settings)“
6. Záložka **HTML**: nech jen `%%SSR%%` (u Shortcode elementu už tam je).
7. Obsah `ssr.php` nahraď souborem `docs/element-ssr.php`. Pokud ES nemá pro `ssr.php` vlastní editor, uprav soubor `elements/<Název>/ssr.php` přímo v pluginu.
8. **Property Paths → SSR when value changes**: přidej
   `content.projekt.projekt_id`, `content.projekt.skryt_tabulku`, `design.barvy.akcent`.
9. **CSS / Default CSS**: nech prázdné. Styl dodává plugin.
10. Ulož. Zavři a znovu otevři builder → Add → element „Výběr bytů“ → zadej ID projektu.
11. Commit vygenerovaných souborů v `elements/`.

## Co ověřit při prvním vytvoření

- Že duplikovaný Shortcode element opravdu obsahuje `ssr.php` a `%%SSR%%`. Pokud ne, vrať se a napiš mi, co v ES vidíš – postup upravíme.
- Hodnotu Toggle v `ssr.php` (zda je `true`/`false`, nebo jiný tvar). `!empty()` zvládne běžné varianty.
- Barvu z Color pickeru: když vybereš globální barvu z palety, Breakdance vrátí `var(--…)`. Plugin `var()` přijímá. Zároveň tak zjistíš přesný název proměnné Brand barvy.
