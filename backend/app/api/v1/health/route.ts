import {NextResponse} from 'next/server';
export function GET() { return NextResponse.json({ok: true, service: 'lanemint-telemetry', version: '0.1.0'}); }

