<?php

declare(strict_types=1);

// Only the disposable runner supplies these paths. Never run against a customer installation.
if (!is_file('/run/secrets/key') || !is_file('/theme/includes/exclusv-age/Records.php')) {
    throw new RuntimeException('Use tests/run-age-storage.py in a disposable Docker environment.');
}
define('ABSPATH', '/usr/src/wordpress/');
define('WPINC', 'wp-includes');
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', false);
define('WP_INSTALLING', true);
require ABSPATH . WPINC . '/compat.php';
require ABSPATH . WPINC . '/load.php';
require ABSPATH . WPINC . '/plugin.php';
require ABSPATH . WPINC . '/class-wpdb.php';
function _doing_it_wrong(...$args) { throw new RuntimeException('Invalid WordPress database API call'); }
function wp_die(...$args) { throw new RuntimeException('WordPress database unavailable'); }
function wp_debug_backtrace_summary(...$args) { return 'age-storage-test'; }

foreach (['StorageUnavailable', 'Database', 'RecordCipher', 'Records', 'Sessions'] as $class) {
    require '/theme/includes/exclusv-age/' . $class . '.php';
}
use FlexPress\Age\{RecordCipher, Records, Sessions, StorageUnavailable};

function connection(): wpdb {
    return new wpdb('age_test', trim(file_get_contents('/run/secrets/password')), 'flexpress_age_verify', 'age-db');
}
function cipher(): RecordCipher {
    return new RecordCipher(['test' => hex2bin(trim(file_get_contents('/run/secrets/key')))], 'test');
}
function store(): Records {
    return Records::connect('age_test', trim(file_get_contents('/run/secrets/password')),
        'flexpress_age_verify', 'age-db', cipher(), 'fixture_');
}
function check(bool $condition, string $message): void {
    ++$GLOBALS['checks'];
    if (!$condition) { throw new RuntimeException($message); }
}
function denied(callable $call, string $message): void {
    try { $call(); } catch (StorageUnavailable $error) {
        check($error->getPrevious() === null, 'Storage error disclosed its underlying exception');
        return;
    }
    throw new RuntimeException($message);
}
function hashHandle(string $label): string { return hash('sha256', $label); }

if (in_array($argv[1] ?? '', ['race', 'limit'], true)) {
    $records = store();
    $deadline = microtime(true) + 10;
    while (!is_file('/tmp/age-storage-race-start') && microtime(true) < $deadline) { usleep(1000); }
    if (!is_file('/tmp/age-storage-race-start')) { exit(2); }
    echo $argv[1] === 'limit' ? ($records->allow($argv[2], 1, 60) ? '1' : '0') : ($records->take('transaction', $argv[2], time()) === null ? '0' : '1');
    exit;
}

$checks = 0;
$db = connection();
$db->suppress_errors(true);
$records = store();
$records->install();
$now = time();
$transaction = ['state' => 'fixture-state', 'nonce' => 'fixture-nonce', 'code_verifier' => 'fixture-private-verifier'];
$hash = hashHandle('transaction');
$records->put('transaction', $hash, $transaction, $now + 300);
$records->install();
check($records->get('transaction', $hash, $now) === $transaction, 'Migration destroyed an existing handoff');
$raw = $db->get_row("SELECT * FROM fixture_flexpress_age_records WHERE kind = 'transaction'", ARRAY_A);
check($raw['handle_hash'] === $hash && !str_contains($raw['payload'], 'fixture-private-verifier'), 'Plain verifier stored');
denied(fn() => $records->put('transaction', $hash, ['state' => 'overwrite'], $now + 300), 'Duplicate handle overwrote flow');
check(store()->take('transaction', $hash, $now) === $transaction, 'New worker could not consume persisted flow');
check($records->take('transaction', $hash, $now) === null, 'Consumed handoff replayed');

// Eight independent PHP processes/connections contest the same database row at the same barrier.
$hash = hashHandle('race');
$records->put('transaction', $hash, $transaction, time() + 300);
$children = [];
for ($index = 0; $index < 8; ++$index) {
    $process = proc_open([PHP_BINARY, '-d', 'zend.exception_ignore_args=1', __FILE__, 'race', $hash],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Could not start concurrency worker'); }
    fclose($pipes[0]);
    $children[] = [$process, $pipes];
}
touch('/tmp/age-storage-race-start');
$winners = 0;
foreach ($children as [$process, $pipes]) {
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    check(proc_close($process) === 0 && $error === '' && in_array($output, ['0', '1'], true), 'Concurrency worker failed');
    $winners += (int) $output;
}
check($winners === 1, 'More or fewer than one callback consumed the transaction');

unlink('/tmp/age-storage-race-start');
$children = [];
for ($index = 0; $index < 8; ++$index) {
    $process = proc_open([PHP_BINARY, __FILE__, 'limit', hashHandle('rate-race')],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]);
    $children[] = [$process, $pipes];
}
touch('/tmp/age-storage-race-start');
$allowed = 0;
foreach ($children as [$process, $pipes]) {
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    check(proc_close($process) === 0 && $error === '' && in_array($output, ['0', '1'], true), 'Rate worker failed');
    $allowed += (int) $output;
}
check($allowed === 1, 'Concurrent rate limit allowed an incorrect number of starts');
check(!$records->allow(hashHandle('rate-race'), 1, 60), 'Rate limit reset during the window');
$db->query('UPDATE fixture_flexpress_age_records_limits SET expires_at = 1');
check($records->allow(hashHandle('rate-race'), 1, 60), 'Expired rate window did not restart');
$db->query('UPDATE fixture_flexpress_age_records_limits SET expires_at = 1');
check($records->purgeLimits() === 1, 'Expired rate bucket did not purge');

// Authentication covers the site table, purpose, browser hash, deadline and envelope key ID.
foreach (['hash', 'kind', 'expiry', 'ciphertext'] as $mutation) {
    $hash = hashHandle('tamper-' . $mutation);
    $records->put('transaction', $hash, $transaction, time() + 300);
    $kind = 'transaction';
    if ($mutation === 'hash') {
        $next = hashHandle('moved');
        $db->query($db->prepare('UPDATE fixture_flexpress_age_records SET handle_hash = %s WHERE handle_hash = %s', $next, $hash));
        $hash = $next;
    } elseif ($mutation === 'kind') {
        $db->query($db->prepare("UPDATE fixture_flexpress_age_records SET kind = 'session' WHERE handle_hash = %s", $hash));
        $kind = 'session';
    } elseif ($mutation === 'expiry') {
        $db->query($db->prepare('UPDATE fixture_flexpress_age_records SET expires_at = expires_at + 1 WHERE handle_hash = %s', $hash));
    } else {
        $db->query($db->prepare("UPDATE fixture_flexpress_age_records SET payload = CONCAT(payload, 'invalid') WHERE handle_hash = %s", $hash));
    }
    denied(fn() => $records->take($kind, $hash, time()), 'Tampered record accepted: ' . $mutation);
    check($records->take($kind, $hash, time()) === null, 'Corrupt handoff was not consumed');
}

$key = random_bytes(32);
$old = new RecordCipher(['old' => $key], 'old');
$envelope = $old->seal($transaction, 'context');
$rotated = new RecordCipher(['old' => $key, 'next' => random_bytes(32)], 'next');
check($rotated->open($envelope, 'context') === $transaction, 'Previous key stopped working during rotation');
check(str_starts_with($rotated->seal($transaction, 'context'), 'v1:next:'), 'New records used the old key');
denied(fn() => cipher()->open($envelope, 'context'), 'Removed encryption key accepted');
denied(fn() => $old->open($envelope, 'wrong-site'), 'Cross-site ciphertext accepted');
denied(fn() => $old->seal(['huge' => str_repeat('x', 16385)], 'context'), 'Unbounded payload accepted');
denied(fn() => new RecordCipher(['weak' => 'weak'], 'weak'), 'Weak storage key accepted');

// Sessions survive workers, rotate old IDs, bind CSRF to the new session, and disappear on logout.
$sessions = new Sessions($records);
$proof = ['expires_at' => time() + 200, 'age_expires_at' => time() + 400, 'access_token' => 'fixture-private-token'];
$first = $sessions->create($proof);
$saved = (new Sessions(store()))->read($first['handle']);
check($saved['proof'] === $proof, 'Session did not persist across workers');
check($sessions->csrfMatches($first['handle'], $saved['csrf']), 'Own-session CSRF failed');
check(!$sessions->csrfMatches($first['handle'], str_repeat('a', 43)), 'Guessed CSRF accepted');
check($sessions->read('../invalid') === null, 'Malformed session handle accepted');
$second = $sessions->create($proof, $first['handle']);
check($sessions->read($first['handle']) === null, 'Old session survived rotation');
check(!$sessions->csrfMatches($second['handle'], $saved['csrf']), 'Old-session CSRF survived rotation');
$raw = $db->get_var("SELECT payload FROM fixture_flexpress_age_records WHERE kind = 'session' LIMIT 1");
check(!str_contains($raw, 'fixture-private-token'), 'Bearer token stored in plaintext');
$sessions->destroy($second['handle']);
check((new Sessions(store()))->read($second['handle']) === null, 'Local logout left a usable session');
$sessions->destroy($second['handle']);
denied(fn() => $sessions->create(['expires_at' => time() - 1, 'age_expires_at' => time() + 30]), 'Expired proof persisted');
denied(fn() => $records->put('transaction', hashHandle('long'), [], time() + 610), 'Handoff lifetime exceeds 10 minutes');
denied(fn() => $records->put('session', hashHandle('long'), [], time() + 3610), 'Session lifetime exceeds 1 hour');

// Expiry is checked before decrypting and cleanup is bounded, including abandoned claims.
for ($index = 0; $index < 3; ++$index) {
    $records->put('transaction', hashHandle('expire-' . $index), $transaction, time() + 30);
}
$db->query("UPDATE fixture_flexpress_age_records SET claim = REPEAT('a', 64) WHERE handle_hash = '" . hashHandle('expire-0') . "'");
check($records->get('transaction', hashHandle('expire-0'), time()) === null, 'Abandoned claimed flow remained readable');
check($records->take('transaction', hashHandle('expire-1'), time() + 31) === null, 'Expired flow accepted');
check($records->purge(time() + 31, 2) === 2 && $records->purge(time() + 31) === 1, 'Bounded cleanup failed');

// Failed deletion never returns credentials; the irrevocable claim prevents a later replay.
$hash = hashHandle('failed-delete');
$records->put('transaction', $hash, $transaction, time() + 300);
$db->query("CREATE TRIGGER age_fail_delete BEFORE DELETE ON fixture_flexpress_age_records FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic deletion failure'");
check($db->last_error === '', 'Could not install deletion-failure fixture');
denied(fn() => $records->take('transaction', $hash, time()), 'Failed delete returned a verifier');
$db->query('DROP TRIGGER age_fail_delete');
check($records->take('transaction', $hash, time()) === null, 'Failed delete made the handoff reusable');

// An unrelated WordPress/payment transaction cannot roll back the age store's consumption.
$hash = hashHandle('other-transaction');
$records->put('transaction', $hash, $transaction, time() + 300);
$db->query('START TRANSACTION');
check($records->take('transaction', $hash, time()) === $transaction, 'Separate connection could not consume flow');
$db->query('ROLLBACK');
check(store()->take('transaction', $hash, time()) === null, 'Payment rollback restored a consumed handoff');
denied(fn() => Records::connect('age_test', 'invalid', 'flexpress_age_verify', 'age-db:1', cipher(), 'fixture_'), 'Failed connection accepted');
$db->query('DROP TABLE fixture_flexpress_age_records');
denied(fn() => $records->get('session', hashHandle('none'), time()), 'Missing table treated as an authorization result');
echo "Age storage tests passed ($checks assertions): real WordPress wpdb + MySQL, 8 competing workers, encrypted sessions, rotation, expiry, tampering and database failures.\n";
