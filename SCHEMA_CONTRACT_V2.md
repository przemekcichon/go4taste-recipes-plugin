# GO4TASTE Recipes Plugin - Schema Contract v2

Last Updated: 2026-05-19
Status: Stable
Supersedes: SCHEMA_CONTRACT_V1.md

## Breaking change summary

Schema v2 changes ingredient amount type:
- v1: ingredients[].amount = float|null
- v2: ingredients[].amount = string|null

Reason:
- support culinary textual quantities, including fractions (for example: 1/3, 1 1/2, szczypta)

## Single Recipe View Model (excerpt)

PHP type: array<string, mixed>

```php
$recipe = [
    'ingredients' => [
        [
            'ingredient_term_id' => (int|null),
            'name'               => (string),
            'amount'             => (string|null),
            'unit'               => (string),
            'ingredient_note'    => (string),
        ],
    ],
];
```

## Nullability and normalization for amount

- Empty after trim -> null
- Non-empty -> preserved as string
- No numeric coercion in normalizers or converters

Examples:
- 500 -> "500"
- 1.5 -> "1.5"
- 1/3 -> "1/3"
- szczypta -> "szczypta"
- "   " -> null

## Difficulty field (additive change — v2.1)

The view model now exposes two difficulty fields:

| Field | Type | Example | Purpose |
|---|---|---|---|
| `difficulty` | `string` | `'Łatwy'` | Human-readable Polish label for display |
| `difficulty_key` | `string` | `'easy'` | Canonical key (`easy`\|`medium`\|`hard`\|`''`) for CSS classes, logic |

**Migration for existing templates:**

- Templates using `{{ recipe.difficulty }}` for display labels: no change needed.
- Templates using `{{ recipe.difficulty }}` as a CSS key (e.g. `class="difficulty--{{ recipe.difficulty }}"`) **must** switch to `{{ recipe.difficulty_key }}`.

Both fields are always present (empty string when not set).

## Compatibility notes

- Legacy numeric values stored in post_meta remain readable.
- Theme rendering that only prints amount remains compatible.
- Consumers doing numeric math must parse/cast explicitly.

## Related files

- features/frontend-creator/acf-fields.php
- features/recipe-display/normalizers.php
- features/frontend-creator/creator-meta-sync.php
- features/recipe-display/view-model-builder.php
