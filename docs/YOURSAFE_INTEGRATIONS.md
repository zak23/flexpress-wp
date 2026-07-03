# Yoursafe integration reference

Overview of everything Yoursafe offers via [integrations.yoursafe.com](https://integrations.yoursafe.com/),
and how each product relates to FlexPress. For the age-verification
implementation itself, see [YOURSAFE_ID_AGE_VERIFICATION.md](YOURSAFE_ID_AGE_VERIFICATION.md).

Yoursafe's documentation is split into three pillars:

1. **Identity Services** — Yoursafe ID (OIDC login with verified identity claims)
2. **Payment Processing** — Flowguard, FlexPay, Yoursafe Direct, merchant APIs
3. **Accounts & Payouts** — mass payment / bulk disbursement workflows

---

## 1. Identity Services (Yoursafe ID)

OAuth 2.0 / OpenID Connect identity provider at `accounts.yoursafe.com`.
Users hold a Yoursafe YOU account and complete full KYC once (ID document +
biometric check via the Yoursafe app, reviewed by their compliance team).
Relying sites receive verified claims without learning the user's identity.

**FlexPress status: integrated** for age verification (working since July 2026).

### Live endpoints (from OIDC discovery)

| Purpose | Endpoint |
|---|---|
| Discovery | `GET /.well-known/openid-configuration` |
| Authorize | `GET /oauth2/authorize` |
| Token exchange | `POST /oauth2/token` (docs show `PUT`, but only `POST` works — see below) |
| Userinfo | `GET /userinfo` |
| JWKS (signing keys) | `GET /oauth2/jwks` |
| Device authorization | `GET /oauth2/device_authorization` |
| Token introspection | `POST /oauth2/introspect` |
| Token revocation | `POST /oauth2/revoke` |
| End session (logout) | `/connect/logout` |

Auth methods: `client_secret_post` and `client_secret_basic`. Grant:
`authorization_code` only. ID tokens: RS256. PKCE: S256.

**Gotcha (July 2026):** the integration guide's example shows
`PUT https://accounts.yoursafe.com/oauth2/token`, but the live server rejects
`PUT` at the web-server level with a bare Apache 403 (no OAuth error body).
Only the standard OAuth 2.0 `POST` is accepted.

**Gotcha:** `idverifieddate` marks Yoursafe's one-time identity verification
and is commonly years old. Do not treat it as a freshness signal; the
`eighteenplus` claim is recomputed at every login.

### Scopes

| Scope | What it unlocks | Notes |
|---|---|---|
| `openid` | Required for OIDC | Always include |
| `default` | AccountID/AliasToken, `accountstatus`, `eighteenplus`, `idverifieddate`, `countrycode`, sanction/PEP screening status | What FlexPress uses (`openid default`) |
| `platform` | Adds alias and IBAN serials | For PCI-compliant merchants |
| `profile` | Personal claims: real name, date of birth, etc. | Requires extra Yoursafe onboarding |

Full claim list: [Claims Glossary](https://www.yoursafe.com/nl/yoursafeid_scopesandclaims.html).

### Alias Token queries (continuous re-verification, no API needed)

Every verified user has an AliasToken — a fully qualified domain name like
`abc123.yoursafe.id`. It can be queried publicly at any time:

- **HTTPS**: visit `https://abc123.yoursafe.id` (JSON supported) for
  non-personal status: 18+, account standing, ID-verified date, last
  sanction/PEP screening dates, country.
- **DNS**: the same data is available via DNS TXT queries.

FlexPress already stores the alias in user meta
(`flexpress_age_verified_reference`), so periodic re-validation of verified
members is possible without asking them to log in to Yoursafe again.

Docs: [Alias Token Queries](https://integrations.yoursafe.com/en/id-aliastoken).

### Other identity capabilities not used by FlexPress

- **Device authorization flow** — login for devices without a browser
  (TV/console style).
- **Token introspection / revocation** — server-side session management for
  long-lived access tokens (FlexPress discards tokens immediately, so unused).

---

## 2. Payment Processing

Merchant payment products. FlexPress already uses this pillar via Flowguard.

| Product | What it is | FlexPress status |
|---|---|---|
| **Flowguard SDK / API** | Hosted checkout and subscription billing (`flowguard.yoursafe.com/api/merchant`) | **Integrated** — memberships, PPV unlocks, webhooks (see `includes/class-flexpress-flowguard-api.php`) |
| **FlexPay** | Older payment API with ready-made plugins | Not used (superseded by Flowguard) |
| **Yoursafe Direct** | Direct account-to-account payments between Yoursafe accounts | Not used |
| **Remote User Management** | Programmatically create/manage Yoursafe accounts for your users (e.g. onboarding performers for payout) | Not used — potentially useful for model onboarding |
| **Management APIs** | Merchant-side transaction management, refunds, reporting | Not used |
| **Control Center** | Web dashboard for merchant configuration, test vs live setup | Used for Flowguard configuration |

Note: Flowguard payment approval does **not** imply Yoursafe ID age
verification — they are separate systems with separate user bases.

---

## 3. Accounts & Payouts

Bulk disbursement workflows for paying many recipients:

- **Mass Payments Upload** — file-based batch payouts: upload a validated
  payment file, Yoursafe processes the batch.
- **Mass Payments API** — API-first version of the same for fully automated
  payout runs.

**FlexPress relevance:** the affiliate/payout system already collects
Yoursafe IBANs as the free payout method (see `includes/payout-display-helpers.php`
and the affiliate settings). Mass Payments could automate affiliate and model
payouts instead of manual processing. Not yet integrated.

---

## Context: why Yoursafe

Yoursafe B.V. is a Dutch payment institution focused on the adult industry.
Since October 2021, card networks require MCC 5967 merchants to identify all
people appearing in content; Yoursafe ID is their privacy-preserving answer
(merchants get an AliasToken, not personal data). Their partners include
high-risk processors like CardBilling and GayCharge.

Practical implication for age assurance: Yoursafe ID only verifies existing
Yoursafe YOU account holders (mostly industry performers/creators/payees).
It is not a general-visitor age-assurance product (no facial age estimation
or one-off checks), so a second provider is still needed for ordinary
Australian visitors.

## Links

- Documentation portal: <https://integrations.yoursafe.com/>
- ID integration guide: <https://integrations.yoursafe.com/en/id-integration>
- Scopes and claims: <https://www.yoursafe.com/nl/yoursafeid_scopesandclaims.html>
- Yoursafe ID product page: <https://www.yoursafe.com/nl/yoursafeid.html>
- Alias Token explainer: <https://www.yoursafe.com/en/transactionquestions.html>
