# Testing guide

From the canonical monorepo, run the dependency-free gates first:

```bash
php tools/lint.php
php packages/core/tools/protobuf/validate.php
php tests/smoke.php
php tests/integration_mock.php
php tests/documentation.php
bash tools/release/simulate_splits.sh
```

In an environment with Composer and `protoc`, run the complete suite:

```bash
composer install
composer proto:generate
composer dump-autoload
REQUIRE_GENERATED_PROTO=1 composer test
```

Before a protocol release also run:

- descriptor field-map comparison for every legacy/new generated module;
- Python/PHP request-byte and AES ciphertext parity;
- sanitized login/player response parsing through the selected generated OB class;
- valid, invalid and missing UID tests;
- every distinct credential group and region;
- token expiry, forced refresh and concurrent lock tests;
- ASTC valid, truncated, wrong-magic, undersized, oversized and unsupported-depth tests;
- real GD WebP + `astcenc` visual fixtures;
- Laravel package discovery, profile selection, route, throttle and error-redaction tests;
- starter homepage, developer guide and result page tests;
- split distribution installation and generated class presence.

Live integration tests are opt-in. They must never print or persist upstream passwords, bearer tokens, open IDs or unsanitized private player responses.

Use `LIVE_PROTOCOL_VERIFICATION.md` for the exact opt-in matrix and sanitized evidence record. Authentication failures should be traced stage-by-stage with `TOKEN_GENERATION_FLOW.md`; credential rotation follows `ACCOUNT_CREDENTIAL_CAPTURE.md`.
