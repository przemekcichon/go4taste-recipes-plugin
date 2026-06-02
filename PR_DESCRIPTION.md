# PR: Recipe Creator Product Discovery UI — Algolia Data in Existing Frontend

**Branch:** feature/picker-ui-extensibility -> main

## Implementation status

| Stage | Status | Description |
|---|---|---|
| Stage 1: Algolia list in existing `product-list` | ✅ Verified | Algolia products load into `product-list` on step 6 open |
| Stage 2: Search bridge via existing `.search-box` | ✅ Verified | Search input drives Algolia query; results update `product-list` |
| Stage 3: Preview in existing `selected-strip` | ✅ Verified | Selected products render as chips with index + name |
| Stage 4: Persistence + compatibility guard | ✅ Fixed and verified | `_g4t_a_recipe_bottom_product_ids` persists correctly after save |
| Stage 5: Docs + QA checklist | ✅ Updated | Root cause documented; regression risks noted |

---

## Root cause of previous persistence failure

### Symptom

After selecting Algolia products in step 6 and saving, `_g4t_a_recipe_bottom_product_ids` was persisted as empty string:

```sql
SELECT meta_value
FROM wp_postmeta
WHERE post_id = 2299
  AND meta_key = '_g4t_a_recipe_bottom_product_ids';

-- before fix: "" (empty)
-- after fix:  '["99","98","97","96"]'
```

Title changes were also not saved in the same request.

### Diagnosed cause: PHP `max_input_vars` truncation

The native ACF form submits a large number of POST variables for ingredient and step repeaters. The bridge mechanism in `submitToAcf()` appended product IDs as nested ACF fields at the very end of the POST body:

```
acf[field_recipe_ads_products][0][field_recipe_ads_product_id] = "99"
acf[field_recipe_ads_products][1][field_recipe_ads_product_id] = "98"
...
```

PHP silently drops all variables after the `max_input_vars` limit without any error or warning. Because `field_recipe_ads_products` was the last field appended by `appendAcfPayload()`, it was consistently the first to be cut. The same mechanism caused `acf[_post_title]` to not arrive, breaking title updates.

Diagnostic confirmation: `console.log` before `form.submit()` showed the payload was correct in JS. PHP `error_log` inside `g4t_recipe_creator_extract_product_ids_from_request()` showed `field_recipe_ads_products = KEY_MISSING` in `$_POST['acf']`, while `g4t_recipe_creator_action` (submitted first) arrived correctly.

### Fix

**JS (`creator.js` — `submitToAcf()`):** product IDs and title are now submitted as flat top-level POST fields, before `appendAcfPayload()`, so they land early in the POST body and are never truncated:

```javascript
// Submitted before appendAcfPayload() — safe from max_input_vars truncation
appendField(form, 'post_title', (state.data.title || '').trim());
appendField(form, 'g4t_recipe_ads_product_ids_json', JSON.stringify(earlyProductIds));

appendAcfPayload(form, payload);  // large payload appended after
```

**PHP (`creator-form.php` — `g4t_recipe_creator_extract_product_ids_from_request()`):** reads from the new flat JSON field as primary source, with the old nested ACF path as fallback:

```php
if ( isset( $_POST['g4t_recipe_ads_product_ids_json'] ) ) {
    $decoded = json_decode( sanitize_text_field( wp_unslash( $_POST['g4t_recipe_ads_product_ids_json'] ) ), true );
    if ( is_array( $decoded ) ) {
        return g4t_normalize_recipe_bottom_strip_product_ids( $decoded );
    }
}
```

**PHP (`creator-form.php` — `g4t_recipe_creator_sync_recipe_bottom_strip_meta_after_acf_save()`):** title update moved here via `wp_update_post()` from `$_POST['post_title']`, which is a flat field and arrives reliably.

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

## Architecture

### 1) Data source and rendering boundary

- Data retrieval: `algoliaIndex.search()` called directly in `creator.js` via credentials from `G4T_RECIPE_CREATOR_DATA.adsPicker`.
- Rendering: creator-native row rendering in `product-list`, no injection of standard picker result UI.

### 2) Search boundary

- Query source: creator input `#product-search`.
- Algolia search triggered via `refreshProductResults()` with debounce.

### 3) Preview/selection boundary

- `selected-strip` is the only preview surface for selected products.
- Chip format: `{index}. {product name}`.

### 4) Save contract and fallback

- Persist selected IDs to `_g4t_a_recipe_bottom_product_ids` (JSON array of strings) via dedicated flat POST field `g4t_recipe_ads_product_ids_json`.
- On edit, if JSON key is empty, fallback to legacy `recipe_ads_products` repeater read path.

### 5) POST field ordering constraint

Fields that must be saved reliably — product IDs, post title — are submitted as flat top-level POST fields before `appendAcfPayload()`. Any future additions to the must-save set should follow the same pattern.

---

## Validation checklist

### Passed

1. ✅ Step 6 loads Algolia products into `product-list` on open.
2. ✅ Searching via `#product-search` updates Algolia results in real time.
3. ✅ `selected-strip` displays chips with index + product name for all selected items.
4. ✅ Selecting/unselecting updates state correctly.
5. ✅ Save persists real Algolia product IDs to `_g4t_a_recipe_bottom_product_ids`.
6. ✅ Edit mode loads previously saved product IDs back into `selected-strip`.
7. ✅ Post title changes are saved correctly.

### Follow-up

8. Mobile and desktop QA for step 6 interactions.
9. No regressions in summary screen and submit/update flow.
10. Verify behavior on servers with varying `max_input_vars` values.

---

## Risks and mitigations

- Risk: partial coupling to standard picker DOM.
  Mitigation: creator rendering/search controls are source of truth; Algolia access is isolated as data layer.

- Risk: duplicate selection state between provider and creator.
  Mitigation: creator state remains canonical; provider returns data only.

- Risk: performance issues with frequent search queries.
  Mitigation: debounce in creator input handler; initial hits limited by `hitsPerPage`.

- Risk: `max_input_vars` truncation on low-limit servers.
  Mitigation: all must-save data submitted as flat top-level fields before `appendAcfPayload()`.

---

## Merge readiness

- Stages 1–4 verified, root cause fixed, regression risks documented.
- Merge readiness pending follow-up QA items 8–10.
