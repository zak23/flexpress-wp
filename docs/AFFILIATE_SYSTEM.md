# FlexPress Affiliate System

Toggleable affiliate module integrated with FlowGuard payments and FlexPress
membership. Enable/disable from **FlexPress → Affiliate System**. Gate all
frontend and commission logic with `flexpress_is_affiliate_system_enabled()`.

Frontend surfaces are **PHP page templates** under `page-templates/affiliate-*.php`
plus theme JS/CSS (`affiliate-dashboard.js`, `affiliate-signup.js`, admin JS).
There is no separate React SPA.

## Data model (tables)

- `wp_flexpress_affiliates`
- `wp_flexpress_affiliate_promo_codes`
- `wp_flexpress_affiliate_clicks`
- `wp_flexpress_affiliate_transactions`
- `wp_flexpress_affiliate_payouts`

## Tracking

- Referral parameters: `?aff={affiliate_code}` (preferred); legacy `?ref=` supported
- Optional `?promo={code}`
- 30-day cookie `flexpress_affiliate_tracking` stores `{ affiliate_id, promo_code_id, click_id, timestamp }`
- Click rows recorded in `wp_flexpress_affiliate_clicks`

## Attribution

- On payment, promo → affiliate mapping takes precedence over cookie attribution
- Fallback to cookie-based affiliate

## FlowGuard webhook

- Route: `POST /wp-json/flexpress/v1/flowguard/webhook` (mirrors existing AJAX handler)
- Events: approved, rebill, purchase, cancel, expiry, chargeback/credit (refund)
- Auto-approve commissions after 14 days via daily WP-Cron

## REST API (prefix: `/wp-json/flexpress/v1`)

Public:

- `POST /auth/login` → `{ token, user }`

Affiliate (JWT Bearer):

- `GET /me` → profile, stats
- `GET /me/visits`
- `GET /me/commissions`
- `GET /me/payouts`
- `POST /me/payouts/request`
- `GET /me/promo-codes`
- `PATCH /me/settings` `{ payout_method, payout_details }`

Admin (`manage_options` / founder feature caps):

- `GET /admin/affiliates`
- `GET /admin/transactions`
- `GET /admin/payouts`
- `POST /admin/payouts/{id}/{action}` where action ∈ `approve|deny|complete`
- `GET /admin/payouts/export?method={paypal|crypto|aus_bank_transfer|yoursafe|ach|swift}` → CSV string

## Security

- JWT HS256 using `AUTH_KEY` (15 min TTL)
- Payout details encrypted at rest with AES-256-CBC (WP salts)
- Sanitize all inputs; escape outputs in templates

## Roles

- `affiliate_user`, `affiliate_manager` created on theme activation

## Frontend pages

- Signup / application / applications list / dashboard / terms:
  `page-templates/affiliate-signup.php`, `affiliate-application.php`,
  `affiliate-applications.php`, `affiliate-dashboard.php`, `affiliate-terms.php`
- Admin management: **FlexPress → Affiliate System** (`class-flexpress-affiliate-settings.php`)
- Ignore leftover `-broken` / `-backup` / `-clean` affiliate settings copies; do not load them
