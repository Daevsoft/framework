<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Ds\Helper\Date;
use Ds\Helper\Str;
use PHPUnit\Framework\TestCase;

final class LegacyHelperTest extends TestCase
{
    public function testLegacyStringHelpersAreAvailableWithoutThirdPartyDependencies(): void
    {
        self::assertTrue(Str::contains('Ds Framework', 'framework'));
        self::assertSame('Hi DS', Str::replace('Hello World', ['Hello' => 'Hi', 'World' => 'DS']));
        self::assertTrue(Str::empty(''));
        self::assertFalse(Str::empty('0'));
        self::assertTrue(Str::nullEmpty(null));
        self::assertTrue(Str::nullEmpty(''));
        self::assertFalse(Str::nullEmpty('0'));
    }

    public function testLegacyDateTimestampReturnsUnixSeconds(): void
    {
        $before = time();
        $timestamp = Date::timestamp();
        $after = time();

        self::assertGreaterThanOrEqual($before, $timestamp);
        self::assertLessThanOrEqual($after, $timestamp);
    }
}
