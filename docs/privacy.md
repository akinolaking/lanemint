# Privacy and external services

## NIPOST Postcode API

When a merchant enables API verification, the checkout postcode is sent from the merchant’s WordPress server to `https://api.postcode.gov.ng/v1/lookup` with the merchant’s API key. NIPOST’s terms and privacy information apply. Local format-only validation makes no external request.

## LaneMint diagnostics

Diagnostics are off by default. Enabling them sends:

- a random installation UUID;
- LaneMint, WordPress, WooCommerce and PHP versions;
- activation, deactivation, weekly heartbeat or CSV-import events;
- LaneMint-defined error area and error code.

It does not send store URL, raw IP, user/customer information, orders, addresses, postcodes, products, prices, CSV contents, logs, stack traces or credentials. Disabling diagnostics stops new events. The backend should retain event data only as long as required to support releases and security maintenance.

