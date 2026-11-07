<?php

declare(strict_types=1);

namespace FlexPress\Age;

/** A private wpdb connection: no shared payment transaction and no credential-bearing error page. */
final class Database extends \wpdb
{
    public function db_connect($allow_bail = true)
    {
        try {
            // WordPress normally renders a database error page here. Let the age boundary deny instead.
            if (!@parent::db_connect(false)) {
                throw new StorageUnavailable();
            }
            $this->suppress_errors(true);
            return true;
        } catch (\Throwable) {
            throw new StorageUnavailable();
        }
    }

    public function bail($message, $error_code = '500')
    {
        throw new StorageUnavailable();
    }

    public function print_error($str = '')
    {
        // Preserve last_error for callers, but never render or log a query from this private store.
        return false;
    }
}
