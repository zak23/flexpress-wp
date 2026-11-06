#!/usr/bin/env python3
"""Exercise the repository Caddyfile against a synthetic upstream, never WordPress.

Requires Python 3 and the deployed Caddy binary. Example:
  python3 tests/test-proxy-cache.py --bind 192.168.1.248 --config Caddyfile
"""
import argparse
import http.client
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
import socket
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
        if 'cookie' in self.path:
            self.send_header('Set-Cookie', 'session=example; HttpOnly')
        if status == 302:
            self.send_header('Location', '/login/')
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
source = source.replace('output file /var/log/caddy/flexpress.log', 'output discard')
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
        config.write_text(source)
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
                    connection = http.client.HTTPConnection(args.bind, proxy_port, timeout=5)
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
                print(f'Proxy cache tests passed ({len(cases)} response scenarios).')
            finally:
                process.terminate()
                process.wait(timeout=10)
finally:
    upstream.shutdown()
    upstream.server_close()
