# /add-acf-field-group — Add a New ACF Field Group

Ask the developer for the following before doing anything:

1. **Group title** — human-readable (e.g. "Recipe SEO")
2. **Group key** — unique identifier (e.g. `group_go4taste_recipe_seo`)
3. **Fields** — for each field: key, label, name, type (`text`/`textarea`/`number`/`repeater`/`image`/`select`), sub-fields if repeater

---

## Execute these steps in order

### Step 1 — `includes/acf/field-groups.php`

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

### Step 2 — `includes/view-model/builder.php`

In `g4t_get_recipe_acf_data()`, add:
```php
'{field_key}' => g4t_acf_field( 'recipe_{field_name}', $post_id, null ),
```

In `g4t_build_single_recipe_view_model()`, add:
```php
'{field_key}' => $data['{field_key}'] ?? null,
```

Add `'{field_key}'` to the `$keys` array in `g4t_merge_recipe_data()`.

For repeaters: follow the `recipe_ads_products` pattern in `g4t_build_single_recipe_view_model()` — call `g4t_acf_field()`, loop rows, extract sub-field values.

### Step 3 — `SCHEMA_CONTRACT_V1.md`

Document: field name, type, nullable/non-nullable, default, ACF-only or block-synced.

If the change is non-additive, follow major schema bump policy.

### Step 4 — tests

- PHPUnit tests for new helpers or normalizers.
- WordPress integration tests for field persistence and view model merge.
