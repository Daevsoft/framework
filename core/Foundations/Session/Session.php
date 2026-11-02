<?php

declare(strict_types=1);

namespace Ds\Foundations\Session;

final class Session
{
    public static function user(): mixed
    {
        return \session('user');
    }

    public static function destroy(): void
    {
        \session_manager()->destroy();
    }
}
