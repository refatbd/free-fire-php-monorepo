# Free Fire PHP Monorepo

Canonical development repository for the Free Fire player-information engine, its Laravel integration, and the ready-to-use Laravel starter application.

> This is an unofficial community project. It is not affiliated with or endorsed by Garena.

## Packages

| Path | Published package/repository | Purpose |
|---|---|---|
| `packages/core` | `refatbd/free-fire-php` | Framework-independent PHP engine |
| `packages/laravel` | `refatbd/laravel-free-fire` | Laravel service provider, facade, routes, controllers, and commands |
| `apps/starter` | `refatbd/free-fire-info-starter` | Ready-made Laravel checker site |

All development happens in this monorepo. Split repositories are generated automatically.

## Version and release status

**Latest coordinated release: [`v1.1.1`](https://github.com/refatbd/free-fire-php-monorepo/tree/v1.1.1) (binary-safe cache & OB55).** The previous [`v1.1.0`](https://github.com/refatbd/free-fire-php-monorepo/tree/v1.1.0) release does not include the MySQL binary caching fix. Both tags apply to this monorepo and all three split repositories.

The package release number (`vX.Y.Z`) and the game protocol number (`OB55`) are different. A coordinated `vX.Y.Z` tag in this monorepo publishes the same version to the core, Laravel and starter repositories. A `core-vX.Y.Z`, `laravel-vX.Y.Z` or `starter-vX.Y.Z` tag publishes only the named distribution. See the [release process](docs/RELEASE_PROCESS.md) and [changelog](CHANGELOG.md). Create release tags only after the intended README and code changes are committed on `main`.

**Current protocol: OB55.** The PHP core, Laravel package and starter default to OB55. The seven bundled account groups passed live login and self player lookup on 2026-09-27. See the [OB55 migration record](packages/core/docs/OB55_MIGRATION.md) for evidence, diagnostics and limitations.

## Key Features

- **Automatic Global Region Detection**: Single UID lookup automatically scans all Garena regional gateways (`BD`, `SG`, `IND`, `BR`, `VN`, `ID`, `TH`, `TW`) without requiring country selection.
- **Comprehensive Profile Statistics**: Displays 100% of player data across 5 detailed cards (Account Info, Activity, Overview, Pet Details, and Guild/Leader Details).
- **High-Contrast Banner Graphic Engine**: Bold, heavy sans-serif typography with solid black outlines and clean bottom-right level badge layout.
- **Protobuf & Garena API Engine**: Versioned OB54/OB55 Protobuf schemas, guest token auth, MajorLogin JWT, regional encryption codecs, and built-in pure PHP wire decoder fallback.
- **Official Media Engine**: Downloads ASTC textures from official Garena CDNs (`dl-tata.freefireind.in`), decodes via `astcenc` (Linux & Windows), and renders high-quality WebP avatars and banners.
- **Clean Banner Mode (`raw=1`)**: Supports pure uncomposited ASTC texture background graphics for custom HTML/CSS overlays, as well as composited in-game banner graphics.
- **Diagnostic Command**: `php artisan freefire:media-check` inspects server capabilities, `proc_open` availability, and binary resolution with step-by-step fix guidance.
- **Graceful Fallback**: Automatically degrades to PHP GD gradient graphics if `proc_open` or `astcenc` is missing on cheap shared hosting without crashing player API responses.

## Server Deployment & Media Checker

Run the diagnostic command on your server to verify setup:

```bash
php artisan freefire:media-check
```

### Quick Server Setup Guide

* **Linux (Ubuntu / Debian / VPS)**: Run `sudo apt update && sudo apt install astc-encoder`
* **cPanel / Shared Hosting**: leave `FREEFIRE_ASTCENC_BINARY=astcenc`; the core package auto-detects its bundled Linux decoder when `proc_open` is allowed
* **Windows**: leave `FREEFIRE_ASTCENC_BINARY=astcenc`; the core package auto-detects its bundled Windows decoder

## Contributor Build

```bash
composer install
composer proto:validate
composer proto:generate
composer dump-autoload
composer test
```

## Protocol and credential maintenance

Detailed maintainer runbooks are shipped with the core package in `packages/core/docs/`:

- [`OB_PROTOCOL_CAPTURE.md`](packages/core/docs/OB_PROTOCOL_CAPTURE.md) — obtain and verify a new OB profile from a controlled official client;
- [`ACCOUNT_CREDENTIAL_CAPTURE.md`](packages/core/docs/ACCOUNT_CREDENTIAL_CAPTURE.md) — provision, configure and rotate owned test-account pairs;
- [`TOKEN_GENERATION_FLOW.md`](packages/core/docs/TOKEN_GENERATION_FLOW.md) — understand automatic guest-token, MajorLogin and bearer generation;
- [`LIVE_PROTOCOL_VERIFICATION.md`](packages/core/docs/LIVE_PROTOCOL_VERIFICATION.md) — run the group/region release matrix without leaking secrets;
- [`OB_UPDATE_GUIDE.md`](packages/core/docs/OB_UPDATE_GUIDE.md) — coordinate the complete versioned update and rollback.
- [`OB55_MIGRATION.md`](packages/core/docs/OB55_MIGRATION.md) — understand the OB55 incident, PHP fixes and verified account groups.

The package retains bundled service accounts for install-and-run behavior. Complete server-side environment pairs override them without source edits; generated access/open-ID/bearer tokens are never manually configured. A partial override is an error.
