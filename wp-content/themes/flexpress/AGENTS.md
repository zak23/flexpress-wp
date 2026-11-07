# Agent instructions for FlexPress

These instructions travel with the theme. Apply them to this directory and its descendants. Follow the user's current scope and higher-priority environment instructions; do not assume that an earlier deployment authorizes changes to a different site.

## Orient before changing anything

1. Read `README.md`, `HOSTING.md` and `UPLOAD-CHECKLIST.md` in this directory. Inspect existing project instructions above it if available.
2. Identify whether this is a source checkout, extracted release, active WordPress theme or staging copy. Check Git status when applicable and preserve unrelated edits. A mounted live theme can change production immediately.
3. Discover the destination hostname, WordPress root, Caddy/service/container topology, actual upstream address, Cloudflare zone, payment shop, media zones and backup/restore route. Use connected tools and existing non-secret configuration first; ask only for missing information needed to proceed.
4. Record site-specific facts privately outside the web root. Never bake the previous site's domain, IP address, user IDs, email, shop ID or credentials into reusable theme code or documentation.

## Hosting work

- Use `HOSTING.md` as the deployment runbook and `hosting/Caddyfile.example` as the reviewed starting point. An example is not a live server configuration.
- Render every placeholder for the destination and use unique snippet names, a separate access-log file and an appropriately scoped DNS token. Fetch current Cloudflare IPv4 AND IPv6 ranges; update both range lists together.
- Require proxied DNS records and Full (strict) TLS with a valid origin certificate. Keep WordPress inaccessible from the public Internet. Trust visitor/geolocation headers only from verified immediate proxies; never trust arbitrary forwarded headers or all private networks.
- The example assumes Cloudflare connects directly to Caddy. Inspect additional proxies or tunnels and adapt the entire trust chain instead of disabling the peer check to make traffic work.
- Keep dynamic/customer/payment traffic private/no-store. Inspect Cache Rules, Page Rules, Workers and WordPress caches for overrides. Never enable blanket HTML caching or a service worker page cache.
- Preserve payment callbacks through the edge: no interactive challenge/login/redirect on legitimate provider POSTs. Keep signature verification enabled. Do not disable security zone-wide to work around one route.
- Do not change every site, DNS record, firewall or service on a shared host as a side effect of configuring one site. Validate the whole import tree and compare other sites before/after shared binary changes.
- Back up config, executable, service unit and certificate storage privately. Validate the exact candidate and prepare rollback before reload/restart. Restart when changing the executable, service environment or file-log settings; a simple reload will not apply everything.
- Store secrets in the host's secret mechanism; never in the theme, Git, shell history, command-line arguments or chat. Remove `--environ` from Caddy startup before using secret environment variables. Do not print full configs, process environments, admin JSON or credential-bearing errors.
- Strip sensitive request query strings and headers from access logs. Verify upstream and provider logging separately; a Caddy filter does not sanitize other systems.
- Report verified outcomes and missing access accurately. Public reachability does not prove Full (strict), cache rules, renewal or payment settlement. Do not claim settings were checked when the API returned a permission error.

## Product invariants

- Reusable code stays in the theme, not a custom plugin or mu-plugin. Use WordPress URL/settings helpers instead of fixed hostnames. Hosting remains a separate deployment step.
- Use capability checks, nonces, input validation, output escaping and prepared database queries. Founders bypass paid access; ordinary customers must not gain that bypass.
- Never grant access or establish login from return-query user IDs, session references or an unsigned webhook. Verify signed provider callbacks for the destination shop and preserve replay/transaction handling.
- Keep subscription and PPV metadata separate. Recurring retry/extend notices do not move the paid-through date. Refund/cancellation processing must not lift bans or cancel a later replacement subscription.
- Persist notifications/cancellation jobs only with committed payment changes. Keep retries and WordPress cron working; external delivery is at-least-once.
- Lifetime checkout remains unsupported until implemented and verified deliberately. Do not silently map it to another purchase type.
- Do not charge a real card for tests. Use only a provider test instrument authorized for this destination or a confirmed provider test environment. Prepare non-charging tests while that is unknown.
- Do not copy customer databases, entitlements, provider secrets or the previous site's test card as part of a theme upload. Audit historical anomalies without automatically removing access.
- Leave the AU server-side age gate parked unless requested. Enabling it requires the master flag, Yoursafe configuration, trusted geolocation and callback/exemption tests.
- Settings sharing `flexpress_general_settings` must merge changes without deleting other tabs' keys. Include hidden `0` values for checkboxes. Preserve existing form recipients and destination credentials.
- The service worker is same-origin, static-assets-only. Runtime vendor files ship; development dependencies, backups and diagnostics do not.

## Editing, checks and handoff

Use PHP 4-space indentation, JS/CSS 2-space indentation, existing project conventions and readable comments explaining non-obvious behaviour. Do not modify `.wakatime-project` if present.

Run checks appropriate to the actual change. Source-repository checks include PHP lint, `tests/test-payment-security.php`, `tests/test-payment-effects.php`, `tests/test-admin-login.php`, `tests/test-payment-buttons.cjs`, `tests/test-theme-package.py` and `tests/test-proxy-cache.py`. These may not exist in an installed theme ZIP; never claim they ran there. Use disposable data for integration tests, intercept outgoing messages in synthetic suites, and obtain destination-specific test-payment authorization before provider tests.

After hosting changes, verify public HTTPS, native admin login, asset and dynamic cache headers, webhook GET rejection, origin blocking, visitor-IP handling and private redacted logs. Confirm edge caching after Development Mode is off. Real certificate renewal and signed payment fulfillment need their own evidence.

Update the portable docs when behaviour or setup changes. Keep destination facts in a private inventory or the source repository's deployment record, not the generic guide. If the user has authorized commits, commit meaningful steps and stage only your changes. Bump both theme version declarations for a release, build from committed files, inspect the exact ZIP, and provide the artifact plus validation results, limitations and rollback location. Do not claim the theme upload configured Caddy or Cloudflare.
