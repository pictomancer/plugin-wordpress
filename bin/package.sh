#!/bin/sh
# Build a clean WordPress.org distribution zip: only the files the plugin ships,
# no dev tooling, dotfiles or compressed artifacts. Run plugin-check on the zip
# this produces, not on the working directory.
set -e

cd "$(dirname "$0")/.."
SLUG=pictomancer-image-optimizer
DIST=dist
STAGE="$DIST/$SLUG"

# Deterministic inputs: compiled admin bundle and a production-only vendor whose
# autoloader references no dev package. Re-run `composer install` afterwards to
# get dev dependencies back.
yarn build
composer install --no-dev --optimize-autoloader --quiet

rm -rf "$DIST"
mkdir -p "$STAGE"

rsync -a \
	--exclude='.git' \
	--exclude='.github' \
	--exclude='.yarn' \
	--exclude='.yarnrc.yml' \
	--exclude='.gitignore' \
	--exclude='.phpunit.cache' \
	--exclude='.phpunit.result.cache' \
	--exclude='node_modules' \
	--exclude='/src' \
	--exclude='/tests' \
	--exclude='/bin' \
	--exclude='dist' \
	--exclude='package.json' \
	--exclude='yarn.lock' \
	--exclude='composer.lock' \
	--exclude='phpunit.xml' \
	--exclude='vite.config.ts' \
	--exclude='vitest.config.ts' \
	--exclude='tsconfig.json' \
	--exclude='tailwind.config.js' \
	--exclude='postcss.config.js' \
	--exclude='biome.json' \
	./ "$STAGE/"

# Trim the staged vendor to runtime essentials: no test/doc payloads, dotfiles
# or archives (all rejected by Plugin Check). Acts on the staged copy only.
find "$STAGE/vendor" -type d \( -name tests -o -name test -o -name docs -o -name '.github' \) -prune -exec rm -rf {} + 2>/dev/null || true
find "$STAGE" -depth -name '.*' ! -name '.' ! -name '..' -exec rm -rf {} + 2>/dev/null || true
find "$STAGE" \( -name '*.gz' -o -name '*.tgz' -o -name '*.zip' \) -delete 2>/dev/null || true

( cd "$DIST" && zip -rq "$SLUG.zip" "$SLUG" )
echo "Built $DIST/$SLUG.zip"
