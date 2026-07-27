# Monorepo workflow

The canonical repository is `refatbd/free-fire-php-monorepo`. Edit, review, test, tag and release only here.

```text
packages/core    -> refatbd/free-fire-php
packages/laravel -> refatbd/laravel-free-fire
apps/starter     -> refatbd/free-fire-info-starter
```

`.github/workflows/split-repositories.yml` verifies the whole project before any destination push. It also generates and includes PHP Protobuf classes in the core distribution.

Never commit directly to a split repository. A direct commit will be overwritten on the next successful split. Issues and pull requests should target the monorepo.

Configure a repository secret named `SPLIT_TOKEN` with permission to push to the three destination repositories. Create all destination repositories before the first run and protect their `main` branches from human pushes while allowing the automation identity.
