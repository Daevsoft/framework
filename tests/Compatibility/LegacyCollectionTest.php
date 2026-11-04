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

        $flattened = $collection->dot();
        self::assertSame(
            ['user' => ['name' => 'Ada', 'active' => true], 'count' => 3],
            (new LegacyCollection($flattened))->undot()
        );
        self::assertSame(['name' => 'Ada'], (new LegacyCollection(['name' => 'Ada', 'age' => 20]))->only('name'));
        self::assertSame('Ada', $collection->get('user.name'));
        self::assertTrue($collection->has('user.name'));
        self::assertFalse($collection->has('user.missing'));
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

    public function testLegacyMutationAndSerializationMethodsRemainConsistent(): void
    {
        $collection = new LegacyCollection(['profile' => ['name' => 'Ada'], 'unused' => null]);
        self::assertSame(['profile' => ['name' => 'Grace'], 'unused' => null], $collection->set('profile.name', 'Grace'));
        self::assertSame('Grace', $collection->get('profile.name'));
        self::assertNull($collection->pull('unused', 'fallback'));
        self::assertFalse($collection->exists('unused'));
        self::assertSame(['profile' => ['name' => 'Grace']], $collection->forget('unused'));

        $serialized = $collection->serialize();
        $restored = new LegacyCollection();
        $restored->unserialize($serialized);
        self::assertSame($collection->toArray(), $restored->toArray());
        self::assertSame('Grace', $collection->profile['name']);
        self::assertSame($collection->toArray(), $collection->__debugInfo());
    }

    public function testNestedAccessMappingAndJsonSerialization(): void
    {
        $collection = new LegacyCollection([
            'profile' => ['name' => 'Ada'],
            'enabled' => true,
        ]);

        self::assertSame('Ada', $collection->get('profile.name'));
        self::assertSame(
            ['profile' => 'Ada', 'enabled' => true],
            $collection->map(static fn ($value, $key) => is_array($value) ? $value['name'] : $value)
        );
        self::assertSame($collection->toArray(), json_decode(json_encode($collection), true));
    }}
