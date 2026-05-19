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

## Compatibility notes

- Legacy numeric values stored in post_meta remain readable.
- Theme rendering that only prints amount remains compatible.
- Consumers doing numeric math must parse/cast explicitly.

## Related files

- includes/acf/field-groups.php
- includes/view-model/normalizers.php
- includes/sync/meta-sync.php
- includes/sync/converters.php
- includes/view-model/builder.php
