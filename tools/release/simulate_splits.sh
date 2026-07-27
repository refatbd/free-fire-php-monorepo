#!/usr/bin/env bash
set -euo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
WORK=$(mktemp -d "${TMPDIR:-/tmp}/freefire-split-sim.XXXXXX")
trap 'rm -rf "$WORK"' EXIT

cp -a "$ROOT/." "$WORK/repository"
cd "$WORK/repository"
rm -rf .git

git init -q
git config user.name "Free Fire release simulation"
git config user.email "release-simulation@example.invalid"

# Convert the starter's local monorepo path dependencies into its public
# Packagist manifest before the temporary release commit.
php tools/release/prepare_starter_distribution.php apps/starter

# The real workflow runs protoc. When local protoc is unavailable, use a
# temporary generated marker only to prove force-add + subtree mechanics.
if command -v protoc >/dev/null 2>&1; then
    PROTOC_BINARY=$(command -v protoc) php packages/core/tools/protobuf/generate.php
else
    mkdir -p packages/core/protocol/generated/php/ReleaseSimulation
    cat > packages/core/protocol/generated/php/ReleaseSimulation/GeneratedMarker.php <<'PHP'
<?php
// Temporary release-simulation marker. Never copied back to the monorepo.
PHP
fi

git add .
git add -f packages/core/protocol/generated/php
git commit -qm "Release split simulation"

for specification in \
    "core:packages/core" \
    "laravel:packages/laravel" \
    "starter:apps/starter"
do
    kind=${specification%%:*}
    prefix=${specification#*:}
    sha=$(git subtree split --prefix="$prefix" HEAD)
    export_dir="$WORK/export-$kind"
    mkdir -p "$export_dir"
    git archive "$sha" | tar -x -C "$export_dir"
    php "$ROOT/tools/release/verify_split.php" "$kind" "$export_dir"
done

echo "All split repository mechanics passed."
