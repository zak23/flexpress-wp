# Yoursafe ID age verification

Yoursafe ID is retained as an alternative on the current Exclusv.Ai `/_age/`
panel. Its registered `/age-verification/yoursafe/start/` and callback URLs
remain unchanged. Errors now return to `/_age/`; the old standalone page is
an uncached redirect and its AU-only enforcement has been retired.

The configured `FLEXPRESS_EXCLUSV_AGE_ENABLED` adapter controls visitor access.
The old `FLEXPRESS_AGE_GATE_ENABLED` switch remains available for legacy
provider configuration but no longer enables a visitor gate. An ordinary
membership login alone is not valid age proof. See
[CLOUDFLARE_GEOLOCATION.md](CLOUDFLARE_GEOLOCATION.md) for trusted country routing.

## Setup

1. In the Yoursafe Business portal, create a Yoursafe ID Client Configuration.
2. Register the callback URL shown under **FlexPress > Yoursafe ID** exactly.
3. Use the `openid default` scope unless Yoursafe instructs otherwise.
4. Save the Client ID and Client Secret in FlexPress, then enable the provider.
5. Configure the current Exclusv.Ai adapter and test the retained Yoursafe flow
   with an active, fully identified Yoursafe YOU account before general enforcement.

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
