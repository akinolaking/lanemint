import {NextRequest, NextResponse} from 'next/server';
import {db} from '@/lib/db';
import {eventSchema} from '@/lib/schema';
import {verifyInstallToken} from '@/lib/security';

export const runtime = 'nodejs';

export async function POST(request: NextRequest) {
  if (Number(request.headers.get('content-length') || 0) > 16384) return NextResponse.json({error: 'payload_too_large'}, {status: 413});
  const auth = request.headers.get('authorization') || '';
  if (!auth.startsWith('Bearer ')) return NextResponse.json({error: 'unauthorized'}, {status: 401});

  let subject = '';
  try { subject = await verifyInstallToken(auth.slice(7)); }
  catch { return NextResponse.json({error: 'unauthorized'}, {status: 401}); }

  const parsed = eventSchema.safeParse(await request.json().catch(() => null));
  if (!parsed.success || parsed.data.install_id !== subject) return NextResponse.json({error: 'invalid_request'}, {status: 400});

  const p = parsed.data;
  const fingerprint = p.event === 'error' ? `${p.area}:${p.code}` : '';
  const sql = db();
  await sql.begin(async tx => {
    await tx`
      INSERT INTO events (install_id,event,plugin_version,wordpress_version,woocommerce_version,php_version,error_code,error_area,fingerprint,occurred_at)
      VALUES (${p.install_id},${p.event},${p.plugin_version},${p.wordpress},${p.woocommerce},${p.php},${p.code},${p.area},${fingerprint},${p.occurred_at})`;
    await tx`
      UPDATE installs SET plugin_version=${p.plugin_version}, wordpress_version=${p.wordpress}, woocommerce_version=${p.woocommerce}, php_version=${p.php}, last_seen_at=NOW(), active=${p.event !== 'deactivation'}
      WHERE install_id=${p.install_id}`;
    await tx`DELETE FROM registration_attempts WHERE created_at < NOW() - INTERVAL '24 hours'`;
    await tx`DELETE FROM events WHERE received_at < NOW() - INTERVAL '180 days'`;
  });

  return new NextResponse(null, {status: 204});
}

