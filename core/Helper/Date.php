<?php

declare(strict_types=1);

namespace Ds\Helper;

/** Small v3-compatible date helper. */
final class Date
{
    public static function timestamp(): int
    {
        return time();
    }
}
