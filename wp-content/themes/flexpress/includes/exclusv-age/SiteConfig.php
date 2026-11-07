<?php

declare(strict_types=1);

namespace FlexPress\Age;

final readonly class SiteConfig
{
    public \Exclusv\Age\Config $client;
    public string $origin;
    public string $supportEmail;
    public array $peers;
    public string $peerSource;
    public string $ingressSecret;
    public array $keys;
    public string $activeKey;
    public string $rateKey;
    public bool $loopback;

    public function __construct(#[\SensitiveParameter] array $values)
    {
        $this->origin = $values['site_origin'] ?? '';
        $this->client = new \Exclusv\Age\Config(
            $values['issuer'] ?? '', $values['client_id'] ?? '', $values['client_secret'] ?? '',
            $this->origin . '/_age/callback', $values['policies'] ?? [], $values['countries'] ?? [],
            $values['environment'] ?? 'production', $values['allow_loopback'] ?? false
        );
        $parts = parse_url($this->origin);
        if (!is_array($parts) || isset($parts['path']) || $this->origin !== rtrim(home_url(), '/')) {
            throw new StorageUnavailable();
        }
        $this->loopback = $this->client->allowLoopback && $this->client->environment === 'sandbox' &&
            in_array($parts['host'], ['localhost', '127.0.0.1'], true);
        if ($this->client->environment === 'sandbox' && wp_get_environment_type() === 'production') {
            throw new StorageUnavailable();
        }
        $this->supportEmail = $values['support_email'] ?? '';
        $this->peers = $values['trusted_peers'] ?? [];
        $this->peerSource = $values['peer_source'] ?? 'remote_addr';
        $this->ingressSecret = $values['ingress_secret'] ?? '';
        $this->keys = $values['storage_keys'] ?? [];
        $this->activeKey = $values['active_storage_key'] ?? '';
        if (!in_array($this->peerSource, ['remote_addr', 'apache_connection'], true) ||
            !filter_var($this->supportEmail, FILTER_VALIDATE_EMAIL) || !$this->peers ||
            !preg_match('/^[A-Za-z0-9_-]{43}$/D', $this->ingressSecret)) {
            throw new StorageUnavailable();
        }
        foreach ($this->peers as $peer) {
            if (!is_string($peer) || !filter_var($peer, FILTER_VALIDATE_IP)) {
                throw new StorageUnavailable();
            }
        }
        new RecordCipher($this->keys, $this->activeKey);
        $this->rateKey = hash_hmac('sha256', 'flexpress-age-limits-v1', $this->keys[$this->activeKey], true);
    }

    public function country(array $server): ?string
    {
        $country = $server['HTTP_CF_IPCOUNTRY'] ?? null;
        $host = parse_url($this->origin, PHP_URL_HOST);
        $port = parse_url($this->origin, PHP_URL_PORT);
        $authority = $host . ($port === null ? '' : ':' . $port);
        // mod_remoteip may replace REMOTE_ADDR for payment/registration callers. An
        // explicitly configured Apache environment value preserves the socket peer.
        // Never accept its HTTP_ header counterpart or fall back when it is missing.
        $peerKey = $this->peerSource === 'apache_connection' ? 'EXCLUSV_CONNECTION_ADDR' : 'REMOTE_ADDR';
        if (!in_array($server[$peerKey] ?? '', $this->peers, true) ||
            !is_string($server['HTTP_X_EXCLUSV_ORIGIN'] ?? null) ||
            !hash_equals($this->ingressSecret, $server['HTTP_X_EXCLUSV_ORIGIN']) ||
            strtolower($server['HTTP_HOST'] ?? '') !== strtolower($authority) ||
            (!$this->loopback && !is_ssl()) || !is_string($country) ||
            !in_array($country, $this->client->countries, true)) {
            return null;
        }
        return $country;
    }

    public function rateBucket(string $purpose, array $server): string
    {
        if ($this->country($server) === null || !filter_var($server['HTTP_CF_CONNECTING_IP'] ?? '', FILTER_VALIDATE_IP)) {
            throw new StorageUnavailable();
        }
        return hash_hmac('sha256', $purpose . ':' . $server['HTTP_CF_CONNECTING_IP'], $this->rateKey);
    }
}
