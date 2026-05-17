# Remove Unused `recipe_archive.total_posts` Context Field

## Summary

This PR removes an unused archive context field from the plugin:

- Removed `recipe_archive.total_posts` assignment from archive context filter.
- Updated schema contract documentation to match runtime context shape.

## Motivation

Archive total counter is now handled in theme/UI layer, and plugin no longer needs to inject `recipe_archive.total_posts`.
Keeping it in plugin context created contract drift and unnecessary coupling.

## Changes

### 1. Archive context cleanup

File: `includes/timber/context-filters.php`

- Removed block that read global `WP_Query` and injected:
  - `$context['recipe_archive']['total_posts'] = (int) $wp_query->found_posts;`

Result: archive context now contains only fields actually used by plugin contract for archive scope.

### 2. Schema contract alignment

File: `SCHEMA_CONTRACT_V1.md`

- Removed `total_posts` from `recipe_archive` shape documentation.
- Kept `title`, `description`, `post_type`, and taxonomy `hero` mapping intact.

## Validation

- Verified plugin files changed only in:
  - `includes/timber/context-filters.php`
  - `SCHEMA_CONTRACT_V1.md`
- No runtime behavior change expected in plugin for archive rendering logic besides removal of unused context key.

## Backward compatibility

- This is safe for current setup where archive total is computed/displayed in theme layer.
- Consumers relying on `recipe_archive.total_posts` should switch to theme/query-derived count.

## Risks

- Low risk.
- Potential only if external code depends on deprecated `recipe_archive.total_posts` key.
