# PR: Recipe Creator Product Discovery UI — Algolia Data in Existing Frontend

**Branch:** feature/picker-ui-extensibility -> main

## Implementation status

| Stage | Status | Description |
|---|---|---|
| Stage 1: Algolia list in existing `product-list` | 🔲 Next | Replace dummy/local-only list with Algolia-backed results rendered in current creator cards |
| Stage 2: Search bridge via existing `.search-box` | 🔲 Next | Use existing creator search input as the only query source (no `.ais-SearchBox-input` in UI flow) |
| Stage 3: Preview in existing `selected-strip` | 🔲 Next | Keep current strip component and render chips with index + product name |
| Stage 4: Persistence + compatibility guard | ✅ Done | Keep `_g4t_a_recipe_bottom_product_ids` JSON contract and legacy `recipe_ads_products` fallback |
| Stage 5: Docs + QA checklist | 🔲 Next | Confirm behavior on create/edit, mobile/desktop, and no regressions in summary/save |

---

## Motivation

Current work showed that simply mounting the standard Ads picker UI in creator step 6 is not the target UX.
The target is to keep the current creator frontend components and only reuse Ads/Algolia data logic.

Main goals:
- keep the current creator layout and interactions,
- feed `product-list` with Algolia data,
- drive search from existing `.search-box` input,
- use `selected-strip` as preview/selection UI,
- preserve existing save contract and backward compatibility.

---

## Desired outcome

After this implementation:
1. Product rows in creator `product-list` are loaded from Algolia (not dummy/local-only source).
2. Typing in creator `.search-box` filters Algolia results directly.
3. `selected-strip` shows selected products with chip number and product name.
4. Save/edit behavior remains unchanged for `_g4t_a_recipe_bottom_product_ids` and legacy fallback.

---

## Proposed architecture

### 1) Data source and rendering boundary

- Data retrieval: use Ads picker logic as data provider (Algolia request path).
- Rendering: keep creator-native row rendering in `product-list` (`creator.js`), no injection of standard picker result UI.

### 2) Search boundary

- Query source must remain creator input inside `.search-box`.
- Any internal Ads picker input (`.ais-SearchBox-input`) is treated as implementation detail and not used as creator-facing control.

### 3) Preview/selection boundary

- Use `selected-strip` as the only creator preview surface for selected products.
- Chip format: index + product name (for example: `1. Oliwa extra virgin`).

### 4) Save contract and fallback

- Persist selected IDs only to `_g4t_a_recipe_bottom_product_ids` (JSON array of strings).
- On edit, if JSON key is empty, fallback to legacy `recipe_ads_products` read path.

---

## Validation plan

### Must pass

1. Step 6 loads first page of Algolia products directly into `product-list` on open.
2. Searching via creator `.search-box` updates Algolia results and keeps current row/card UI.
3. `selected-strip` displays chips with index + product name for all selected items.
4. Selecting/unselecting updates state correctly and save payload still maps to `_g4t_a_recipe_bottom_product_ids`.
5. Edit mode still supports legacy `recipe_ads_products` fallback when JSON key is empty.

### Follow-up verification

6. Mobile and desktop QA for step 6 interactions.
7. No regressions in summary screen and submit/update flow.

---

## Risks and mitigations

- Risk: partial coupling to standard picker DOM.
  Mitigation: keep creator rendering/search controls as source of truth; isolate Algolia access as data layer.

- Risk: duplicate selection state between provider and creator.
  Mitigation: creator state remains canonical; provider returns data only.

- Risk: performance issues with frequent search queries.
  Mitigation: add debounce/throttle in creator input handler and limit initial hits.

---

## Decision points

- Exact debounce interval for `.search-box` query.
- Initial hits/page size for `product-list` in step 6.

---

## Merge readiness

- This branch is implementation in progress.
- Merge readiness after Stage 1-3 functional QA plus Stage 5 checklist.
