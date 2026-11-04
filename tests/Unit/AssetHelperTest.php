<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AssetHelperTest extends TestCase
{
    public function testPrefixesLocalAssetsWithConfiguredAssetUrl(): void
    {
        $hadValue = array_key_exists('ASSET_URL', $_ENV);
        $previous = $_ENV['ASSET_URL'] ?? null;
        $_ENV['ASSET_URL'] = 'https://cdn.example.test/static/';

        try {
            self::assertSame(
                'https://cdn.example.test/static/images/logo.svg',
                asset('/images/logo.svg')
            );
        } finally {
            if ($hadValue) {
                $_ENV['ASSET_URL'] = $previous;
            } else {
                unset($_ENV['ASSET_URL']);
            }
        }
    }

    public function testAppUrlPrefersConfiguredAbsoluteUrl(): void
    {
        $hadValue = array_key_exists('APP_URL', $_ENV);
        $previous = $_ENV['APP_URL'] ?? null;
        $_ENV['APP_URL'] = 'https://app.example.test/base/';

        try {
            self::assertSame('https://app.example.test/base', app_url());
        } finally {
            if ($hadValue) {
                $_ENV['APP_URL'] = $previous;
            } else {
                unset($_ENV['APP_URL']);
            }
        }
    }

    public function testRejectsInvalidConfiguredAppUrl(): void
    {
        $hadValue = array_key_exists('APP_URL', $_ENV);
        $previous = $_ENV['APP_URL'] ?? null;
        $_ENV['APP_URL'] = 'javascript:alert(1)';

        try {
            $this->expectException(\RuntimeException::class);
            app_url();
        } finally {
            if ($hadValue) {
                $_ENV['APP_URL'] = $previous;
            } else {
                unset($_ENV['APP_URL']);
            }
        }
    }

    public function testLeavesAbsoluteAssetUrlsUnchanged(): void
    {
        self::assertSame(
            'https://images.example.test/logo.svg',
            asset('https://images.example.test/logo.svg')
        );
    }
}
