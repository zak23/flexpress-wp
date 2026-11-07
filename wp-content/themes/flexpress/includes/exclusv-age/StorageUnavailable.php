<?php

declare(strict_types=1);

namespace FlexPress\Age;

/** Do not include SQL, payloads or caught exceptions in browser/log messages. */
final class StorageUnavailable extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Age session storage is unavailable. Please start again.');
    }
}
