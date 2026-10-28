import {z} from 'zod';

export const registerSchema = z.object({
  install_id: z.string().uuid(),
  plugin_version: z.string().regex(/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/).max(32)
}).strict();

export const eventSchema = z.object({
  install_id: z.string().uuid(),
  event: z.enum(['activation', 'deactivation', 'heartbeat', 'error', 'csv_import']),
  plugin_version: z.string().max(32),
  wordpress: z.string().max(32),
  woocommerce: z.string().max(32),
  php: z.string().max(16),
  code: z.string().regex(/^[a-z0-9_-]*$/).max(64),
  area: z.string().regex(/^[a-z0-9_-]*$/).max(64),
  occurred_at: z.string().datetime()
}).strict();

