---
mode: agent
description: "Register a new taxonomy for the GO4TASTE Recipes Plugin — flat classification or term-synced."
---

# Add a New Taxonomy

Ask the developer for:

1. **Taxonomy key** — `snake_case` (e.g. `recipe_cuisine`)
2. **Labels** — singular name, plural name
3. **URL slug** — kebab-case (e.g. `recipe-cuisines`)
4. **Hierarchical?** — yes or no
5. **Show admin column?** — yes or no
6. **Term-synced?** — should saving a creator field automatically create/link terms? If yes, which creator field drives it?

---

## Steps to perform

### 1. `includes/registration/taxonomies.php`

Add a PHP constant at the top of the file:
```php
define( 'G4T_{TAXONOMY_UPPER}_TAXONOMY', '{taxonomy_key}' );
```

**Classification taxonomy** (not term-synced): add an entry to the `$taxonomies` array in `g4t_register_flat_taxonomies()`:

```php
G4T_{TAXONOMY_UPPER}_TAXONOMY => array(
    'labels' => array(
        'name'          => __( '{Plural}', 'go4taste-recipes-plugin' ),
        'singular_name' => __( '{Singular}', 'go4taste-recipes-plugin' ),
        'all_items'     => __( 'All {Plural}', 'go4taste-recipes-plugin' ),
        'edit_item'     => __( 'Edit {Singular}', 'go4taste-recipes-plugin' ),
        'add_new_item'  => __( 'Add New {Singular}', 'go4taste-recipes-plugin' ),
    ),
    'rewrite' => array( 'slug' => '{url-slug}' ),
),
```

**Term-synced taxonomy**: create a dedicated `g4t_register_{taxonomy_key}_taxonomy()` function following the `g4t_register_ingredient_taxonomy()` pattern (with `taxonomy_exists` guard and `register_taxonomy_for_object_type()` fallback).

### 2. `includes/class-go4taste-recipes-plugin.php` (term-synced only)

Add a proxy method and an `add_action( 'init', ... )` call in `__construct()`:

```php
add_action( 'init', array( $this, 'proxy_register_{taxonomy_key}_taxonomy' ) );
// ...
public function proxy_register_{taxonomy_key}_taxonomy(): void {
    g4t_register_{taxonomy_key}_taxonomy( $this );
}
```

### 3. `includes/slices/recipe-input-surface/recipe-creator.php` (term-synced only)

- Add `g4t_ensure_{taxonomy_key}_term( string $name ): ?int` helper in `includes/slices/recipe-storage/meta-sync.php` following `g4t_ensure_ingredient_term()` pattern.
- Call it from the creator field save path when the user selects or creates a term.

### 4. `SCHEMA_CONTRACT_V1.md`

Document: key, type (flat/hierarchical), assignment method, which block field drives it.

If taxonomy behavior changes are non-additive for consumers, enforce major schema bump policy.

### 5. Tests

- PHPUnit tests for taxonomy helper logic.
- WordPress integration tests for term creation, assignment, and sync behavior.
