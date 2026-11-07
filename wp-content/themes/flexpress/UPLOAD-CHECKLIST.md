# FlexPress 1.0.37: Zakspov → Dolls Down Under

Zakspov is the development/proving site. The ZIP contains theme code and assets only. Uploading it does not copy Zakspov users, purchases, media, WordPress options, provider credentials, database records, plugins, or server configuration.

## Before upload

1. Back up Dolls Down Under's database and current theme; keep the backup outside its public web directory. Confirm a restore route with the host. The Zakspov database backup was restored into an isolated MySQL instance successfully; that does not verify Dolls Down Under's backups.
2. Confirm the destination is updating the **flexpress** theme directory. If Dolls Down Under currently uses a differently named theme, treat this as a theme switch: verify menu locations, widgets, templates, customizer settings, and existing payment data separately.
3. Record the destination's existing FlexPress payment plans, currency, Flowguard shop/signature settings, Bunny CDN/Stream settings, branding, and notification settings. Preserve destination credentials. Theme upload cannot migrate settings from another theme.
4. Confirm required plugins are installed, particularly Advanced Custom Fields for content fields. This release was exercised with PHP 8.3 and WordPress on the Zakspov clone. Check the destination's compatibility before activating.
5. Run the read-only audit after installing the files, before accepting payments:
   ```sh
   wp eval-file wp-content/themes/flexpress/tools/payment-audit.php --skip-plugins --skip-themes
   ```
   Payment transactions, webhook receipts, user metadata and options must use InnoDB for rollback guarantees. Review flags with payment records; missing local evidence is not proof of unpaid access. Never publish the report or delete flagged access automatically.
6. Verify Flowguard's destination callback is the destination site's `/wp-admin/admin-ajax.php?action=flowguard_webhook`. Browser success/decline URLs must also use the destination host. Confirm those settings in Flowguard ControlCenter; test/live status is controlled there, not by a theme checkbox.

## Upload and verify

1. Upload `flexpress-1.0.37.zip` through Appearance → Themes and replace the installed FlexPress version. Use the host's deployment route if the theme directory is read-only. Keep the previous ZIP for rollback.
2. Check homepage, episode, Extras, login, join, member account and payment pages on desktop and mobile. Verify logos, menus, content fields, public previews, locked content and destination links. Founders bypass access checks; use a normal customer test account for access testing.
3. Confirm WordPress cron runs. The hooks `flexpress_flowguard_deliver_effects` and `flexpress_flowguard_effects_sweep` deliver/retry committed notifications and refund-related cancellations. `flowguard_pending_effects` user metadata holds outstanding jobs; inspect persistent failures locally.
4. Confirm public HTML, login/account/payment pages and redirects receive `Cache-Control: private, no-store`. The Caddy fix on Zakspov is **not inside this theme ZIP**. Configure the destination host/CDN separately, and clear its old page cache when deploying. Never cache personalized HTML in a page cache or service worker.
5. Check delivery of a legitimate notification after a confirmed test event. Delivery is at-least-once: a lost remote response or progress write can still produce a repeated external notification. Cancellation work preserves bans and does not cancel a later replacement subscription.

## Payment acceptance matrix — no charges run by this release check

Until ControlCenter test mode is confirmed, stop before submitting a card or invoking live payment/refund/cancellation operations. The automated checks use fake transport responses, including those run against the restored database.

| Flow | Expected result to verify in confirmed provider test mode |
| --- | --- |
| New subscription | Pending return until verified callback; correct amount/currency; membership activates once. |
| Renewal | Deadline advances once; replay does not duplicate activity/notifications. |
| Cancel / uncancel | Access continues to paid-through date; deliberate uncancel works; replayed approval does not reverse cancellation. |
| Expiry | Normal customer loses member access at the deadline; bans remain bans. |
| Episode and Extras PPV | Server-calculated price; only the purchased item unlocks; subscription ID/price/date stay intact. |
| Refund / chargeback | Existing policy bans the account and revokes the refunded item; provider cancellation runs after commit; ban stays in place. |
| Notification outage | Payment remains committed; failed delivery stays queued and retries. |
| Signed-out return | Login required; URL user IDs never establish authentication or grant access. |

## What was verified on Zakspov

- Isolated payment/signature/price/access regressions and notification adapters, with no provider traffic.
- A private database backup restored to a disposable MySQL database; real WordPress transactions, injected receipt-write failure/rollback, replay, PPV and refund flows tested there with all outgoing HTTP/email intercepted.
- Caddy's actual installed version tested against 17 response scenarios; public dynamic-cache behavior checked separately.
- ZIP contents are selected from tracked files, omit development/backup files, include runtime vendor assets and generated combined CSS, and carry a file-hash manifest. The builder lints all shipped PHP and verifies ZIP integrity without changing source versions.

## Host configuration stays separate

Zakspov's Docker/Apache changes are host-specific: fixed LAN addresses, proxy trust, read-only mounts and named volumes. Do not transplant them into Dolls Down Under or package them with the theme. Existing uncommitted host files were reviewed separately and retained as-is. In particular, the PHP security config is copied over an earlier generated config in the Dockerfile; the final copied file must contain the intended setting. Read-only theme mounts intentionally prevent in-dashboard file replacement.
