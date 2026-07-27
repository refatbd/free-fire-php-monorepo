# Credential management

The upstream login flow requires default guest accounts. They are intentionally retained in exactly one canonical class: `src/Credentials/BundledCredentialProvider.php`.

Resolution order is environment override first, bundled provider second. This allows operators to rotate credentials without source edits while preserving install-and-run behavior.

Rules:
- Never return UID/password, guest access tokens, open IDs, bearer tokens, encryption material, or client secret through public API responses.
- Redact sensitive fields before logging context.
- Do not duplicate credentials in Laravel config, starter `.env.example`, documentation, fixtures, or frontend code.
- Test account health per credential group, not only per region alias.
- A credential change is a core patch release unless behavior or public interfaces change.
