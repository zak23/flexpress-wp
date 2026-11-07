# FlexPress

FlexPress is a portable WordPress membership theme for launching independent content sites. It provides episode and model pages, Extras, paid memberships, per-item purchases, affiliate tools and Bunny media delivery. **Each site keeps its own identity, customer data, provider accounts and hosting configuration.**

Candidate **1.0.47** binds card-preview signing to published content and removes unsigned-player fallback. It also adds an opt-in gateway adapter with encrypted sessions, consent/callback/logout, per-request WordPress guards and trusted ingress. It is disabled by default; private runtime installation and complete media/CDN enforcement remain separate requirements. It retains the portable hosting guides and prior payment/admin-login fixes. Activating the theme does not install or change Caddy or Cloudflare.

## Start here

| Task | Read |
| --- | --- |
| Agent taking over this theme | [AGENTS.md](AGENTS.md) |
| New domain, origin, Caddy or Cloudflare | [HOSTING.md](HOSTING.md) |
| Install, upgrade, migrate and accept a release | [UPLOAD-CHECKLIST.md](UPLOAD-CHECKLIST.md) |
| Render a site-specific Caddy configuration | [hosting/Caddyfile.example](hosting/Caddyfile.example) |
| Inspect existing payment data without changing it | [tools/payment-audit.php](tools/payment-audit.php) through WP-CLI only |
| Age storage, preview signing and remaining integration | [AGE-INTEGRATION.md](AGE-INTEGRATION.md) |

Use these documents from the extracted theme even when the original Git repository is unavailable. References to repository tests or build scripts are optional tooling available in the source repository, not commands guaranteed to exist in an installed ZIP.

## Requirements and boundaries

- WordPress with this theme installed as `wp-content/themes/flexpress`, PHP and MySQL/MariaDB supported by the destination host, and Advanced Custom Fields for the theme's content fields. Review destination plugin requirements before activation.
- The recent integration checks used PHP 8.3 and WordPress 7.1.3. The older minimums in the theme header are compatibility declarations, not evidence that every feature was tested on those versions. Verify the destination runtime before launch.
- Payment transactions, webhook receipts, user metadata and options need InnoDB for transaction rollback. Run the audit below before accepting payments.
- HTTPS, working email delivery and a reliable WordPress cron runner. Redis is optional and off unless `WP_REDIS_ENABLED` is enabled in WordPress configuration.
- Provider accounts for features being used: Flowguard for payments; Bunny CDN/Stream for media; optionally Turnstile, Plunk, Discord and Yoursafe ID. Enable only configured integrations.

Theme files do not contain the WordPress database, installed plugins, uploads, settings, secrets or server configuration. Updating the theme preserves those independent resources. Switching from another theme may require a separate content/settings migration.

## Launch a new site

1. Identify the destination hostname, WordPress installation and hosting operator. Complete the site inventory in [HOSTING.md](HOSTING.md); do not reuse an old site's addresses or credentials.
2. Back up and verify a restore route for the destination database and existing theme. Install required plugins and confirm runtime compatibility.
3. Install the ZIP in Appearance → Themes, or use the host's deployment process for a read-only theme directory. Activate FlexPress and review permalinks, menu locations, page templates and content fields.
4. Configure branding, logos, colours, homepage sections, contact addresses and legal pages in the destination's FlexPress settings. Review any auto-created forms and their recipient addresses.
5. Configure the destination's Bunny zones, media identifiers and provider credentials. Configure Flowguard's shop, pricing plans, currency and callback/return URLs for this hostname.
6. Complete Caddy/Cloudflare setup, email and cron checks, then run the acceptance checklist with a normal customer account. Founder accounts bypass access checks and cannot establish that paid-content restrictions work.

## Payment behaviour to preserve

- Flowguard's API endpoint is `https://flowguard.yoursafe.com/api/merchant`. Test versus live behaviour is configured by the provider, not a theme environment toggle.
- The callback is `https://YOUR_DOMAIN/wp-admin/admin-ajax.php?action=flowguard_webhook`. It requires POST and a valid signed payload for the configured shop. A GET returning 405 is expected and only proves route reachability.
- Browser return URLs never authenticate a customer or grant access. A receipt waits for a verified callback belonging to the signed-in customer.
- Recurring and fixed-duration memberships are supported. Lifetime checkout is currently rejected before contacting the provider. Recurring `extend` notices do not advance paid access.
- Episode and Extras PPV must leave membership billing metadata intact. Prices are calculated by the server.
- Committed notifications and refund-related cancellation work run through WP-Cron with retries. Delivery is at-least-once, so a lost acknowledgement can produce a duplicate external notification.
- Provider-issued test cards are shop-specific. Confirm explicit authorization for the destination before any checkout test. If uncertain, prepare synthetic tests without charging.

Read-only audit, run from the destination WordPress root:

```sh
wp eval-file wp-content/themes/flexpress/tools/payment-audit.php --skip-plugins --skip-themes
```

Review flags against provider and migration records. Missing local evidence does not prove an entitlement is invalid. Do not automatically delete users, purchases or membership access. Keep reports private.

## Hosting and cache contract

The intended request path is **visitor → Cloudflare proxy → Caddy → private WordPress origin**. Bunny serves configured media independently. Use equivalent controls if the destination host uses another web server; do not install Caddy over an existing service blindly.

Dynamic HTML, authentication, customer/account pages, API/AJAX responses and payment callbacks must stay private/no-store. Only verified public static assets may be cached. Cloudflare must respect this policy; a theme upload cannot override an edge rule that caches everything. Keep asset query strings in cache keys so version changes invalidate assets. The service worker must remain on the same origin and must not cache HTML.

[HOSTING.md](HOSTING.md) contains the complete configuration, origin protection, secret handling, verification and rollback procedure. Its sample is for the standard Cloudflare-to-Caddy topology. Cloudflare Tunnel, an extra load balancer or a managed host needs a reviewed adaptation of the trust chain.

## Administration and optional features

- Use `/wp-admin/` for WordPress administration; it redirects to native `/wp-login.php` when signed out. Customer login is `/login/`. Never infer the administrator email from a previous site.
- Founder permissions use `manage_flexpress_founder_settings`; preserve founder safeguards and access bypass. Use separate ordinary accounts for customer tests.
- The AU server-side age gate is parked by default. `FLEXPRESS_AGE_GATE_ENABLED` is an explicit opt-in in WordPress configuration; enabling it requires a configured Yoursafe flow and a complete test of geolocation, callbacks and exemptions. The soft age-confirmation modal is separate.
- Preserve existing Contact Form 7 recipients when adjusting branding or settings. Check actual delivery through the destination's mail provider.
- Keep paid media protected with the configured Bunny signing/access controls. Changing theme appearance does not secure a public media origin.

## Code map

| Path | Responsibility |
| --- | --- |
| `functions.php`, `style.css` | Bootstrap and matching release version |
| `includes/admin/` | FlexPress settings screens |
| `includes/flowguard-*.php` | Checkout, verification, membership/PPV and queued side effects |
| `includes/bunnycdn.php` | Media URL/optimizer helpers |
| `includes/geolocation.php`, `includes/age-gate.php` | Country classification and optional age gate |
| `page-templates/`, `template-parts/` | Pages and reusable components |
| `assets/` | CSS, JS, fonts and runtime vendor assets |
| `tools/payment-audit.php` | Read-only CLI audit |
| `hosting/` | Deployment examples; never auto-executed by WordPress |

All reusable application features belong in the theme. Keep actual server configs, secret files, backups and deployment-specific inventories outside the public web root and outside release archives.

## Development and release

Read [AGENTS.md](AGENTS.md) before editing. In the source repository, run the relevant tests in `tests/`, commit changes in reviewable steps and use `./build-theme-zip.sh`. The builder uses committed theme files, lints shipped PHP, creates combined CSS and records hashes in `release-manifest.json`. It excludes debug/maintenance scripts, backups, development dependencies and untracked files.

Do not hand-edit the generated manifest. Keep `Version` in `style.css` and `FLEXPRESS_VERSION` in `functions.php` aligned when making a new release. Verify the exact ZIP contains this README, AGENTS.md, HOSTING.md, UPLOAD-CHECKLIST.md and the Caddy example. Use [UPLOAD-CHECKLIST.md](UPLOAD-CHECKLIST.md) for destination acceptance and rollback.

Focused visitor rollout: retain **Sign in with Yoursafe** as an alternative to **Verify with Didit**. Both methods return to the requested site page. Existing media/payment behaviour remains outside this change; the broad CDN/gallery candidate is separate. See AGE-INTEGRATION.md for proof lifetimes and verification limits.
