<?php

declare(strict_types=1);

namespace Ds\Events;

interface Subscriber
{
    /**
     * Return daftar event => handler.
     */
    public static function subscribe(): array;
}


