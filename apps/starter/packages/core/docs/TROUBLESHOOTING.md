# Troubleshooting

## Generated class missing

Package consumers should reinstall/update the core distribution because release archives already contain generated classes. Monorepo contributors run `composer proto:validate`, `composer proto:generate` and `composer dump-autoload`; ensure `protoc` is on PATH or set `PROTOC_BINARY`.

## Wrong/unsupported protocol profile

Confirm the profile exists in core `BuiltInProtocolProfiles` (or as a Laravel custom override) and the environment uses a value such as `FREEFIRE_PROTOCOL=OB54`. Do not point an OB profile at another OB version's generated response class.

## ASTC unavailable

Run `php artisan freefire:media-check`; install/configure `astcenc` and PHP GD with WebP. Player JSON remains available when official media cannot be rendered.

## Token refresh fails

Check outbound HTTPS, account health, system clock, selected OB profile, endpoint reachability and protected server logs. Do not paste credentials or tokens into public support issues.

Follow the stage map in `TOKEN_GENERATION_FLOW.md`. For a rotated account, verify the complete-pair and group rules in `ACCOUNT_CREDENTIAL_CAPTURE.md`. If bytes or headers changed with a new release, use `OB_PROTOCOL_CAPTURE.md` and rerun `LIVE_PROTOCOL_VERIFICATION.md`.

## Split repository did not update

Run `bash tools/release/simulate_splits.sh`, then check the monorepo Actions run, `SPLIT_TOKEN` permissions, destination repository existence and branch rules. Rerun from the monorepo rather than editing the destination.
