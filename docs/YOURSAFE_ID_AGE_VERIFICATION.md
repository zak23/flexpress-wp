# Yoursafe ID age verification

FlexPress supports Yoursafe ID as an optional age-verification provider for
existing Yoursafe YOU account holders. It is disabled until credentials are
configured and does not replace the temporary DOB form automatically.

## Setup

1. In the Yoursafe Business portal, create a Yoursafe ID Client Configuration.
2. Register the callback URL shown under **FlexPress > Yoursafe ID** exactly.
3. Use the `openid default` scope unless Yoursafe instructs otherwise.
4. Save the Client ID and Client Secret in FlexPress, then enable the provider.
5. Test with an active, fully identified Yoursafe YOU account before production.

## Flow

1. FlexPress creates a one-time state, nonce and PKCE verifier and stores them
   for ten minutes.
2. The visitor authenticates and consents at `accounts.yoursafe.com`.
3. FlexPress exchanges the one-time code server-side and fetches claims from
   Yoursafe's authenticated userinfo endpoint.
4. Access is granted only when the account is active, `eighteenplus` is the
   boolean `true`, the identity verification is recent, and the Alias Token has
   a valid `*.yoursafe.id` form.
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
