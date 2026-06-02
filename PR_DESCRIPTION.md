# PR: Recipe Creator Picker Adapter — Context-Aware UI Variant for Ads Picker

**Branch:** feature/picker-ui-extensibility -> main

## Implementation status

| Stage | Status | Description |
|---|---|---|
| Stage 1: Creator adapter contract | 🔲 Next | Pass context/variant metadata from creator to shared picker runtime |
| Stage 2: Creator UI integration | 🔲 Next | Apply creator-specific picker section and fallback handling |
| Stage 3: Meta persistence alignment | 🔲 Next | Keep `_g4t_a_recipe_bottom_product_ids` JSON contract as source of truth |
| Stage 4: Compatibility behavior | 🔲 Next | Preserve legacy fallback to `recipe_ads_products` in edit mode |
| Stage 5: Docs + QA checklist | 🔲 Next | Update PR docs and verify creator flow end-to-end |

---

## Motivation

Recipes plugin already uses shared picker runtime and JSON storage key, but the picker visuals are still shared with admin/widget surfaces.
For creator UX we need a dedicated variant while keeping common resolver and save contract.

Main goals:
- keep creator save flow in recipes-plugin,
- adopt context/variant contract from ads plugin picker extensibility,
- avoid forking product resolution logic,
- preserve compatibility for existing recipes and templates.

---

## Desired outcome

After this implementation:
1. Creator step 6 declares its picker context and style variant explicitly.
2. Creator can use dedicated picker visuals without custom resolver duplication.
3. Saved IDs remain in `_g4t_a_recipe_bottom_product_ids` JSON array format.
4. Legacy `recipe_ads_products` fallback remains safe in edit mode.

---

## Proposed architecture

### 1) Creator context mapping

Creator adapter passes:
- context: `recipe_creator`,
- variant: creator-specific key,
- metadata: post_id/edit mode when available.

### 2) UI boundary in creator

Keep picker integration inside frontend creator files:
- `features/frontend-creator/creator-template.php`
- `features/frontend-creator/creator.js`
- `features/frontend-creator/creator-form.php`
- `features/frontend-creator/index.php`

### 3) Save contract

Persist selected product IDs only as JSON array under:
- `_g4t_a_recipe_bottom_product_ids`

### 4) Compatibility

When JSON key is empty in edit mode:
- read legacy `recipe_ads_products` as fallback source.

---

## Validation plan

### Must pass

1. Creator step 6 renders picker in creator variant when ads picker assets are available.
2. Fallback local list remains available if picker runtime/config is unavailable.
3. Save from creator updates `_g4t_a_recipe_bottom_product_ids` correctly.
4. Re-open edit mode loads selected products from JSON key.
5. Legacy recipes still load products through repeater fallback.

### Follow-up verification

6. Visual QA on mobile and desktop for creator picker section.
7. No regressions in summary/review step data mapping.

---

## Risks and mitigations

- Risk: creator-specific UI regressions.
  Mitigation: keep fallback list and conservative DOM integration.

- Risk: accidental divergence of save contract.
  Mitigation: enforce single write path to `_g4t_a_recipe_bottom_product_ids`.

- Risk: dependency on ads plugin assets.
  Mitigation: runtime checks + graceful fallback behavior.

---

## Decision points

- Final creator variant key naming.
- Whether creator needs extra payload fields beyond existing picker preview.

---

## Merge readiness

- This branch is planning scope only at this moment.
- Merge readiness will be reassessed after implementation + QA checklist.
# PR: Recipe Creator Picker Adapter — Context-Aware UI Variant for Ads Picker

**Branch:** feature/picker-ui-extensibility -> main

## Implementation status

| Stage | Status | Description |
|---|---|---|
| Stage 1: Creator adapter contract | 🔲 Next | Pass context/variant metadata from creator to shared picker runtime |
| Stage 2: Creator UI integration | 🔲 Next | Apply creator-specific picker section and fallback handling |
| Stage 3: Meta persistence alignment | 🔲 Next | Keep `_g4t_a_recipe_bottom_product_ids` JSON contract as source of truth |
| Stage 4: Compatibility behavior | 🔲 Next | Preserve legacy fallback to `recipe_ads_products` in edit mode |
| Stage 5: Docs + QA checklist | 🔲 Next | Update PR docs and verify creator flow end-to-end |

---

## Motivation

Recipes plugin already uses shared picker runtime and JSON storage key, but the picker visuals are still shared with admin/widget surfaces.
For creator UX we need a dedicated variant while keeping common resolver and save contract.

Main goals:
- keep creator save flow in recipes-plugin,
- adopt context/variant contract from ads plugin picker extensibility,
- avoid forking product resolution logic,
- preserve compatibility for existing recipes and templates.

---

## Desired outcome

After this implementation:
1. Creator step 6 declares its picker context and style variant explicitly.
2. Creator can use dedicated picker visuals without custom resolver duplication.
3. Saved IDs remain in `_g4t_a_recipe_bottom_product_ids` JSON array format.
4. Legacy `recipe_ads_products` fallback remains safe in edit mode.

---

## Proposed architecture

### 1) Creator context mapping

Creator adapter passes:
- context: `recipe_creator`,
- variant: creator-specific key,
- metadata: post_id/edit mode when available.

### 2) UI boundary in creator

Keep picker integration inside frontend creator files:
- `features/frontend-creator/creator-template.php`
- `features/frontend-creator/creator.js`
- `features/frontend-creator/creator-form.php`
- `features/frontend-creator/index.php`

### 3) Save contract

Persist selected product IDs only as JSON array under:
- `_g4t_a_recipe_bottom_product_ids`

### 4) Compatibility

When JSON key is empty in edit mode:
- read legacy `recipe_ads_products` as fallback source.

---

## Validation plan

### Must pass

1. Creator step 6 renders picker in creator variant when ads picker assets are available.
2. Fallback local list remains available if picker runtime/config is unavailable.
3. Save from creator updates `_g4t_a_recipe_bottom_product_ids` correctly.
4. Re-open edit mode loads selected products from JSON key.
5. Legacy recipes still load products through repeater fallback.

### Follow-up verification

6. Visual QA on mobile and desktop for creator picker section.
7. No regressions in summary/review step data mapping.

---

## Risks and mitigations

- Risk: creator-specific UI regressions.
  Mitigation: keep fallback list and conservative DOM integration.

- Risk: accidental divergence of save contract.
  Mitigation: enforce single write path to `_g4t_a_recipe_bottom_product_ids`.

- Risk: dependency on ads plugin assets.
  Mitigation: runtime checks + graceful fallback behavior.

---

## Decision points

- Final creator variant key naming.
- Whether creator needs extra payload fields beyond existing picker preview.

---

## Merge readiness

- This branch is planning scope only at this moment.
- Merge readiness will be reassessed after implementation + QA checklist.# PR: Ads Phase 3+4 — Wspólny resolver + adapter pickera w frontend creator

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
