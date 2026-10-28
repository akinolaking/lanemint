# Security policy

Report vulnerabilities privately to **hi@akinola.co.uk** with the subject “LaneMint security report”. Please include the affected version, impact, reproduction steps and a minimal proof of concept. Do not include live customer or store data.

## Supported versions

Security fixes are provided for the latest released minor version. Critical fixes will be released as soon as practical and documented in the changelog.

## Threat model and mitigations

### CSV uploads

- Restricted to users with `manage_woocommerce`.
- CSRF protection with a purpose-specific WordPress nonce.
- Maximum 2 MB and 10,000 data rows.
- CSV-only WordPress upload handling; uploaded files are deleted immediately after parsing.
- No caller-controlled filesystem paths and no extraction of archives.
- Strict headers, numeric limits and per-field sanitization before database writes.
- Imports use a database transaction so a failed import does not leave a partial rate card.

These controls directly address the arbitrary-file-upload and arbitrary-file-deletion patterns repeatedly documented by Wordfence in WordPress CSV/import plugins.

### Authorization and output

- Administrative actions require both a capability check and nonce verification.
- User-controlled output is escaped at render time.
- SQL values use WordPress prepared statements or structured insert/update APIs.
- No public REST route can modify rate cards.

### NIPOST credentials

- Secret keys are used only in server-side `wp_safe_remote_*` requests to the hard-coded HTTPS NIPOST API origin.
- Browser code never receives the secret.
- `LANEMINT_NIPOST_API_KEY` in `wp-config.php` is preferred to database storage.
- Redirects are disabled and remote requests have short timeouts.

### Telemetry

- Disabled until a store administrator explicitly opts in.
- Does not send site URLs, IP addresses, customers, orders, addresses, postcodes, products, prices, CSV contents or API keys.
- Install tokens expire; payloads use an allowlisted strict schema.
- Backend stores only a salted one-way hash of registration IPs for abuse prevention.
- Admin summaries require a timing-safe API-key comparison.

## Secure development checklist

- Run WordPress Coding Standards, PHPCompatibility, PHPStan, ESLint and dependency audit before release.
- Test the lowest supported PHP/WordPress/WooCommerce versions and latest stable versions.
- Review every new file upload, REST endpoint, SQL query and rendered variable.
- Rotate backend secrets and NIPOST credentials after any suspected exposure.
- Publish a CVE/GHSA and patched release for confirmed vulnerabilities.

