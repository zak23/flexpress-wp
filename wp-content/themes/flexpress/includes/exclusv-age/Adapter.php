<?php

declare(strict_types=1);

namespace FlexPress\Age;

final class Adapter
{
    private Sessions $sessions;
    private Transactions $transactions;
    private \Exclusv\Age\GatewayClient $client;
    private const SESSION = '__Host-flexpress_age';
    private const TRANSACTION = '__Host-flexpress_age_tx';
    private const START = '__Host-flexpress_age_start';

    public function __construct(private readonly SiteConfig $config, private readonly Records $records)
    {
        $this->sessions = new Sessions($records);
        $this->transactions = new Transactions($records);
        $this->client = new \Exclusv\Age\GatewayClient($config->client);
    }

    public function install(): void
    {
        $this->records->install();
    }

    public function purge(): array
    {
        return ['records' => $this->records->purge(time()), 'limits' => $this->records->purgeLimits()];
    }

    /** Gateway proofs are checked online; Yoursafe retains its existing signed local proof. */
    public function eligible(): array
    {
        $country = $this->config->country($_SERVER);
        if ($country === null) {
            throw new \Exclusv\Age\Denied();
        }
        $yoursafe = $this->yoursafeSession();
        if ($yoursafe !== null) { return $yoursafe; }
        $session = $this->sessions->read($this->cookieValue(self::SESSION));
        if ($session === null) {
            throw new \Exclusv\Age\Denied();
        }
        $this->client->assertEligible($session['proof'], $country);
        return $session;
    }

    /** Accept only the existing server-signed Yoursafe proof, never a membership login or modal flag. */
    private function yoursafeSession(): ?array
    {
        if (!flexpress_yoursafe_is_enabled() || !flexpress_is_age_verified()) { return null; }
        $cookie = wp_unslash($_COOKIE[FLEXPRESS_AGE_COOKIE]);
        $payload = json_decode(flexpress_age_base64url_decode(explode('.', $cookie, 2)[0]), true);
        return ['provider' => 'yoursafe', 'expires_at' => $payload['exp'],
            'csrf' => hash_hmac('sha256', 'exclusv-age-logout:' . $cookie, wp_salt('auth'))];
    }

    public function mediaDeadline(): int
    {
        $session = $this->eligible();
        return min(time() + 300, $session['expires_at']);
    }

    public function guard(bool $api): void
    {
        try {
            $this->eligible();
            self::privateHeaders();
        } catch (\Throwable) {
            if ($api || !in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true)) {
                wp_send_json_error(['code' => 'age_required', 'verification_url' => '/_age/'], 403);
                exit;
            }
            $path = \Exclusv\Age\GatewayClient::returnPath(self::path());
            $this->redirect('/_age/?return=' . rawurlencode($path));
        }
    }

    public function routes(): void
    {
        $path = self::path();
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        self::privateHeaders();
        if ($path === '/_age' && in_array($method, ['GET', 'HEAD'], true)) {
            $this->redirect('/_age/');
        }
        if ($path === '/_age/privacy' && in_array($method, ['GET', 'HEAD'], true)) {
            $this->page('Your privacy', '<p>You can use Yoursafe, when configured, or Didit through the age gateway. '
                . 'This site receives a verification reference and an age result, not your identity documents, '
                . 'face images, name or date of birth.</p><p>The verification provider handles any identity capture. '
                . 'Review the gateway and provider notices before continuing. Your membership login and purchases stay separate.</p>'
                . '<p><a href="/_age/">Back to age access</a></p>');
        }
        if ($path === '/_age/help' && in_array($method, ['GET', 'HEAD'], true)) {
            $this->page('Help with age access', '<p>If verification is unavailable or you need help, contact the site operator.</p>'
                . '<p><a href="mailto:' . esc_attr($this->config->supportEmail) . '">Contact support</a></p>'
                . '<p>Do not email identity documents, face images or verification links.</p><p><a href="/_age/">Try again</a></p>');
        }
        if ($path === '/_age/' && in_array($method, ['GET', 'HEAD'], true)) {
            $this->entry();
        }
        try {
            if ($path === '/_age/start' && $method === 'POST') {
                $this->start();
            } elseif ($path === '/_age/callback' && $method === 'GET') {
                $this->complete();
            } elseif ($path === '/_age/logout' && $method === 'POST') {
                $this->logout();
            } else {
                $this->page('Request unavailable', '<p><a href="/_age/">Return to age access</a></p>', 405);
            }
        } catch (\Throwable) {
            if ($path === '/_age/callback') {
                $this->cookie(self::TRANSACTION, '', 0);
            }
            $this->page('We could not confirm age access', '<p>The request expired or verification is unavailable. '
                . 'No access was granted. Please start again.</p><p><a href="/_age/">Try again</a> · '
                . '<a href="/_age/help">Get help</a></p>', 403);
        }
    }

    private function entry(): never
    {
        if ($this->config->country($_SERVER) === null) {
            $this->page('Age access is unavailable here', '<p>We cannot confirm an eligible location for this request.</p>'
                . '<p><a href="/_age/help">Get help</a></p>', 403);
        }
        try {
            $this->rate('entry', 60, 60);
            $session = null;
            try { $session = $this->eligible(); } catch (\Throwable) {}
            if ($session !== null) {
                $this->page('Your age access is active', '<p>You can continue browsing. Membership access and purchases still apply.</p>'
                    . '<p><a class="button" href="/">Continue to the site</a></p>'
                    . '<form action="/_age/logout" method="post"><input type="hidden" name="csrf" value="'
                    . esc_attr($session['csrf']) . '"><button type="submit">Clear access on this device</button></form>');
            }
            $csrf = self::random();
            $this->cookie(self::START, $csrf, time() + 600);
            $return = is_string($_GET['return'] ?? null) ? \Exclusv\Age\GatewayClient::returnPath(wp_unslash($_GET['return'])) : '/';
            $yoursafe = flexpress_yoursafe_is_enabled()
                ? '<p><a class="button" href="' . esc_url(flexpress_yoursafe_start_url($this->config->origin . $return))
                    . '">Sign in with Yoursafe</a></p><p>Or verify with Didit below.</p>' : '';
            $error = isset($_GET['yoursafe_error'])
                ? '<p role="alert">Yoursafe could not confirm your age. Please try again or choose Didit.</p>' : '';
            $this->page('Confirm your age to continue', '<p>Verify that you are 18 or older to browse this site. '
                . 'Age verification does not create a membership or unlock purchases.</p>' . $error . $yoursafe
                . '<form action="/_age/start" method="post"><input type="hidden" name="csrf" value="' . esc_attr($csrf) . '">'
                . '<input type="hidden" name="return" value="' . esc_attr($return) . '">'
                . '<button type="submit">Verify with Didit</button></form>'
                . '<p>Already verified through the age gateway? You can use your passkey there.</p>');
        } catch (\Throwable) {
            $this->page('Age access is temporarily unavailable', '<p>Please try again shortly.</p>'
                . '<p><a href="/_age/help">Get help</a></p>', 503);
        }
    }

    private function start(): never
    {
        $this->sameOrigin();
        $csrf = $_POST['csrf'] ?? null;
        if (!is_string($csrf) || !preg_match('/^[A-Za-z0-9_-]{43}$/D', $csrf) ||
            !hash_equals($this->cookieValue(self::START), $csrf)) {
            throw new \Exclusv\Age\Denied();
        }
        $this->rate('start', 10, 600);
        $return = is_string($_POST['return'] ?? null) ? wp_unslash($_POST['return']) : '/';
        $old = $this->cookieValue(self::TRANSACTION);
        if ($old !== '') {
            $this->records->delete('transaction', hash('sha256', $old));
        }
        $this->cookie(self::START, '', 0);
        $flow = $this->client->begin($this->transactions, $return);
        $this->cookie(self::TRANSACTION, $flow['handle'], $flow['expires_at']);
        // The URL comes only from the pinned issuer metadata, never a request redirect parameter.
        header('Location: ' . $flow['url'], true, 303);
        exit;
    }

    private function complete(): never
    {
        $this->rate('callback', 20, 600);
        $handle = $this->cookieValue(self::TRANSACTION);
        $this->cookie(self::TRANSACTION, '', 0);
        $result = $this->client->complete($this->transactions, $handle, wp_unslash($_GET), $this->config->country($_SERVER));
        $session = $this->sessions->create($result['proof'], $this->cookieValue(self::SESSION));
        $this->cookie(self::SESSION, $session['handle'], $session['expires_at']);
        $this->redirect($result['return_path']);
    }

    private function logout(): never
    {
        $this->sameOrigin();
        $handle = $this->cookieValue(self::SESSION);
        $yoursafe = $this->yoursafeSession();
        if (!is_string($_POST['csrf'] ?? null) || ($yoursafe !== null
            ? !hash_equals($yoursafe['csrf'], $_POST['csrf'])
            : !$this->sessions->csrfMatches($handle, $_POST['csrf']))) {
            throw new \Exclusv\Age\Denied();
        }
        $this->clear();
        $this->redirect('/_age/');
    }

    /** Also called on WordPress logout; remote failure cannot restore local access. */
    public function clear(): void
    {
        flexpress_clear_age_verified_cookie();
        $handle = $this->cookieValue(self::SESSION);
        $this->cookie(self::SESSION, '', 0);
        $session = $this->sessions->read($handle);
        $this->sessions->destroy($handle);
        if ($session !== null) {
            try { $this->client->revoke($session['proof']); } catch (\Throwable) {}
        }
    }

    private function sameOrigin(): void
    {
        if (($_SERVER['HTTP_ORIGIN'] ?? '') !== $this->config->origin ||
            (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096 || $this->config->country($_SERVER) === null) {
            throw new \Exclusv\Age\Denied();
        }
    }

    private function rate(string $purpose, int $maximum, int $seconds): void
    {
        if (!$this->records->allow($this->config->rateBucket($purpose, $_SERVER), $maximum, $seconds)) {
            throw new \Exclusv\Age\Denied();
        }
    }

    private function cookieValue(string $name): string
    {
        $value = $_COOKIE[$name] ?? '';
        return is_string($value) && preg_match('/^[A-Za-z0-9_-]{43}$/D', $value) ? $value : '';
    }

    private function cookie(string $name, string $value, int $expires): void
    {
        if (!setcookie($name, $value, ['expires' => $expires ?: time() - 3600, 'path' => '/',
            'secure' => true, 'httponly' => true, 'samesite' => 'Lax'])) {
            throw new StorageUnavailable();
        }
    }

    private function redirect(string $path): never
    {
        self::privateHeaders();
        header('Location: ' . $this->config->origin . $path, true, 303);
        exit;
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        return is_string($path) ? $path : '';
    }

    public static function privateHeaders(): void
    {
        nocache_headers();
        header('Cache-Control: private, no-store, max-age=0');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
    }

    private function page(string $title, string $content, int $status = 200): never
    {
        self::privateHeaders();
        status_header($status);
        // Native form POSTs send Origin:null under no-referrer. Keep origin validation intact
        // while withholding referrers from the external issuer. Callback redirects stay no-referrer.
        header('Referrer-Policy: same-origin');
        // Browsers also apply form-action to the start POST's 303 redirect to the pinned issuer.
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self' "
            . $this->config->client->issuer . "; base-uri 'none'; frame-ancestors 'none'");
        header('Content-Type: text/html; charset=utf-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD') { exit; }
        $sandbox = $this->config->client->environment === 'sandbox'
            ? '<p class="notice">Test environment. This does not grant live content access.</p>' : '';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . esc_html($title) . '</title><style>html{color-scheme:dark}body{margin:0;background:#101818;color:#eef5f4;'
            . 'font:18px/1.6 system-ui,sans-serif}main{max-width:36rem;margin:8vh auto;padding:2rem}h1{font-size:2rem;line-height:1.2}'
            . 'a{color:#7de0cf}button,.button{display:inline-block;background:#74dbc9;color:#10201e;padding:.85rem 1.2rem;'
            . 'border:0;border-radius:.5rem;font:inherit;font-weight:650;cursor:pointer;text-decoration:none}button:focus-visible,a:focus-visible'
            . '{outline:3px solid white;outline-offset:5px}.notice{border:1px solid #74dbc9;padding:.75rem}footer{margin-top:2.5rem;font-size:1rem}'
            . '</style></head><body><main><p>Age access</p><h1>' . esc_html($title) . '</h1>' . $sandbox . $content
            . '<footer><a href="/_age/privacy">Privacy</a> · <a href="/_age/help">Help</a></footer></main></body></html>';
        exit;
    }

    private static function random(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
