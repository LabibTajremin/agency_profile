<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Color;

use Edulume\Core\Domain\Color\ContrastRequirement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContrastRequirementTest extends TestCase
{
    /**
     * @return array<string, array{ContrastRequirement, float}>
     */
    public static function thresholdProvider(): array
    {
        return [
            'normal text AA' => [ContrastRequirement::NormalTextAa, 4.5],
            'large text AA' => [ContrastRequirement::LargeTextAa, 3.0],
            'non text' => [ContrastRequirement::NonText, 3.0],
            'normal text AAA' => [ContrastRequirement::NormalTextAaa, 7.0],
            'large text AAA' => [ContrastRequirement::LargeTextAaa, 4.5],
        ];
    }

    #[Test]
    #[DataProvider('thresholdProvider')]
    public function it_states_the_wcag_threshold(ContrastRequirement $requirement, float $threshold): void
    {
        $this->assertSame($threshold, $requirement->threshold());
    }

    #[Test]
    public function it_accepts_a_ratio_exactly_on_the_threshold(): void
    {
        $this->assertTrue(ContrastRequirement::NormalTextAa->isSatisfiedBy(4.5));
    }

    #[Test]
    public function it_rejects_a_ratio_just_below_the_threshold(): void
    {
        $this->assertFalse(ContrastRequirement::NormalTextAa->isSatisfiedBy(4.49));
    }
}
