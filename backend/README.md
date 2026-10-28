# LaneMint telemetry backend

A small Next.js service for explicitly opted-in LaneMint installations.

## Deploy on Vercel

1. Create a PostgreSQL database (Vercel Marketplace/Neon is suitable).
2. Run `sql/schema.sql` against it.
3. Import the `backend` directory as the Vercel project root.
4. Set `DATABASE_URL`, `JWT_SECRET`, `IP_HASH_SECRET`, and `ADMIN_API_KEY` from `.env.example`.
5. Deploy and confirm `/api/v1/health` returns `ok: true`.
6. Update the plugin telemetry endpoint before a tagged release, or override it with the `lanemint_telemetry_endpoint` filter.

Generate independent secrets with a cryptographically secure password generator. Never reuse the NIPOST key.

## Endpoints

- `POST /api/v1/register` — rate-limited install-token issuance.
- `POST /api/v1/events` — authenticated strict-schema events.
- `GET /api/v1/summary` — protected maintenance summary; send `X-Admin-Key`.
- `GET /api/v1/health` — public health check.

The service deliberately has no endpoint for raw event export and stores no site URL or customer/store content.

