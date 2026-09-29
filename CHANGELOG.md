# Changelog

## v1.1.2 - 2026-09-29

### Upstream Rate Limit Resilience & CLI Diagnostics

- **Core (`refatbd/free-fire-php`):**
  - Added dedicated `RateLimitedException` (extends `TransportException`) thrown when Garena returns HTTP 429, allowing consuming applications to detect upstream rate limiting explicitly.
- **Laravel Bridge (`refatbd/laravel-free-fire`):**
  - Updated `MediaController` (`avatar` and `banner`) to gracefully fall back to placeholder player profiles on upstream `FreeFireException` (rate limiting, network timeouts, or unreachable gateways), ensuring `<img>` tags never fail with 502 JSON errors.
  - Set default `FREEFIRE_CACHE_STORE=file` in `config/freefire.php` to safeguard MySQL database cache tables.
  - Added new Artisan command `php artisan freefire:player {uid} {--region=} {--raw}` for fast CLI player diagnostics and network verification directly from terminal.
  - Added test coverage for CLI player lookup command and media fallback resilience.

## v1.1.1 - 2026-09-28

### Binary-safe media caching and storage resilience

- Implemented Base64 `__serialize()` and `__unserialize()` on `RenderedMedia` to ensure raw image payloads serialize into safe UTF-8 strings for text-based cache drivers (e.g. MySQL `MEDIUMTEXT`).
- Base64 encoded official cached assets in `OfficialAssetDownloader` to avoid database character encoding violations.
- Added defensive exception containment on cache writes in `MediaService`, `OfficialAssetDownloader`, and `LaravelCacheStore` so cache persistence failures never block returning rendered avatars or banners.
- Automatically fallback to the `file` cache store in `FreeFireServiceProvider` when the application default cache is `database` to protect relational databases from image payload bloat.

## v1.1.0 - 2026-09-27

### OB55 recovery

- Added a versioned OB55 profile, fresh `X-GA-SV` headers, exact 64-byte MajorLogin framing and separate OB55 Protobuf sources; retained OB54 for rollback.
- Updated the seven bundled service account groups with verified pairs, including owner-approved public BR/VN replacements; incomplete environment overrides now fail explicitly.
- Require token, region and an allowlisted HTTPS player host before caching; leave OB55 field-13 tokenless responses unclassified.
- Preserve explicit-region failures and report incomplete automatic lookups instead of false player-not-found results. Added distinct Laravel error codes.
- Updated core, Laravel and starter defaults to OB55; added metadata-only manual live diagnostics and an English incident guide.
- Verified all seven groups by live login and self player lookup, plus offline PHP tests and generated Protobuf parity.
- Documented the package release version, OB55 protocol version and coordinated tag behavior in the canonical and distribution READMEs.

## v1.0.1 and earlier

### Architecture and distribution

- Created one canonical monorepo for the framework-independent core, Laravel bridge and ready-made starter application.
- Added automatic verified subtree splitting into `refatbd/free-fire-php`, `refatbd/laravel-free-fire` and `refatbd/free-fire-info-starter`.
- Added generated Protobuf build artifacts to core distribution commits, local split simulation, package export validation and immutable destination tags.

### Core

- Recovered exact OB54 login, player request and player response schemas from legacy generated descriptors.
- Added OB-versioned Protobuf packages, generated PHP namespaces and metadata namespaces for safe multi-OB coexistence.
- Added a core-owned built-in protocol profile registry plus profile-driven request, response, endpoint and media behavior, allowing future built-in OB profiles to be registered once in core.
- Added bundled credentials with environment override, AES-128-CBC, guest/MajorLogin token flow, cross-process refresh locking and normalized player lookup.
- Added bounded transport, redirect blocking, strict upstream URL validation, uint64-safe wire handling, signed-int64 UID validation, safer cache deserialization and atomic cache replacement.
- Added official CDN allowlisting, bounded ASTC downloads, ASTC payload validation, shell-free `astcenc` decoding, GD/WebP rendering with per-character Unicode font fallback, deterministic media versioning and safe fallback media.
- Centralized region-to-credential-group mapping and made environment resolution require a complete UID/password pair from one scope before falling back to group, default or bundled credentials.
