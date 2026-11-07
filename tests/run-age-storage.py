#!/usr/bin/env python3
"""Disposable MySQL + real WordPress wpdb tests. No live mounts, data or credentials."""
import os
from pathlib import Path
import secrets
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[1]


def docker(*args, check=True):
    return subprocess.run(['docker', *args], check=check, capture_output=True, text=True)


def main():
    suffix = secrets.token_hex(5)
    network = 'flexpress-age-test-' + suffix
    database = network + '-db'
    runner = network + '-php'
    with tempfile.TemporaryDirectory(prefix='flexpress-age-test-') as temporary:
        private = Path(temporary)
        for name in ['root', 'password', 'key']:
            (private / name).write_text(secrets.token_hex(32))
            os.chmod(private / name, 0o600)
        try:
            docker('network', 'create', '--internal', network)
            docker('run', '-d', '--name', database, '--network', network, '--network-alias', 'age-db',
                   '--tmpfs', '/var/lib/mysql', '--mount', f'type=bind,src={private},dst=/run/secrets,readonly',
                   '-e', 'MYSQL_ROOT_PASSWORD_FILE=/run/secrets/root', '-e', 'MYSQL_PASSWORD_FILE=/run/secrets/password',
                   '-e', 'MYSQL_USER=age_test', '-e', 'MYSQL_DATABASE=flexpress_age_verify',
                   os.environ.get('AGE_TEST_MYSQL_IMAGE', 'mysql:8.0.43'), '--skip-log-bin')
            ready = False
            for _ in range(60):
                if docker('exec', database, 'mysqladmin', 'ping', '--protocol=tcp', '-h', '127.0.0.1', '--silent', check=False).returncode == 0:
                    ready = True
                    break
                time.sleep(1)
            if not ready:
                raise RuntimeError('Disposable database did not become ready')
            result = docker('run', '--rm', '--name', runner, '--network', network, '--entrypoint', 'php',
                            '--mount', f'type=bind,src={private},dst=/run/secrets,readonly',
                            '--mount', f'type=bind,src={ROOT / "tests"},dst=/tests,readonly',
                            '--mount', f'type=bind,src={ROOT / "wp-content/themes/flexpress"},dst=/theme,readonly',
                            os.environ.get('AGE_TEST_PHP_IMAGE', 'wordpress:php8.3-apache'),
                            '-d', 'zend.exception_ignore_args=1', '/tests/test-age-storage.php', check=False)
            print(result.stdout, end='')
            if result.returncode:
                # Test failures contain assertion names only; never dump container env or MySQL logs.
                print(result.stderr, end='')
                raise RuntimeError('Age storage verification failed')
        finally:
            docker('rm', '-f', runner, check=False)
            docker('rm', '-f', database, check=False)
            docker('network', 'rm', network, check=False)


if __name__ == '__main__':
    main()
