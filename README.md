# go4taste-recipes-plugin

WordPress plugin for recipe content structure and editor experience used in go4taste.pl.

## Release Checklist

Before each production deployment:

1. Confirm repository is clean: `git status`.
2. Pull latest changes: `git pull --ff-only`.
3. Smoke test critical flows in local WP:
   - recipe editor blocks,
   - single recipe rendering,
   - archive listing rendering.
4. Push release commit to `main`.
5. Deploy plugin directory to `wp-content/plugins/go4taste-recipes-plugin/`.
6. Activate or refresh plugin on target WordPress instance.
7. Verify one recipe in wp-admin editor and one recipe page on frontend.

## Release Notes Template

Use this template for each new tag/release:

```md
# go4taste-recipes-plugin vX.Y.Z

Release date: YYYY-MM-DD
Tag: vX.Y.Z

## Summary
- Short description of the release purpose.

## Added
- New feature A

## Changed
- Updated behavior A

## Fixed
- Bug fix A

## Deployment Notes
- Plugin path: wp-content/plugins/go4taste-recipes-plugin/
- Smoke test: editor blocks, single recipe page, archive page.

## Rollback
- Roll back to previous tag: `git checkout <previous-tag>`
```
