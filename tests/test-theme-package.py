#!/usr/bin/env python3
import hashlib
import importlib.util
from pathlib import Path
import tempfile
import unittest
import zipfile

spec = importlib.util.spec_from_file_location('builder', Path(__file__).resolve().parents[1] / 'scripts/build-theme.py')
builder = importlib.util.module_from_spec(spec)
spec.loader.exec_module(builder)


class PackageTests(unittest.TestCase):
    def test_excludes_development_and_sensitive_files(self):
        for path in ['test-payment.php', 'debug-provider.php', 'create-pages-menus.php', '.env', 'vendor/autoload.php', 'includes/admin/settings.php.backup', 'includes/admin/class-settings-backup.php', 'includes/admin/class-settings-broken.php', 'database.sql', 'error.log', '../outside.php']:
            with self.subTest(path=path):
                self.assertFalse(builder.include_path(path))
        for path in ['assets/vendor/css/bootstrap.min.css', 'tools/payment-audit.php', 'includes/flowguard-effects.php']:
            self.assertTrue(builder.include_path(path))

    def test_deterministic_archive_preserves_sources(self):
        with tempfile.TemporaryDirectory() as directory:
            theme = Path(directory) / 'theme'
            paths = builder.REQUIRED + ['assets/css/' + name for name in builder.CSS_SOURCES]
            for name in paths:
                file = theme / name
                file.parent.mkdir(parents=True, exist_ok=True)
                file.write_text('/* fixture */')
            (theme / 'style.css').write_text('Version: 1.0.37\n')
            (theme / 'functions.php').write_text("<?php define('FLEXPRESS_VERSION', '1.0.37');")
            (theme / 'untracked-secret.php').write_text('MUST NOT SHIP')
            before = {name:(theme / name).read_bytes() for name in paths}
            version, files = builder.assemble(theme, paths, 'synthetic-commit')
            self.assertEqual(version, '1.0.37')
            self.assertNotIn('untracked-secret.php', files)
            self.assertIn('assets/css/combined.css', files)
            first, second = Path(directory) / 'one.zip', Path(directory) / 'two.zip'
            builder.write_zip(first, files)
            builder.write_zip(second, files)
            self.assertEqual(hashlib.sha256(first.read_bytes()).digest(), hashlib.sha256(second.read_bytes()).digest())
            with zipfile.ZipFile(first) as archive:
                self.assertIsNone(archive.testzip())
                self.assertTrue(all(name.startswith('flexpress/') for name in archive.namelist()))
            self.assertEqual(before, {name:(theme / name).read_bytes() for name in paths})
            (theme / 'functions.php').write_text("<?php define('FLEXPRESS_VERSION', '1.0.36');")
            with self.assertRaisesRegex(ValueError, 'disagree'):
                builder.assemble(theme, paths, 'synthetic-commit')


if __name__ == '__main__':
    unittest.main()
