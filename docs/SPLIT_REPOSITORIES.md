# Split repositories

| Monorepo prefix | Destination | Package tag prefix in monorepo |
|---|---|---|
| `packages/core` | `refatbd/free-fire-php` | `core-v*` |
| `packages/laravel` | `refatbd/laravel-free-fire` | `laravel-v*` |
| `apps/starter` | `refatbd/free-fire-info-starter` | `starter-v*` |

Only the canonical monorepo is edited. Every destination README identifies itself as an automatically generated distribution.

## Verification flow

Before publishing, run:

```bash
bash tools/release/simulate_splits.sh
```

The simulator creates a temporary Git repository, force-adds a generated Protobuf marker when `protoc` is unavailable, subtree-splits all three prefixes, exports them and runs `tools/release/verify_split.php` against each distribution.

In GitHub Actions the core build uses real `protoc`, force-adds generated PHP classes to a temporary distribution commit and splits that commit. Therefore Composer users receive generated classes and do not need `protoc`.

The publish workflow:

- verifies package names and required files;
- prevents monorepo-only paths from leaking into a split;
- requires generated Protobuf PHP files in core;
- validates each split `composer.json`;
- installs the core split and checks the generated OB54 class;
- force-updates destination `main` as an automation mirror;
- refuses to rewrite an existing destination release tag.

Protect destination repositories from normal human pushes while allowing the automation identity. Configure the canonical repository secret `SPLIT_TOKEN` with the minimum permissions required to push the three destinations.

## First-time GitHub setup

Create these four public repositories under the `refatbd` account:

1. `free-fire-php-monorepo` — canonical source; upload and edit only this repository.
2. `free-fire-php` — empty destination for the core package.
3. `laravel-free-fire` — empty destination for the Laravel package.
4. `free-fire-info-starter` — empty destination for the ready-made application.

Do not initialize the three destination repositories with a README, license, or `.gitignore`. The split workflow force-updates their `main` branches from the canonical monorepo.

### Split token

Create a fine-grained personal access token owned by `refatbd` and grant it access only to the three destination repositories. Required repository permission:

- **Contents: Read and write**

Store the token in the canonical repository as an Actions repository secret named exactly:

```text
SPLIT_TOKEN
```

The workflow uses the normal GitHub-provided token only to read the canonical repository; `SPLIT_TOKEN` is used only for cross-repository pushes.

### Initial publication

After the four repositories and secret exist, push this monorepo to `refatbd/free-fire-php-monorepo` on branch `main`. The `Split repositories` workflow will verify the source and publish the three destination `main` branches.

After the first successful main split, create the coordinated first release:

```bash
git tag v1.0.0
git push origin v1.0.0
```

That tag publishes `v1.0.0` to all three destination repositories. For package-specific releases, use `core-v*`, `laravel-v*`, or `starter-v*` as documented in `RELEASE_PROCESS.md`.

### Destination branch rules

The workflow intentionally force-updates destination `main`. Do not enable a branch rule that blocks this automation unless the token owner is explicitly allowed to bypass it. Never edit or merge pull requests directly in a destination repository; apply changes to the canonical monorepo instead.
