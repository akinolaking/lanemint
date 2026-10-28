# LaneMint

LaneMint is an open-source WooCommerce shipping-rate engine for merchants whose delivery partners publish prices in spreadsheets instead of APIs.

Import one CSV per carrier, add **LaneMint rate cards** to any WooCommerce shipping zone, and match checkout rates by country, state, LGA, city, NIPOST Digital Postcode pattern, and package weight.

**Author:** Akinola Muyiwa · [hi@akinola.co.uk](mailto:hi@akinola.co.uk)  
**Company:** OneNet Servers  
**License:** GPL-2.0-or-later

## Why LaneMint

- Carrier-neutral: use any delivery partner that can provide a CSV rate card.
- Native WooCommerce zones and shipping-method API.
- NIPOST Digital Postcode format validation and optional server-side API verification.
- Safe replacement imports with row, size, type and schema limits.
- Privacy-safe diagnostics are disabled by default and require explicit consent.
- HPOS-compatible and designed for classic and block-era WooCommerce stores.

## Repository layout

- `plugin/lanemint` — installable WordPress plugin source.
- `backend` — Vercel/Next.js telemetry service.
- `docs` — CSV schema, architecture, privacy and security notes.

## Quick start

1. Zip the contents of `plugin/lanemint` so `lanemint.php` is at the ZIP root.
2. Install and activate it in WordPress.
3. Open **WooCommerce → LaneMint**, download the CSV template and import a partner rate card.
4. Open **WooCommerce → Settings → Shipping**, edit a zone and add **LaneMint rate cards**.
5. Optionally add a NIPOST key under **WooCommerce → LaneMint → NIPOST**.

For production secrets, prefer this in `wp-config.php`:

```php
define( 'LANEMINT_NIPOST_API_KEY', 'nipost_live_…' );
```

## Status

`0.1.0` is a security-first public preview. Run the automated checks and test with a staging store before production use.

## Documentation

- [CSV rate-card format](docs/csv-format.md)
- [Architecture](docs/architecture.md)
- [Security model](SECURITY.md)
- [Privacy and telemetry](docs/privacy.md)
- [Telemetry deployment](backend/README.md)

