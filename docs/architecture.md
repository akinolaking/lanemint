# Architecture

## Plugin

1. An authorized WooCommerce manager uploads a carrier CSV.
2. LaneMint validates the whole file, then replaces that partner’s rate rows inside one database transaction.
3. A LaneMint shipping-method instance is added to one or more native WooCommerce zones.
4. WooCommerce passes the checkout package and destination to LaneMint.
5. LaneMint calculates package weight, finds destination/weight matches, and returns one checkout rate per partner service.
6. If NIPOST API validation is enabled, the server verifies the supplied digital postcode before order creation.

The plugin remains functional without NIPOST API access and without the telemetry service.

## Telemetry service

1. An opted-in installation registers its random UUID and receives a short-scoped signed token.
2. It sends allowlisted lifecycle or LaneMint error-code events.
3. The API validates the token and strict schema, then writes the event to PostgreSQL.
4. A protected summary endpoint groups active versions and error fingerprints for maintenance decisions.

No customer/store content is part of this data flow.

