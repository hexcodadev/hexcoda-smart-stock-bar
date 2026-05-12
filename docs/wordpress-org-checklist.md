# WordPress.org Submission Checklist

Use this checklist before submitting HexCoda Smart Stock Bar to WordPress.org.

## Plugin Identity

- [ ] Confirm final plugin name.
- [ ] Confirm final slug: `hexcoda-smart-stock-bar`.
- [ ] Confirm author name: `HexCoda`.
- [ ] Confirm plugin URI and author URI use live `https://hexcoda.com/` pages.
- [ ] Replace placeholder WordPress.org contributor username in `readme.txt`.

## Code Quality

- [ ] Run PHP syntax checks on all PHP files.
- [ ] Run WordPress Coding Standards with `WordPress` and `WordPress-Extra` rulesets.
- [ ] Run Plugin Check.
- [ ] Confirm all direct file access is blocked with `defined( 'ABSPATH' ) || exit;`.
- [ ] Confirm all admin actions use capability checks.
- [ ] Confirm all saved settings are sanitized.
- [ ] Confirm all output is escaped.
- [ ] Confirm no local filesystem paths are hardcoded.
- [ ] Confirm no external requests run without user action/permission.

## WooCommerce Compatibility

- [ ] Test with the latest WooCommerce stable release.
- [ ] Test with High-Performance Order Storage enabled.
- [ ] Test with a simple product using managed stock.
- [ ] Test with stock above threshold.
- [ ] Test with stock below threshold.
- [ ] Test with stock quantity `0`.
- [ ] Test with unmanaged stock.
- [ ] Test each display position.

## WordPress.org Readme

- [ ] Confirm `Requires at least`.
- [ ] Confirm `Tested up to`.
- [ ] Confirm `Requires PHP`.
- [ ] Confirm stable tag matches plugin version.
- [ ] Add real screenshots before submission.
- [ ] Add a clear FAQ.
- [ ] Add first changelog entry.

## Assets

- [ ] Create WordPress.org banner: `banner-1544x500.png`.
- [ ] Create WordPress.org banner: `banner-772x250.png`.
- [ ] Create icon: `icon-256x256.png`.
- [ ] Create icon: `icon-128x128.png`.
- [ ] Capture screenshot of product page output.
- [ ] Capture screenshot of settings page.

## Privacy and Trust

- [ ] Confirm the plugin does not track users.
- [ ] Confirm no third-party services are used.
- [ ] Add a privacy note to docs and product page.
- [ ] Add support policy to HexCoda.com.

## Release

- [ ] Tag release version.
- [ ] Build release ZIP without development-only files.
- [ ] Install release ZIP on a clean WordPress site.
- [ ] Submit to WordPress.org plugin review.
