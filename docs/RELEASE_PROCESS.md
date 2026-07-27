# Release process

1. Complete `docs/MAINTAINER_CHECKLIST.md` and the core OB checklist when protocol behavior changed.
2. Update `CHANGELOG.md` and all affected package or maintainer documentation.
3. Run the local gates:

```bash
php tools/lint.php
php packages/core/tools/protobuf/validate.php
php tests/smoke.php
php tests/integration_mock.php
bash tools/release/simulate_splits.sh
```

4. In the dependency-backed environment run:

```bash
composer install
composer proto:generate
composer dump-autoload
composer test
```

5. Run media fixtures and opt-in live tests when the protocol/media path changed.
6. Merge only to canonical monorepo `main`.
7. Choose a tag:
   - `core-v1.2.3` — publish/tag only `refatbd/free-fire-php` as `v1.2.3`;
   - `laravel-v1.2.3` — publish/tag only `refatbd/laravel-free-fire`;
   - `starter-v1.2.3` — publish/tag only `refatbd/free-fire-info-starter`;
   - `v1.2.3` — coordinated release/tag of all three distributions.
8. The workflow verifies the full monorepo, builds core generated Protobuf artifacts, converts the starter manifest from local path dependencies to stable public package dependencies, removes its monorepo-only lock file, subtree-splits each prefix, exports it and runs `tools/release/verify_split.php` before pushing.
9. Verify destination `main`, immutable destination tags and Packagist metadata.
10. If a split fails, fix the monorepo/workflow and rerun. Never patch a destination repository manually and never rewrite an existing release tag.

A compatible endpoint, credential or internal profile fix normally releases core only. A newly selectable built-in profile is registered in core and normally does not require a Laravel source release; Laravel changes only for new Laravel-facing behavior. Starter changes only when the application/UI/default environment changes.
