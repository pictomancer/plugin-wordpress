# Pictomancer Image Optimizer

Source code for the [Pictomancer Image Optimizer](https://pictomancer.ai/integrations/wordpress) WordPress plugin. It compresses and converts WordPress media through the [Pictomancer.ai](https://pictomancer.ai) optimization API.

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

## License

GPL-2.0-or-later.
