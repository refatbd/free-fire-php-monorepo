# Live protocol verification

Use this release gate after changing an OB profile, credential group, endpoint, schema or token behavior. Live testing is opt-in and uses only owned test accounts and non-sensitive player fixtures. Never run it in public CI or print upstream secrets.

## Preconditions

- the exact OB client/profile source is recorded;
- credentials are configured as complete environment pairs or intentionally use bundled private defaults;
- outbound HTTPS, DNS and the system clock are healthy;
- Composer dependencies and generated Protobuf classes exist;
- logs are protected and application debug output is not publicly reachable.

## Offline preflight

From the monorepo root:

```bash
composer install
composer lint
composer proto:validate
composer proto:generate
composer dump-autoload
composer test
```

Do not proceed when request-byte, crypto, decoder, security or documentation tests fail.

## Test matrix

Test at least one representative region for every distinct account group, then every production region affected by the change:

| Group | Minimum representative | Additional mapped regions to check when affected |
|---|---|---|
| `IND` | `IND` | none |
| `AMERICAS` | `BR` | `US`, `SAC`, `NA`, `EUROPE`, `EU` |
| `VN` | `VN` | none |
| `ID` | `ID` | none |
| `TH` | `TH` | none |
| `TW` | `TW` | none |
| `GLOBAL` | `BD` | `SG` and other supported global aliases |

For each region prepare one authorized known-valid player UID and one syntactically valid but nonexistent UID. Keep these in a private test inventory, not committed fixtures.

## Execute a region check

With the starter running, first force authentication:

```bash
php artisan optimize:clear
php artisan freefire:tokens-refresh --region=BD
```

Then request the player endpoint, substituting the private test UID and local base URL:

```text
GET /api/free-fire/v1/players/{UID}?region=BD
```

Also exercise automatic region detection through the application's supported no-region path. A failure in one unavailable gateway must remain non-fatal so later candidates can run.

Verify, without printing values:

1. credential scope selected as expected;
2. guest token returned required fields;
3. MajorLogin decoded a non-empty bearer, lock region and allowed HTTPS server URL;
4. TTL is bounded and a second request uses cache;
5. forced refresh obtains a new valid token safely;
6. encrypted player request reaches the MajorLogin-returned server;
7. known-valid UID normalizes required public sections;
8. nonexistent UID produces a controlled not-found/upstream result, not a crash;
9. malformed UID is rejected locally;
10. public response/log context contains no credentials, tokens, crypto material or raw body.

Repeat after token expiry or with a deliberately expired sanitized cache fixture to verify refresh locking. If media is enabled, run `php artisan freefire:media-check`; lookup JSON must still succeed when ASTC decoding is unavailable.

## Sanitized evidence record

Store a private or secret-free release record in this shape:

```text
Date/time (UTC):
Operator:
OB profile and commit:
Official client build/hash:
Credential group / region (no UID or password):
Guest token stage: PASS/FAIL + status only
MajorLogin stage: PASS/FAIL + duration/status only
Returned host: approved/expected (omit full signed URLs)
Valid UID lookup: PASS/FAIL + normalized-field list
Invalid UID lookup: PASS/FAIL + public error code
Cache/refresh/lock: PASS/FAIL
Secret-redaction review: PASS/FAIL
Media fallback: PASS/FAIL/NOT TESTED
```

Never attach raw request/response bodies, `.env`, account pairs, access/open-ID/bearer values or unsanitized player data.

## Release gate and rollback

Release only when offline tests pass, every changed group passes live validation, safe failures are confirmed and the previous profile remains selectable. If rollout differs by region, keep the new profile opt-in for verified deployments. Roll back by selecting the last known-good OB profile and restoring the previous credential override; do not delete failed-profile evidence until the incident is understood.
