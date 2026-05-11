# Release Notes

## go4taste-recipes-plugin
Data: 2026-05-11
Zakres: merge feature/recipe-structured-fields do main (91008ad)

## Highlights
- Structured fields dla przepisow z fallbackiem block data -> ACF/meta.
- Synchronizacja block content do kluczy meta zgodnych z ACF.
- Integracja skladnikow z taksonomia recipe_ingredient (mapowanie term_id i auto-resolve po nazwie).
- Nowe plaskie taksonomie do filtrowania i kategoryzacji:
  - recipe_meal_type
  - recipe_prep_time_range
  - recipe_feature
- Rozszerzenie archiwow recipe dla nowych taksonomii.
- Usprawnienia UX edytora skladnikow: autocomplete, REST fetch, tworzenie brakujacych skladnikow.
- Aktualizacja kontraktu danych i dodanie dokumentow planu blueprint.

## Included commits
- 9a520fc docs: add blueprint transformation plan, summary and findings
- 3fa23e5 Enable archive template for custom recipe taxonomies in MODE_POST
- e51c1ae Apply QA recommendations: add taxonomy docs, sanitization, re-entry guard, unique datalist ID
- 249d798 Register flat taxonomies: meal type, prep time range, and recipe features
- 839e422 Fix ingredient name autocomplete by using native input with datalist
- 44d572c Add ingredient taxonomy contract, sync, and editor autocomplete flow
- 28ff729 Store ingredients and steps in ACF repeater-style meta structure
- b4e9391 Sync block values to ACF-compatible meta keys and remove recipe metabox fields
- 5d4438a Add structured ACF recipe fields with block-to-ACF fallback

## Known notes
- W commitach wystapily ostrzezenia CRLF/LF (bez wplywu na dzialanie).
- Zalecana regresja end-to-end dla: edycja przepisu, zapis, archiwa taksonomii, odczyt w motywie.
