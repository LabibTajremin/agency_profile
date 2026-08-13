<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * Ranks a catalogue against one profile.
 *
 * Results are ordered by score and then by title, so the same profile against the same
 * catalogue always produces the same list — a quiz whose answers reshuffle between reloads is a
 * quiz nobody believes.
 */
final class EligibilityMatcher
{
    public const DEFAULT_LIMIT = 12;

    /**
     * @param list<CourseRequirements> $catalogue
     *
     * @return list<EligibilityMatch>
     */
    public function match(array $catalogue, EligibilityProfile $profile, int $limit = self::DEFAULT_LIMIT): array
    {
        $matches = [];

        foreach ($catalogue as $course) {
            if ($profile->hasCountryPreference() && !$profile->prefers($course->country)) {
                continue;
            }

            $matches[] = EligibilityMatch::judge($course, $profile);
        }

        usort(
            $matches,
            static fn (EligibilityMatch $first, EligibilityMatch $second): int
                => $second->score === $first->score
                    ? strcmp($first->course->title, $second->course->title)
                    : $second->score - $first->score,
        );

        return array_slice($matches, 0, max(0, $limit));
    }

    /**
     * @param list<EligibilityMatch> $matches
     *
     * @return list<EligibilityMatch>
     */
    public function eligibleOnly(array $matches): array
    {
        return array_values(array_filter(
            $matches,
            static fn (EligibilityMatch $match): bool => $match->isEligible(),
        ));
    }

    /**
     * @param list<EligibilityMatch> $matches
     *
     * @return list<EligibilityMatch>
     */
    public function nearMisses(array $matches): array
    {
        return array_values(array_filter(
            $matches,
            static fn (EligibilityMatch $match): bool => $match->isNearMiss(),
        ));
    }
}
