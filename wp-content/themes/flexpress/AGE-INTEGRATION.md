# Optional Exclusv age gateway adapter

Focused visitor candidate 1.0.47 implements an opt-in gateway adapter. It is disabled by default and has not been installed on a live site. It protects WordPress HTML, REST and AJAX when configured, but **direct uploads, other media helpers, CDN origins and edge caches still require enforcement**. This is a visitor page gate, not a complete media-protection solution. Direct media hardening is separate from this focused rollout.

## Installation contract

Requires PHP 8.2+, native Sodium, WordPress 6.2+, InnoDB and a single-site installation at a root origin. Yoursafe remains available with its existing enabled settings and credentials. Either age-gate flag enables that provider; when the gateway flag is on, the combined visitor guard owns redirects so the two guards do not loop. Production WordPress rejects sandbox assertions. Loopback HTTP is allowed only for an explicitly configured local sandbox fixture; production requires HTTPS.

Two artifacts are required: the reviewed theme ZIP and the matching `exclusv-age-php-runtime.zip` from the gateway repository. The private runtime includes locked Facile OIDC/JOSE, Guzzle and PSR dependencies under `ExclusvAgeRuntime`, with a distinct Composer loader. Install the runtime **outside every served web root**, readable by PHP. Do not upload that ZIP through WordPress or put it in `wp-content`. Verify archive hashes and the runtime manifest before installation. No Composer or development tools are needed on the destination. A theme ZIP alone does not supply or activate the gateway.

Provision a private PHP configuration file outside the web root, readable only by the PHP operator. Return an array with these keys:

| Key | Required value |
|---|---|
| `runtime_autoload` | Absolute path to the private runtime's `vendor/autoload.php` |
| `site_origin` | Exact canonical WordPress home origin, no trailing slash or path |
| `issuer`, `client_id`, `client_secret` | Approved gateway origin and this site's confidential client registration |
| `policies`, `countries` | Explicit nonempty allowlists of approved policies and countries |
| `environment` | `production` for live; `sandbox` only for nonproduction WordPress |
| `allow_loopback` | `false` for hosted deployments |
| `support_email` | Destination operator's support address |
| `trusted_peers` | Exact immediate proxy IPs as seen in PHP `REMOTE_ADDR`, not CIDRs or visitor IPs |
| `peer_source` | Optional `remote_addr` (default), or `apache_connection` when mod_remoteip rewrites `REMOTE_ADDR` |
| `ingress_secret` | Unique 32 random bytes encoded base64url without padding (43 characters) |
| `storage_keys` | Map of short key versions to unique raw 32-byte encryption keys |
| `active_storage_key` | Version used for new records |

Read secret values from the host's protected secret mechanism inside that private file. Never put them in theme settings, source control, command arguments or the public document root. Configure `FLEXPRESS_EXCLUSV_AGE_CONFIG` to its absolute path in private WordPress configuration. Keep `FLEXPRESS_EXCLUSV_AGE_ENABLED` false during installation.

Register exactly `site_origin + /_age/callback`; a real registration requires the gateway's separate release process. Run `wp flexpress-age install` with the theme loaded to create the two private age tables and schedule hourly bounded cleanup. Inspect the schema and scheduler before enabling `FLEXPRESS_EXCLUSV_AGE_ENABLED`. The CLI registration is available while enforcement is disabled. These commands never enable the gateway or change membership/payment records.

## Ingress and cache requirements

Caddy must strip browser-supplied `X-Exclusv-Origin` and set the private value only for this site's verified ingress. Accept Cloudflare country/visitor headers only from verified Cloudflare peers, and overwrite forwarded chains. Restrict the WordPress backend to the exact proxy. Confirm PHP's actual immediate peer and trusted HTTPS handling before choosing `trusted_peers`; do not rewrite `REMOTE_ADDR` to an untrusted visitor address. Unknown country, wrong host, missing secret, untrusted peer or unavailable issuer deny access.

If Apache already uses `mod_remoteip`, preserve the visitor address needed by existing site/payment code. In the WordPress VirtualHost, use `SetEnvIfExpr "%{CONN_REMOTE_ADDR} =~ /^(.+)$/" EXCLUSV_CONNECTION_ADDR=$1` and set private `peer_source` to `apache_connection`. The adapter then checks that server-owned environment value against `trusted_peers`; it never accepts `HTTP_EXCLUSV_CONNECTION_ADDR` or falls back to `REMOTE_ADDR` when the value is absent. Do not populate it from a request header. Verify root/index, rewritten pages, REST and callback routes under the destination's actual PHP handler. [Apache connection variable](https://httpd.apache.org/docs/current/expr.html) and [SetEnvIfExpr](https://httpd.apache.org/docs/2.4/mod/mod_setenvif.html) document this distinction.

Keep all HTML, API, redirects and age routes private/no-store at Apache, Caddy and Cloudflare. Exclude protected uploads/images from the portable public-static cache exception. Apply the reviewed CDN rules separately. A theme upload cannot secure a public media origin or invalidate existing cached copies.

## Request behavior

- `GET /_age/`: neutral page with no content images; fresh CSRF cookie or active-session controls.
- `POST /_age/start`: exact Origin plus double-submit CSRF, atomic rate limit, PKCE/state/nonce and pinned issuer redirect.
- `GET /_age/callback`: one-use encrypted handoff, verified ES256 claims and online eligibility; rotates the local session and returns only to a safe same-site path. Query strings are not preserved in return destinations.
- `POST /_age/logout`: exact Origin and session CSRF; local cookie/record removal before attempted remote revocation. An issuer outage cannot retain local access; failed remote revocation may remain until its bounded expiry.
- `GET /_age/privacy` and `/_age/help`: neutral public guidance and configured operator contact. The privacy screen links to the site policy selected in WordPress Settings → Privacy.
- The selected, published, password-free privacy page remains readable before verification at its same-origin permalink (GET/HEAD). It renders only that policy in the neutral age layout, with basic formatting and links; no theme chrome, media, embedded content, shortcodes or forms execute. Query parameters cannot select another page; other routes and REST requests retain the age guard. Select a real policy page and verify its text before launch.

Gateway cookies are `__Host-` prefixed, Secure, HttpOnly, host-only, path `/`, SameSite=Lax. The callback uses no-referrer. Entry forms use same-origin referrer policy so native POST Origin checks work, and CSP allows form redirects only to self and the exact issuer. The obsolete self-declaration popup is omitted when gateway enforcement is enabled; localStorage never grants gateway access.

A valid existing server-signed Yoursafe cookie is accepted independently of gateway availability. Otherwise, protected authorization uses current gateway introspection, without a positive authorization cache or refresh token. Invalid or expired results deny access. Age access never creates a WordPress login or membership entitlement. Founder paid-access bypass does not bypass age checks.

Native admin/login and WP-CLI/cron retain their own access controls. Only the exact signed Flowguard/PPV payment AJAX actions and Flowguard REST callback bypass the age guard; their existing signature/method checks remain. Other AJAX and all other REST routes require age access, including alternate REST query routing. Admin-post is protected. Third-party payment callbacks need explicit review, not blanket API exemptions.

## Storage and operations

`Records` uses a dedicated wpdb connection so payment rollbacks cannot resurrect handoffs. XChaCha20-Poly1305 binds ciphertext to table, purpose, handle hash, expiry and versioned key. PKCE verifiers and access tokens stay encrypted. An atomic claim followed by deletion permits one callback consumer; crash/delete failure leaves the flow unusable. Rate-limit buckets are keyed hashes of trusted visitor IP and purpose, with bounded windows, never raw IPs.

Handoffs last at most ten minutes. Local sessions are capped by assurance, gateway session and actual access-token expiry, maximum one hour; they do not slide. Expired records cannot authorize even before cleanup.

Hourly `flexpress_exclusv_age_cleanup` removes at most 500 expired records and 500 rate buckets per run. Monitor the `flexpress_exclusv_age_cleanup_failed` option and table backlog. Run `wp flexpress-age purge` in additional bounded batches where traffic exceeds hourly cleanup capacity. A host cron runner, alerting and operational capacity review are required before production; installing the hook alone is not proof of reliable scheduling.

Rotate storage keys with overlap: new records use the active version; retain previous keys through maximum record lifetime and cleanup. Removing a key early invalidates those sessions. Keep private backups and keys outside web roots. Restoring a backup must deliberately invalidate prior browser sessions. Never restore a customer database casually over new purchases.

## Media behavior and remaining work

The preview signing endpoint requires POST, nonce, a published/password-free episode ID and its exact preview video. Preview/full aliases also require normal paid/release authorization; banned ordinary customers cannot reuse purchases. When gateway enforcement is enabled, signing requires a fresh age check and expiry is the earlier of five minutes and the age-session deadline. The player has no unsigned fallback.

This does not secure all thumbnails, full players, gallery images, direct uploads or CDN origins. Their entitlement checks and direct delivery controls are deferred work; do not claim this visitor gate protects direct media URLs.

## Verification evidence

Disposable fixtures only; no live customers, provider charges or outbound notifications:

- Actual WordPress/ACF/theme + MySQL + Node issuer: 152 request assertions, 19 configuration assertions and production rejection of sandbox configuration.
- Storage: 61 assertions including competing callback/rate workers, ciphertext tampering, key rotation, cleanup and payment-transaction isolation.
- Preview endpoint: 37 assertions including age expiry/denial and founder behavior; player: six scenarios.
- Browser: native entry → gateway consent → WordPress, with session-management/logout checked separately in the gateway repository's fixture record.

Source tests are `tests/run-age-storage.py`, `tests/test-media-authorization.php` and `tests/test-preview-player.cjs`. The gateway repository owns `tests/wordpress/` and the private runtime builder. Existing payment/admin/package regressions remain required. The full fixture uses a synthetic database, intercepted mail/WordPress HTTP and an internal-only Docker network; no live content or secrets are copied.

Before live activation: finish visitor-page cache and exact HTTPS ingress validation, ordinary-customer scenarios, cleanup/monitoring and restore drills, provider/privacy/security acceptance and the applicable production approval. Roll back by restoring the reviewed prior theme/runtime/config and retaining payment data. Disabling the flag removes this protection; it is not a safe fallback for a site required to enforce age access.

## Yoursafe alternative

The neutral visitor screen offers **Sign in with Yoursafe** when its existing settings are enabled, alongside **Verify with Didit** through the age gateway. The existing `/age-verification/yoursafe/start/` and `/age-verification/yoursafe/callback/` routes remain unchanged. Only these exact GET routes pass the new guard before verification; no general route or API exemption was added. Cancelled/failed callbacks return to the method choice with the original destination.

Yoursafe still validates the signed RS256 response, issuer, audience, nonce, expiry, active account and `eighteenplus=true` before issuing its signed cookie. The existing cookie lifetime is 90 days; this increment does not change that policy. Gateway sessions remain at most one hour under the existing configuration. An ordinary WordPress login or self-declaration cannot satisfy the combined guard. Both methods require the configured trusted ingress and country allowlist for protected content.

The active-session clear action clears both provider proofs locally before any remote gateway revocation. WordPress logout also clears both. Yoursafe proof validation is local and does not check later provider revocation until another sign-in or cookie expiry; no new online-revocation guarantee is implied.

This focused branch excludes the broader gallery/CDN and full-player entitlement rewrite. Keep the existing provider credentials, callback registration, payment settings and media delivery unchanged during rollout. Production gateway configuration is still required before live activation; sandbox results must never grant live visitor access.
