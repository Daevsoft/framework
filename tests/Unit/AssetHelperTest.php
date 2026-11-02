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

    public function testLeavesAbsoluteAssetUrlsUnchanged(): void
    {
        self::assertSame(
            'https://images.example.test/logo.svg',
            asset('https://images.example.test/logo.svg')
        );
    }
}
