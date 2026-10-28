CREATE TABLE IF NOT EXISTS installs (
  install_id uuid PRIMARY KEY,
  plugin_version varchar(32) NOT NULL,
  wordpress_version varchar(32) NOT NULL DEFAULT '',
  woocommerce_version varchar(32) NOT NULL DEFAULT '',
  php_version varchar(16) NOT NULL DEFAULT '',
  first_seen_at timestamptz NOT NULL DEFAULT now(),
  last_seen_at timestamptz NOT NULL DEFAULT now(),
  active boolean NOT NULL DEFAULT true
);

CREATE TABLE IF NOT EXISTS events (
  id bigserial PRIMARY KEY,
  install_id uuid NOT NULL REFERENCES installs(install_id) ON DELETE CASCADE,
  event varchar(32) NOT NULL,
  plugin_version varchar(32) NOT NULL,
  wordpress_version varchar(32) NOT NULL,
  woocommerce_version varchar(32) NOT NULL,
  php_version varchar(16) NOT NULL,
  error_code varchar(64) NOT NULL DEFAULT '',
  error_area varchar(64) NOT NULL DEFAULT '',
  fingerprint varchar(129) NOT NULL DEFAULT '',
  occurred_at timestamptz NOT NULL,
  received_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS events_install_id_idx ON events(install_id);
CREATE INDEX IF NOT EXISTS events_fingerprint_idx ON events(fingerprint, occurred_at DESC);

CREATE TABLE IF NOT EXISTS registration_attempts (
  id bigserial PRIMARY KEY,
  ip_hash char(64) NOT NULL,
  created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS registration_attempts_window_idx ON registration_attempts(ip_hash, created_at DESC);

