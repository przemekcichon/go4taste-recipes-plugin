# PR: Security hardening for ingredient meta writes and debug mode access

**Branch:** `feature/security-hardening-ingredients-debug` -> `main`

## Motywacja

W ramach przegladu security wykryto dwa obszary do utwardzenia:
1. Dane ingredients zapisywane do post meta byly zapisywane bez sanitizacji na sciezce block -> meta.
2. Tryb debug recept byl dostepny dla kazdego uzytkownika na local env po dodaniu parametru query.

Cel tej zmiany to zmniejszenie powierzchni ataku (stored XSS i ekspozycja danych diagnostycznych) bez zmiany logiki biznesowej pluginu.

## Zakres zmian

### [includes/sync/meta-sync.php](includes/sync/meta-sync.php)
- W `g4t_sync_ingredients_repeater_meta()` dodano sanitizacje przed zapisem:
	- `name` -> `sanitize_text_field`
	- `amount` -> `sanitize_text_field( trim(...) )`
	- `unit` -> `sanitize_text_field`
	- `ingredient_note` -> `sanitize_text_field`
- Zapis do `update_post_meta()` odbywa sie juz na przetworzonych, bezpieczniejszych wartosciach.

### [includes/timber/context-filters.php](includes/timber/context-filters.php)
- W `g4t_is_recipe_debug_enabled()` usunieto automatyczne wlaczenie debug na local env.
- Debug jest teraz dostepny tylko dla uzytkownika z capability `manage_options` i parametrem `g4t_recipe_debug`.

## Weryfikacja

- Skladnia PHP:
	- `php -l includes/sync/meta-sync.php`
	- `php -l includes/timber/context-filters.php`
	- wynik: brak bledow skladni

- Testy jednostkowe:
	- `vendor/bin/phpunit tests/unit/test-meta-sync.php tests/unit/test-normalizers.php`
	- wynik: `EXIT:0`

## Ryzyko / kompatybilnosc

- Niskie ryzyko regresji.
- Brak zmian publicznego API pluginu.
- Mozliwy efekt uboczny: wartosci ingredients z nietypowymi znakami HTML/JS sa teraz normalizowane/sanitizowane przy zapisie.
- Tryb debug nie jest juz widoczny dla nieuprzywilejowanych uzytkownikow, nawet na local env.

## Checklist

- [x] Hardening zapisu ingredients do meta
- [x] Hardening dostepu do debug mode
- [x] Testy unit przechodza
- [x] Branch wypchniety i gotowy do PR
