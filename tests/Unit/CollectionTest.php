<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Support\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    public function testMapsFiltersAndPreservesKeys(): void
    {
        $collection = Collection::make([1, 2, 3, 4])
            ->map(static fn (int $value): int => $value * 2)
            ->filter(static fn (int $value): bool => $value > 4);

        self::assertSame([2 => 6, 3 => 8], $collection->all());
    }

    public function testPlucksGroupsAndSerializes(): void
    {
        $collection = Collection::make([
            ['team' => 'A', 'name' => 'One'],
            ['team' => 'B', 'name' => 'Two'],
            ['team' => 'A', 'name' => 'Three'],
        ]);

        self::assertSame(['One', 'Two', 'Three'], $collection->pluck('name')->values()->all());
        self::assertSame(['A', 'B'], $collection->groupBy('team')->keys()->all());
        self::assertSame($collection->all(), json_decode(json_encode($collection), true));
    }

    public function testSupportsArrayAccessAndMutation(): void
    {
        $collection = new Collection(['first' => 1]);
        $collection['second'] = 2;

        self::assertTrue($collection->has('first'));
        self::assertSame(2, $collection->get('second'));
        self::assertSame(2, $collection->pop());
        self::assertSame(1, $collection->first());
    }
}
