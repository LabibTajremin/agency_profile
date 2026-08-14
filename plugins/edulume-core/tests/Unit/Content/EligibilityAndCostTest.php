<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Content;

use Edulume\Core\Domain\Content\CostCalculator;
use Edulume\Core\Domain\Content\CostEstimate;
use Edulume\Core\Domain\Content\CourseRequirements;
use Edulume\Core\Domain\Content\EligibilityMatch;
use Edulume\Core\Domain\Content\EligibilityMatcher;
use Edulume\Core\Domain\Content\EligibilityProfile;
use Edulume\Core\Domain\Content\ExchangeRateTable;
use Edulume\Core\Domain\Content\StudyLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EligibilityAndCostTest extends TestCase
{
    /**
     * @return list<CourseRequirements>
     */
    private static function catalogue(): array
    {
        return [
            CourseRequirements::of(1, 'MSc Data Science', StudyLevel::Postgraduate, 'Canada', 65.0, 6.5, 24000),
            CourseRequirements::of(2, 'MSc Computing', StudyLevel::Postgraduate, 'UK', 60.0, 6.0, 19000),
            CourseRequirements::of(3, 'MBA', StudyLevel::Postgraduate, 'Canada', 75.0, 7.0, 42000),
            CourseRequirements::of(4, 'BA Economics', StudyLevel::Undergraduate, 'UK', 55.0, 5.5, 16000),
            CourseRequirements::of(5, 'MSc Analytics', StudyLevel::Postgraduate, 'Australia', 62.0, 6.5, 21000),
        ];
    }

    private static function profile(
        float $grade = 70.0,
        float $english = 7.0,
        int $budget = 30000,
        array $countries = []
    ): EligibilityProfile {
        return EligibilityProfile::of(StudyLevel::Undergraduate, $grade, $english, $budget, $countries);
    }

    #[Test]
    public function it_matches_a_strong_applicant_to_courses_they_can_actually_get_into(): void
    {
        $matcher = new EligibilityMatcher();
        $matches = $matcher->match(self::catalogue(), self::profile());

        $eligible = $matcher->eligibleOnly($matches);
        $titles = array_map(static fn (EligibilityMatch $match): string => $match->course->title, $eligible);

        $this->assertContains('MSc Data Science', $titles);
        $this->assertContains('MSc Computing', $titles);
        $this->assertNotContains('MBA', $titles);
    }

    #[Test]
    public function it_ranks_a_preferred_country_above_an_equivalent_course_elsewhere(): void
    {
        $matches = (new EligibilityMatcher())->match(self::catalogue(), self::profile(countries: ['Canada']));

        $this->assertNotEmpty($matches);

        foreach ($matches as $match) {
            $this->assertSame('Canada', $match->course->country);
        }
    }

    #[Test]
    public function it_prefers_the_level_the_applicant_is_actually_applying_to(): void
    {
        $matches = (new EligibilityMatcher())->match(self::catalogue(), self::profile());

        $this->assertSame(StudyLevel::Undergraduate, self::profile()->targetLevel());
        $this->assertSame('BA Economics', $matches[0]->course->title);
    }

    /**
     * The single most useful thing this tool can say. Hiding the course turns actionable
     * advice into an empty results page.
     */
    #[Test]
    public function it_reports_a_near_miss_rather_than_hiding_the_course(): void
    {
        $matcher = new EligibilityMatcher();
        $matches = $matcher->match(self::catalogue(), self::profile(grade: 63.0, english: 6.0));

        $nearMisses = $matcher->nearMisses($matches);
        $titles = array_map(static fn (EligibilityMatch $match): string => $match->course->title, $nearMisses);

        $this->assertContains('MSc Data Science', $titles);

        foreach ($nearMisses as $match) {
            $this->assertFalse($match->isEligible());
            $this->assertLessThanOrEqual(0.5, $match->englishShortfall);
        }
    }

    #[Test]
    public function it_states_exactly_how_far_short_an_applicant_is(): void
    {
        $match = EligibilityMatch::judge(self::catalogue()[0], self::profile(grade: 60.0, english: 6.0, budget: 20000));

        $this->assertSame(5.0, $match->gradeShortfall);
        $this->assertSame(0.5, $match->englishShortfall);
        $this->assertSame(4000, $match->budgetShortfall);
        $this->assertFalse($match->meetsGrades);
        $this->assertFalse($match->meetsEnglish);
        $this->assertFalse($match->isAffordable);
        $this->assertFalse($match->isNearMiss());
    }

    #[Test]
    public function it_treats_an_unaffordable_course_as_more_than_a_near_miss(): void
    {
        $match = EligibilityMatch::judge(self::catalogue()[2], self::profile(budget: 10000));

        $this->assertFalse($match->isNearMiss());
        $this->assertGreaterThan(0, $match->budgetShortfall);
    }

    #[Test]
    public function it_produces_the_same_ranking_for_the_same_inputs(): void
    {
        $matcher = new EligibilityMatcher();

        $first = $matcher->match(self::catalogue(), self::profile());
        $second = $matcher->match(self::catalogue(), self::profile());

        $this->assertSame(
            array_map(static fn (EligibilityMatch $match): int => $match->course->courseId, $first),
            array_map(static fn (EligibilityMatch $match): int => $match->course->courseId, $second),
        );
    }

    #[Test]
    public function it_honours_the_result_limit(): void
    {
        $matcher = new EligibilityMatcher();

        $this->assertCount(2, $matcher->match(self::catalogue(), self::profile(), 2));
        $this->assertSame([], $matcher->match(self::catalogue(), self::profile(), 0));
        $this->assertSame([], $matcher->match([], self::profile()));
    }

    /**
     * @return array<string, array{float, float, float, float}>
     */
    public static function nonsenseAnswerProvider(): array
    {
        return [
            'grades above 100' => [140.0, 7.0, 100.0, 7.0],
            'negative grades' => [-20.0, 7.0, 0.0, 7.0],
            'IELTS above 9' => [70.0, 12.0, 70.0, 9.0],
            'negative IELTS' => [70.0, -3.0, 70.0, 0.0],
            'infinite grade' => [INF, 7.0, 0.0, 7.0],
            'not a number' => [NAN, 7.0, 0.0, 7.0],
        ];
    }

    #[Test]
    #[DataProvider('nonsenseAnswerProvider')]
    public function it_clamps_whatever_a_stranger_types_into_the_quiz(
        float $grade,
        float $english,
        float $expectedGrade,
        float $expectedEnglish
    ): void {
        $profile = EligibilityProfile::of(StudyLevel::Undergraduate, $grade, $english, -500);

        $this->assertSame($expectedGrade, $profile->gradePercentage);
        $this->assertSame($expectedEnglish, $profile->englishScore);
        $this->assertSame(0, $profile->annualBudget);
    }

    #[Test]
    public function it_normalises_the_preferred_countries(): void
    {
        $profile = EligibilityProfile::of(StudyLevel::Undergraduate, 70.0, 7.0, 30000, [' Canada ', 'canada', '', 'UK']);

        $this->assertSame(['Canada', 'UK'], $profile->preferredCountries);
        $this->assertTrue($profile->prefers('CANADA'));
        $this->assertFalse($profile->prefers('Australia'));
        $this->assertTrue($profile->hasCountryPreference());
        $this->assertFalse(self::profile()->hasCountryPreference());
    }

    #[Test]
    public function it_knows_what_level_each_applicant_is_applying_to(): void
    {
        $this->assertSame(StudyLevel::Diploma, StudyLevel::Foundation->nextLevel());
        $this->assertSame(StudyLevel::Undergraduate, StudyLevel::Diploma->nextLevel());
        $this->assertSame(StudyLevel::Undergraduate, StudyLevel::Undergraduate->nextLevel());
        $this->assertSame(StudyLevel::Postgraduate, StudyLevel::Postgraduate->nextLevel());
        $this->assertSame(StudyLevel::Doctorate, StudyLevel::Doctorate->nextLevel());
        $this->assertGreaterThan(StudyLevel::Diploma->rank(), StudyLevel::Postgraduate->rank());
    }

    private static function rates(): ExchangeRateTable
    {
        return ExchangeRateTable::of(['GBP' => 0.79, 'CAD' => 1.36, 'BDT' => 118.0]);
    }

    #[Test]
    public function it_adds_up_a_year_abroad(): void
    {
        $estimate = (new CostCalculator(self::rates()))->estimate(24000, 900, 235, 1100, 700, 12, 'USD');

        $this->assertSame(24000, $estimate->tuition);
        $this->assertSame(10800, $estimate->living);
        $this->assertSame(235, $estimate->visa);
        $this->assertSame(1100, $estimate->flights);
        $this->assertSame(700, $estimate->insurance);
        $this->assertSame(36835, $estimate->total());
    }

    #[Test]
    public function it_prorates_a_shorter_course(): void
    {
        $estimate = (new CostCalculator(self::rates()))->estimate(24000, 900, 235, 1100, 700, 6, 'USD');

        $this->assertSame(12000, $estimate->tuition);
        $this->assertSame(5400, $estimate->living);
        $this->assertSame(350, $estimate->insurance);
        $this->assertSame(1100, $estimate->flights);
    }

    #[Test]
    public function it_pays_for_a_second_return_flight_on_a_two_year_course(): void
    {
        $estimate = (new CostCalculator(self::rates()))->estimate(24000, 900, 235, 1100, 700, 24, 'USD');

        $this->assertSame(2200, $estimate->flights);
        $this->assertSame(48000, $estimate->tuition);
    }

    #[Test]
    public function it_converts_into_the_currency_the_visitor_reads_in(): void
    {
        $estimate = (new CostCalculator(self::rates()))->estimate(24000, 900, 235, 1100, 700, 12, 'GBP');

        $this->assertSame('GBP', $estimate->currency);
        $this->assertSame(18960, $estimate->tuition);
        $this->assertSame(8532, $estimate->living);
    }

    /**
     * A calculator that silently reports a year abroad as costing nothing is worse than one
     * that is slightly wrong.
     */
    #[Test]
    public function it_converts_an_unknown_currency_at_parity_rather_than_at_zero(): void
    {
        $estimate = (new CostCalculator(self::rates()))->estimate(24000, 900, 235, 1100, 700, 12, 'XYZ');

        $this->assertSame(24000, $estimate->tuition);
        $this->assertGreaterThan(0, $estimate->total());
        $this->assertFalse(self::rates()->knows('XYZ'));
        $this->assertTrue(self::rates()->knows(' gbp '));
    }

    #[Test]
    public function it_clamps_a_nonsensical_duration_and_negative_costs(): void
    {
        $calculator = new CostCalculator(self::rates());

        $this->assertSame(2000, $calculator->estimate(24000, 0, 0, 0, 0, 0, 'USD')->tuition);
        $this->assertSame(120000, $calculator->estimate(24000, 0, 0, 0, 0, 999, 'USD')->tuition);
        $this->assertSame(0, $calculator->estimate(-5, -5, -5, -5, -5, 12, 'USD')->total());
    }

    #[Test]
    public function it_keeps_the_percentages_adding_up_to_a_hundred(): void
    {
        $estimate = CostEstimate::of(24000, 10800, 235, 1100, 700, 'USD');

        $this->assertSame(100, array_sum($estimate->percentages()));

        // Tuition floors to 65% and absorbs the 3-point rounding remainder, so a pie chart
        // shows 100 rather than 97.
        $this->assertSame(68, $estimate->percentages()['tuition']);
        $this->assertSame(29, $estimate->percentages()['living']);
    }

    #[Test]
    public function it_reports_zeroes_rather_than_dividing_by_nothing(): void
    {
        $percentages = CostEstimate::of(0, 0, 0, 0, 0, 'USD')->percentages();

        $this->assertSame(0, array_sum($percentages));
        $this->assertSame(0, $percentages['tuition']);
    }

    #[Test]
    public function it_falls_back_to_the_base_currency_when_none_is_given(): void
    {
        $this->assertSame(ExchangeRateTable::BASE_CURRENCY, CostEstimate::of(1, 1, 1, 1, 1, '  ')->currency);
    }

    #[Test]
    public function it_rejects_a_rate_that_would_break_the_arithmetic(): void
    {
        $rates = ExchangeRateTable::of(['GBP' => 0.79, 'BAD' => 0.0, 'WORSE' => -3.0, 'NANNY' => NAN, '' => 2.0]);

        $this->assertSame(['USD', 'GBP'], $rates->currencies());
        $this->assertSame(100, $rates->convert(100, 'BAD'));
    }

    #[Test]
    public function it_round_trips_the_rate_table_through_storage(): void
    {
        $rates = self::rates();

        $this->assertSame($rates->toArray(), ExchangeRateTable::fromArray($rates->toArray())->toArray());
        $this->assertSame(['USD'], ExchangeRateTable::fromArray([1 => 2.0])->currencies());
    }
}
