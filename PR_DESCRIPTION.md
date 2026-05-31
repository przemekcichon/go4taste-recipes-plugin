# PR: Ads Phase 3 — Delegacja pobierania produktów do wspólnego resolvera go4taste-ads

**Branch:** feature/ads-phase3-shared-resolver → main

## Motywacja

`g4t_build_single_recipe_view_model()` zawierał własną kopię logiki pobierania produktów reklamowych
(`g4t_fetch_ads_products()`), która bezpośrednio wywołuje PrestaShop API przez instancję klasy
`Go4Taste_Ads`. Taka duplikacja logiki oznacza:
- dwa miejsca do aktualizacji przy zmianie kształtu danych produktu,
- brak korzystania ze wspólnego normalizatora `g4t_ads_normalize_product_item()`,
- brak możliwości filtrowania payloadu przez hooki `go4taste/ads/placement_product_item` i `go4taste/ads/resolve_placement_payload`.

## Zakres zmian

### Plik: `features/recipe-display/view-model-builder.php`

- `g4t_build_single_recipe_view_model()` teraz wywołuje `g4t_recipe_bottom_strip_get_products_for_post($post_id)` gdy go4taste-ads jest aktywne (guard `function_exists()`).
- Podwójny fallback dla wstecznej kompatybilności:
  1. Gdy go4taste-ads nieaktywne → lokalne `g4t_fetch_ads_products()`.
  2. Gdy nowy klucz `_g4t_a_recipe_bottom_product_ids` pusty, ale stare dane ACF repeatera istnieją → `g4t_fetch_ads_products()` (istniejące przepisy bez migracji renderują się poprawnie).
- `ads.enabled` teraz bazuje na faktycznie załadowanych produktach, nie tylko na obecności ID w ACF meta.
- `g4t_fetch_ads_products()` **nie usunięta** — zachowana jako legacy fallback.

## Kontekst architektoniczny

Zmiana jest częścią **Phase 3** planu rozszerzalności go4taste-ads (`feature/banner-extensibility`).

Funkcja `g4t_recipe_bottom_strip_get_products_for_post()` pochodzi z nowego slice'a
`features/recipe-bottom-strip/helpers.php` w pluginie go4taste-ads. Czyta klucz
`_g4t_a_recipe_bottom_product_ids` (kontrakt JSON array) i używa wspólnego
`g4t_ads_build_placement_payload()` do normalizacji produktów.

## Weryfikacja

- Brak błędów w `view-model-builder.php` (walidacja Problems).
- Kształt `ads.products` w view modelu jest identyczny jak poprzednio — Twig konsumuje `recipe_data.ads.products` bez zmian.
- Istniejące przepisy z danymi w ACF repeaterze (`recipe_ads_products`) renderują się poprawnie przez fallback.

## Ryzyko / kompatybilność

- Ryzyko niskie: zmiana dotyczy tylko warstwy pobierania danych w view model builderze.
- Brak zmian w Twig, ACF schema, hookach backendowych, zapisie recipe meta.
- Soft dependency na go4taste-ads — plugin jest opcjonalny, fallback zawsze działa.

## Checklist

- [x] Guard `function_exists('g4t_recipe_bottom_strip_get_products_for_post')`
- [x] Fallback 1: go4taste-ads nieaktywne → `g4t_fetch_ads_products()`
- [x] Fallback 2: nowy klucz pusty + stare ACF dane → `g4t_fetch_ads_products()`
- [x] `ads.enabled` odzwierciedla faktycznie załadowane produkty
- [x] `g4t_fetch_ads_products()` zachowana (nie usunięta)
- [x] Brak zmian w kształcie view modelu (Twig kompatybilny)
