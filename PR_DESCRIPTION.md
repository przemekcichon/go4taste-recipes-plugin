# PR: Ads Phase 3+4 — Wspólny resolver + adapter pickera w frontend creator

**Branch:** feature/ads-phase3-shared-resolver → main

## Motywacja

W recipes-plugin były dwa osobne problemy:
- Phase 3: lokalna duplikacja pobierania produktów reklamowych w view modelu (`g4t_fetch_ads_products()`),
- Phase 4: brak zapisu nowego kontraktu `_g4t_a_recipe_bottom_product_ids` z kreatora przepisu.

Celem jest pełna zgodność recipes-plugin z nową architekturą go4taste-ads bez zmiany kształtu danych konsumowanych przez Twig.

## Zakres zmian

### 1) Phase 3 — delegacja pobierania do shared resolvera ads

Plik: `features/recipe-display/view-model-builder.php`

- `g4t_build_single_recipe_view_model()` wywołuje `g4t_recipe_bottom_strip_get_products_for_post($post_id)` gdy go4taste-ads jest aktywne (`function_exists()`).
- Zachowane fallbacki kompatybilności:
  1. go4taste-ads nieaktywne → lokalne `g4t_fetch_ads_products()`,
  2. nowy key pusty + legacy ACF repeater istnieje → `g4t_fetch_ads_products()`.
- `ads.enabled` bazuje na faktycznie załadowanych produktach.
- `g4t_fetch_ads_products()` pozostaje jako legacy fallback.

### 2) Phase 4 — adapter pickera i zapis JSON key

Pliki:
- `features/frontend-creator/index.php`
- `features/frontend-creator/creator-form.php`
- `features/frontend-creator/creator-template.php`
- `features/frontend-creator/creator.js`

Co zostało dodane:
- Adapter pickera w kroku 6 kreatora (`G4TAdsPicker`) z fallbackiem do lokalnej listy produktów.
- Enqueue assets pickera (`picker-ui.js`, `instantsearch.js`, `algoliasearch`) na stronie kreatora.
- Zapis `_g4t_a_recipe_bottom_product_ids` jako JSON array of strings.
- Odczyt nowego key w trybie edycji + fallback do legacy `recipe_ads_products`.
- Sync po `acf/save_post` dla utrzymania nowego key po submit.
- Usunięcie błędnego gate'a blokującego picker (`function_exists('go4taste_ads_get_options')`).

## Kontekst architektoniczny

Zmiany domykają integrację recipes-plugin z planem rozszerzalności ads:
- Phase 3 (read-model delegation),
- Phase 4 (creator save adapter, bez modyfikacji ads core).

Kontrakt danych pozostaje spójny: `_g4t_a_recipe_bottom_product_ids` przechowuje JSON array string IDs.

## Weryfikacja

- Brak błędów Problems dla zmienionych plików.
- Lint PHP przechodzi dla zmienionych plików creator.
- Kształt `ads.products` w view modelu pozostaje kompatybilny z Twig.
- Krok 6 kreatora ładuje picker Algolii, jeśli ustawione są poprawne opcje ads; fallback lokalny działa gdy picker niedostępny.

## Ryzyko / kompatybilność

- Ryzyko niskie/średnie: zmiany obejmują view model + frontend creator.
- Kompatybilność wsteczna zachowana dzięki fallbackom (`g4t_fetch_ads_products`, `recipe_ads_products`).
- Brak zmian w schema Twig.

## Scope follow-up

- Phase 5 (cache/invalidation) jest poza zakresem tego PR i pozostaje do osobnego wdrożenia.

## Merge readiness

- PR merge-ready dla zakresu recipes-plugin: Phase 3 + Phase 4.

## Checklist

- [x] Guard `function_exists('g4t_recipe_bottom_strip_get_products_for_post')`
- [x] Fallback 1: go4taste-ads nieaktywne → `g4t_fetch_ads_products()`
- [x] Fallback 2: nowy key pusty + legacy ACF dane → `g4t_fetch_ads_products()`
- [x] `ads.enabled` odzwierciedla faktycznie załadowane produkty
- [x] `g4t_fetch_ads_products()` zachowana (nie usunięta)
- [x] Adapter pickera w kroku 6 kreatora
- [x] Zapis `_g4t_a_recipe_bottom_product_ids` (JSON array)
- [x] Fallback odczytu do `recipe_ads_products`
- [x] Sync po `acf/save_post`
