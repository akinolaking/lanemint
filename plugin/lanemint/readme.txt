=== LaneMint ===
Contributors: akinolamuyiwa
Tags: woocommerce, shipping, csv, postcode, nigeria
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import delivery-partner rate cards from CSV and offer postcode-aware WooCommerce shipping choices.

== Description ==

LaneMint lets a store manager import a simple CSV rate card for each delivery partner. Rates can be matched by WooCommerce shipping zone, country, state, LGA, city, NIPOST Digital Postcode pattern and package weight.

NIPOST integration is optional. Format validation runs locally. API verification requires a key issued by NIPOST and is performed server-side against `https://api.postcode.gov.ng`.

Anonymous diagnostics are disabled by default. If explicitly enabled, LaneMint sends only a random installation ID, software versions, lifecycle events and LaneMint error codes. It never sends the site URL, customer data, orders, addresses, postcodes, products or prices.

== Installation ==

1. Upload and activate LaneMint.
2. Go to WooCommerce > LaneMint and import a carrier CSV.
3. Go to WooCommerce > Settings > Shipping and add “LaneMint rate cards” to a shipping zone.
4. Optionally configure the NIPOST API under WooCommerce > LaneMint > NIPOST.

== Changelog ==

= 0.1.0 =
* Initial public preview.
* CSV partner-rate import with strict validation and replacement imports.
* WooCommerce zone shipping method and weight/postcode matching.
* Optional NIPOST Digital Postcode format and API validation.
* Explicitly opt-in privacy-safe diagnostics.

