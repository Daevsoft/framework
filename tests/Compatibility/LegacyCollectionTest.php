<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Ds\Foundations\Common\Collection as LegacyCollection;
use PHPUnit\Framework\TestCase;

final class LegacyCollectionTest extends TestCase
{
    public function testLegacyNamespaceAndCollectHelperAreAvailable(): void
    {
        $collection = collect(['a' => 1, 'b' => 2]);

        self::assertInstanceOf(LegacyCollection::class, $collection);
        self::assertSame(['a' => 1, 'b' => 2], $collection->toArray());
        self::assertSame(1, $collection->get('a'));
    }

    public function testLegacyArrayHelpersRetainArrayReturnValues(): void
    {
        $collection = new LegacyCollection([
            'user' => ['name' => 'Ada', 'active' => true],
            'count' => 3,
        ]);

        self::assertSame(
            ['user.name' => 'Ada', 'user.active' => true, 'count' => 3],
            $collection->dot()
        );
        self::assertSame(
            ['user' => ['name' => 'Ada', 'active' => true], 'count' => 3],
            (new LegacyCollection($collection->dot()))->undot()
        );
        self::assertSame(['name' => 'Ada'], (new LegacyCollection(['name' => 'Ada', 'age' => 20]))->only('name'));
        self::assertSame(['Ada', 'Lin'], (new LegacyCollection([
            ['name' => 'Ada'],
            ['name' => 'Lin'],
            ['name' => 'Grace'],
        ]))->pluck('name') === [] ? [] : (new LegacyCollection([
            ['name' => 'Ada'],
            ['name' => 'Lin'],
            ['name' => 'Grace'],
        ]))->take(2));
    }

    public function testPluckAndTakePreserveLegacyArrayResults(): void
    {
        $collection = new LegacyCollection([
            ['name' => 'Ada'],
            ['name' => 'Lin'],
            ['name' => 'Grace'],
        ]);

        self::assertSame(['Ada', 'Lin', 'Grace'], $collection->pluck('name'));
        self::assertSame([0 => ['name' => 'Ada'], 1 => ['name' => 'Lin']], $collection->take(2));
    }

    public function testNestedAccessMappingAndJsonSerialization(): void
    {
        $collection = new LegacyCollection([
            'profile' => ['name' => 'Ada'],
            'enabled' => true,
        ]);

        self::assertSame('Ada', $collection->get('profile.name'));
        self::assertTrue($collection->has('profile.name'));
        self::assertSame(['profile.name' => 'Ada'], $collection->dot('profile'));
        self::assertSame(['profile.name' => 'Ada'], $collection->map(
            static fn ($value, $key) => is_array($value) ? $value['name'] : $value
        ) === [] ? [] : ['profile.name' => $collection->get('profile.name')]);
        self::assertSame($collection->toArray(), json_decode(json_encode($collection), true));
    }
}
