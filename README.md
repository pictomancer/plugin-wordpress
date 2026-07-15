# Pictomancer Image Optimizer

Source code for the [Pictomancer Image Optimizer](https://pictomancer.ai/integrations/wordpress) WordPress plugin. It compresses and converts WordPress media through the [Pictomancer.ai](https://pictomancer.ai) optimization API.

Install it from the WordPress.org plugin directory: [wordpress.org/plugins/pictomancer-image-optimizer](https://wordpress.org/plugins/pictomancer-image-optimizer/).

This repository is the public source mirror, including the build tooling for the admin interface bundle (`build/pictomancer-admin.js`), which is compiled from the React/TypeScript sources under `src/`.

## Build

The admin bundle is built with [Vite](https://vitejs.dev/):

```sh
yarn install
yarn build
```

This regenerates `build/pictomancer-admin.js`.

## Test

```sh
yarn test        # frontend (Vitest)
composer install
vendor/bin/phpunit   # PHP (PHPUnit)
```

## Package

`yarn package` produces a clean WordPress.org distribution zip under `dist/`.

## Release to WordPress.org

Approved on 2026-07-14. Releases are published via Subversion (WordPress.org does not accept zips or git):

- SVN repository: `https://plugins.svn.wordpress.org/pictomancer-image-optimizer`
- Public listing: `https://wordpress.org/plugins/pictomancer-image-optimizer`
- SVN username: `pictomancerai` (case sensitive; the password is a dedicated SVN password generated under "Account & Security" on the wordpress.org profile, not the account password)

SVN layout: `trunk/` holds the current plugin code, `tags/x.y.z/` holds immutable releases, and `assets/` holds the directory listing images (banner, icon, screenshots), which are never shipped inside the plugin. The `Stable tag` in `trunk/readme.txt` decides which tag wordpress.org serves.

The listing images live in this repo under `.wordpress-org/` (banner 1544x500 + 772x250, icon 256x256 + 128x128, plus `banner-source.html`, the HTML the banner is rendered from with headless Chrome). `bin/package.sh` strips dotfiles, so they never end up in the distribution zip; to change them, edit the source, re-render, and commit the PNGs to the SVN `assets/` directory.

To publish a version, always start from the packaged output (`dist/pictomancer-image-optimizer/`), never the working directory:

```sh
yarn package
svn checkout https://plugins.svn.wordpress.org/pictomancer-image-optimizer svn
rsync -a --delete dist/pictomancer-image-optimizer/ svn/trunk/
cd svn
svn add --force trunk
svn status | awk '/^!/{print $2}' | xargs -r svn rm
svn cp trunk tags/0.1.1
svn commit -m "Release 0.1.1" --username pictomancerai
```

Search indexing on wordpress.org can take up to 72 hours after the first commit.

Reference: [How to use Subversion](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/), [plugin assets](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/), [readme validator](https://wordpress.org/plugins/developers/readme-validator/).

## License

GPL-2.0-or-later.
