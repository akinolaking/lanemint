import {createHash, timingSafeEqual} from 'node:crypto';
import {SignJWT, jwtVerify} from 'jose';
import type {NextRequest} from 'next/server';

function secret(name: 'JWT_SECRET' | 'IP_HASH_SECRET') {
  const value = process.env[name];
  if (!value || value.length < 32) throw new Error(`${name} must be at least 32 characters`);
  return value;
}

export async function issueInstallToken(installId: string) {
  return new SignJWT({scope: 'events'})
    .setProtectedHeader({alg: 'HS256', typ: 'JWT'})
    .setSubject(installId)
    .setIssuer('lanemint-telemetry')
    .setIssuedAt()
    .setExpirationTime('180d')
    .sign(new TextEncoder().encode(secret('JWT_SECRET')));
}

export async function verifyInstallToken(token: string) {
  const result = await jwtVerify(token, new TextEncoder().encode(secret('JWT_SECRET')), {issuer: 'lanemint-telemetry'});
  if (result.payload.scope !== 'events' || !result.payload.sub) throw new Error('Invalid token scope');
  return result.payload.sub;
}

export function anonymizedIp(request: NextRequest) {
  const raw = (request.headers.get('x-forwarded-for') || 'unknown').split(',')[0].trim();
  return createHash('sha256').update(`${secret('IP_HASH_SECRET')}:${raw}`).digest('hex');
}

export function adminAuthorized(request: NextRequest) {
  const expected = process.env.ADMIN_API_KEY || '';
  const supplied = request.headers.get('x-admin-key') || '';
  if (expected.length < 32 || expected.length !== supplied.length) return false;
  return timingSafeEqual(Buffer.from(expected), Buffer.from(supplied));
}

