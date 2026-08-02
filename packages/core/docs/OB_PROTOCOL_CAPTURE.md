# OB protocol capture and profile update

This guide covers the controlled research needed when an official Free Fire OB release changes login or player lookup. Work only with an official client and test account you are authorized to inspect. Keep raw binaries, captures and credentials outside Git; commit only verified constants, schemas and sanitized fixtures.

## 1. Freeze the source identity

Before extracting anything, record:

- OB label, release date and visible client version;
- package name, distribution channel, architecture and APK/bundle hashes;
- Android version, device model/ABI, locale and test region;
- exact test time and whether the client was a clean install or upgrade.

Never combine values taken from different builds and call them one profile.

## 2. Create a new versioned workspace

Copy the last known-good profile, protocol directory and sanitized fixtures. Do not overwrite the previous profile:

```text
src/Protocol/Profiles/Ob54ProtocolProfile.php -> Ob55ProtocolProfile.php
protocol/ob54/                                -> protocol/ob55/
tests/Fixtures/Protocol/OB54/                 -> tests/Fixtures/Protocol/OB55/
```

Register the new class in `BuiltInProtocolProfiles::classes()` and keep it opt-in with `FREEFIRE_PROTOCOL=OB55` until live validation passes.

## 3. Locate maintained constants

Use authorized source access or static inspection tools such as JADX, apktool and ordinary string search on your local client package. Useful anchors include:

```text
MajorLogin
GetPlayerPersonalShow
oauth/guest/token/grant
ReleaseVersion
X-Unity-Version
Authorization
client_id
client_secret
AES/CBC/PKCS5Padding
```

Trace each string to the code that constructs the request. Record the value, encoding, call site and build hash. Confirm rather than infer:

- user agent and Android/device identity;
- Unity and release versions;
- guest-token and MajorLogin URLs;
- client ID, client secret, login open-ID type and origin platform type;
- binary headers and player endpoint path;
- fallback token TTL;
- AES key, IV and their stored encoding.

For AES material, follow the arguments passed into the cipher initializer. Determine whether the source value is raw bytes, hexadecimal or Base64, decode it once, and verify both key and IV are exactly 16 bytes for AES-128-CBC. Do not copy a displayed hash or textual wrapper as the key. Validate with a sanitized known-plaintext fixture before accepting it.

## 4. Observe an authorized request

When local rules and the client permit it, use your controlled device to observe request metadata and byte shape. Capture the smallest possible guest-token, MajorLogin and player-show sample. Keep the raw capture encrypted and private.

Confirm:

- method, HTTPS host, path, content type and redirect behavior;
- required headers and capitalization-sensitive values;
- form fields for guest token;
- unencrypted Protobuf bytes before AES and encrypted request bytes;
- response content type, status and Protobuf framing;
- the MajorLogin-returned server URL used by the player call.

Do not add certificate-bypass code to this library, distribute a modified client, or commit a capture containing tokens/account data.

## 5. Recover and diff Protobuf

Prefer descriptors or generated classes from the exact controlled build. Normalize package, message, field number, wire type, label, enum values, oneofs and dependencies. Never infer field numbers from community field names.

The repository helpers support legacy Python generated modules and canonical descriptors:

```bash
python packages/core/tools/protobuf/extract_legacy_descriptors.py --help
python packages/core/tools/protobuf/compare_descriptor_maps.py --help
composer proto:validate
composer proto:generate
```

Update the canonical `.proto` sources, not generated PHP. Every OB must use its own Protobuf package, PHP namespace and metadata namespace. Classify changes as additive, renamed-with-same-wire-identity, wire-type changes, removed/reserved fields, dependency changes or enum changes.

## 6. Prove byte parity

For fixed non-secret inputs, compare the controlled reference implementation with PHP:

1. serialized login request bytes;
2. padded AES plaintext and ciphertext;
3. decoded login response fields;
4. serialized/encrypted player request;
5. decoded and normalized sanitized player response.

All byte comparisons must be exact. A response that merely “parses” is not enough. Store only sanitized fixtures under `tests/Fixtures/Protocol/OBXX/`.

## 7. Validate the profile

Run:

```bash
composer lint
composer proto:validate
composer proto:generate
composer dump-autoload
composer test
```

Then follow `LIVE_PROTOCOL_VERIFICATION.md` for every credential group and supported gateway. Test clock skew, expiry, forced token refresh, invalid UID, returned-server validation, redirects, timeouts and safe error redaction.

For media/CDN changes, follow `MEDIA_ASTC_GUIDE.md`; protocol success must not depend on ASTC tooling being installed.

## Common extraction mistakes

| Mistake | Result |
|---|---|
| Mixing constants from regional APK variants | Guest token or MajorLogin rejection in only some regions |
| Treating Base64 text as raw AES bytes | Ciphertext mismatch or unreadable response |
| Guessing a Protobuf field number | Valid-looking but incompatible wire payload |
| Hardcoding the observed player server | Breaks when MajorLogin routes a different region |
| Replacing the old profile | Removes rollback and hides regional staged rollout |
| Committing raw captures | Leaks accounts, tokens and private player data |

The final maintenance record should contain hashes, versions, sanitized diffs and pass/fail evidence—not reusable secrets.
