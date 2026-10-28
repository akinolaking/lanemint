import {NextRequest, NextResponse} from 'next/server';
import {db} from '@/lib/db';
import {adminAuthorized} from '@/lib/security';

export const runtime = 'nodejs';

export async function GET(request: NextRequest) {
  if (!adminAuthorized(request)) return NextResponse.json({error: 'unauthorized'}, {status: 401});
  const sql = db();
  const [installs, versions, errors] = await Promise.all([
    sql`SELECT COUNT(*)::int AS total, COUNT(*) FILTER (WHERE active AND last_seen_at > NOW()-INTERVAL '14 days')::int AS active FROM installs`,
    sql`SELECT plugin_version AS version, COUNT(*)::int AS installs FROM installs WHERE active GROUP BY plugin_version ORDER BY installs DESC`,
    sql`SELECT fingerprint, COUNT(*)::int AS occurrences, COUNT(DISTINCT install_id)::int AS affected_installs, MAX(occurred_at) AS last_seen FROM events WHERE event='error' AND occurred_at > NOW()-INTERVAL '30 days' GROUP BY fingerprint ORDER BY affected_installs DESC, occurrences DESC LIMIT 50`
  ]);
  return NextResponse.json({installs: installs[0], versions, errors}, {headers: {'Cache-Control': 'no-store'}});
}

