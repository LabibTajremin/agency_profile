<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Performance;

use Edulume\Core\Domain\Accessibility\AccessibilityRules;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Performance\BudgetBreach;
use Edulume\Core\Domain\Performance\PerformanceBudget;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BudgetAndAccessibilityTest extends TestCase
{
    /**
     * @return array<string, int|float>
     */
    private function passingRun(): array
    {
        return [
            'score' => 96,
            'lcp-ms' => 1400,
            'cls' => 0.01,
            'inp-ms' => 120,
            'js-bytes' => 80 * 1024,
            'css-bytes' => 40 * 1024,
            'font-bytes' => 60 * 1024,
            'requests' => 30,
        ];
    }

    #[Test]
    public function a_run_inside_every_threshold_passes(): void
    {
        $report = PerformanceBudget::evaluate($this->passingRun());

        self::assertTrue($report->isWithinBudget());
        self::assertSame('Within budget.', $report->describe());
        self::assertSame([], $report->breachedMetrics());
    }

    #[Test]
    public function a_run_exactly_on_every_threshold_passes(): void
    {
        $report = PerformanceBudget::evaluate(PerformanceBudget::thresholds());

        self::assertTrue($report->isWithinBudget());
    }

    #[Test]
    public function a_slower_page_breaches_and_says_by_how_much(): void
    {
        $report = PerformanceBudget::evaluate([...$this->passingRun(), 'lcp-ms' => 3200]);

        self::assertFalse($report->isWithinBudget());
        self::assertSame(['lcp-ms'], $report->breachedMetrics());
        self::assertSame('lcp-ms is 3200 against a budget of 2000', $report->describe());
    }

    #[Test]
    public function a_lower_score_breaches_while_a_lower_cost_does_not(): void
    {
        self::assertSame(['score'], PerformanceBudget::evaluate([...$this->passingRun(), 'score' => 61])->breachedMetrics());
        self::assertTrue(PerformanceBudget::evaluate([...$this->passingRun(), 'js-bytes' => 1])->isWithinBudget());
        self::assertTrue(PerformanceBudget::isHigherBetter('score'));
        self::assertFalse(PerformanceBudget::isHigherBetter('lcp-ms'));
    }

    #[Test]
    public function a_metric_the_run_never_produced_counts_as_a_breach(): void
    {
        $measurements = $this->passingRun();

        unset($measurements['inp-ms']);

        $report = PerformanceBudget::evaluate($measurements);

        self::assertFalse($report->isWithinBudget());
        self::assertSame('inp-ms was not measured (budget 200)', $report->describe());
    }

    #[Test]
    public function an_empty_run_breaches_every_metric_rather_than_passing(): void
    {
        $report = PerformanceBudget::evaluate([]);

        self::assertSame(array_keys(PerformanceBudget::thresholds()), $report->breachedMetrics());
    }

    #[Test]
    public function a_fractional_threshold_is_reported_readably(): void
    {
        $breach = new BudgetBreach('cls', 0.25, PerformanceBudget::CLS);

        self::assertSame('cls is 0.25 against a budget of 0.05', $breach->describe());
        self::assertTrue($breach->wasMeasured());
        self::assertFalse((new BudgetBreach('cls', null, PerformanceBudget::CLS))->wasMeasured());
    }

    #[Test]
    public function the_focus_ring_is_checked_against_the_surface_behind_it(): void
    {
        $rules = new AccessibilityRules();
        $ring = Srgb::fromHex('#1b5fa8');

        self::assertTrue($rules->focusRingIsVisible($ring, Srgb::fromHex('#ffffff')));
        self::assertFalse($rules->focusRingIsVisible($ring, Srgb::fromHex('#123a6b')));
    }

    #[Test]
    public function body_text_needs_more_contrast_than_large_text(): void
    {
        $rules = new AccessibilityRules();
        $ink = Srgb::fromHex('#767676');
        $surface = Srgb::fromHex('#ffffff');

        self::assertTrue($rules->textIsLegible($ink, $surface));
        self::assertTrue($rules->textIsLegible(Srgb::fromHex('#949494'), $surface, true));
        self::assertFalse($rules->textIsLegible(Srgb::fromHex('#949494'), $surface));
    }

    /**
     * @return list<array{int, int, bool}>
     */
    public static function touchTargets(): array
    {
        return [
            [44, 44, true],
            [48, 48, true],
            [200, 20, false],
            [20, 200, false],
            [43, 44, false],
        ];
    }

    #[Test]
    #[DataProvider('touchTargets')]
    public function a_touch_target_must_clear_the_floor_on_both_axes(int $width, int $height, bool $expected): void
    {
        self::assertSame($expected, AccessibilityRules::targetIsBigEnough($width, $height));
    }

    #[Test]
    public function a_heading_sequence_starts_at_one_and_skips_nothing(): void
    {
        self::assertTrue(AccessibilityRules::headingOrderIsValid([1, 2, 2, 3, 2]));
        self::assertTrue(AccessibilityRules::headingOrderIsValid([1]));
        self::assertFalse(AccessibilityRules::headingOrderIsValid([1, 2, 4]));
        self::assertFalse(AccessibilityRules::headingOrderIsValid([2, 3]));
        self::assertFalse(AccessibilityRules::headingOrderIsValid([1, 2, 1]));
        self::assertFalse(AccessibilityRules::headingOrderIsValid([]));
    }

    #[Test]
    public function every_template_must_carry_the_three_landmarks(): void
    {
        self::assertSame(['banner', 'main', 'contentinfo'], AccessibilityRules::requiredLandmarks());
        self::assertSame([], AccessibilityRules::missingLandmarks(['banner', 'main', 'contentinfo', 'search']));
        self::assertSame(['contentinfo'], AccessibilityRules::missingLandmarks(['banner', 'main']));
    }

    #[Test]
    public function a_decorative_image_takes_an_empty_alt_and_a_meaningful_one_does_not(): void
    {
        self::assertTrue(AccessibilityRules::altTextIsValid('', true));
        self::assertFalse(AccessibilityRules::altTextIsValid('Decorative swirl', true));
        self::assertTrue(AccessibilityRules::altTextIsValid('Students outside the library', false));
        self::assertFalse(AccessibilityRules::altTextIsValid('   ', false));
    }
}
