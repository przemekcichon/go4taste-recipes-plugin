# Schema migration v1 -> v2

## Scope

This migration covers ingredient amount semantics only.

Changed field:
- ingredients[].amount: float|null -> string|null

## Why

Recipes require textual and fractional quantities that cannot be represented as float safely in authoring UX.

## Impact

- ACF ingredient amount field is now text input.
- View model preserves non-empty amount as string.
- Meta loaders/converters no longer cast amount to float.

## Backward compatibility

- Reading v1 data in v2: compatible.
  - Example: stored 500 becomes "500" in view model.
- Downgrade from v2 to v1: not guaranteed.
  - Example: 1/3 may be dropped by numeric-only logic in v1.

## Required checks after upgrade

1. Edit a recipe in ACF and save amount as 1/3.
2. Confirm block content keeps amount as 1/3.
3. Confirm front-end shows amount as 1/3.
4. Confirm existing recipes with numeric amounts still render.

## Optional data backfill

No mandatory DB migration is required.
If needed, normalize legacy numeric formatting in place (for example, 500.0 -> 500) with a one-time maintenance script.
