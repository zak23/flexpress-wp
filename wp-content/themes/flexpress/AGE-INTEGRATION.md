# Age integration preparation

This branch contains the storage foundation for an Exclusv gateway integration and a narrower card-preview signing endpoint. **It does not enable age enforcement or connect a site to the gateway.** The existing Yoursafe gate remains separate and parked. Do not deploy this candidate as a complete age-control solution.

## Implemented storage

`includes/exclusv-age/` requires PHP 8.2+, native Sodium and WordPress 6.2+ (`wpdb` identifier placeholders). These classes are not automatically loaded by the theme yet. The forthcoming adapter must load the approved PHP OIDC client before `Transactions.php`, and load the other classes before constructing them.

- `RecordCipher` encrypts JSON with XChaCha20-Poly1305. Authenticated context includes the table, record kind, hashed browser handle, expiry and key identifier. Copying an encrypted record to another purpose, handle or deadline fails authentication.
- `Records::connect()` creates a private WordPress database connection. A membership/payment rollback cannot resurrect a consumed verification handoff. Credentials come from private host configuration; never put them in theme settings or source files.
- `Records::install()` is an explicit, additive deployment migration. It creates `<prefix>flexpress_age_records`; it does not run on visitor requests. Records are keyed by purpose and SHA-256 of a random browser handle. Payloads, including PKCE verifiers and gateway tokens, are encrypted.
- `Transactions` implements the gateway client's one-use store contract. A single atomic database claim wins across workers, then the row is deleted before returning its verifier. A crash or failed delete after claiming leaves the handoff unusable until cleanup. Expired flows are rejected.
- `Sessions` creates random opaque session handles, stores a separate CSRF value and rotates/deletes old records. It does not validate an age result, log anyone into WordPress, grant membership or replace online gateway introspection. Only a proof returned by the verified OIDC client may be passed to `create()`.

Handoffs last at most ten minutes; sessions last at most one hour and never beyond their supplied proof deadline. The PHP client must also cap that proof to the actual access-token lifetime. No sliding extension is implemented. Retention requires a scheduled call to `Records::purge(time(), 500)` until a batch returns fewer than 500; the adapter/host scheduler is not wired yet. Purging never calls a provider.

The adapter must keep cookies host-only, Secure, HttpOnly and SameSite=Lax, and keep all authentication responses private/no-store. Clear the browser cookie and destroy the local record before attempting remote logout. A database failure must deny access; it must not fall back to a soft age cookie or WordPress membership.

## Encryption-key operation

Provision a unique random 32-byte key per site using the host's secret mechanism outside the web root. `RecordCipher` accepts raw binary keys indexed by short named versions, plus the active version. Keep one previous version during rotation; new records use the active version and old records can be read until their ordinary expiry. Retire the previous key after the maximum record lifetime, cleanup and operational allowance. Removing it early deliberately makes those sessions unusable. Up to three versions are accepted; this is not a provider/signing-key rotation mechanism.

Never copy live keys or customer databases into a development fixture. Protect backups and account for key retention when deciding whether restoring an old database should restore sessions. The production rollout must explicitly invalidate restored browser sessions. Keep debug dumps and query logging disabled for private authentication data.

## Preview signing change

The AJAX card-preview endpoint now requires POST, the existing nonce, a published/password-free episode ID and that episode's exact configured preview video ID. It does not sign arbitrary submitted library IDs. If the preview and full-video fields refer to the same video, normal release/membership/PPV access is also required; banned ordinary customers cannot use retained purchases. Founder paid-access behaviour is preserved.

Tokens from this endpoint last five minutes. Each episode card supplies its post ID, and the browser stops when signing is denied or unavailable. There is no unsigned-URL fallback. Deploy the PHP and versioned JavaScript/templates together; stale clients without a post ID fail closed.

This only closes this signing endpoint. Thumbnail helpers, player URLs, gallery images, direct uploads and CDN origins are separate paths. Their actual provider enforcement, age checks, expiry/renewal and cache policy must be verified before live enforcement. Do not describe a signed-looking URL as protected without an unsigned/direct-origin denial test.

## Evidence and remaining integration

Source-repository commands (not shipped as runtime dependencies):

```sh
python3 tests/run-age-storage.py
php tests/test-media-authorization.php
node tests/test-preview-player.cjs
```

The storage runner creates an internal-only Docker network and disposable MySQL data, uses WordPress's actual `wpdb` implementation, runs eight independent competing PHP workers and removes its containers, network and generated test secrets. It never mounts a live WordPress installation or copies a customer database. Override `AGE_TEST_MYSQL_IMAGE` / `AGE_TEST_PHP_IMAGE` only with compatible reviewed test images. This verifies storage, not a complete WordPress request flow.

Verified: 49 storage assertions; 33 preview authorization assertions with actual entitlement helpers and synthetic content; six browser-player response scenarios. Existing payment, admin-login and package regression suites remain required. Official API references: [WordPress prepared queries](https://developer.wordpress.org/reference/classes/wpdb/prepare/) and [PHP authenticated encryption](https://www.php.net/manual/en/function.sodium-crypto-aead-xchacha20poly1305-ietf-encrypt.php).

Remaining: isolated full WordPress fixture, private runtime packaging, callback/start/logout routes, trusted ingress/country checks, rate limits, neutral verification page, every HTML/REST/AJAX/media guard, narrow payment/admin exemptions, CDN/cache controls, scheduled cleanup, production configuration and approval. No real site callback or production client is registered by this code.
