# PR: Support textual ingredient amounts and schema v2

**Branch:** `feature/ingredient-amount-text-v2` -> `main`

## Motywacja

Aktualne pole ingredient amount bylo traktowane jako wartosc numeryczna (float), co blokowalo poprawne wpisy kulinarne typu:
- `1/3`
- `1 1/2`
- `szczypta`

Wymaganiem biznesowym jest pelne wsparcie ilosci tekstowych w calym przeplywie danych (ACF -> sync -> view model -> frontend).

## Zakres zmian

### `includes/acf/field-groups.php`
- Pole `field_ingredient_amount` zmieniono z `number` na `text`.
- Usunieto `step` i dodano placeholder z przykladowymi wartosciami.

### `includes/view-model/normalizers.php`
- `g4t_normalize_ingredients_list()` nie castuje juz `amount` do `float`.
- Nowa semantyka:
	- pusty string po trim -> `null`
	- niepusty string -> zachowany jako `string`

### `includes/sync/meta-sync.php`
- `g4t_load_ingredients_repeater_meta()` nie wymusza juz `is_numeric()`.
- `amount` ladowane i zwracane jako `string|null`.

### `includes/sync/converters.php`
- `g4t_convert_ingredients_meta_to_array()` nie wykonuje `floatval()`.
- `amount` jest zachowywane jako tekst (po sanitize/trim) dla sciezki meta -> blocks.

### `includes/view-model/builder.php`
- Aktualizacja odniesien dokumentacyjnych z `SCHEMA_CONTRACT_V1.md` na `SCHEMA_CONTRACT_V2.md`.

### `includes/registration/blocks.php`
- Aktualizacja komentarza referencyjnego do kontraktu v2.

### Testy

#### `tests/unit/test-normalizers.php`
- Aktualizacja oczekiwan typu amount (`string|null`).
- Dodany coverage dla przypadkow tekstowych i ulamkow (`1/3`).

#### `tests/unit/test-meta-sync.php`
- Aktualizacja asercji pod `string` amount.
- Dodany test preserving fractional amount z meta (`1/3`).

### Dokumentacja kontraktu

#### Nowe pliki
- `SCHEMA_CONTRACT_V2.md`
- `SCHEMA_MIGRATION_v1_to_v2.md`

#### Istniejace pliki
- `SCHEMA_CONTRACT_V1.md` oznaczone jako superseded.
- `RELEASE_NOTES.md` uzupelnione o sekcje breaking changes dla schema v2.

## Weryfikacja

- Testy jednostkowe:
	- `vendor/bin/phpunit tests/unit/test-normalizers.php tests/unit/test-meta-sync.php`
	- wynik: `EXIT:0`

## Ryzyko / kompatybilnosc

- To jest zmiana breaking w kontrakcie danych:
	- `ingredients[].amount`: `float|null` -> `string|null`
- Odczyt legacy danych pozostaje kompatybilny (stare wartosci numeryczne sa nadal czytelne).
- Konsumenci wykonujacy obliczenia na amount musza jawnie parsowac/castowac wartosc.

## Checklist

- [x] Kod backend i sync zaktualizowany do semantyki `string|null`
- [x] Testy jednostkowe zaktualizowane i przechodza
- [x] Dokumentacja kontraktu v2 i migracji dodana
- [x] Release notes uzupelnione
