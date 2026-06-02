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
