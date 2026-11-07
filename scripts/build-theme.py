#!/usr/bin/env python3
"""Build an uploadable theme from committed files, without modifying the checkout."""
import argparse
import hashlib
import json
from pathlib import Path, PurePosixPath
import re
import subprocess
import tempfile
import zipfile

PREFIX = 'wp-content/themes/flexpress/'
CSS_SOURCES = ['variables.css', 'main.css', 'gallery.css', 'age-verification.css', 'casting-section.css', 'join-now-cta.css']
REQUIRED = ['style.css', 'functions.php', 'index.php', 'includes/flowguard-postback-processing.php', 'includes/flowguard-effects.php', 'includes/flowguard-webhook-handler.php', 'includes/flowguard-integration.php', 'tools/payment-audit.php', 'assets/vendor/css/bootstrap.min.css', 'assets/vendor/css/font-awesome.min.css']
REQUIRED += ['README.md', 'AGENTS.md', 'HOSTING.md', 'UPLOAD-CHECKLIST.md', 'hosting/Caddyfile.example']


def include_path(name):
    path = PurePosixPath(name)
    if path.is_absolute() or '..' in path.parts:
        return False
    if any(part.startswith('.') for part in path.parts):
        return name == '.htaccess'
    if path.parts[0] in ('vendor', 'node_modules', 'tests'):
        return False
    if name.startswith(('assets/dist/', 'assets/build/')):
        return False
    if path.name.startswith(('test-', 'debug-')) or path.name in ('create-pages-menus.php', 'reset-menus.php', 'collection-editor.php', 'wordpress-stubs.php', 'stubs.php', 'composer.json', 'composer.lock', 'phpcs.xml', 'phpstan.neon', 'combined.css'):
        return False
    if re.search(r'(-backup|-broken|\.(bak|backup|old|orig|sql|log|zip|wpress|env|swp))($|\.)', path.name, re.I):
        return False
    return True


def assemble(theme, paths, revision):
    files = {}
    for name in sorted(paths):
        if not include_path(name):
            continue
        source = theme / name
        if source.is_symlink() or not source.resolve().is_relative_to(theme.resolve()):
            raise ValueError('Refusing linked or escaping release path: ' + name)
        files[name] = source.read_bytes()
    for name in REQUIRED:
        if name not in files:
            raise ValueError('Required runtime file missing: ' + name)
    version_match = re.search(rb'^Version:\s*(\d+\.\d+\.\d+)\s*$', files['style.css'], re.M)
    if not version_match:
        raise ValueError('No valid theme version')
    version = version_match.group(1).decode()
    if not re.search(rb"define\('FLEXPRESS_VERSION',\s*'" + re.escape(version.encode()) + rb"'\)", files['functions.php']):
        raise ValueError('style.css and FLEXPRESS_VERSION disagree')
    combined = b'/* FlexPress release CSS; generated from committed source files. */\n'
    for name in CSS_SOURCES:
        combined += ('/* ' + name + ' */\n').encode() + files['assets/css/' + name] + b'\n'
    files['assets/css/combined.css'] = combined
    manifest = {'theme': 'flexpress', 'version': version, 'commit': revision,
                'sha256': {name: hashlib.sha256(data).hexdigest() for name, data in sorted(files.items())}}
    files['release-manifest.json'] = (json.dumps(manifest, indent=2, sort_keys=True) + '\n').encode()
    return version, files


def write_zip(output, files):
    output.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.NamedTemporaryFile(dir=output.parent, suffix='.zip', delete=False) as temporary:
        candidate = Path(temporary.name)
    try:
        with zipfile.ZipFile(candidate, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
            for name, data in sorted(files.items()):
                info = zipfile.ZipInfo('flexpress/' + name, date_time=(2026, 11, 7, 0, 0, 0))
                info.compress_type = zipfile.ZIP_DEFLATED
                info.external_attr = 0o100644 << 16
                archive.writestr(info, data)
        with zipfile.ZipFile(candidate) as archive:
            if archive.testzip() is not None:
                raise ValueError('ZIP integrity check failed')
        candidate.replace(output)
    finally:
        candidate.unlink(missing_ok=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    root = Path(__file__).resolve().parent.parent
    theme = root / PREFIX
    dirty = subprocess.check_output(['git', '-C', str(root), 'diff', '--name-only', 'HEAD', '--', PREFIX], text=True).strip()
    if dirty:
        raise SystemExit('Commit theme changes before packaging. The builder never changes theme versions.')
    tracked = subprocess.check_output(['git', '-C', str(root), 'ls-files', '-z', '--', PREFIX]).decode().split('\0')
    paths = [name[len(PREFIX):] for name in tracked if name]
    revision = subprocess.check_output(['git', '-C', str(root), 'rev-parse', 'HEAD'], text=True).strip()
    version, files = assemble(theme, paths, revision)
    # Validate the exact PHP bytes being shipped, including files not exercised by tests.
    for name, data in files.items():
        if name.endswith('.php'):
            result = subprocess.run(['php', '-l'], input=data, capture_output=True)
            if result.returncode:
                raise SystemExit('PHP syntax failure in ' + name + ': ' + result.stdout.decode())
    output = (args.output or root / 'dist' / ('flexpress-' + version + '.zip')).resolve()
    if output.is_relative_to((root / 'wp-content').resolve()):
        raise SystemExit('Release archives must be saved outside the web root.')
    write_zip(output, files)
    digest = hashlib.sha256(output.read_bytes()).hexdigest()
    output.with_suffix('.zip.sha256').write_text(digest + '  ' + output.name + '\n')
    print(f'Built {output} ({len(files)} files; PHP syntax and ZIP integrity verified)')
    print('SHA256: ' + digest)


if __name__ == '__main__':
    main()
