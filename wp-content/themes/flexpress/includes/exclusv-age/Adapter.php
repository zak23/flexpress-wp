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
        $member = flexpress_member_age_result();
        if ($member !== null && $member['provider'] === 'didit') {
            $saved = $this->records->get('member', $this->memberKey(), time());
            if ($saved !== null && ($saved['user_id'] ?? null) === get_current_user_id()) {
                $receipt = $this->client->memberStatus($saved['reference'], $this->memberKey(), $country);
                return ['provider' => 'didit', 'member' => true, 'expires_at' => $receipt['age_expires_at'],
                    'csrf' => $this->memberCsrf()];
            }
        }
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
        if (!flexpress_yoursafe_is_enabled()) { return null; }
        $member = flexpress_member_age_result();
        if ($member !== null && $member['provider'] === 'yoursafe_id') {
            return ['provider' => 'yoursafe', 'member' => true, 'expires_at' => $member['expires_at'], 'csrf' => $this->memberCsrf()];
        }
        if (!flexpress_is_age_verified()) { return null; }
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

    public function publicPrivacy(): void
    {
        $url = get_privacy_policy_url();
        $path = is_string($url) ? parse_url($url, PHP_URL_PATH) : null;
        // Resolve one configured policy directly, never a visitor-supplied page/query.
        if (!is_string($path) || $path === '' || $path === '/' ||
            $url !== $this->config->origin . $path || rtrim(self::path(), '/') !== rtrim($path, '/')) {
            return;
        }
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true)) {
            header('Allow: GET, HEAD');
            $this->page('Method not allowed', '<p>Open the privacy policy with a normal page request.</p>', 405);
        }
        $policy = get_post((int) get_option('wp_page_for_privacy_policy'));
        if (!$policy || $policy->post_type !== 'page' || $policy->post_status !== 'publish' || $policy->post_password !== '') {
            return;
        }
        // Keep the notice readable before verification without theme chrome, embeds,
        // shortcode execution, forms or media. Content still comes from the site's policy.
        $allowed = [
            'p' => [], 'br' => [], 'hr' => [], 'h2' => [], 'h3' => [], 'h4' => [],
            'strong' => [], 'em' => [], 'b' => [], 'i' => [], 'ul' => [], 'ol' => [],
            'li' => [], 'blockquote' => [], 'a' => ['href' => true, 'title' => true],
            'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
        ];
        $content = wp_kses(wpautop(strip_shortcodes($policy->post_content)), $allowed, ['https', 'http', 'mailto']);
        $this->page('Privacy policy', $content . '<p><a href="/_age/">Back to age access</a></p>');
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
            $policyUrl = get_privacy_policy_url();
            $this->page('Your privacy', '<p>You can use Yoursafe, when configured, or Exclusv.Ai with age checks powered by Didit. '
                . 'This site receives a verification reference and an age result, not your identity documents, '
                . 'face images, name or date of birth.</p><p>The verification provider handles any identity capture. '
                . 'Review the gateway and provider notices before continuing. If you are logged in when verifying, a minimal age result is saved with your membership so future logins can reuse it. Purchases remain separate.</p>'
                . ($policyUrl ? '<p><a href="' . esc_url($policyUrl) . '">Read the site privacy policy</a></p>' : '')
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
            } elseif ($path === '/_age/login' && $method === 'POST') {
                $this->login();
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
        $return = is_string($_GET['return'] ?? null) ? \Exclusv\Age\GatewayClient::returnPath(wp_unslash($_GET['return'])) : '/';
        try {
            $csrf = self::random();
            $this->cookie(self::START, $csrf, time() + 600);
            $this->rate('entry', 60, 60);
            $session = null;
            try { $session = $this->eligible(); } catch (\Throwable) {}
            if ($session !== null) {
                $membership = '';
                if (is_user_logged_in()) {
                    if (($session['member'] ?? false) === true) {
                        $membership = '<p>Your age result is saved with your membership.</p>';
                    } else {
                        // A valid guest cookie is not an account-bound member result.
                        $membership = '<p>Your age access is active on this device, but is not saved to your membership yet.</p>';
                        if (($session['provider'] ?? 'didit') === 'yoursafe') {
                            $membership .= '<p><a class="button" href="' . esc_url(flexpress_yoursafe_start_url($this->config->origin . '/_age/'))
                                . '">Sign in with Yoursafe to save your result</a></p>';
                        } else {
                            $csrf = self::random();
                            $this->cookie(self::START, $csrf, time() + 600);
                            $membership .= '<form action="/_age/start" method="post"><input type="hidden" name="csrf" value="'
                                . esc_attr($csrf) . '"><input type="hidden" name="return" value="/_age/">'
                                . '<button type="submit">Save verification to membership</button></form>'
                                . '<p class="note">Continue to Exclusv.Ai, use your passkey, then share your existing age result. A new check is only needed if that result is no longer valid for this site.</p>';
                        }
                    }
                    $membership .= '<p><a href="' . esc_url(wp_logout_url($this->config->origin . '/_age/')) . '">Sign out</a></p>';
                } else {
                    $membership = '<section class="age-option"><h2>Keep access with your membership</h2>'
                        . '<p>Log in, then save your existing result with Exclusv.Ai so future logins can reuse it.</p>'
                        . $this->loginPanel($csrf, '/_age/') . '</section>';
                }
                $this->page('Your age access is active', '<p>You can continue browsing. Membership access and purchases still apply.</p>'
                    . '<p><a class="button" href="' . esc_url($this->config->origin . $return) . '">Continue to the site</a></p>'
                    . $membership
                    . '<form action="/_age/logout" method="post"><input type="hidden" name="csrf" value="'
                    . esc_attr(is_user_logged_in() ? $this->memberCsrf() : $session['csrf']) . '"><button type="submit">Clear access on this device</button></form>');
            }
            $yoursafe = flexpress_yoursafe_is_enabled()
                ? '<section class="age-option"><p class="separator">Or use Yoursafe</p>'
                    . '<p><a class="button button-yoursafe" href="' . esc_url(flexpress_yoursafe_start_url($this->config->origin . $return))
                    . '">Sign in with Yoursafe</a></p></section>' : '';
            $error = isset($_GET['yoursafe_error'])
                ? '<p role="alert">Yoursafe could not confirm your age. Try again or continue with Exclusv.</p>' : '';
            $this->page(is_user_logged_in() ? 'Verify your age' : 'Verify age or log in', '<p>Confirm you are 18 or older to continue.</p>'
                . (is_user_logged_in() ? '<p class="notice">You are signed in to your membership. Your successful age result will be saved for future logins.</p>'
                    : '<section class="age-option"><h2>Already a member?</h2>'
                    . '<p>Log in first to use a saved age result or save your next verification.</p>'
                    . $this->loginPanel($csrf, $return) . '</section>')
                . $error
                . '<section class="age-option"><h2>' . (is_user_logged_in() ? 'Connect your age result' : 'Verify your age') . '</h2>'
                . '<form action="/_age/start" method="post"><input type="hidden" name="csrf" value="' . esc_attr($csrf) . '">'
                . '<input type="hidden" name="return" value="' . esc_attr($return) . '">'
                . '<button type="submit" aria-describedby="exclusv-next">Verify with Exclusv.Ai</button></form>'
                . '<p class="note" id="exclusv-next">Continue to Exclusv.Ai at ' . esc_html((string) parse_url($this->config->client->issuer, PHP_URL_HOST))
                . ' to use your existing passkey or complete an age check. Powered by Didit.</p>'
                . '<p class="flow-path" aria-label="Verification journey">This site <span aria-hidden="true">→</span> Exclusv.Ai <span aria-hidden="true">→</span> Back here</p></section>'
                . $yoursafe
                . '<p class="note">Age verification does not create a membership or unlock purchases.</p>');
        } catch (\Throwable) {
            $this->page('Age access is temporarily unavailable', '<p>Please try again shortly.</p>'
                . '<p><a href="/_age/help">Get help</a></p>', 503);
        }
    }

    private function loginPanel(string $csrf, string $return): string
    {
        return '<details><summary class="button">Log in to membership</summary><form action="/_age/login" method="post">'
            . '<input type="hidden" name="csrf" value="' . esc_attr($csrf) . '"><input type="hidden" name="return" value="' . esc_attr($return) . '">'
            . '<p><label>Email or username<input name="username" autocomplete="username" required></label></p>'
            . '<p><label>Password<input name="password" type="password" autocomplete="current-password" required></label></p>'
            . '<button type="submit">Log in and continue</button></form></details>';
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
        if (is_user_logged_in()) {
            $receipt = $this->client->registerMember($result['proof'], $this->memberKey(), $this->config->country($_SERVER));
            $this->records->delete('member', $this->memberKey());
            $this->records->put('member', $this->memberKey(), ['user_id' => get_current_user_id(),
                'reference' => $receipt['reference']], $receipt['age_expires_at']);
            flexpress_save_member_age_result(get_current_user_id(), 'didit', $receipt['age_verified_at'], $receipt['age_expires_at']);
        }
        $session = $this->sessions->create($result['proof'], $this->cookieValue(self::SESSION));
        $this->cookie(self::SESSION, $session['handle'], $session['expires_at']);
        $this->redirect($result['return_path']);
    }

    private function logout(): never
    {
        $this->sameOrigin();
        $handle = $this->cookieValue(self::SESSION);
        $yoursafe = $this->yoursafeSession();
        if (!is_string($_POST['csrf'] ?? null) || (is_user_logged_in()
            ? !hash_equals($this->memberCsrf(), $_POST['csrf']) : ($yoursafe !== null
            ? !hash_equals($yoursafe['csrf'], $_POST['csrf'])
            : !$this->sessions->csrfMatches($handle, $_POST['csrf'])))) {
            throw new \Exclusv\Age\Denied();
        }
        $this->clear();
        if (is_user_logged_in()) { wp_logout(); }
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

    private function memberKey(): string
    {
        return hash_hmac('sha256', 'age-member:' . get_current_user_id(), wp_salt('auth'));
    }

    private function memberCsrf(): string
    {
        return hash_hmac('sha256', 'age-member-logout:' . wp_get_session_token(), wp_salt('auth'));
    }

    private function login(): never
    {
        $this->sameOrigin();
        $csrf = $_POST['csrf'] ?? null;
        if (!is_string($csrf) || !preg_match('/^[A-Za-z0-9_-]{43}$/D', $csrf) ||
            !hash_equals($this->cookieValue(self::START), $csrf) || is_user_logged_in()) { throw new \Exclusv\Age\Denied(); }
        $this->rate('login', 5, 600);
        $this->cookie(self::START, '', 0);
        $username = is_string($_POST['username'] ?? null) ? wp_unslash($_POST['username']) : '';
        $password = is_string($_POST['password'] ?? null) ? wp_unslash($_POST['password']) : '';
        $user = wp_signon(['user_login' => $username, 'user_password' => $password, 'remember' => false], true);
        if (is_wp_error($user)) { throw new \Exclusv\Age\Denied(); }
        // Guest proof is never silently copied onto the account just logged into.
        $this->clear();
        wp_set_current_user($user->ID);
        $return = is_string($_POST['return'] ?? null) ? \Exclusv\Age\GatewayClient::returnPath(wp_unslash($_POST['return'])) : '/';
        // Saved proof returns directly; an unverified member stays in the verification journey.
        try {
            if (($this->eligible()['member'] ?? false) === true) { $this->redirect($return); }
        } catch (\Throwable) {}
        $this->redirect('/_age/?return=' . rawurlencode($return));
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
        $settings = get_option('flexpress_general_settings', []);
        $accent = sanitize_hex_color($settings['accent_color'] ?? '') ?: '#ff5093';
        $accentText = function_exists('flexpress_get_contrast_text_color')
            ? flexpress_get_contrast_text_color($accent) : '#000000';
        $logo = function_exists('flexpress_get_custom_logo') ? flexpress_get_custom_logo('full', 'primary') : false;
        $logoUrl = $logo ? esc_url($logo['url'], ['http', 'https']) : '';
        $imageSource = "'self'";
        if ($logoUrl !== '') {
            // Permit only the configured logo's origin; the age panel still loads no scripts.
            $parts = wp_parse_url($logoUrl);
            if (isset($parts['scheme'], $parts['host']) && preg_match('/^[a-z0-9.-]+$/iD', $parts['host'])) {
                $imageSource .= ' ' . $parts['scheme'] . '://' . $parts['host']
                    . (isset($parts['port']) ? ':' . (int) $parts['port'] : '');
            }
        }
        // Browsers also apply form-action to the start POST's 303 redirect to the pinned issuer.
        header("Content-Security-Policy: default-src 'none'; img-src " . $imageSource . "; style-src 'unsafe-inline'; form-action 'self' "
            . $this->config->client->issuer . "; base-uri 'none'; frame-ancestors 'none'");
        header('Content-Type: text/html; charset=utf-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD') { exit; }
        $sandbox = $this->config->client->environment === 'sandbox'
            ? '<p class="notice">Test environment. This does not grant live content access.</p>' : '';
        $brand = $logoUrl !== ''
            ? '<img src="' . $logoUrl . '" alt="' . esc_attr(get_bloginfo('name')) . '" decoding="async">'
            : '<span class="site-title">' . esc_html(get_bloginfo('name')) . '</span>';
        // Inline the small, dedicated stylesheet so this isolated page needs no theme scripts or CSS requests.
        $styles = file_get_contents(__DIR__ . '/../../assets/css/age-access.css');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . esc_html($title . ' – ' . get_bloginfo('name')) . '</title><style>'
            . ':root{--age-accent:' . $accent . ';--age-accent-text:' . sanitize_hex_color($accentText) . '}' . $styles
            . '</style></head><body><main><div class="age-logo">' . $brand . '</div><h1>' . esc_html($title) . '</h1>'
            . $sandbox . '<div class="age-content">' . $content . '</div>'
            . '<footer><a href="/_age/privacy">Privacy policy</a><span aria-hidden="true">·</span><a href="/_age/help">Help</a></footer></main></body></html>';
        exit;
    }

    private static function random(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
