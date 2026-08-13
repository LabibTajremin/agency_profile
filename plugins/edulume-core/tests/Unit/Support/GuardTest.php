<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Support;

use Edulume\Core\Domain\Support\Guard;
use Edulume\Core\Domain\Theming\ThemeMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GuardTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function stringProvider(): array
    {
        return [
            'a string passes through' => ['inter', 'inter'],
            'an int becomes its digits' => [42, '42'],
            'a float becomes its digits' => [1.5, '1.5'],
            'an array falls back' => [['inter'], 'fallback'],
            'null falls back' => [null, 'fallback'],
            'a bool falls back' => [true, 'fallback'],
        ];
    }

    #[Test]
    #[DataProvider('stringProvider')]
    public function it_coerces_to_a_string(mixed $value, string $expected): void
    {
        $this->assertSame($expected, Guard::toString($value, 'fallback'));
    }

    /**
     * @return array<string, array{mixed, int}>
     */
    public static function intProvider(): array
    {
        return [
            'an int passes through' => [42, 42],
            'a float rounds' => [41.6, 42],
            'a numeric string parses' => ['42', 42],
            'a padded numeric string parses' => ['  42.4 ', 42],
            'true is one' => [true, 1],
            'false is zero' => [false, 0],
            'a word falls back' => ['forty two', 7],
            'an infinite float falls back' => [INF, 7],
            'null falls back' => [null, 7],
            'an array falls back' => [[42], 7],
        ];
    }

    #[Test]
    #[DataProvider('intProvider')]
    public function it_coerces_to_an_int(mixed $value, int $expected): void
    {
        $this->assertSame($expected, Guard::toInt($value, 7));
    }

    #[Test]
    public function it_clamps_an_int_into_a_range(): void
    {
        $this->assertSame(10, Guard::toInt(4, 0, 10, 20));
        $this->assertSame(20, Guard::toInt(99, 0, 10, 20));
        $this->assertSame(15, Guard::toInt(15, 0, 10, 20));
        $this->assertSame(4, Guard::toInt(4, 0, null, 20));
        $this->assertSame(99, Guard::toInt(99, 0, 10, null));
    }

    /**
     * @return array<string, array{mixed, float}>
     */
    public static function floatProvider(): array
    {
        return [
            'a float passes through' => [1.5, 1.5],
            'an int widens' => [2, 2.0],
            'a numeric string parses' => ['1.5', 1.5],
            'true is one' => [true, 1.0],
            'a word falls back' => ['one point five', 0.25],
            'a NAN falls back' => [NAN, 0.25],
            'null falls back' => [null, 0.25],
        ];
    }

    #[Test]
    #[DataProvider('floatProvider')]
    public function it_coerces_to_a_float(mixed $value, float $expected): void
    {
        $this->assertSame($expected, Guard::toFloat($value, 0.25));
    }

    #[Test]
    public function it_clamps_a_float_into_a_range(): void
    {
        $this->assertSame(0.5, Guard::toFloat(0.1, 0.0, 0.5, 3.0));
        $this->assertSame(3.0, Guard::toFloat(9.0, 0.0, 0.5, 3.0));
        $this->assertSame(0.1, Guard::toFloat(0.1, 0.0, null, 3.0));
        $this->assertSame(9.0, Guard::toFloat(9.0, 0.0, 0.5, null));
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function boolProvider(): array
    {
        return [
            'true passes through' => [true, true],
            'false passes through' => [false, false],
            'one is true' => [1, true],
            'zero is false' => [0, false],
            'the word true' => ['TRUE', true],
            'the word yes' => ['yes', true],
            'the word on' => ['on', true],
            'the string one' => ['1', true],
            'the word false' => ['false', false],
            'the word no' => ['no', false],
            'the word off' => ['off', false],
            'the string zero' => ['0', false],
            'an empty string' => ['', false],
            'a word falls back' => ['perhaps', true],
            'null falls back' => [null, true],
            'an array falls back' => [[], true],
        ];
    }

    #[Test]
    #[DataProvider('boolProvider')]
    public function it_coerces_to_a_bool(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Guard::toBool($value, true));
    }

    #[Test]
    public function it_coerces_to_an_array(): void
    {
        $this->assertSame(['a' => 1], Guard::toArray(['a' => 1]));
        $this->assertSame([], Guard::toArray('not an array'));
    }

    #[Test]
    public function it_keeps_only_strings_in_a_string_list(): void
    {
        $this->assertSame(['a', 'b'], Guard::toStringList(['a', 1, 'b', null, ['c']]));
        $this->assertSame([], Guard::toStringList('not a list'));
    }

    #[Test]
    public function it_coerces_to_an_enum_case(): void
    {
        $this->assertSame(ThemeMode::Dark, Guard::toEnum(ThemeMode::class, 'dark', ThemeMode::Light));
        $this->assertSame(ThemeMode::Dark, Guard::toEnum(ThemeMode::class, ThemeMode::Dark, ThemeMode::Light));
        $this->assertSame(ThemeMode::Light, Guard::toEnum(ThemeMode::class, 'ultraviolet', ThemeMode::Light));
        $this->assertSame(ThemeMode::Light, Guard::toEnum(ThemeMode::class, 7, ThemeMode::Light));
        $this->assertSame(ThemeMode::Light, Guard::toEnum(ThemeMode::class, ['dark'], ThemeMode::Light));
    }

    #[Test]
    public function it_keeps_only_recognised_cases_in_an_enum_list(): void
    {
        $list = Guard::toEnumList(ThemeMode::class, ['dark', 'ultraviolet', ThemeMode::Light, 3, ['dark']]);

        $this->assertSame([ThemeMode::Dark, ThemeMode::Light], $list);
        $this->assertSame([], Guard::toEnumList(ThemeMode::class, 'dark'));
    }

    #[Test]
    public function it_keeps_only_string_keys_in_a_bool_map(): void
    {
        $this->assertSame(['fade-in' => true, 'marquee' => false], Guard::toBoolMap([
            'fade-in' => 'yes',
            'marquee' => 0,
            7 => true,
        ]));
        $this->assertSame([], Guard::toBoolMap(null));
    }

    #[Test]
    public function it_keeps_only_string_pairs_in_a_string_map(): void
    {
        $this->assertSame(['code' => 'jetbrains-mono'], Guard::toStringMap([
            'code' => 'jetbrains-mono',
            'body' => 7,
            9 => 'inter',
        ]));
        $this->assertSame([], Guard::toStringMap(false));
    }
}
