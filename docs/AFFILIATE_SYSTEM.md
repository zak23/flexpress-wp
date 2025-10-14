# FlexPress Affiliate System

This document describes the v1 Affiliate System integrated with Flowguard and the FlexPress membership system.

## Data Model (tables)

- wp_flexpress_affiliates
- wp_flexpress_affiliate_promo_codes
- wp_flexpress_affiliate_clicks
- wp_flexpress_affiliate_transactions
- wp_flexpress_affiliate_payouts

## Tracking

- Referral parameters: ?aff={affiliate_code} (preferred) and ?ref={legacy} supported
- Optional ?promo={code}
- 30-day cookie: flexpress_affiliate_tracking stores { affiliate_id, promo_code_id, click_id, timestamp }
- Click rows recorded in wp_flexpress_affiliate_clicks

## Attribution

- On payment, promo → affiliate mapping takes precedence over cookie attribution
- Fallback to cookie-based affiliate

## Flowguard Webhook

- Route: POST /wp-json/flexpress/v1/flowguard/webhook (mirrors existing AJAX handler)
- Events: approved, rebill, purchase, cancel, expiry, chargeback/credit (refund)
- Auto-approve commissions after 14 days via daily WP-Cron

## REST API (prefix: /wp-json/flexpress/v1)

Public:

- POST /auth/login → { token, user }

Affiliate (JWT Bearer):

- GET /me → profile, stats
- GET /me/visits
- GET /me/commissions
- GET /me/payouts
- POST /me/payouts/request
- GET /me/promo-codes
- PATCH /me/settings { payout_method, payout_details }

Admin (manage_options):

- GET /admin/affiliates
- GET /admin/transactions
- GET /admin/payouts
- POST /admin/payouts/{id}/{action} where action ∈ approve|deny|complete
- GET /admin/payouts/export?method={paypal|crypto|aus_bank_transfer|yoursafe|ach|swift} → CSV string

## Security

- JWT HS256 using AUTH_KEY (15 min TTL)
- Payout details encrypted at rest with AES-256-CBC (WP salts)
- Sanitize all inputs; escape outputs in templates

## Roles

- affiliate_user, affiliate_manager created on theme activation

## Frontend (Phase 2)

- React SPA for Affiliate Dashboard and Admin under FlexPress → Affiliates
