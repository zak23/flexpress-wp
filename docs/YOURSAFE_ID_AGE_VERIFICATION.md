# Yoursafe ID age verification

**Status (September 2026): AU/Yoursafe server gate parked.** Soft “I am 18”
modal and newsletter modal still run. Yoursafe OIDC routes and AU redirects are
disabled until `FLEXPRESS_AGE_GATE_ENABLED` is set to `true` in `wp-config.php`
(or the `flexpress_age_gate_enabled` filter returns true). Code and the
**FlexPress → Yoursafe ID** admin screen remain for continued development.

FlexPress uses Yoursafe ID to age-verify existing Yoursafe YOU account holders.
It is disabled until credentials are configured; while disabled, new visitors
cannot pass the age gate unless they log in with an already verified account.

When the master switch is on, the gate applies to **AU** geolocation
classifications only (temporary policy). See
[CLOUDFLARE_GEOLOCATION.md](CLOUDFLARE_GEOLOCATION.md) for Cloudflare header
setup and origin binding.

## Resume local work

```php
define( 'FLEXPRESS_AGE_GATE_ENABLED', true );
```

## Setup

1. In the Yoursafe Business portal, create a Yoursafe ID Client Configuration.
2. Register the callback URL shown under **FlexPress > Yoursafe ID** exactly.
3. Use the `openid default` scope unless Yoursafe instructs otherwise.
4. Save the Client ID and Client Secret in FlexPress, then enable the provider.
5. Enable the age gate master switch (above), then test with an active, fully
   identified Yoursafe YOU account before production.

## Flow

1. FlexPress creates a one-time state, nonce and PKCE verifier and stores them
   for ten minutes.
2. The visitor authenticates and consents at `accounts.yoursafe.com`.
3. FlexPress exchanges the one-time code server-side (`POST` to the token
   endpoint; `client_secret_post` then `client_secret_basic` on 401/403) and
   fetches claims from Yoursafe's authenticated userinfo endpoint.
4. Access is granted only when the account is active, `eighteenplus` is the
   boolean `true`, the `idverifieddate` claim is present and not in the future,
   and the Alias Token has a valid `*.yoursafe.id` form. The age of
   `idverifieddate` itself is not limited: Yoursafe verifies identity once
   (possibly years ago) and recomputes `eighteenplus` at every login. The
   "Account Proof Lifetime" setting controls how long the local FlexPress
   proof (cookie and user metadata) remains valid instead.
5. FlexPress sets the signed 90-day device cookie. If the visitor is already
   logged in, the provider, Alias Token, verification time and expiry are also
   saved as WordPress user metadata.

No date of birth, identity document, selfie, access token, refresh token or raw
OIDC response is stored by FlexPress.

## Limitations

- FlowGuard payment approval does not imply Yoursafe ID verification.
- Visitors need a verified Yoursafe YOU account.
- A second provider is still required for visitors unable to use Yoursafe ID.
- Production acceptance under Australia's Age-Restricted Material Codes should
  be confirmed with Yoursafe and Australian legal counsel.
