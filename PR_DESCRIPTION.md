# PR: Migrate recipes plugin to vertical slices with compatibility adapters

**Branch:** feat/vertical-slices-migration -> main

## Motywacja

Domkniecie kolejnego etapu reorganizacji architektury pluginu z ukladu warstwowego
na vertical slices, bez zmiany kontraktu danych i bez zmiany publicznych hookow.

Cel tej zmiany:
1. Przeniesc logike do `includes/slices/{slice}/...`.
2. Zostawic cienkie adaptery w starych lokalizacjach dla kompatybilnosci.
3. Utrzymac niezmieniony runtime behavior i SCHEMA_CONTRACT_V2.

## Zakres zmian

### Nowe slices

- `includes/slices/recipe-composition/`
- `includes/slices/recipe-read-model/`
- `includes/slices/recipe-registration/`
- `includes/slices/recipe-storage/`
- `includes/slices/recipe-input-surface/`
- `includes/slices/recipe-output-surface/`
- `includes/slices/recipe-admin-config/`

### Adaptery kompatybilnosci w starych sciezkach

Nizsze warstwy (`includes/view-model/*`, `includes/registration/*`, `includes/sync/*`,
`includes/front/*`, `includes/timber/*`, `includes/integrations/*`, `includes/admin/*`)
zostaly zamienione na cienkie pliki `require_once` wskazujace na nowe slices.

### Orchestrator

- `includes/class-go4taste-recipes-plugin.php` laduje teraz entrypointy slices.
- Publiczne hooki, akcje AJAX i sygnatury callbackow pozostaja bez zmian.

### Fixy w trakcie migracji

- Naprawiony fatal na admin options page:
  - input slice entrypoint przestal ladowac executable template files przy bootstrapie.
- Naprawione znikajace etapy przygotowania na froncie:
  - normalizer krokow obsluguje klucze ACF (`step_header`, `step_description`, `step_tip`, `step_image_url`).

### Testy

- Dodany test regresji dla normalizacji krokow ACF:
  - `tests/unit/test-normalizers.php`

## Weryfikacja

- `vendor\\bin\\phpunit tests\\unit`
- wynik: `EXIT=0`

## Ryzyko / kompatybilnosc

- Ryzyko funkcjonalne niskie: zmiana ma charakter architektoniczny (relokacja kodu + adaptery).
- Publiczne API pluginu (hooki/filtry/AJAX) pozostaje stabilne.
- Adaptery pozostaja tymczasowo; ich cleanup planowany w kolejnym PR (Faza 6).

## Checklist

- [x] Slices utworzone i podlaczone
- [x] Legacy sciezki utrzymane przez adaptery
- [x] Naprawiony fatal na options page
- [x] Naprawione renderowanie etapow przygotowania
- [x] Testy unit przechodza
