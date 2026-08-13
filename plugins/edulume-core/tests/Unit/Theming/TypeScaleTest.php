<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\TypeScale;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypeScaleTest extends TestCase
{
    private const CLAMP_PATTERN = '/^clamp\(-?\d+(\.\d+)?rem, -?\d+(\.\d+)?rem [+-] \d+(\.\d+)?vw, -?\d+(\.\d+)?rem\)$/';

    /**
     * @return array<string, array{float}>
     */
    public static function ratioProvider(): array
    {
        return [
            'minor second' => [1.067],
            'minor third' => [1.2],
            'major third' => [1.25],
            'perfect fourth' => [1.333],
            'golden' => [1.618],
        ];
    }

    #[Test]
    #[DataProvider('ratioProvider')]
    public function it_emits_valid_css_for_every_step(float $ratio): void
    {
        foreach (TypeScale::of($ratio)->toClampMap() as $step => $size) {
            if ($step === 0) {
                $this->assertMatchesRegularExpression('/^\d+(\.\d+)?rem$/', $size);
                continue;
            }

            $this->assertMatchesRegularExpression(
                self::CLAMP_PATTERN,
                $size,
                sprintf('Step %d of ratio %.3f emitted invalid CSS: %s', $step, $ratio, $size),
            );
        }
    }

    #[Test]
    #[DataProvider('ratioProvider')]
    public function it_grows_monotonically_across_steps(float $ratio): void
    {
        $scale = TypeScale::of($ratio);

        for ($step = TypeScale::SMALLEST_STEP + 1; $step <= TypeScale::LARGEST_STEP; $step++) {
            $this->assertGreaterThan($scale->maximumRemAt($step - 1), $scale->maximumRemAt($step));
            $this->assertGreaterThan($scale->minimumRemAt($step - 1), $scale->minimumRemAt($step));
        }
    }

    #[Test]
    public function it_damps_the_ratio_at_the_narrow_end_of_the_viewport(): void
    {
        $scale = TypeScale::of(1.5);

        $this->assertLessThan($scale->ratio, $scale->mobileRatio());
        $this->assertGreaterThan(1.0, $scale->mobileRatio());
    }

    #[Test]
    public function it_keeps_a_damped_headline_smaller_on_a_phone_than_on_a_desktop(): void
    {
        $scale = TypeScale::of(1.5);

        $this->assertLessThan($scale->maximumRemAt(4), $scale->minimumRemAt(4));
    }

    #[Test]
    public function it_returns_the_base_size_unchanged_at_step_zero(): void
    {
        $this->assertSame('1rem', TypeScale::of(1.25)->clampAt(0));
        $this->assertSame(1.0, TypeScale::of(1.25)->maximumRemAt(0));
    }

    #[Test]
    public function it_orders_the_clamp_bounds_below_the_base_step(): void
    {
        $size = TypeScale::of(1.5)->clampAt(-2);

        preg_match('/^clamp\((-?[\d.]+)rem, .+, (-?[\d.]+)rem\)$/', $size, $matches);

        $this->assertCount(3, $matches, sprintf('Step -2 emitted %s', $size));
        $this->assertLessThan((float) $matches[2], (float) $matches[1]);
    }

    #[Test]
    public function it_uses_a_falling_viewport_term_below_the_base_step(): void
    {
        $this->assertStringContainsString(' - ', TypeScale::of(1.5)->clampAt(-2));
        $this->assertStringContainsString(' + ', TypeScale::of(1.5)->clampAt(2));
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function outOfRangeRatioProvider(): array
    {
        return [
            'below the floor' => [0.5, TypeScale::MINIMUM_RATIO],
            'above the ceiling' => [4.0, TypeScale::MAXIMUM_RATIO],
            'inside the range' => [1.333, 1.333],
        ];
    }

    #[Test]
    #[DataProvider('outOfRangeRatioProvider')]
    public function it_coerces_the_ratio_into_the_supported_range(float $given, float $expected): void
    {
        $this->assertSame($expected, TypeScale::of($given)->ratio);
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function outOfRangeBaseProvider(): array
    {
        return [
            'below the floor' => [0.2, TypeScale::MINIMUM_BASE_REM],
            'above the ceiling' => [3.0, TypeScale::MAXIMUM_BASE_REM],
            'inside the range' => [1.125, 1.125],
        ];
    }

    #[Test]
    #[DataProvider('outOfRangeBaseProvider')]
    public function it_coerces_the_base_size_into_the_supported_range(float $given, float $expected): void
    {
        $this->assertSame($expected, TypeScale::of(1.25, $given)->baseSizeRem);
    }

    #[Test]
    public function it_covers_every_step_from_smallest_to_largest(): void
    {
        $sizes = TypeScale::of(1.25)->toClampMap();

        $this->assertArrayHasKey(TypeScale::SMALLEST_STEP, $sizes);
        $this->assertArrayHasKey(TypeScale::LARGEST_STEP, $sizes);
        $this->assertCount(TypeScale::LARGEST_STEP - TypeScale::SMALLEST_STEP + 1, $sizes);
    }
}
