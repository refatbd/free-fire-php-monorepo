# Runtime token generation flow

The application automatically generates short-lived upstream tokens from a configured guest-account pair. Operators maintain the account pair and OB protocol profile; they do not manually paste guest access tokens, open IDs, bearer tokens or regional server URLs.

## End-to-end flow

```text
credential provider
  -> official guest-token request
  -> access_token + open_id
  -> encode LegacyLogin Protobuf
  -> AES-128-CBC encrypt
  -> official MajorLogin
  -> bearer token + lock region + server URL + TTL
  -> cache TokenInfo
  -> encode/encrypt player UID request
  -> POST {returned server URL}/GetPlayerPersonalShow
  -> decode and normalize player response
```

### 1. Credential resolution

`EnvironmentCredentialProvider` checks a complete exact-region pair, mapped credential-group pair, then `DEFAULT`. `ChainCredentialProvider` falls back to `BundledCredentialProvider`, preserving install-and-run behavior for the private package. See `ACCOUNT_CREDENTIAL_CAPTURE.md` for the group matrix and rotation procedure.

### 2. Guest token

The selected UID/password and profile-maintained client identity are posted to the profile's guest-token endpoint. The response supplies `access_token` and `open_id`. These are intermediate runtime values only.

### 3. MajorLogin

The core builds the versioned login Protobuf using the profile's field contract, encrypts it with AES-128-CBC and sends it with the profile's binary headers. The decoded response supplies:

- bearer token;
- lock region;
- HTTPS player server URL;
- token lifetime/TTL.

If the upstream TTL is absent or unusable, the profile's bounded fallback TTL is used. The server URL is validated before any request is sent; it is not copied from documentation or permanently hardcoded.

### 4. Cache and concurrency

The final `TokenInfo` is cached by OB version and region:

```text
freefire:{OB}:token:{region}
```

A refresh lock prevents multiple PHP workers from performing the same login simultaneously. Cached data is rejected when expired or unsafe. Forced refresh bypasses the current token and replaces it atomically.

### 5. Player lookup

The UID and call-sign source are encoded in the player request Protobuf, encrypted with the selected profile and sent with `Authorization: Bearer ...` to the server returned by MajorLogin. Successful responses are decoded and normalized. Player cache entries are scoped by OB, region and UID:

```text
freefire:{OB}:player:{region}:{uid}
```

Automatic detection scans configured regional gateways until a valid account is found; an unavailable credential group or gateway must fail safely and allow later candidates to run.

## Manually maintained versus generated

| Maintained in configuration/profile | Generated automatically at runtime |
|---|---|
| Guest UID/password pairs | Guest access token |
| Client ID/client secret | Open ID |
| OB/build identity and headers | Encrypted login/player payloads |
| AES key/IV and Protobuf contract | Bearer token |
| Official guest/MajorLogin endpoints | Lock region and routed server URL |
| Fallback TTL and player path | Cache timestamps and refresh locks |

Do not move generated values into `.env`; they expire and may be bound to account, region or build state.

## Force and verify a refresh

In the Laravel starter:

```bash
php artisan optimize:clear
php artisan freefire:tokens-refresh --region=BD
```

Add more `--region` options as needed. The command should report success without printing tokens. For plain PHP, construct the client through `FreeFireFactory` with the required profile/cache/transport and invoke the supported refresh or lookup path; do not call private token internals from application code.

## Failure map

| Stage | Typical causes |
|---|---|
| Credential resolution | Missing half of a pair, misspelled scope, unhealthy account |
| Guest token | Account rejection, wrong client identity, endpoint change, rate limit |
| Login encoding/encryption | OB field change, wrong AES decoding, padding/header mismatch |
| MajorLogin | Profile drift, clock, token/account restriction, upstream outage |
| Server validation | Non-HTTPS/untrusted returned URL or unexpected redirect |
| Player request | Expired bearer, wrong region, changed player Protobuf/path |
| Decode/normalize | Response schema drift or truncated/non-Protobuf body |

Inspect protected server logs and sanitized status metadata. Never expose or log credentials, access/open-ID/bearer values, AES material, client secrets or raw upstream responses.
