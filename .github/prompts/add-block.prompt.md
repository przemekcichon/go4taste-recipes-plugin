---
mode: agent
description: "Register a new ACF Block in the GO4TASTE Recipes Plugin with editor controls, meta sync, and view model keys."
---

# Add a New ACF Block

Ask the developer for:

1. **Block slug** — kebab-case (e.g. `nutrition`)
2. **Block display label** — (e.g. "Nutrition Info")
3. **Block icon** — a Dashicons name (e.g. `heart`, `chart-bar`)
4. **Fields** — for each field: name (camelCase for JS, snake_case for PHP), type (`text`/`textarea`/`number`/`image`/`url`), repeater?
5. **Term-synced?** — does any field drive a taxonomy? Which key?

Note: new blocks are added to the locked CPT template by default.

---

## Steps to perform

### 1. `includes/registration/blocks.php`

Add the block name to `G4T_RECIPE_BLOCK_NAMES`:

```php
'go4taste-recipes/{slug}',
```

### 2. `assets/js/editor-blocks-new.js`

Add a `registerBlockType('go4taste-recipes/{slug}', { ... })` call.
- Copy the closest existing block as pattern (hero for simple fields, ingredients for repeaters with term lookup, steps for repeaters with images).
- `save: function() { return null; }` — always.
- CSS class: `g4t-recipes-block g4t-recipes-block--{slug}`
- All labels wrapped in `__( 'Label', 'go4taste-recipes-plugin' )`.

### 3. `includes/acf/field-groups.php`

Add a new `acf_add_local_field_group()` call inside `g4t_register_acf_fields()`. Use a unique key prefixed `group_go4taste_{slug}`.

### 4. `includes/sync/meta-sync.php`

In `g4t_get_recipe_blocks_data()`, add a new `if` block:

```php
if ( 'go4taste-recipes/{slug}' === $block['blockName'] ) {
    $result['_has_recipe_blocks'] = true;
    $result['{field_key}'] = isset( $attrs['{attrName}'] ) ? (string) $attrs['{attrName}'] : '';
}
```

In `g4t_sync_recipe_meta_from_blocks()`, add to `$payload`:

```php
'recipe_{field_key}' => sanitize_text_field( (string) ( $block_data['{field_key}'] ?? '' ) ),
```

### 5. `includes/view-model/builder.php`

In `g4t_build_single_recipe_view_model()` return array:
```php
'{field_key}' => (string) ( $data['{field_key}'] ?? '' ),
```

In `g4t_get_recipe_acf_data()`:
```php
'{field_key}' => (string) g4t_acf_field( 'recipe_{field_key}', $post_id, '' ),
```

In the `$keys` array inside `g4t_merge_recipe_data()`:
```php
'{field_key}',
```

### 6. `includes/registration/post-types.php`

Add the block to `$args['template']` in `g4t_apply_recipe_block_template()`:

```php
array( 'go4taste-recipes/{slug}' ),
```

### 7. `SCHEMA_CONTRACT_V1.md`

Document the new field: name, type, nullability, default, which block attribute maps to it.

If the change is non-additive, enforce major schema bump policy.

### 8. Tests

- PHPUnit tests for any new normalizer or helper logic.
- WordPress integration tests for block save and meta sync paths.
