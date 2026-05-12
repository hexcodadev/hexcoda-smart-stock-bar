# HexCoda Smart Stock Bar for WooCommerce

HexCoda Smart Stock Bar for WooCommerce adds a lightweight stock progress bar to WooCommerce product pages.

This repository is the development source for the WordPress.org free plugin.

## Product Principle

One small plugin. One useful WooCommerce improvement. No bloat.

## Current Features

- Show a stock progress bar on WooCommerce product pages.
- Display only when stock is managed by WooCommerce.
- Optional low-stock threshold.
- Configurable reference stock amount for progress calculation.
- Custom message with `{stock}` placeholder.
- Basic color controls.
- Display position control.
- No tracking and no external services.

## Local Development

Place this folder inside a WordPress installation:

```text
wp-content/plugins/hexcoda-smart-stock-bar
```

Then activate it from the WordPress admin. WooCommerce must be installed and active.

## Test Product Setup

Create a simple WooCommerce product with:

- Manage stock enabled.
- Stock quantity set below the configured threshold.
- Product status set to published.

The stock bar should appear on the single product page.

## Release Checklist

See [docs/wordpress-org-checklist.md](docs/wordpress-org-checklist.md).

Plugin Check should be run against a clean release package, not the full development repository. Development-only files such as `.github`, `.gitignore`, and `.gitattributes` are useful in GitHub but should not be included in the WordPress.org ZIP.

## License

GPLv2 or later.
