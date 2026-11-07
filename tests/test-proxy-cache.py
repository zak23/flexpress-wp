#!/usr/bin/env python3
"""Exercise the repository Caddyfile against a synthetic upstream, never WordPress.

Requires Python 3 and the deployed Caddy binary. Example:
  python3 tests/test-proxy-cache.py --bind 192.168.1.248 --config Caddyfile
"""
import argparse
import http.client
import json
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
import socket
import re
import stat
import subprocess
import tempfile
import threading
import time

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--bind', required=True, help='Private interface IP for temporary listeners')
parser.add_argument('--config', type=Path, default=Path('Caddyfile'))
parser.add_argument('--caddy', default='caddy')
args = parser.parse_args()


class Upstream(BaseHTTPRequestHandler):
    def log_message(self, *_):
        pass

    def do_GET(self):
        status = 200
        content_type = 'text/css; charset=UTF-8' if self.path.endswith('.css') else 'text/html; charset=UTF-8'
        if 'missing' in self.path:
            status, content_type = 404, 'text/html'
        if 'fallback' in self.path:
            content_type = 'text/html'
        if 'payment-success' in self.path:
            status = 302
        if 'error' in self.path:
            status = 500
        self.send_response(status)
        self.send_header('Content-Type', content_type)
        self.send_header('Cache-Control', 'public, max-age=31536000')
        self.send_header('Expires', 'Thu, 31 Dec 2026 23:59:59 GMT')
        self.send_header('ETag', '"actual-asset-version"')
        self.send_header('Last-Modified', 'Fri, 06 Nov 2026 00:00:00 GMT')
        self.send_header('X-Cache-Status', 'HIT')
        self.send_header('X-Cache-Enabled', 'true')
        self.send_header('Age', '900')
        for name in ['X-Real-IP', 'X-Forwarded-For', 'CF-Connecting-IP', 'CF-IPCountry']:
            if self.headers.get(name):
                self.send_header('Echo-' + name, self.headers[name])
        if 'cookie' in self.path:
            self.send_header('Set-Cookie', 'session=example; HttpOnly')
        if status == 302:
            self.send_header('Location', '/login/?key=private-log-marker')
        self.send_header('Content-Length', '0')
        self.end_headers()

    do_HEAD = do_GET
    do_POST = do_GET


upstream = ThreadingHTTPServer((args.bind, 0), Upstream)
threading.Thread(target=upstream.serve_forever, daemon=True).start()
with socket.socket() as port_probe:
    port_probe.bind((args.bind, 0))
    proxy_port = port_probe.getsockname()[1]
source = args.config.read_text().split('# Redirect www to non-www')[0]
source = source.replace('zakspov.com {', f'http://{args.bind}:{proxy_port} {{', 1)
source = source.replace('192.168.1.238:8085', f'{args.bind}:{upstream.server_port}')
# Substitute controlled peers only in this isolated test; production ranges stay intact.
ranges = re.search(r'\(flexpress-cloudflare-ranges\) \{\s*remote_ip ([^\n]+)', source).group(1)
source = source.replace(ranges, '127.0.0.2/32')
source, tls_blocks = re.subn(r'\s*tls \{\s*dns cloudflare \{env.CLOUDFLARE_API_TOKEN\}\s*\}', '', source)
assert tls_blocks == 1, 'Expected the shared DNS challenge block'
source = '{\n    admin off\n    auto_https off\n}\n' + source
cases = [
    ('/', {}, 'GET', False),
    ('/login/', {}, 'GET', False),
    ('/join/', {}, 'GET', False),
    ('/payment-success/', {}, 'GET', False),
    ('/wp-json/test', {}, 'GET', False),
    ('/wp-admin/admin-ajax.php', {}, 'POST', False),
    ('/wp-content/theme.css', {}, 'GET', True),
    ('/wp-includes/theme.css', {}, 'HEAD', True),
    ('/wp-content/theme.css', {'Cookie': 'wordpress_logged_in_example=x'}, 'GET', False),
    ('/wp-content/theme.css', {'Cookie': 'wordpress_sec_example=x'}, 'GET', False),
    ('/wp-content/theme.css', {'Authorization': 'Bearer example'}, 'GET', False),
    ('/wp-content/theme.css', {}, 'POST', False),
    ('/wp-content/missing.css', {}, 'GET', False),
    ('/wp-content/fallback.css', {}, 'GET', False),
    ('/wp-content/cookie.css', {}, 'GET', False),
    ('/wp-content/error.css', {}, 'GET', False),
    ('/outside-assets.css', {}, 'GET', False),
]
try:
    with tempfile.TemporaryDirectory(prefix='flexpress-caddy-test-') as directory:
        config = Path(directory) / 'Caddyfile'
        access_log = Path(directory) / 'access.json'
        config.write_text(source.replace('/var/log/caddy/flexpress.log', str(access_log)))
        with (Path(directory) / 'caddy.log').open('w+') as log:
            process = subprocess.Popen([args.caddy, 'run', '--config', str(config), '--adapter', 'caddyfile'], stdout=log, stderr=log)
            try:
                for attempt in range(100):
                    if process.poll() is not None:
                        log.seek(0)
                        raise RuntimeError(log.read())
                    try:
                        with socket.create_connection((args.bind, proxy_port), timeout=.1):
                            break
                    except OSError:
                        time.sleep(.05)
                else:
                    raise RuntimeError('Test proxy failed to listen')
                for path, headers, method, public in cases:
                    connection = http.client.HTTPConnection(args.bind, proxy_port, timeout=5, source_address=('127.0.0.1', 0))
                    connection.request(method, path, headers=headers)
                    response = connection.getresponse()
                    expected = 'public, max-age=86400' if public else 'private, no-store, max-age=0'
                    assert response.getheader('Cache-Control') == expected, (path, headers, method, response.getheaders())
                    assert response.getheader('ETag') == '"actual-asset-version"', path
                    assert response.getheader('Last-Modified') == 'Fri, 06 Nov 2026 00:00:00 GMT', path
                    for field in ['X-Cache-Status', 'X-Cache-Enabled', 'Age', 'path', 'Server']:
                        assert response.getheader(field) is None, (path, field)
                    assert response.getheader('Expires') == (None if public else '0'), path
                    assert response.getheader('Pragma') == (None if public else 'no-cache'), path
                    response.read()
                    connection.close()
                def request(peer, path='/', headers=None):
                    connection = http.client.HTTPConnection(args.bind, proxy_port, timeout=5, source_address=(peer, 0))
                    connection.request('GET', path, headers=headers or {})
                    response = connection.getresponse()
                    response.read()
                    connection.close()
                    return response

                forged = {'CF-Connecting-IP': '198.51.100.42', 'CF-IPCountry': 'AU', 'X-Forwarded-For': '198.51.100.99'}
                edge = request('127.0.0.2', headers=forged)
                assert edge.status == 200
                assert edge.getheader('Echo-X-Real-IP') == '198.51.100.42'
                assert edge.getheader('Echo-X-Forwarded-For') == '198.51.100.42'
                assert edge.getheader('Echo-CF-IPCountry') == 'AU'
                local = request('127.0.0.1', headers=forged)
                assert local.status == 200
                assert local.getheader('Echo-X-Real-IP') == '127.0.0.1'
                assert local.getheader('Echo-X-Forwarded-For') == '127.0.0.1'
                assert local.getheader('Echo-CF-Connecting-IP') is None
                assert local.getheader('Echo-CF-IPCountry') is None
                assert request('127.0.0.3', headers=forged).status == 403
                admin = request('127.0.0.2', '/wp-admin')
                assert admin.status == 308
                assert admin.getheader('Location') == 'https://zakspov.com/wp-admin/'
                request('127.0.0.2', '/payment-success/?key=private-log-marker&session_id=private-log-marker',
                        {'Referer': 'https://example.com/?key=private-log-marker'})
                process.terminate()
                process.wait(timeout=10)
                logged = access_log.read_text()
                assert 'private-log-marker' not in logged, 'Sensitive query/header leaked into access log'
                entries = [json.loads(line) for line in logged.splitlines()]
                assert any(entry['request']['uri'] == '/payment-success/' for entry in entries)
                assert stat.S_IMODE(access_log.stat().st_mode) == 0o600
                print(f'Proxy tests passed ({len(cases)} cache scenarios; trusted IP, spoofing, origin denial, admin redirect and log redaction).')
            finally:
                process.terminate()
                process.wait(timeout=10)
finally:
    upstream.shutdown()
    upstream.server_close()
