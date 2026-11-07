# FlexPress 1.0.43 candidate installation and acceptance

Use this checklist for a fresh site or an update. The theme ZIP contains reusable code, assets and setup documentation. It does not migrate users, purchases, media, WordPress options, plugins, credentials or hosting configuration.

## Prepare

- Read [README.md](README.md), [AGENTS.md](AGENTS.md) and [HOSTING.md](HOSTING.md). Complete a private destination inventory; verify the exact site and installation being changed.
- Back up the destination database and current theme outside the web root. Establish how to restore them. A successful restore on another site is not evidence for this site's backup.
- Confirm whether this updates the existing `flexpress` directory or switches from another theme. For a switch, plan content fields, templates, menus, widgets and payment-data migration separately.
- Record and preserve destination branding, plans, currency, provider/media settings and form recipient addresses. Never overwrite destination secrets with a development site's values.
- Verify PHP/WordPress compatibility and required plugins, particularly Advanced Custom Fields. Confirm write/deployment permissions; read-only theme mounts need the host's deployment process.
- Inspect the archive: matching version declarations, release manifest, runtime vendor assets, generated combined CSS, README, AGENTS.md, HOSTING.md, this checklist and `hosting/Caddyfile.example`. No secrets, backups, live configs or debug scripts should be present.

## Install and configure

1. Install the reviewed `flexpress-1.0.43.zip` candidate through Appearance → Themes, or the host's deployment mechanism. Retain the prior release for rollback. Activate/check FlexPress, menu assignments, templates, permalinks and content fields. This candidate does not enable age enforcement; read [AGE-INTEGRATION.md](AGE-INTEGRATION.md).
2. Follow HOSTING.md separately for DNS, Full (strict), origin restrictions, trusted headers, caching, logs and certificate management. The included Caddy example is not activated by WordPress.
3. Configure the destination's Flowguard shop, plans and currency. Verify its provider-side callback is `https://YOUR_DOMAIN/wp-admin/admin-ajax.php?action=flowguard_webhook` and browser return URLs use the same intended site. Test/live behaviour is controlled by the provider.
4. Configure Bunny media zones/signing and optional integrations. Confirm legal/contact content and form recipients belong to this site.
5. Run the read-only payment audit from the WordPress root:
   ```sh
   wp eval-file wp-content/themes/flexpress/tools/payment-audit.php --skip-plugins --skip-themes
   ```
   Review flagged historical records with provider/migration evidence. Do not remove access automatically. Verify InnoDB for transactional tables and keep the report private.
6. Verify cron. `flexpress_flowguard_deliver_effects` and `flexpress_flowguard_effects_sweep` process committed notification/cancellation jobs; `flowguard_pending_effects` user metadata contains outstanding work. Delivery is at-least-once, so investigate persistent failures without blindly duplicating external actions.

## Verify the site

- Homepage, episode, Extras, models, login, join, account and payment pages work on desktop/mobile. Branding, links, media and forms belong to the destination.
- Native `/wp-admin/` login works with its redirect destination retained. Test ordinary customer access separately: founders bypass restrictions.
- Public previews work; locked content remains locked; signed media is delivered correctly. Test episode, Extras, homepage and coming-soon players with missing keys and denied/expired age access: no iframe URL should be issued. Test ordinary membership/PPV customers and preview/full aliases separately. A coming-soon clip is explicitly published by its setting; select a dedicated marketing clip. Service worker registration is same-origin and never caches HTML.
- Deploy the preview PHP, templates and versioned JavaScript together. Preview-token requests must include the published episode ID; arbitrary full-video IDs are denied, and failed signing must not cause an unsigned media request. This endpoint check does not establish direct CDN or age protection.
- Public HTTPS goes through Cloudflare; origin and backend bypasses are denied; verified visitor/geolocation headers survive. Test both address families where available.
- Dynamic HTML/login/account/payment/API responses remain private/no-store. Genuine public assets cache only under the HOSTING.md policy; missing asset paths do not become publicly cached HTML. Repeat edge tests after Development Mode is off and remove affected stale cache entries.
- Callback GET returns application 405, not a challenge page. This is only reachability evidence; it does not prove payment fulfillment.
- Logs are private/redacted, backups are outside the web root, certificate operations and rollback access are documented.
- Test an explicitly authorized notification delivery; synthetic suites must intercept mail and external messages. Review existing form mail settings before changing recipients.

## Provider payment acceptance

Use only a provider-issued test instrument explicitly authorized for this destination shop, or a confirmed provider test environment. Test-card authorization does not transfer between websites. Never use an ordinary card for release testing. If authorization is unknown, prepare non-charging tests and leave provider acceptance pending.

| Flow | Expected result |
| --- | --- |
| New recurring membership | Wait for verified callback; correct amount/currency; access activates once |
| Fixed-duration membership | Provider expiry; no stale recurring rebill date; correct receipt |
| Renewal/replay | Paid-through deadline and activity advance once |
| Cancel/uncancel | Access through paid-through date; deliberate uncancel works; replay cannot reverse cancellation |
| Expiry | Ordinary customer loses access at deadline; existing bans remain |
| Episode/Extras PPV | Only purchased item unlocks; subscription metadata remains intact |
| Refund/chargeback | Existing ban/access policy holds; deferred cancellation cannot cancel a later subscription |
| Notification outage | Payment stays committed; queued delivery retries |
| Signed-out browser return | Login required; URL values never authenticate or grant access |

Lifetime checkout is unsupported and must reject before payment. Record provider events that could not be exercised; synthetic coverage is not proof of real settlement, rebilling or refunds.

## Rollback and handoff

If an update breaks acceptance, restore the previous theme through the host's deployment route and recheck core pages. Restore database changes only using a deliberate recovery plan that accounts for new purchases made since the backup; do not overwrite live transactions casually. Hosting rollback is separate: restore the saved config/binary/unit while preserving certificate storage, then verify public access and other shared sites.

Record release/manifest, destination, configuration paths, backup locations, tests and remaining issues outside the public theme. Do not claim a launch is ready while payments, origin protection or cache policy remain unverified. Keep this checklist generic so it remains useful on the next site.
