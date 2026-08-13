<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\CubicBezier;
use Edulume\Core\Domain\Theming\InvalidMotionException;
use Edulume\Core\Domain\Theming\MotionEasing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CubicBezierTest extends TestCase
{
    #[Test]
    public function it_emits_a_css_timing_function(): void
    {
        $this->assertSame('cubic-bezier(0.4, 0, 0.2, 1)', CubicBezier::of(0.4, 0.0, 0.2, 1.0)->toCssValue());
    }

    #[Test]
    public function it_allows_a_vertical_overshoot_so_a_spring_is_possible(): void
    {
        $this->assertSame('cubic-bezier(0.34, 1.56, 0.64, 1)', CubicBezier::of(0.34, 1.56, 0.64, 1.0)->toCssValue());
    }

    /**
     * @return array<string, array{float, float, float, float}>
     */
    public static function invalidCurveProvider(): array
    {
        return [
            'first x below zero' => [-0.1, 0.0, 0.2, 1.0],
            'first x above one' => [1.4, 0.0, 0.2, 1.0],
            'second x below zero' => [0.4, 0.0, -0.2, 1.0],
            'second x above one' => [0.4, 0.0, 1.2, 1.0],
            'first x not a number' => [NAN, 0.0, 0.2, 1.0],
            'first y infinite' => [0.4, INF, 0.2, 1.0],
            'second x not a number' => [0.4, 0.0, NAN, 1.0],
            'second y infinite' => [0.4, 0.0, 0.2, -INF],
        ];
    }

    #[Test]
    #[DataProvider('invalidCurveProvider')]
    public function it_rejects_a_curve_css_would_drop(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->expectException(InvalidMotionException::class);

        CubicBezier::of($x1, $y1, $x2, $y2);
    }

    #[Test]
    #[DataProvider('invalidCurveProvider')]
    public function it_reports_an_invalid_curve_before_anyone_tries_to_build_it(
        float $x1,
        float $y1,
        float $x2,
        float $y2
    ): void {
        $this->assertFalse(CubicBezier::isValid($x1, $y1, $x2, $y2));
    }

    #[Test]
    public function it_accepts_a_curve_css_would_honour(): void
    {
        $this->assertTrue(CubicBezier::isValid(0.4, 0.0, 0.2, 1.0));
        $this->assertTrue(CubicBezier::isValid(0.0, -0.5, 1.0, 1.5));
    }

    /**
     * @return array<string, array{MotionEasing}>
     */
    public static function easingProvider(): array
    {
        $cases = [];

        foreach (MotionEasing::cases() as $easing) {
            $cases[$easing->value] = [$easing];
        }

        return $cases;
    }

    #[Test]
    public function it_offers_six_named_curves(): void
    {
        $this->assertCount(6, MotionEasing::cases());
    }

    #[Test]
    #[DataProvider('easingProvider')]
    public function it_expresses_every_named_curve_as_a_valid_bezier(MotionEasing $easing): void
    {
        $this->assertStringStartsWith('cubic-bezier(', $easing->toCubicBezier()->toCssValue());
    }
}
