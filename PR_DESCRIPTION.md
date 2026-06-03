# PR: Recipe Creator Step 6 - Algolia products, stable persistence, autosave/publish fixes

**Branch:** feature/picker-ui-extensibility -> main

## Co wnosi ten branch

Ten branch domyka wdrozenie sekcji produktowej (Step 6) w kreatorze przepisu oraz usuwa bledy zapisu, ktore blokowaly stabilna prace flow:

- wyszukiwanie i wybor produktow Algolia w natywnym UI kreatora,
- poprawny zapis ID produktow do docelowego kontraktu meta,
- poprawny odczyt zapisanych produktow przy edycji,
- usuniecie problemu `post_id=0` po publikacji nowego wpisu,
- usuniecie duplikowania postow przy sekwencji autosave (heartbeat) -> publish.

---

## Zakres zmian (w kolejnosci rozwoju)

### 1) Integracja danych produktowych w Step 6

- Kreator zaczal korzystac z danych Algolia do listy i paska wybranych produktow.
- Dodano mapowanie/hydratacje danych tak, aby po wejciu w edycje odtworzyc poprawnie wybrane produkty (z miniaturami i podstawowymi polami).
- Dodano odporniejsze mapowanie rekordow z Algolia i fallbacki dla roznych wariantow kluczy.

### 2) Utrwalenie kontraktu zapisu produktow

- Zapis produktow zostal ustabilizowany do wspolnego kontraktu:
	- meta key: `_g4t_a_recipe_bottom_product_ids`
	- format: JSON array of strings, np. `["154","89"]`
- Dodano obsluge legacy fallback dla starszych wariantow kluczy ID produktu.

### 3) Realny root cause bledow persystencji i fix

Pierwotna hipoteza o `max_input_vars` okazala sie nietrafiona.

**Rzeczywista przyczyna:** ACF security field filtering.

ACF usuwa z `$_POST['acf']` pola, ktore nie naleza do field groups zadeklarowanych w `acf_form()`. W efekcie pole produktow bylo odfiltrowywane zanim uruchamial sie hook zapisu.

**Naprawa:** krytyczne dane dla zapisu produktow sa wysylane jako top-level POST field poza `acf[...]`:

- `g4t_recipe_ads_product_ids_json`

To omija filtracje ACF dla niedeklarowanych pol i zapewnia stabilny zapis.

### 4) Stabilizacja zapisu nowego wpisu i publikacji

- Poprawiono flow nowego posta tak, aby nie konczyc w stanie `post_id=0`.
- Dodano logike ponownego wykorzystania draftu utworzonego przez heartbeat przy publikacji.
- Efekt: brak duplikatow postow przy scenariuszu autosave -> publish.

---

## Najwazniejsze commity na branchu

- `e902bd1` feat(creator): use algolia data in native list and strip
- `218d01a` fix(creator): hydrate selected products and show thumbnails
- `9d0dd63` fix(creator): robust product hydration and image extraction
- `60ab50c` fix(creator): persist product ids from posted acf payload
- `13894c7` fix(creator): support product_id legacy fallback keys
- `d81023a` fix(creator): disable dummy products when algolia is configured
- `b291120` fix(creator): persist product ids via flat POST fields
- `388ed1d` fix(creator): prevent duplicate posts on autosave + publish sequence

---

## Walidacja (zakres funkcjonalny)

### Potwierdzone

- Step 6 laduje produkty Algolia.
- Wyszukiwanie filtruje wyniki poprawnie.
- Wybor produktow aktualizuje selected strip.
- Zapis utrwala dane do `_g4t_a_recipe_bottom_product_ids`.
- Edycja odtwarza zapisane produkty.
- Tytul wpisu zapisuje sie poprawnie.
- Publish nowego wpisu nie konczy sie na `post_id=0`.
- Sekwencja autosave -> publish nie tworzy duplikatow.

### Follow-up QA

- Pelny retest mobile + desktop dla Step 6.
- Krotki regression check calego flow kreatora po publikacji.

---

## Ryzyka i uwagi

- Branch zawiera kumulatywny zestaw fixow (UI + persistence + publish flow). Zalecany merge jako jedna calosc.
- Save path produktow jest celowo niezalezny od `acf[...]` dla krytycznego pola IDs, aby uniknac ponownego efektu filtracji ACF.

---

## Merge readiness

- Status: gotowe do merge.
- Wymaganie: standardowy smoke test po merge na srodowisku docelowym.