# Core maintenance documentation

The canonical core-specific maintenance guides live inside `packages/core/docs/` so they are included in the automatically split `refatbd/free-fire-php` repository:

- `OB_UPDATE_GUIDE.md`
- `OB_PROTOCOL_CAPTURE.md`
- `PROTOCOL_RECOVERY.md`
- `CREDENTIAL_MANAGEMENT.md`
- `ACCOUNT_CREDENTIAL_CAPTURE.md`
- `TOKEN_GENERATION_FLOW.md`
- `LIVE_PROTOCOL_VERIFICATION.md`
- `MEDIA_ASTC_GUIDE.md`
- `TESTING_GUIDE.md`
- `SECURITY.md`
- `TROUBLESHOOTING.md`

Monorepo source lineage and credential-deduplication notes are in `docs/SOURCE_PROVENANCE.md`.

Recommended sequence for an upstream change:

1. capture the controlled OB profile with `OB_PROTOCOL_CAPTURE.md`;
2. acquire/rotate owned test accounts with `ACCOUNT_CREDENTIAL_CAPTURE.md` when needed;
3. verify automatic authentication against `TOKEN_GENERATION_FLOW.md`;
4. complete the matrix in `LIVE_PROTOCOL_VERIFICATION.md`;
5. finish the release checklist in `OB_UPDATE_GUIDE.md`.
