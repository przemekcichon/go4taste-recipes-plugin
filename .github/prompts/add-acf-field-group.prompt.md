---
mode: agent
description: "Add a new ACF field group to the GO4TASTE Recipes Plugin, with view model keys and schema documentation."
---

# Add a New ACF Field Group

Ask the developer for:

1. **Group title** — human-readable (e.g. "Recipe SEO")
2. **Group key** — unique identifier (e.g. `group_go4taste_recipe_seo`)
3. **Fields** — for each field: key, label, name, type (`text`/`textarea`/`number`/`repeater`/`image`/`select`), sub-fields if repeater
4. **Where will this field be edited?** — Admin only (wp-admin ACF metabox)? Frontend creator form? Both?
5. **Add to view model?** — should this field be included in the recipe view model for theme templates?

---

## Steps to perform

### 1. `features/frontend-creator/acf-fields.php`

Add a new `acf_add_local_field_group()` call inside `g4t_register_acf_fields()`:

```php
acf_add_local_field_group( array(
    'key'    => 'group_go4taste_{group_slug}',
    'title'  => 'Your Group Title',
    'fields' => array(
        array(
            'key'   => 'field_{field_name}',
            'label' => 'Field Label',
            'name'  => 'recipe_{field_name}',
            'type'  => 'text',
        ),
    ),
    'location' => array(
        array(
            array(
                'param'    => 'post_type',
                'operator' => '==',
                'value'    => $strategy['post_type'],
            ),
        ),
    ),
    'position' => 'normal',
    'style'    => 'default',
) );
```

### 2. `features/frontend-creator/creator-form.php` (if creator-exposed)

If the field is editable in the frontend creator form, add a mapping in `g4t_creator_state_to_meta()` for this field (map creator state key → ACF meta key). Update `g4t_recipe_creator_validate_form_data()` to add any custom validation.

### 3. `features/recipe-display/view-model-builder.php`

In `g4t_get_recipe_acf_data()`, add:
```php
'{field_key}' => g4t_acf_field( 'recipe_{field_name}', $post_id, null ),
```

In `g4t_build_single_recipe_view_model()`, add:
```php
'{field_key}' => $data['{field_key}'] ?? null,
```

For repeaters: read the repeater rows via `g4t_acf_field()`, loop and extract sub-fields, return a clean array — follow the `g4t_get_recipe_acf_data()` pattern for `recipe_ads_products`.

### 4. `SCHEMA_CONTRACT_V2.md`

Document the new field: name, type, nullable/non-nullable, default value, whether it is ACF-only or also synced from creator.

If the change is non-additive, enforce major schema bump policy.

### 5. Tests

- PHPUnit tests for new normalizers or helpers.
- WordPress integration tests for field persistence and view model mapping behavior.
