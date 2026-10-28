import {NextRequest, NextResponse} from 'next/server';
import {db} from '@/lib/db';
import {registerSchema} from '@/lib/schema';
import {anonymizedIp, issueInstallToken} from '@/lib/security';

export const runtime = 'nodejs';

export async function POST(request: NextRequest) {
  if (Number(request.headers.get('content-length') || 0) > 8192) return NextResponse.json({error: 'payload_too_large'}, {status: 413});
  const parsed = registerSchema.safeParse(await request.json().catch(() => null));
  if (!parsed.success) return NextResponse.json({error: 'invalid_request'}, {status: 400});

  const sql = db();
  const ipHash = anonymizedIp(request);
  const [{count}] = await sql<{count: number}[]>`
    SELECT COUNT(*)::int AS count FROM registration_attempts
    WHERE ip_hash=${ipHash} AND created_at > NOW() - INTERVAL '1 hour'`;
  if (count >= 20) return NextResponse.json({error: 'rate_limited'}, {status: 429});

  await sql.begin(async tx => {
    await tx`INSERT INTO registration_attempts (ip_hash) VALUES (${ipHash})`;
    await tx`
      INSERT INTO installs (install_id, plugin_version, first_seen_at, last_seen_at, active)
      VALUES (${parsed.data.install_id}, ${parsed.data.plugin_version}, NOW(), NOW(), TRUE)
      ON CONFLICT (install_id) DO UPDATE SET plugin_version=EXCLUDED.plugin_version, last_seen_at=NOW()`;
  });

  return NextResponse.json({token: await issueInstallToken(parsed.data.install_id)}, {
    status: 201,
    headers: {'Cache-Control': 'no-store'}
  });
}

