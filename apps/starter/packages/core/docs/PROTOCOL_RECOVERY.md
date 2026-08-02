# Protocol recovery and verification

For a new live client build, begin with `OB_PROTOCOL_CAPTURE.md`. Use this document for schema reconstruction mechanics and finish with `LIVE_PROTOCOL_VERIFICATION.md`.

The original Python repository contains generated `_pb2.py` modules rather than checked-in `.proto` sources. The OB54 schemas in `protocol/ob54/` have now been reconstructed from those generated descriptors:

1. `LegacyLogin.proto` contains the exact login request field numbers and the complete decoded login-response surface used by the source project.
2. `PlayerRequest.proto` contains the exact `GetPlayerPersonalShow` request used by the source project.
3. `AccountPersonalShow.proto` contains the recovered `AccountPersonalShowInfo` response graph, including the original field-number differences that do not match newer community schemas.

Files under `protocol/reference/` are never compiled into the package. They remain comparison material only.

Canonical schemas add an OB-versioned Protobuf package and generated namespace to prevent runtime descriptor collisions; that metadata does not alter wire bytes. Never infer a field number from its name. Field number, wire type, repeated/optional status, enum value, nested message identity, and package/class mapping are compatibility-critical.

## Verification procedure

1. Keep a local copy of the legacy generated Python modules in a private verification workspace.
2. Run `tools/protobuf/extract_legacy_descriptors.py` without importing or executing the modules.
3. Generate a normalized JSON field map from the canonical `.proto` descriptor set.
4. Run `tools/protobuf/compare_descriptor_maps.py` against the two maps.
5. Generate PHP classes with `composer proto:generate`.
6. Serialize fixed login/player requests in Python and PHP and compare exact bytes.
7. Parse sanitized captured responses in both languages and compare normalized JSON.
8. Commit only sanitized fixtures. Never commit bearer tokens, open IDs, or account-specific private response data.

Generated PHP classes are build output and are never manually edited. The current repository includes source-level field-map recovery; live response-fixture validation remains a release gate.
