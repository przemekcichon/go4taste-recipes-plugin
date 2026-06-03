# PR: Recipe Creator Product Discovery UI — Algolia Data in Existing Frontend

**Branch:** feature/picker-ui-extensibility -> main

## Implementation status

| Stage | Status | Description |
|---|---|---|
| Stage 1: Algolia list in existing `product-list` | ✅ Verified | Algolia products load into `product-list` on step 6 open |
| Stage 2: Search bridge via existing `.search-box` | ✅ Verified | Search input drives Algolia query; results update `product-list` |
| Stage 3: Preview in existing `selected-strip` | ✅ Verified | Selected products render as chips with index + name |
| Stage 4: Persistence + compatibility guard | ✅ Fixed and verified | `_g4t_a_recipe_bottom_product_ids` persists correctly after save |
| Stage 5: Docs + QA checklist | ✅ Updated | All root causes documented; regression risks noted |
| Stage 6: New recipe publish flow | ✅ Fixed and verified | Correct post ID in redirect, no orphaned drafts |

---

## Bug fixes during QA

### Bug 1: Products not persisted after save

**Symptom:** After selecting Algolia products in step 6 and saving, `_g4t_a_recipe_bottom_product_ids` was persisted as empty string. Title changes were also not saved.

```sql
-- before fix
SELECT meta_value FROM wp_postmeta
WHERE post_id = 2299 AND meta_key = '_g4t_a_recipe_bottom_product_ids';
-- result: ""

-- after fix
-- result: '["99","98","97","96"]'
```

**Cause:** ACF front-end forms encode a signed `_acf_form` token with the allowed field groups. When processing the submission ACF strips any `$_POST['acf']` keys that don't belong to a declared group. `group_recipe_ads` is not in the creator's `field_groups`, so ACF removed `field_recipe_ads_products` and `_post_title` before our `acf/save_post` hook ran. Confirmed by comparing browser DevTools payload (77 variables, all present) against PHP `array_keys($_POST['acf'])` in debug log (only declared-group fields visible).

**Fix:** Submit product IDs and post title as flat top-level `$_POST` fields outside `acf[...]`, before `appendAcfPayload()`. ACF does not touch top-level `$_POST` fields.

```javascript
// JS — submitToAcf(): flat fields submitted before ACF payload
appendField(form, 'post_title', (state.data.title || '').trim());
appendField(form, 'g4t_recipe_ads_product_ids_json', JSON.stringify(earlyProductIds));
appendAcfPayload(form, payload);
```

```php
// PHP — primary read path uses flat field; ACF nested path is fallback
if ( isset( $_POST['g4t_recipe_ads_product_ids_json'] ) ) {
    $decoded = json_decode( sanitize_text_field( wp_unslash( $_POST['g4t_recipe_ads_product_ids_json'] ) ), true );
    if ( is_array( $decoded ) ) {
        return g4t_normalize_recipe_bottom_strip_product_ids( $decoded );
    }
}
```

Title update moved to `g4t_recipe_creator_sync_recipe_bottom_strip_meta_after_acf_save()` via `wp_update_post()` from `$_POST['post_title']`.

---

### Bug 2: New recipe redirected to `post_id=0`, saved as orphaned draft

**Symptom:** After publishing a new recipe through the creator, redirect URL was `?saved=1&status=published&post_id=0` with "Recipe not found or you do not have permission to edit it." The post existed but as a draft. Products were partially missing (2 out of 4).

**Cause (two parts):**

**Part A — ACF passes integer `0`, not string `'new_post'`:**
Debug log revealed ACF passes `post_id = 0` (integer) to `acf/pre_save_post`, not `'new_post'` (string). Our guard `'new_post' !== $post_id` is TRUE for `0`, so we returned `0` immediately without creating a post or using the heartbeat draft.

```
[G4T pre_save_post] received post_id=0 type=integer
[G4T pre_save_post] g4t_heartbeat_draft_id=2360
[G4T pre_save_post] early return (not new_post), returning=0
```

**Part B — `%post_id%` URL-encoded by `add_query_arg`:**
The return URL was built with `add_query_arg('post_id', '%post_id%', ...)`. WordPress's `build_query` URL-encodes the `%` signs, producing `post_id=%25post_id%25`. ACF's `str_replace('%post_id%', $id, $url)` could not find the pattern. The browser decoded `%25post_id%25` back to `%post_id%`, and `(int)'%post_id%'` = `0`.

**Part C — orphaned draft + missing products:**
Heartbeat had already created a draft (ID 2360) with 2 products. Form submit created a different published post (unreachable due to redirect bug). User found and published the heartbeat draft, which only had the 2 heartbeat-saved products — explaining the partial product count.

**Fix (three parts):**

**Part A — `creator-helpers.php`:** treat `$post_id <= 0` as equivalent to `'new_post'`:
```php
$is_new_post = ( 'new_post' === $post_id ) || ( is_numeric( $post_id ) && (int) $post_id <= 0 );
```

When `$is_new_post` is true and a heartbeat draft exists, publish that draft instead of creating a new post — avoiding orphaned duplicates:
```php
$heartbeat_draft_id = isset( $_POST['g4t_heartbeat_draft_id'] ) ? (int) $_POST['g4t_heartbeat_draft_id'] : 0;
if ( $heartbeat_draft_id > 0 && g4t_can_user_edit_recipe( $heartbeat_draft_id ) ) {
    wp_update_post( array( 'ID' => $heartbeat_draft_id, 'post_status' => $target_status, ... ) );
    return $heartbeat_draft_id;
}
```

**Part B — `creator-helpers.php` / `g4t_get_recipe_form_config()`:** build return URL without passing `%post_id%` through `add_query_arg`:
```php
// For new posts: append %post_id% as raw string, not via add_query_arg
$return_url = add_query_arg( array( 'saved' => '1', 'status' => 'published' ), get_the_permalink() )
    . '&post_id=%post_id%';
```

**Part C — `creator.js` / `submitToAcf()`:** pass heartbeat draft ID as early flat field:
```javascript
if (!isEditMode && autosavePostId > 0) {
    appendField(form, 'g4t_heartbeat_draft_id', autosavePostId);
}
```

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
5. New recipe publish flow redirects correctly with the actual post ID.

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

### 5) ACF field filtering constraint

ACF filters `$_POST['acf']` to only the field groups declared in `acf_form()`. Any data that must survive the save cycle and is not in a declared group must be submitted as a top-level `$_POST` field outside `acf[...]`. The product IDs (`g4t_recipe_ads_product_ids_json`), post title (`post_title`), and heartbeat draft ID (`g4t_heartbeat_draft_id`) follow this pattern. Any future additions to the must-save set should do the same.

### 6) Database state — two storage formats in parallel

Both meta keys can exist simultaneously for a single post. They are independent:

```
-- Post saved via creator (after this PR):
_g4t_a_recipe_bottom_product_ids = ["98","97","96","95"]  ← new contract, Algolia IDs
recipe_ads_products               = 2                      ← ACF repeater row count (legacy)
recipe_ads_products_0_product_id  = p1                     ← legacy repeater row 0
recipe_ads_products_1_product_id  = p2                     ← legacy repeater row 1
```

`recipe_ads_products` is the ACF repeater row **count** (not a product ID). The actual IDs are in the `_N_product_id` sub-keys. This repeater format was written by an older version of the creator and is now only updated via the wp-admin ACF metabox.

Frontend read priority (`view-model-builder.php` + `go4taste-ads`):
1. `_g4t_a_recipe_bottom_product_ids` — primary, read by go4taste-ads directly
2. `recipe_ads_products_N_product_id` — fallback via `g4t_get_ads_product_ids_from_acf()`, only reached when the primary key is empty

For 140 pre-existing production recipes the primary key is empty and the fallback is active. Backfill migration (ACF repeater → `_g4t_a_recipe_bottom_product_ids`) is scoped to a separate branch and PR.

### 7) New recipe publish flow

When creating a new recipe:
1. Heartbeat may create a draft before the user clicks "Opublikuj". The draft ID is stored in `autosavePostId` in JS.
2. On submit, `g4t_heartbeat_draft_id` is passed as a flat POST field.
3. PHP's `acf/pre_save_post` filter detects ACF's `post_id = 0` (the integer ACF uses for new posts — not the string `'new_post'`) and routes to either: updating the heartbeat draft to published, or creating a fresh post if no draft exists.
4. The return URL appends `%post_id%` as a raw string (not via `add_query_arg`) so ACF can replace it correctly.

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
8. ✅ New recipe publish redirects to correct post ID (not `post_id=0`).
9. ✅ Heartbeat draft is reused on publish — no orphaned duplicate posts.

### Follow-up

10. Mobile and desktop QA for step 6 interactions.
11. No regressions in summary screen and submit/update flow.

---

## Risks and mitigations

- Risk: partial coupling to standard picker DOM.
  Mitigation: creator rendering/search controls are source of truth; Algolia access is isolated as data layer.

- Risk: duplicate selection state between provider and creator.
  Mitigation: creator state remains canonical; provider returns data only.

- Risk: performance issues with frequent search queries.
  Mitigation: debounce in creator input handler; initial hits limited by `hitsPerPage`.

- Risk: ACF silently strips undeclared `acf[...]` fields on any surface that uses `acf_form()` without the ads group.
  Mitigation: must-save data that crosses plugin boundaries is submitted as top-level `$_POST` fields, not inside `acf[...]`.

- Risk: ACF passes integer `0` instead of string `'new_post'` for new post forms — version-dependent behavior.
  Mitigation: `g4t_recipe_creator_force_pre_save_post` treats both `'new_post'` and `<= 0` as "new post" case.

---

## Merge readiness

- All stages verified. Both post-QA bugs diagnosed and fixed.
- Follow-up QA items 10–11 recommended before merge to main.
