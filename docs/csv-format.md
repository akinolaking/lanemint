# CSV rate-card format

LaneMint imports one UTF-8 CSV per delivery partner. Re-importing the same partner name replaces its previous rows atomically.

| Column | Required | Example | Meaning |
|---|---:|---|---|
| `service` | Yes | `Standard` | Customer-facing service name. |
| `price` | Yes | `4500` | Shipping price in the stated currency. |
| `country` | No | `NG` | ISO 3166-1 alpha-2; defaults to `NG`. |
| `state` | No | `Lagos` | State name or WooCommerce state prefix. Blank matches all. |
| `lga` | No | `Surulere` | Local government area. Blank matches all. |
| `city` | No | `Ijesha` | City or locality. Blank matches all. |
| `postcode_pattern` | No | `LA01*` | Case-insensitive alphanumeric pattern; `*` is the only wildcard. |
| `min_weight` | No | `0` | Inclusive lower weight in kilograms. |
| `max_weight` | No | `0.5` | Inclusive upper weight in kilograms. |
| `currency` | No | `NGN` | ISO 4217; defaults to store currency. |
| `priority` | No | `10` | Higher values win when rows overlap. |
| `active` | No | `yes` | `0`, `no`, `false`, and `off` disable a row. |

Location cells are matched after lowercasing, accent removal and punctuation normalization. More specific carrier rows should use a higher priority than broad fallback rows.

