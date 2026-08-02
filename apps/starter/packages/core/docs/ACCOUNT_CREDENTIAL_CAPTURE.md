# Account credential capture and rotation

This runbook explains how maintainers obtain, validate, store and rotate the guest-account pairs used by the official Free Fire login flow. Use only accounts and devices you own or are authorized to test. The repository does not mathematically generate account credentials, bypass account access controls, or recover another user's password.

## What must be maintained

Each login identity is a complete pair:

```text
UID + password
```

The pair is sent only to the official guest-token service. The returned access token and open ID are short-lived runtime values and must not be copied into source code. Default pairs are retained in one canonical location, `src/Credentials/BundledCredentialProvider.php`, so a fresh private installation continues to work. Environment overrides are preferred for rotation.

## Safely obtain a test pair

1. Create or reset a guest account in an official Free Fire client running in a dedicated test device, emulator profile, or disposable application data directory.
2. Record the client OB/build, package variant, selected region, account creation date and device profile. Never use a player's production account.
3. Retrieve the new guest UID and password from your own controlled client state or authorized instrumentation used during account creation. Depending on the client build, these values may be in application-private preferences, a local database, or the guest registration request/response.
4. If inspecting your own client traffic is permitted in your environment, capture only the guest registration/login transaction and immediately move it to an encrypted private workspace. Do not weaken certificate validation in a distributed build and do not publish raw captures.
5. Extract the UID and password as exact strings. Preserve leading zeroes and punctuation. Do not confuse the player UID shown in the game with an access token, open ID, bearer token, device ID or session ID.
6. Validate the pair through the guest-token and MajorLogin flow described in `TOKEN_GENERATION_FLOW.md`. A pair is accepted only after MajorLogin returns a bearer token, lock region and valid HTTPS server URL.
7. Delete raw captures when the sanitized maintenance record is complete.

If the official client no longer exposes or issues a reusable guest pair, create an authorized replacement through the current official flow. Do not guess values, scrape unrelated accounts, or use credentials obtained from third parties.

## Credential groups

The core maps regions to seven account groups:

| Lookup region | Credential group |
|---|---|
| `IND` | `IND` |
| `BR`, `US`, `SAC`, `NA`, `EUROPE`, `EU` | `AMERICAS` |
| `VN` | `VN` |
| `ID` | `ID` |
| `TH` | `TH` |
| `TW` | `TW` |
| all remaining regions, including `BD` and `SG` | `GLOBAL` |

Resolution inside the environment provider is:

```text
exact region pair -> mapped group pair -> DEFAULT pair
```

The provider chain then falls back to the bundled provider. A candidate is used only when both its UID and password are non-empty; values from different scopes are never mixed.

## Configure an override

Set both variables for the chosen scope on the server:

```dotenv
FREEFIRE_BR_UID=replace_with_owned_test_uid
FREEFIRE_BR_PASSWORD=replace_with_owned_test_password

FREEFIRE_AMERICAS_UID=replace_with_owned_test_uid
FREEFIRE_AMERICAS_PASSWORD=replace_with_owned_test_password

FREEFIRE_DEFAULT_UID=replace_with_owned_test_uid
FREEFIRE_DEFAULT_PASSWORD=replace_with_owned_test_password
```

Exact-region values are useful during a staged rollout. Group values are the normal long-term configuration. `DEFAULT` is the final environment fallback. Store production values in the hosting control panel, secret manager, or an uncommitted `.env`; keep `.env.example` blank.

After changing Laravel environment values, clear cached configuration before testing:

```bash
php artisan optimize:clear
php artisan freefire:tokens-refresh --region=BD
```

Repeat the refresh for at least one region in every changed group.

## Rotation procedure

1. Provision and validate the replacement account without changing production.
2. Add an exact-region environment override for a canary region.
3. Clear configuration and token caches, force a refresh, then run a known-valid and invalid UID lookup.
4. Promote the pair to its group variable when the canary passes.
5. Test every region served by that group.
6. Retire the previous account only after caches have expired and rollback is no longer required.
7. Update the private rotation record with date, OB profile, group, result and operator. Never record the password in a ticket or Git history.

If environment overrides are not available on the target host, replace only the matching entry in `BundledCredentialProvider.php`, run the complete test and live-verification gates, and publish a private patch release. Never duplicate the same pair in Laravel config or documentation.

## Failure diagnosis

| Symptom | Check |
|---|---|
| Guest token rejected | Pair integrity, account status, correct group, endpoint/client identity and rate limits |
| One region fails but its group passes | Exact-region override, lock region and returned server URL |
| Password appears ignored | Both variables must be non-empty in the same scope; clear Laravel config cache |
| MajorLogin fails after guest token succeeds | OB profile fields, encryption key/IV, login Protobuf and clock |
| Old account is still used | Token/config cache, environment variable spelling and process restart |

Public logs and API responses must never contain UID/password pairs, guest access tokens, open IDs, bearer tokens, client secrets, encryption material or raw upstream bodies.
