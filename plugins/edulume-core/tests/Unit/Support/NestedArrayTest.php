<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Support;

use Edulume\Core\Domain\Support\NestedArray;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NestedArray::class)]
final class NestedArrayTest extends TestCase
{
    public function testEditingOneNestedFieldLeavesItsSiblingsAlone(): void
    {
        $defaults = [
            'hero' => [
                'headline' => 'Study abroad',
                'subheadline' => 'Honest advice',
                'cta' => ['label' => 'Start', 'url' => '/start/'],
            ],
        ];

        $merged = NestedArray::merge($defaults, ['hero' => ['headline' => 'Mine']]);

        self::assertSame('Mine', $merged['hero']['headline']);
        self::assertSame('Honest advice', $merged['hero']['subheadline']);
        self::assertSame(['label' => 'Start', 'url' => '/start/'], $merged['hero']['cta']);
    }

    public function testAStoredListReplacesTheDefaultListWholesale(): void
    {
        $defaults = ['services' => ['items' => [['title' => 'One'], ['title' => 'Two'], ['title' => 'Three']]]];

        $merged = NestedArray::merge($defaults, ['services' => ['items' => [['title' => 'Only']]]]);

        self::assertSame([['title' => 'Only']], $merged['services']['items']);
    }

    public function testAnEmptyStoredListClearsTheDefault(): void
    {
        $merged = NestedArray::merge(['items' => [1, 2, 3]], ['items' => []]);

        self::assertSame([], $merged['items']);
    }

    public function testAStoredScalarReplacesADefaultArray(): void
    {
        $merged = NestedArray::merge(['hero' => ['a' => 1]], ['hero' => 'off']);

        self::assertSame('off', $merged['hero']);
    }

    public function testAStoredArrayReplacesADefaultScalar(): void
    {
        $merged = NestedArray::merge(['hero' => 'off'], ['hero' => ['a' => 1]]);

        self::assertSame(['a' => 1], $merged['hero']);
    }

    public function testStoredKeysWithNoDefaultSurvive(): void
    {
        $merged = NestedArray::merge(['a' => 1], ['b' => 2]);

        self::assertSame(['a' => 1, 'b' => 2], $merged);
    }

    public function testReadsADotPath(): void
    {
        $data = ['hero' => ['cta' => ['label' => 'Start']]];

        self::assertSame('Start', NestedArray::get($data, 'hero.cta.label'));
    }

    public function testReadsAListIndex(): void
    {
        $data = ['items' => [['title' => 'One'], ['title' => 'Two']]];

        self::assertSame('Two', NestedArray::get($data, 'items.1.title'));
    }

    public function testAnEmptyPathReturnsEverything(): void
    {
        self::assertSame(['a' => 1], NestedArray::get(['a' => 1], ''));
    }

    public function testAMissingPathReturnsTheFallback(): void
    {
        self::assertSame('none', NestedArray::get(['a' => 1], 'a.b.c', 'none'));
        self::assertSame('none', NestedArray::get(['a' => ['b' => 1]], 'a.z', 'none'));
        self::assertNull(NestedArray::get([], 'anything'));
    }
}
