# FacetWP Integration and Active Filter Tags for Recipes Plugin

## Summary

This PR adds first-class FacetWP integration in the recipes plugin and extends Timber context with active filter tags used by archive/home hero UI.

## Motivation

The quick actions filter flow needs plugin-level integration points to keep FacetWP behavior aligned with recipe listing requests and to expose active taxonomy filters in a reusable context contract.

## Changes

### 1. New FacetWP integration module

File: `includes/integrations/facetwp.php`

- Added request target detection for recipe listing views.
- Added FacetWP hook callbacks for:
  - `facetwp_is_main_query`
  - `facetwp_preload_url_vars`
  - `facetwp_query_args`
  - `facetwp_template_use_archive`
- Normalizes URL vars and enforces the resolved recipe source post type for FacetWP queries.

### 2. Orchestrator hook wiring

File: `includes/class-go4taste-recipes-plugin.php`

- Added module include for `includes/integrations/facetwp.php`.
- Registered hook proxies for all FacetWP filters listed above.
- Kept orchestration-only responsibility in class layer via proxy methods.

### 3. Active filter tags in Timber context

File: `includes/timber/context-filters.php`

- Added `g4t_get_active_filter_tags()` to resolve active URL facet params to term tag objects.
- Added `recipe_archive.active_filter_tags`.
- Added `recipe_home.active_filter_tags`.
- Extended base meta with `facetwp_available` and `facetwp_prefix` fields.

## Validation

- Verified plugin changes are limited to:
  - `includes/integrations/facetwp.php`
  - `includes/class-go4taste-recipes-plugin.php`
  - `includes/timber/context-filters.php`
- Confirmed active tags are available for both archive and home contexts.
- Confirmed FacetWP callbacks are registered through orchestrator proxies.

## Backward Compatibility

- Additive change only.
- Existing context fields remain intact.
- New context keys are optional for consumers.

## Risks

- Low risk.
- Main risk area is interaction with existing FacetWP site config; mitigated by target-request checks and conservative query arg normalization.

## Rollback

Revert commit:

- `f7ecaf5`
