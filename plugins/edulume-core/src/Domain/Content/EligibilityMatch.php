<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * One course, judged against one profile.
 *
 * A near miss is reported as a near miss rather than filtered away. "You are 0.5 short on
 * IELTS" is the single most useful thing this tool can tell someone, and hiding the course
 * turns actionable advice into an empty results page.
 */
final class EligibilityMatch
{
    private function __construct(
        public readonly CourseRequirements $course,
        public readonly bool $meetsGrades,
        public readonly bool $meetsEnglish,
        public readonly bool $isAffordable,
        public readonly float $englishShortfall,
        public readonly float $gradeShortfall,
        public readonly int $budgetShortfall,
        public readonly int $score,
    ) {
    }

    public static function judge(CourseRequirements $course, EligibilityProfile $profile): self
    {
        $gradeShortfall = max(0.0, $course->minimumGradePercentage - $profile->gradePercentage);
        $englishShortfall = max(0.0, $course->minimumEnglishScore - $profile->englishScore);
        $budgetShortfall = max(0, $course->annualTuition - $profile->annualBudget);

        return new self(
            $course,
            $gradeShortfall === 0.0,
            $englishShortfall === 0.0,
            $budgetShortfall === 0,
            $englishShortfall,
            $gradeShortfall,
            $budgetShortfall,
            self::scoreFor($course, $profile, $gradeShortfall, $englishShortfall, $budgetShortfall),
        );
    }

    public function isEligible(): bool
    {
        return $this->meetsGrades && $this->meetsEnglish && $this->isAffordable;
    }

    /**
     * Close enough that the advice is "sit the test again", not "look elsewhere".
     */
    public function isNearMiss(): bool
    {
        return !$this->isEligible()
            && $this->gradeShortfall <= 5.0
            && $this->englishShortfall <= 0.5
            && $this->budgetShortfall === 0;
    }

    private static function scoreFor(
        CourseRequirements $course,
        EligibilityProfile $profile,
        float $gradeShortfall,
        float $englishShortfall,
        int $budgetShortfall
    ): int {
        $score = 100;

        $score -= (int) round($gradeShortfall * 2);
        $score -= (int) round($englishShortfall * 20);
        $score -= $budgetShortfall > 0 ? 40 : 0;
        $score += $profile->prefers($course->country) ? 15 : 0;
        $score += $course->level === $profile->targetLevel() ? 10 : -25;

        return max(0, min(100, $score));
    }
}
