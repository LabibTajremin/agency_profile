<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * What the quiz learned about one prospective student.
 *
 * Every field is coerced into a sane range on the way in. The quiz is the highest-converting
 * element on a site in this niche, which means it is also the one strangers type nonsense into
 * fastest.
 */
final class EligibilityProfile
{
    public const MINIMUM_GRADE_PERCENTAGE = 0.0;
    public const MAXIMUM_GRADE_PERCENTAGE = 100.0;

    public const MINIMUM_ENGLISH_SCORE = 0.0;
    public const MAXIMUM_ENGLISH_SCORE = 9.0;

    /**
     * @param list<string> $preferredCountries
     */
    private function __construct(
        public readonly StudyLevel $highestLevelCompleted,
        public readonly float $gradePercentage,
        public readonly float $englishScore,
        public readonly int $annualBudget,
        public readonly array $preferredCountries,
    ) {
    }

    /**
     * @param list<string> $preferredCountries
     */
    public static function of(
        StudyLevel $highestLevelCompleted,
        float $gradePercentage,
        float $englishScore,
        int $annualBudget,
        array $preferredCountries = []
    ): self {
        $countries = [];

        foreach ($preferredCountries as $country) {
            $normalised = trim($country);
            $key = strtolower($normalised);

            // The first spelling wins: someone who typed "Canada" then "canada" meant one
            // country, and echoing their own capitalisation back is the polite half of that.
            if ($normalised !== '' && !array_key_exists($key, $countries)) {
                $countries[$key] = $normalised;
            }
        }

        return new self(
            $highestLevelCompleted,
            self::clamp($gradePercentage, self::MINIMUM_GRADE_PERCENTAGE, self::MAXIMUM_GRADE_PERCENTAGE),
            self::clamp($englishScore, self::MINIMUM_ENGLISH_SCORE, self::MAXIMUM_ENGLISH_SCORE),
            max(0, $annualBudget),
            array_values($countries),
        );
    }

    public function targetLevel(): StudyLevel
    {
        return $this->highestLevelCompleted->nextLevel();
    }

    public function hasCountryPreference(): bool
    {
        return $this->preferredCountries !== [];
    }

    public function prefers(string $country): bool
    {
        foreach ($this->preferredCountries as $preferred) {
            if (strcasecmp($preferred, $country) === 0) {
                return true;
            }
        }

        return false;
    }

    private static function clamp(float $value, float $minimum, float $maximum): float
    {
        if (!is_finite($value)) {
            return $minimum;
        }

        return max($minimum, min($maximum, $value));
    }
}
