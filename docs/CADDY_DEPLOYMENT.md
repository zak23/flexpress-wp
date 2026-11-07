# Zakspov hosting deployment — 7 November 2026

This is the deployment record for Zakspov, not a configuration to copy blindly to another site. The portable guide ships inside the theme at `HOSTING.md` with `hosting/Caddyfile.example` and `AGENTS.md`.

## Live topology and policy

`Visitor → Cloudflare proxy → caddy4 → WordPress private origin`.

- Caddy host: `192.168.1.248`; WordPress upstream: `192.168.1.238:8085`. The upstream's Apache ACL restricts access to the proxy/local networks. Repository Docker/Apache changes are separate, pre-existing work; this deployment did not recreate containers or change those files.
- Both Zakspov hostnames require a Cloudflare connecting IP. Loopback checks over SSH are allowed but have Cloudflare identity/geolocation headers removed. Untrusted direct HTTPS requests return 403, even with forged headers.
- Caddy forwards Cloudflare's visitor IP as `X-Real-IP` and `X-Forwarded-For` only after checking the actual peer against Cloudflare's IPv4 and IPv6 networks. There is no global trust change affecting unrelated sites.
- Dynamic content stays private/no-store. Only successful, cookie-free static asset responses to unauthenticated GET/HEAD requests get a one-day public TTL. `/wp-admin` redirects directly to HTTPS with its trailing slash.
- Caddy access logs omit query strings, Referer and response Location, use mode 0600 and rotate at 25 MiB with ten files retained for at most 720 hours. Older FlexPress log files were also made private; historical contents were not rewritten. This does not sanitize WordPress, Apache, provider or Cloudflare logs.

## Runtime and secrets

Caddy **2.11.9**, built with `github.com/caddy-dns/cloudflare@v0.2.4`, replaced 2.6.1. The complete imported configuration was validated before restarting. The shared ZeroSSL fallback now uses `issuer acme` with `dir https://acme.zerossl.com/v2/DV90`; the newer `issuer zerossl` means a different proprietary API. Existing certificate storage was preserved.

- Site config: `/etc/caddy/sites-enabled/zakspov.txt` (repository `Caddyfile`).
- Global config: `/etc/caddy/Caddyfile`.
- DNS credential: `/etc/caddy/cloudflare.env`, root-owned mode 0600, `CLOUDFLARE_API_TOKEN`.
- Service drop-in: `/etc/systemd/system/caddy.service.d/20-cloudflare-secret.conf`. It loads that environment file and removes `--environ` from ExecStart so credentials are not printed on service startup.
- Rollback snapshot: `/root/caddy-backups/flexpress-20261107T004916Z` on caddy4. Contains the old executable, configuration archive, systemd unit and running configuration; keep private because it contains the previous inline credential.

The existing DNS token was moved, **not rotated**. Its API permissions cannot manage tokens. An account owner must create/rotate the credential in Cloudflare, replace the protected environment value on caddy4, restart the service, verify DNS issuance access, then revoke any superseded token. All sites using the shared token need their zones included. Never paste it into chat, Git or the theme.

## Validation and limits

- Synthetic upstream: 17 cache cases, trusted visitor IP forwarding, forged-header rejection, direct-origin denial, HTTPS admin redirect, query/header log redaction and log permissions.
- Before/after probes covered 120 configured hostnames. HTTP status codes and redirect destinations stayed unchanged; 27 pre-existing 5xx responses and five TLS/connection failures remained. Two redirect responses acquired a text/plain Content-Type with the new Caddy version. This was not a full application audit of the other sites.
- Public Zakspov was reachable through Cloudflare; direct HTTPS to Caddy with Zakspov SNI returned 403.
- Cloudflare Development Mode was intentionally left unchanged. It bypasses edge caching temporarily; it does not turn off the proxy. Cache HIT testing must be repeated after it ends.
- DNS inspection also found `fleet.boundarybase.com` and `tarkov.civic.video` DNS-only. Their records were not changed; no host-wide Cloudflare firewall was applied to this shared server.

Run the isolated regression from this repository with a Caddy 2.11+ binary:

```sh
python3 tests/test-proxy-cache.py --bind PRIVATE_INTERFACE_IP --config Caddyfile --caddy /path/to/caddy
```

It starts temporary listeners and a fake upstream; it never contacts WordPress or reloads production. Its synthetic Cloudflare peers exist only in the temporary test configuration.

## Future changes

Back up config, binary and unit before updates; preserve Caddy certificate storage. Refresh both IPv4 and IPv6 sets from Cloudflare's published API when deploying or reviewing networking changes. Validate the entire shared import tree, not just the site file. Reload for ordinary site edits; restart for executable/environment changes and changed file-log options. Recheck public HTTPS, native WordPress login, private dynamic responses, static caching, webhook reachability and origin denial after every change.

References: [Caddy TLS](https://caddyserver.com/docs/caddyfile/directives/tls), [Caddy access logs](https://caddyserver.com/docs/caddyfile/directives/log), [Cloudflare IP ranges](https://api.cloudflare.com/client/v4/ips), [Development Mode](https://developers.cloudflare.com/cache/reference/development-mode/).
