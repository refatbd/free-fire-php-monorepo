# OB55 migration and verification (2026-09-27)

## Incident and correction

The old default OB54 MajorLogin request returned HTTP 503 during the OB55 release. Changing only `ReleaseVersion` to OB55 returned HTTP 400. Sending a fresh Unix timestamp in `X-GA-SV` with `ReleaseVersion: OB55` succeeded. Successful OB55 MajorLogin bodies observed for the tested accounts contain 64 bytes before the login Protobuf. Player-show responses remain unprefixed. The existing AES key/IV, request field numbers and login host worked for this release.

Core now selects `Ob55ProtocolProfile` by default in plain PHP and Laravel. OB54 remains registered for controlled rollback, but the current upstream did not accept the old OB54 request. The OB55 profile adds `X-GA-SV` to each binary request and the login decoder removes exactly 64 bytes for OB55 only. A usable response must include token, lock region and a known HTTPS player host before caching. A tokenless field-13 response is reported as `UNRECOGNIZED_LOGIN_RESPONSE` by the Laravel API; its meaning has **not** been confirmed as a queue or a ban.

The OB55 `.proto` directory keeps the observed compatible player/request field layout in a separate namespace. OB55 login field 13 is named `unverified_status` and stored as bytes because the legacy queue interpretation is unreliable. Full official OB55 descriptor provenance and untested fields remain open research tasks; do not infer their semantics from these copied field names.

## Accounts

`BundledCredentialProvider` is this PHP package's source of bundled service accounts. It does not read the Python site's `accounts.txt`. The seven active pairs were synchronized with the verified `FreeFireInfoSite` configuration. The owner explicitly authorized public distribution of the replacement BR/VN guest test accounts. The original PHP BR/VN pairs were identical to the old Python pairs that returned tokenless replies; other old inventory rows were not evaluated. No conclusion about ban status or the exact error code is justified.

The account groups are `IND`, `AMERICAS` (BR/US/SAC/NA/EUROPE), `VN`, `ID`, `TH`, `TW`, and `GLOBAL` (BD/SG/ME/PK/CIS/RU). A complete `FREEFIRE_<REGION>_UID` and `FREEFIRE_<REGION>_PASSWORD` override wins over the group pair. A partial override now fails explicitly; it cannot silently fall back to a different account. Restart workers and clear token caches after rotating a pair.

## Verification

From the monorepo root:

```bash
composer install
composer proto:validate
composer proto:generate
composer dump-autoload --optimize
composer test
php tools/diagnose-live.php --self-lookup
```

The final command is a **manual live check** and must not run in public CI. It prints status, lock region, host and response section names only. It reads each guest's own player UID from its bearer token in memory; it never prints account IDs, credentials, tokens or raw player data. `--regions=BR,VN` narrows the check. Environment overrides apply to this command too.

On 2026-09-27, all seven groups passed OB55 login and their own player lookup in PHP. BR and VN also passed after local generated Protobuf classes were present. These are point-in-time results; shared service accounts and upstream routing may change. A fresh checkout must verify the bundled pairs again before a future release.

The Laravel player API distinguishes `PLAYER_NOT_FOUND` (422), `CREDENTIAL_CONFIG_ERROR` (503), `LOOKUP_INCOMPLETE` (503), `UNRECOGNIZED_LOGIN_RESPONSE` (502) and upstream failures (502). An explicit region now preserves that region's failure. Automatic lookup reports an incomplete result when any gateway failed and no gateway found the player.

For the next OB release, start with [OB update guide](OB_UPDATE_GUIDE.md) and [live verification](LIVE_PROTOCOL_VERIFICATION.md). Record observed headers, framing, required fields and regional results before changing a profile. Keep old profiles and sanitized fixtures for rollback.
