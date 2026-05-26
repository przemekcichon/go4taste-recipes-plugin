# /add-taxonomy — Register a New Taxonomy

Ask the developer for the following before doing anything:

1. **Taxonomy key** — `snake_case` (e.g. `recipe_cuisine`)
2. **Labels** — singular, plural
3. **URL slug** — kebab-case (e.g. `recipe-cuisines`)
4. **Hierarchical?** — yes or no
5. **Show admin column?** — yes or no
6. **Term-synced?** — automatically create/link terms from a creator field?

---

## Execute these steps in order

### Step 1 — `includes/registration/taxonomies.php`

Add a PHP constant at the top of the file:
```php
define( 'G4T_{TAXONOMY_UPPER}_TAXONOMY', '{taxonomy_key}' );
```

**Classification taxonomy** (not term-synced): add to the `$taxonomies` array in `g4t_register_flat_taxonomies()`:

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

**Term-synced taxonomy**: create a dedicated `g4t_register_{taxonomy_key}_taxonomy()` function following the `g4t_register_ingredient_taxonomy()` pattern (with `taxonomy_exists` guard).

### Step 2 — `includes/class-go4taste-recipes-plugin.php` (term-synced only)

Add a proxy method and `add_action` in `__construct()`:

```php
add_action( 'init', array( $this, 'proxy_register_{taxonomy_key}_taxonomy' ) );
// ...
public function proxy_register_{taxonomy_key}_taxonomy(): void {
    g4t_register_{taxonomy_key}_taxonomy( $this );
}
```

### Step 3 — `includes/slices/recipe-input-surface/recipe-creator.php` (term-synced only)

- Add `g4t_ensure_{taxonomy_key}_term( string $name ): ?int` following `g4t_ensure_ingredient_term()`.
- Call it from the relevant block's parsing block inside `g4t_get_recipe_blocks_data()`.

### Step 4 — `SCHEMA_CONTRACT_V1.md`

Document: key, type (flat/hierarchical), assignment method, which block drives it.

If non-additive, follow major schema bump policy.

### Step 5 — tests

- PHPUnit tests for taxonomy helper logic.
- WordPress integration tests for term creation, assignment, and sync.
