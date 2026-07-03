# Cloudflare geolocation and origin protection

FlexPress reads Cloudflare's `CF-IPCountry` header and classifies each request as
`AU`, `NON_AU`, or `UNKNOWN`. This does not currently restrict content.

## Cloudflare configuration

1. Keep the public DNS records proxied (orange cloud).
2. Enable **Add visitor location headers** under Rules > Transform Rules >
   Managed Transforms.
3. At the origin firewall, allow inbound HTTP/HTTPS only from the IPv4 and IPv6
   networks published at <https://www.cloudflare.com/ips/>. Preserve a separate,
   restricted management path before applying the deny rule.
4. Use DNS-based ACME validation or a Cloudflare Origin CA certificate. HTTP/TLS
   certificate challenges from non-Cloudflare addresses will fail after the ACL
   is enabled.
5. Subscribe to Cloudflare IP-range updates and add new ranges before removing
   retired ranges.

Docker binds WordPress to `127.0.0.1:8085`; Caddy must remain the only public web
entry point. The firewall ACL is host/infrastructure configuration and must be
applied on the production host, not inside the WordPress container.

## Local testing

Set the environment and override in `wp-config.php`:

```php
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'FLEXPRESS_COUNTRY_OVERRIDE', 'AU' );
```

The override is ignored when WordPress reports `staging` or `production`.

Run the classifier regression test with:

```bash
php tests/test-geolocation.php
```

## Production verification

- A normal proxied request should reach the site and include `CF-IPCountry` at
  the origin.
- IPv4 and IPv6 clients should both work through Cloudflare.
- Connecting directly to the origin on ports 80, 443, or 8085 must fail,
  including when a forged `CF-IPCountry` header is supplied.
- The `flexpress_geo_YYYYMMDD_*` options contain aggregate counts only; they do
  not contain IP addresses or request identifiers.
