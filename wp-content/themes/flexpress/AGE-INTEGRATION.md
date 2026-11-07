# Optional Exclusv age gateway adapter

Candidate 1.0.43 implements an opt-in gateway adapter. It is disabled by default and has not been installed on a live site. It protects WordPress HTML, REST and AJAX when configured, but **direct uploads, thumbnails, galleries, CDN origins and edge caches still require enforcement**. Do not activate it as a complete content-protection solution until those paths and production release gates are verified.

## Installation contract

Requires PHP 8.2+, native Sodium, WordPress 6.2+, InnoDB and a single-site installation at a root origin. The legacy Yoursafe server gate must be disabled. Production WordPress rejects sandbox assertions. Loopback HTTP is allowed only for an explicitly configured local sandbox fixture; production requires HTTPS.

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
| `ingress_secret` | Unique 32 random bytes encoded base64url without padding (43 characters) |
| `storage_keys` | Map of short key versions to unique raw 32-byte encryption keys |
| `active_storage_key` | Version used for new records |

Read secret values from the host's protected secret mechanism inside that private file. Never put them in theme settings, source control, command arguments or the public document root. Configure `FLEXPRESS_EXCLUSV_AGE_CONFIG` to its absolute path in private WordPress configuration. Keep `FLEXPRESS_EXCLUSV_AGE_ENABLED` false during installation.

Register exactly `site_origin + /_age/callback`; a real registration requires the gateway's separate release process. Run `wp flexpress-age install` with the theme loaded to create the two private age tables and schedule hourly bounded cleanup. Inspect the schema and scheduler before enabling `FLEXPRESS_EXCLUSV_AGE_ENABLED`. The CLI registration is available while enforcement is disabled. These commands never enable the gateway or change membership/payment records.

## Ingress and cache requirements

Caddy must strip browser-supplied `X-Exclusv-Origin` and set the private value only for this site's verified ingress. Accept Cloudflare country/visitor headers only from verified Cloudflare peers, and overwrite forwarded chains. Restrict the WordPress backend to the exact proxy. Confirm PHP's actual immediate peer and trusted HTTPS handling before choosing `trusted_peers`; do not rewrite `REMOTE_ADDR` to an untrusted visitor address. Unknown country, wrong host, missing secret, untrusted peer or unavailable issuer deny access.

Keep all HTML, API, redirects and age routes private/no-store at Apache, Caddy and Cloudflare. The theme now sets private/no-store before rendering and on standalone templates, and no longer advertises public HTML caching or synthetic cache HIT/ETag headers. The coming-soon template is covered by the rendered HTTP regression. Exclude protected uploads/images from the portable public-static cache exception. Apply the reviewed CDN rules separately. A theme upload cannot secure a public media origin or invalidate existing cached copies.

## Request behavior

- `GET /_age/`: neutral page with no content images; fresh CSRF cookie or active-session controls.
- `POST /_age/start`: exact Origin plus double-submit CSRF, atomic rate limit, PKCE/state/nonce and pinned issuer redirect.
- `GET /_age/callback`: one-use encrypted handoff, verified ES256 claims and online eligibility; rotates the local session and returns only to a safe same-site path. Query strings are not preserved in return destinations.
- `POST /_age/logout`: exact Origin and session CSRF; local cookie/record removal before attempted remote revocation. An issuer outage cannot retain local access; failed remote revocation may remain until its bounded expiry.
- `GET /_age/privacy` and `/_age/help`: neutral public guidance and configured operator contact.

Cookies are `__Host-` prefixed, Secure, HttpOnly, host-only, path `/`, SameSite=Lax. The callback uses no-referrer. Entry forms use same-origin referrer policy so native POST Origin checks work, and CSP allows form redirects only to self and the exact issuer. The obsolete self-declaration popup is omitted when gateway enforcement is enabled; localStorage never grants gateway access.

Every protected authorization uses current gateway introspection. There is no positive authorization cache or refresh token. Failure denies access. Age access never creates a WordPress login or membership entitlement. Founder paid-access bypass does not bypass age checks.

Native admin/login and WP-CLI/cron retain their own access controls. Only the exact signed Flowguard/PPV payment AJAX actions and Flowguard REST callback bypass the age guard; their existing signature/method checks remain. Other AJAX and all other REST routes require age access, including alternate REST query routing. Admin-post is protected. Third-party payment callbacks need explicit review, not blanket API exemptions.

## Storage and operations

`Records` uses a dedicated wpdb connection so payment rollbacks cannot resurrect handoffs. XChaCha20-Poly1305 binds ciphertext to table, purpose, handle hash, expiry and versioned key. PKCE verifiers and access tokens stay encrypted. An atomic claim followed by deletion permits one callback consumer; crash/delete failure leaves the flow unusable. Rate-limit buckets are keyed hashes of trusted visitor IP and purpose, with bounded windows, never raw IPs.

Handoffs last at most ten minutes. Local sessions are capped by assurance, gateway session and actual access-token expiry, maximum one hour; they do not slide. Expired records cannot authorize even before cleanup.

Hourly `flexpress_exclusv_age_cleanup` removes at most 500 expired records and 500 rate buckets per run. Monitor the `flexpress_exclusv_age_cleanup_failed` option and table backlog. Run `wp flexpress-age purge` in additional bounded batches where traffic exceeds hourly cleanup capacity. A host cron runner, alerting and operational capacity review are required before production; installing the hook alone is not proof of reliable scheduling.

Rotate storage keys with overlap: new records use the active version; retain previous keys through maximum record lifetime and cleanup. Removing a key early invalidates those sessions. Keep private backups and keys outside web roots. Restoring a backup must deliberately invalidate prior browser sessions. Never restore a customer database casually over new purchases.

## Media behavior and remaining work

The preview signing endpoint requires POST, nonce, a published/password-free episode ID and its exact preview video. Preview/full aliases also require normal paid/release authorization; banned ordinary customers cannot reuse purchases. When gateway enforcement is enabled, signing requires a fresh age check and expiry is the earlier of five minutes and the age-session deadline. The player has no unsigned fallback.

The same signer now serves episode full/trailer/preview, Extras full/preview, homepage preview and the explicitly configured coming-soon marketing clip. Content players bind to a published, password-free post and exact field. Hidden episodes require a WordPress login. Full videos and preview/trailer aliases require current membership/PPV/release eligibility; ordinary banned users cannot use retained purchases, and founder paid bypass never bypasses age. Missing/invalid signing settings or failed age checks produce no iframe URL. The legacy video-URL helper also requires current-post content binding. Player options cannot replace signing credentials.

The coming-soon setting explicitly publishes its configured marketing clip; only select a dedicated clip suitable for that page. This setting does not infer membership restrictions from other posts. Verify configuration during acceptance.

These are **embed issuance** controls. They do not prove that Bunny expires playback segments when the iframe token expires. Thumbnails, gallery images, direct uploads, direct HLS/MP4 and CDN origins still require separate signing/authorization, bounded playback/renewal, unsigned-origin denial and cache controls. Treat those as release blockers. Bunny documents embed token authentication and CDN pull-zone protection as separate layers: [Stream security](https://bunny.net/docs/stream/security), [embed signing](https://bunny.net/docs/stream/token-authentication).

## Verification evidence

Disposable fixtures only; no live customers, provider charges or outbound notifications:

- Actual WordPress/ACF/theme + MySQL + Node issuer: 171 request assertions, 14 configuration assertions and production rejection of sandbox configuration.
- Storage: 61 assertions including competing callback/rate workers, ciphertext tampering, key rotation, cleanup and payment-transaction isolation.
- Shared media authorization and preview endpoint: 82 assertions including age expiry/denial, ordinary customer entitlements, aliases, hidden/password-protected content, malformed configuration and founder behavior; player: six scenarios.
- Browser: native entry → gateway consent → WordPress, with session-management/logout checked separately in the gateway repository's fixture record.

Source tests are `tests/run-age-storage.py`, `tests/test-media-authorization.php` and `tests/test-preview-player.cjs`. The gateway repository owns `tests/wordpress/` and the private runtime builder. Existing payment/admin/package regressions remain required. The full fixture uses a synthetic database, intercepted mail/WordPress HTTP and an internal-only Docker network; no live content or secrets are copied.

Before live activation: finish media/CDN/cache enforcement, exact HTTPS ingress validation, ordinary-customer scenarios, cleanup/monitoring and restore drills, provider/privacy/security acceptance and the applicable production approval. Roll back by restoring the reviewed prior theme/runtime/config and retaining payment data. Disabling the flag removes this protection; it is not a safe fallback for a site required to enforce age access.
