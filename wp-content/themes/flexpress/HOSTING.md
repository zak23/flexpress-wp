# Deploy FlexPress behind Caddy and Cloudflare

This guide is part of the portable theme. It describes a safe baseline for a new site and a repeatable way to verify it. It is not an installer and contains no destination credentials. **Uploading the theme does not apply any of these server or Cloudflare settings.**

The standard topology is:

```text
Visitor → Cloudflare HTTPS proxy → Caddy HTTPS → private WordPress HTTP origin
                                      └→ DNS challenge for origin certificates
Browser → configured Bunny CDN/Stream for media
```

Use the same security/cache outcomes with a managed host or another server. Do not replace an existing stack merely because this example uses Caddy. A Cloudflare Tunnel, load balancer or additional proxy changes the immediate peer; review that topology before applying this IP-based example.

## 1. Establish the destination facts

Keep this inventory outside the public web root. Discover values from the intended host/account; do not infer them from the development site.

| Input | What to record |
| --- | --- |
| Site domain | Canonical hostname and whether `www` is required; WordPress home/site URLs must agree |
| Cloudflare | Account, zone, authorized operator and proxied A/AAAA/CNAME records |
| Public ingress | Origin/NAT address or tunnel and exposed ports; check both IPv4 and IPv6 |
| Caddy | Host, OS/architecture, version, installed modules, config imports, service/container, data storage |
| WordPress | Root, container/service, actual reachable private upstream address/port and PHP/database versions |
| Trust boundary | Every proxy hop, backend ACL/firewall and which peers can set visitor headers |
| Providers | Destination Flowguard shop/currency/plans, callback/return URLs, Bunny zones, email and optional integrations |
| Operations | Backup/restore route, cron runner, private log directory, DNS-secret location and rollback owner |

Back up the existing theme, database and relevant hosting configuration before changing them. On a shared proxy, capture baseline status/redirects for its other hostnames before a binary/global-config update. Preserve certificate/account storage; replacing it unnecessarily can trigger issuance failures and rate limits.

## 2. Configure Cloudflare

1. Create or inspect the intended site's DNS records. All public web hostnames served by this setup must be **Proxied**. Remove/correct stale origin AAAA records as well as A records. Do not proxy mail/SSH records or alter unrelated services as part of this setup.
2. Use **SSL/TLS → Full (strict)** with a valid, unexpired origin certificate for every hostname. The example uses Caddy-managed public certificates through a DNS challenge. Do not fall back to Flexible or disable certificate checks to hide an origin problem.
3. Enable HTTPS redirection at the edge if appropriate; Caddy also redirects HTTP to HTTPS and the alias to the canonical hostname. Check for redirect loops and preserve POST callback destinations.
4. Enable IP Geolocation if the optional country-based features are needed. Confirm `CF-IPCountry` reaches WordPress through the trusted chain. Do not fabricate a country when Cloudflare supplies an unknown value.
5. Inspect **Cache Rules, legacy Page Rules, Workers, APO/page caching and WordPress cache plugins**. Use ordinary static-file eligibility and respect origin Cache-Control. Remove scoped rules that cache HTML or override no-store. Keep query strings in the cache key for versioned assets. Set Browser Cache TTL to **Respect Existing Headers** where available.
6. Ensure login-cookie and Authorization-bearing requests bypass edge cache. Login, registration, account, join, payment, AJAX, REST and callback requests must not be cached. Do not rely on `Vary: Cookie` alone to make a CDN cache key safe.
7. Leave server-to-server callbacks reachable. A legitimate POST to `/wp-admin/admin-ajax.php?action=flowguard_webhook` must not face an interactive bot challenge, Access login or canonical-host redirect. If edge security blocks it, inspect the actual event and make the smallest necessary exception; preserve the application's signature checks. A broad allow rule for all of `/wp-admin` is inappropriate.
8. Start with script-reordering features such as Rocket Loader disabled while validating the theme's deferred scripts and payment SDK. Enable optimizations individually only after testing login, checkout, Turnstile and video playback.
9. Development Mode temporarily bypasses edge cache; it does not disable the proxy or purge old cached objects. Respect the user's requested mode while working. When ready for cache acceptance, turn it off with the operator's agreement, purge affected stale URLs and run the cache checks below. Browser caches are separate.

If your API token cannot read a setting, record it as **unverified** and have the account operator inspect it. Seeing a `CF-Ray` header only proves that response passed through Cloudflare; it does not prove the zone's TLS mode or cache-rule configuration.

## 3. Install a compatible Caddy and DNS credential

The example was tested with **Caddy 2.11.9** and **`github.com/caddy-dns/cloudflare` v0.2.4**. Check current official releases and module compatibility before a future installation. Preserve other required modules when updating an existing custom binary; the standard package may not include Cloudflare DNS support.

Verify the actual runtime executable:

```sh
caddy version
caddy list-modules --packages --versions
```

It must include `dns.providers.cloudflare`. One reproducible build command, in a suitable build environment with xcaddy installed, is:

```sh
xcaddy build v2.11.9 --with github.com/caddy-dns/cloudflare@v0.2.4 --output ./caddy
```

Use the correct architecture, retain the old executable and verify the built version/modules. Do not run an unreviewed package update against a shared proxy.

Create a Cloudflare API token scoped to the destination zone with Zone DNS Edit and Zone Read permissions required by the DNS provider. A single shared token must cover all zones whose certificate automation uses it; separate tokens reduce cross-site exposure. Put it in the server's secret mechanism, never in this theme or the rendered Caddyfile.

For a conventional systemd deployment, an operator can create a root-owned mode-0600 environment file outside the web root, for example `/etc/caddy/site-dns.env`, containing a site-specific variable such as `SITE_DNS_TOKEN`. Enter the real value with a private editor or secret tool; do not paste it in chat or a command that appears in history.

An example drop-in for an existing service **whose inspected executable/config paths match these** is:

```ini
[Service]
EnvironmentFile=/etc/caddy/site-dns.env
ExecStart=
ExecStart=/usr/bin/caddy run --config /etc/caddy/Caddyfile
```

Adapt rather than overwrite the actual service's other flags and settings. Remove `--environ`, which would print secrets on startup. Systemd reads the protected environment file before switching to the service user; do not make it world-readable. For containers, configure secrets/environment through the deployment's own mechanism and persist Caddy's data/config volumes.

Reference the token at runtime as `{env.SITE_DNS_TOKEN}`. After changing an environment value, **restart** the service; reloading config alone does not refresh a running process's environment. Keep admin API listeners local/private and treat admin JSON/config backups as sensitive. Rotate exposed tokens through Cloudflare, replace the protected value, verify the new token and then revoke the old one. Never log the value during testing.

## 4. Render and review the example

Copy [hosting/Caddyfile.example](hosting/Caddyfile.example) to a private staging directory outside WordPress. Replace every placeholder:

| Placeholder | Replacement |
| --- | --- |
| `__DOMAIN__` | Destination canonical base hostname, without scheme/path; example `example.com` |
| `__UPSTREAM__` | Actual private WordPress listener, for example `127.0.0.1:8085` when Caddy shares the host, or a protected LAN address/port |
| `__SITE_ID__` | Unique simple identifier for this site, such as `example`; use letters/digits/hyphens and start with a letter |
| `__ACCESS_LOG__` | Absolute private log path for this site, such as `/var/log/caddy/example.log` |
| `__TOKEN_ENV__` | Name of the destination DNS token variable, such as `SITE_DNS_TOKEN`; never its value |

The template assumes an apex canonical host plus `www` redirect. Remove the alias block if it is not wanted or has no DNS/certificate coverage. For a subdomain or canonical `www`, deliberately adjust both site labels and redirects; do not accidentally create `www.www...`. Snippet identifiers and log files must be unique when multiple rendered copies are imported on the same Caddy instance.

The example contains Cloudflare's IPv4/IPv6 networks as a dated snapshot. Fetch current lists from [Cloudflare's IP API](https://api.cloudflare.com/client/v4/ips), validate them as CIDRs, and update **both** the Cloudflare-only and allowed-peer snippets together. Do not replace valid ranges with an empty/partial response. Recheck the lists during deployments and networking maintenance.

What the example does:

- Restricts application HTTPS routes to Cloudflare's actual connecting networks. It permits loopback for checks executed on the proxy host over SSH, while stripping Cloudflare identity/country headers from those local requests. It does not permit LAN clients as an origin bypass.
- For verified Cloudflare peers, forwards `CF-Connecting-IP` to WordPress as `X-Real-IP` and `X-Forwarded-For`. It overwrites supplied forwarded-IP chains. Keep Cloudflare visitor-header transforms compatible with this; test IPv6 and Pseudo IPv4 settings if used.
- Uses a DNS challenge so origin certificate issuance does not depend on public HTTP reaching the application. Verify token access and CAA records; syntax validation alone does not prove future renewal.
- Keeps dynamic/upstream redirects/errors private/no-store. Only unauthenticated GET/HEAD requests under `/wp-content/` and `/wp-includes/` with an approved asset extension, a successful 200/206 response, actual asset MIME type and no Set-Cookie receive a one-day public TTL. Canonical HTTPS redirects are separate from this application cache policy.
- Redirects `/wp-admin` to its HTTPS trailing-slash URL before Apache can issue an HTTP directory redirect. Native login still requires WordPress authentication.
- Compresses suitable content, applies basic response headers and removes the obsolete X-XSS-Protection header. Review any future CSP with Bunny, Flowguard and other configured providers; do not paste in a policy that breaks them.
- Removes query strings, Referer and response Location from access logs. Logs use 0600 permissions, rotate at 25 MiB and retain at most ten archives/720 hours. Set ownership for the actual service user and protect old logs too. Audit Apache, WordPress and provider logs separately.

The peer allowlist proves traffic came through Cloudflare's network, not a specific customer's Cloudflare zone. For stronger origin authentication, evaluate zone-specific authenticated origin pulls or an appropriate tunnel; that needs separate certificate/trust configuration and testing.

## 5. Protect the backend and apply safely

Caddy restrictions do not protect an exposed WordPress port. If both services share a host, bind the backend to loopback or a private container network. If Caddy is on another machine, bind a private interface and restrict that listener to the exact proxy peer(s) using the actual firewall/Apache/container topology. Test Docker-published ports and IPv6 explicitly; a host firewall rule alone may not cover Docker's forwarding rules.

Configure WordPress/Apache to recognize HTTPS and visitor headers **only from trusted proxies**. Verify secure cookies, HTTPS-generated URLs, `is_ssl()` and admin redirects. Do not enable blind forwarded-header trust on a publicly reachable backend. Never replace a missing upstream with an arbitrary LAN-wide allow rule.

For a shared host, apply a site-scoped policy first. A host-wide Cloudflare firewall can break DNS-only sites and ACME challenges; inventory and preserve those services before any broader change. Keep SSH/management access independent and verify rollback access.

Before applying:

1. Save private copies of the existing global/site config, binary, service definition and certificate storage. Keep backups outside imported config directories and outside the web root. Record exact restoration steps.
2. Render the example, remove every `__PLACEHOLDER__`, review the diff and ensure no real token appears. Create/protect its log directory with ownership appropriate to the service.
3. Run `caddy fmt --overwrite` on the candidate. Stage a copy of the entire import tree, replace only the intended site config, and run `caddy validate --config /private/staging/Caddyfile --adapter caddyfile` with the correct secret environment. Do not print adapted JSON to shared output.
4. On systemd, validation can be run in a transient service with `EnvironmentFile=` and the intended runtime user/group; use a protected staging directory readable by that user. Or use the host's established secret-aware validation mechanism. An interactive shell does not automatically inherit the service's environment file.
5. Install the validated files atomically. Use a graceful reload for ordinary config-only changes. Run `systemctl daemon-reload` after unit changes and restart for binary, secret environment or file-log option changes. Adapt command/service names to the actual installation.
6. Verify service readiness and the checks below immediately. If readiness, public access or previously working shared sites regress, restore the saved config/binary/unit and restart. Preserve certificate storage and the new private diagnostic logs for investigation.

Current Caddy syntax note: an old `issuer zerossl` block is not interchangeable with the new proprietary ZeroSSL API issuer. To preserve ZeroSSL **ACME** as an optional fallback, use `issuer acme` with `dir https://acme.zerossl.com/v2/DV90` and valid account/contact/EAB configuration. Review the existing accounts; do not invent contact credentials. The portable example uses Caddy's ordinary ACME defaults with DNS validation.

## 6. Acceptance checks

Use a real normal customer account for access checks, not a founder. Start with read-only requests and synthetic payment tests. Keep output free of cookies, keys, signed links and customer data.

| Check | Expected evidence |
| --- | --- |
| Public `/` and `/login/` | HTTPS 200 through Cloudflare; HTML private/no-store; no cache HIT |
| `/wp-admin` → `/wp-admin/` | HTTPS canonical redirect; no intermediate HTTP hop |
| Signed-out `/wp-admin/` | Native WordPress login with destination retained; no redirect loop |
| Genuine public CSS/JS/image | Correct asset MIME type; public one-day TTL; no Set-Cookie |
| Missing `.css` URL | Error/HTML remains private/no-store, never long-lived public caching |
| Asset with login cookie or Authorization | Private/no-store at origin and bypassed at edge; no cached personalized response |
| Callback GET | 405 from the application, not a challenge/login page; this is not a signed-payment test |
| Real provider test callback | Authorized destination test only; signed POST accepted once and correct access/receipt recorded |
| Direct HTTPS origin request with correct SNI/Host | Denied from an untrusted peer, including forged CF and forwarded headers |
| Direct WordPress/backend port | Inaccessible from the public Internet, for both IPv4 and IPv6 |
| Visitor IP and geolocation | Origin receives the verified visitor IP; local probes cannot spoof Cloudflare identity/country |
| Access logs | Private permissions; innocuous query-marker absent; path/status retained; rotation configured |
| Certificate operation | Valid current certificate and working DNS authorization; observe normal renewal without forcing repeated production issuance |
| Shared server | Previously working host status/redirects and key application behaviour unchanged |

Examples below use the **actual inventory values** supplied locally; do not paste secrets into them. `curl -I` checks HEAD behaviour, so also use GET for representative pages/assets. Avoid publishing complete headers that might contain Set-Cookie or signed redirect URLs.

```sh
# DOMAIN is the verified destination hostname; ORIGIN_IP is the actual Caddy address.
# Public request through DNS/Cloudflare:
curl -sS -o /dev/null -w '%{http_code}\n' "https://$DOMAIN/"

# Direct-origin diagnostic from an untrusted machine, retaining certificate verification:
curl -sS --resolve "$DOMAIN:443:$ORIGIN_IP" -o /dev/null -w '%{http_code}\n' "https://$DOMAIN/"

# Read-only reachability check; 405 is expected:
curl -sS -o /dev/null -w '%{http_code}\n' "https://$DOMAIN/wp-admin/admin-ajax.php?action=flowguard_webhook"
```

Do not use `-k` to conceal invalid TLS. A direct HTTP request may be redirected to HTTPS by Caddy's automatic HTTPS handler; the application must still be denied at the final direct HTTPS endpoint. A loopback request on Caddy is intentionally allowed and cannot prove public-origin blocking.

After Development Mode ends, request an eligible asset repeatedly and inspect CF-Cache-Status, Cache-Control and Age. Account for WAF responses, cache eligibility and regional edge misses; a first MISS is normal. Verify that version-query changes select the new content. Repeat dynamic and authenticated checks to ensure no edge override reintroduces page caching. Purge affected stale content when deploying corrected policy; existing browser cache entries may need their old TTL to expire.

In the source repository, `tests/test-proxy-cache.py` exercises a synthetic upstream, including header spoofing and redaction. It is not bundled as an executable dependency of the theme. The deployed configuration must still pass the live checks above.

## 7. Site handoff

Record the deployed version/modules, destination config/log/secret paths (names only), backup location, certificate strategy, origin restrictions, Cloudflare settings, exact checks run and outstanding items. Separate confirmed state from assumptions or inaccessible settings. Record provider tests separately from synthetic checks. Never mark a site ready merely because the theme ZIP uploaded successfully.

Maintain a per-site launch record outside the public theme. Update these portable instructions when changing the common contract; keep site-specific values out of them.

## Official references

- [Caddy TLS and DNS challenges](https://caddyserver.com/docs/caddyfile/directives/tls), [Cloudflare DNS module](https://github.com/caddy-dns/cloudflare)
- [Caddy reverse-proxy headers](https://caddyserver.com/docs/caddyfile/directives/reverse_proxy#defaults), [request matchers](https://caddyserver.com/docs/caddyfile/matchers)
- [Caddy log filters and rotation](https://caddyserver.com/docs/caddyfile/directives/log), [service management](https://caddyserver.com/docs/running)
- [Cloudflare Full (strict)](https://developers.cloudflare.com/ssl/origin-configuration/ssl-modes/full-strict/), [current IP ranges](https://api.cloudflare.com/client/v4/ips)
- [Cloudflare cache settings](https://developers.cloudflare.com/cache/how-to/cache-rules/settings/), [Development Mode](https://developers.cloudflare.com/cache/reference/development-mode/)
