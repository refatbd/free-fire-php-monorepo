# Free Fire OB update guide

This is the authoritative runbook for adding a new Free Fire OB release. Do not modify the existing OB profile in place. Add a new versioned profile, protocol directory, fixtures, tests, and changelog entry so rollback remains possible.

Use `OB_PROTOCOL_CAPTURE.md` for the controlled extraction workflow, `ACCOUNT_CREDENTIAL_CAPTURE.md` for account rotation, `TOKEN_GENERATION_FLOW.md` for the automatic authentication sequence, and `LIVE_PROTOCOL_VERIFICATION.md` for the release gate. This checklist coordinates those detailed runbooks.

## Fast update map

| What changed in the new OB | Canonical location to inspect/update | Usually requires Laravel/starter edits? |
|---|---|---|
| OB/release/client/user-agent values | `src/Protocol/Profiles/ObXXProtocolProfile.php` | No |
| Guest/MajorLogin URL or binary headers | Versioned protocol profile | No |
| AES key/IV or request encryption behavior | Versioned protocol profile and crypto golden fixtures | No |
| Login or player Protobuf fields | `protocol/obXX/*.proto` plus sanitized fixtures | No |
| Region aliases or gateway behavior | `src/Region/RegionRegistry.php` and profile tests | No |
| Required bundled accounts | `src/Credentials/BundledCredentialProvider.php` | No |
| Player response field names | Decoder/normalizer and public compatibility tests | Only when exposing new UI |
| Official asset hosts/path/ASTC behavior | `src/Media/` and media fixtures | Only when config/diagnostics change |
| New API option needed by Laravel users | `packages/laravel` in the monorepo | Yes |
| New player field displayed by starter | `apps/starter` in the monorepo | Starter only |

## Never do these

- Never overwrite the previous OB profile.
- Never manually edit generated PHP Protobuf classes.
- Never copy core protocol/credential logic into Laravel or starter.
- Never put bearer tokens, open IDs, passwords or unsanitized player responses in fixtures.
- Never patch a split repository directly.
- Never publish until Python/reference and PHP golden fixtures agree.

## Release decision

```text
Protocol/profile/account/endpoint changed only
    -> update core and create core-vX.Y.Z

Laravel binding/config/route changed
    -> update core when needed + Laravel and create laravel-vX.Y.Z

Starter presentation changed
    -> update starter and create starter-vX.Y.Z

Coordinated release of all distributions
    -> create vX.Y.Z
```

## 1. Create the update workspace

Copy the previous profile and protocol directory:

```text
src/Protocol/Profiles/Ob54ProtocolProfile.php
    -> Ob55ProtocolProfile.php
protocol/ob54/
    -> protocol/ob55/
tests/Fixtures/Protocol/OB54/
    -> tests/Fixtures/Protocol/OB55/
```

Register the new profile once in core `BuiltInProtocolProfiles::classes()` and select it with `FREEFIRE_PROTOCOL=OB55`. Laravel merges built-in profiles with optional custom overrides, so old published Laravel config files do not need to duplicate every new built-in profile. Plain PHP callers pass the same profile instance to both `FreeFireFactory::make()` and `FreeFireFactory::makeMedia()` so player and media cache behavior stay on the same OB version.

Record exact release date, OB label, client build, APK/package variant, source environment, and regions tested. Do not rely on a social post or guessed version string.

## 2. Inspect protocol/profile values

Review and update only when verified:

- `ReleaseVersion` header and OB label;
- user agent and Android build identity;
- Unity version;
- guest-token URL;
- MajorLogin URL;
- client ID and client secret;
- AES key and IV;
- required binary headers;
- fallback token TTL;
- player endpoint path and region-returned server URL behavior.

Location:

```text
src/Protocol/Profiles/ObXXProtocolProfile.php
```

Never log or expose changed secrets while collecting them.

Follow `OB_PROTOCOL_CAPTURE.md` when locating these values and proving that constants, encodings and bytes all came from the same official client build.

## 3. Recover and diff Protobuf schemas

Collect the generated descriptor from the controlled client/reference implementation. Export a normalized map containing package, message, field name, field number, scalar/message type, label, oneof, enum values, and dependencies. Compare it with `protocol/obXX`.

Classify every change:

- additive compatible field;
- renamed field with same number/type;
- changed wire type;
- changed message dependency;
- removed/reserved field;
- enum addition/value change;
- request shape change.

Every canonical schema must be collision-safe with older OB versions:

- the Protobuf `package` must contain the lowercase OB segment, for example `freefire.ob55`;
- `php_namespace` must contain `Generated\\Ob55\\...`;
- `php_metadata_namespace` must contain `Generated\\Ob55\\Metadata`;
- the new profile's `playerResponseMessageClass()` must return the generated OB55 response class;
- `BuiltInProtocolProfiles::classes()` must register `Ob55ProtocolProfile::class`.

These package/name changes do not alter wire bytes; field numbers and wire types control compatibility. Never reuse a removed field number. Never manually edit generated PHP classes. Contributors run the following commands after accepting schema changes. The generator scans every `protocol/ob*` directory, and the split workflow repeats generation and includes the generated classes in the core distribution:

```bash
composer proto:validate
composer proto:generate
```

## 4. Create golden protocol fixtures

For fixed non-secret inputs produce:

```text
tests/Fixtures/Protocol/OBXX/login-request.bin
tests/Fixtures/Protocol/OBXX/login-encrypted.bin
tests/Fixtures/Protocol/OBXX/login-response.sanitized.bin
tests/Fixtures/Protocol/OBXX/player-request.bin
tests/Fixtures/Protocol/OBXX/player-encrypted.bin
tests/Fixtures/Protocol/OBXX/player-response.sanitized.bin
tests/Fixtures/Protocol/OBXX/player-response.normalized.json
```

Compare Python/reference and PHP request bytes byte-for-byte. Compare AES ciphertext byte-for-byte using the same key, IV, padding, and plaintext. Replace real tokens, open IDs, account data, and server-specific identifiers before committing response fixtures.

## 5. Validate default credentials and account pools

Bundled defaults live only in `BundledCredentialProvider`; server overrides are resolved by `EnvironmentCredentialProvider`. Test each distinct account group:

- IND;
- Americas/Europe mapping;
- VN;
- ID;
- TH;
- TW;
- global gateway used by BD/SG/ME/PK/CIS and fallbacks.

Verify guest token, MajorLogin, lock region, server URL, TTL, bans, rate limits, and fallback/rotation behavior. If only a credential changes, update core only and issue an appropriate patch release.

Follow `ACCOUNT_CREDENTIAL_CAPTURE.md` for complete-pair resolution and rotation, then `LIVE_PROTOCOL_VERIFICATION.md` for the group-by-region evidence matrix.

## 6. Verify regions and endpoints

For every supported region verify:

- alias normalization;
- TLS and hostname;
- redirect policy;
- required headers;
- status and content type;
- timeout/retry behavior;
- server URL returned by MajorLogin;
- `/GetPlayerPersonalShow` compatibility.

Do not hardcode a returned regional server URL unless the protocol requires it.

## 7. Verify response normalization

Check old and new fields for basic, clan, captain, social, pet, rank/MMR, equipped items, avatar/head picture, banner, guild, credit score, titles, and newly introduced structures. Preserve the public normalized keys whenever possible:

```json
{"basicInfo":{},"clanBasicInfo":{},"captainBasicInfo":{},"socialInfo":{},"mediaInfo":{}}
```

An upstream rename must normally be handled by the decoder/normalizer, not forced onto package consumers.

## 8. Verify official media and ASTC

Check asset CDN bases, allowlisted hosts, item path convention, ASTC magic/header, block dimensions, texture dimensions, image orientation, avatar/banner IDs, decoder output, WebP encoding, fallback behavior, and cache versioning.

Use OB-specific cache namespaces:

```text
freefire:media:OBXX:{itemId}:{sourceHash}:{renderSettings}
```

Run the Laravel diagnostic and malicious/truncated fixture tests. If hosting lacks decoder/WebP support, data lookup must still work.

## 9. Run regression matrix

Minimum matrix:

- `php tools/lint.php`;
- `php packages/core/tools/protobuf/validate.php`;
- `php tests/smoke.php` and `php tests/integration_mock.php`;
- `bash tools/release/simulate_splits.sh`;
- PHP 8.2, 8.3, 8.4 and any newer supported version;
- supported Laravel major versions;
- plain PHP factory usage;
- every region and credential group;
- valid/invalid/missing UID;
- token cache, expiry, forced refresh, and concurrent lock;
- login/player protocol fixtures;
- ASTC safety and fallback;
- route throttle and safe production errors;
- no secret leakage in logs/API/fixtures;
- starter homepage and result page.

## 10. Version and release

- Endpoint/profile/credential compatible fix: patch version.
- New backward-compatible OB profile/support: minor version.
- Public PHP API breaking change: major version.

Usually only `packages/core` requires protocol source changes for an OB update: add the profile, schema, built-in registry entry, fixtures and tests there. Laravel automatically merges the core built-in registry and needs changes only for genuinely new Laravel-facing configuration or behavior. Edit the starter only when its default `.env.example`, UI or exposed fields change. Release from the monorepo, run `bash tools/release/simulate_splits.sh`, and verify automatic split repositories; never hot-fix a split repository.

## 11. Rollback

Keep the previous profile selectable until the new profile has passed live validation. If upstream rollout differs by region, use separate deployment configurations until an explicit region-profile resolver is introduced. Roll back by selecting the last known-good profile and releasing from the monorepo; do not delete the failed profile or its fixtures until the incident is documented.
