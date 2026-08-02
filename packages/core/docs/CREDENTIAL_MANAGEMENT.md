# Credential management

The upstream login flow requires default guest accounts. They are intentionally retained in exactly one canonical class, `src/Credentials/BundledCredentialProvider.php`, so this private package keeps install-and-run behavior.

Environment overrides resolve before bundled credentials:

```text
complete exact-region pair -> complete mapped-group pair -> complete DEFAULT pair -> bundled group
```

A pair means both UID and password are non-empty in the same scope. The resolver never combines a UID from one scope with a password from another. Region mapping is centralized in `src/Credentials/CredentialGroupResolver.php` and shared by both providers.

Use `ACCOUNT_CREDENTIAL_CAPTURE.md` for the full authorized account acquisition, group matrix, environment naming, validation and rotation procedure. Use `TOKEN_GENERATION_FLOW.md` to understand which values are maintained and which tokens are generated automatically.

Rules:

- Never return UID/password, guest access tokens, open IDs, bearer tokens, encryption material, or client secret through public API responses.
- Redact sensitive fields before logging context.
- Do not duplicate credentials in Laravel config, starter `.env.example`, documentation, fixtures, or frontend code.
- Test account health per credential group, not only per region alias.
- Keep environment examples empty; real values belong in a server secret store or uncommitted `.env`.
- A credential change is a core patch release unless behavior or public interfaces change.
