<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * What one course asks of an applicant.
 */
final class CourseRequirements
{
    private function __construct(
        public readonly int $courseId,
        public readonly string $title,
        public readonly StudyLevel $level,
        public readonly string $country,
        public readonly float $minimumGradePercentage,
        public readonly float $minimumEnglishScore,
        public readonly int $annualTuition,
    ) {
    }

    public static function of(
        int $courseId,
        string $title,
        StudyLevel $level,
        string $country,
        float $minimumGradePercentage,
        float $minimumEnglishScore,
        int $annualTuition
    ): self {
        return new self(
            $courseId,
            $title,
            $level,
            $country,
            $minimumGradePercentage,
            $minimumEnglishScore,
            max(0, $annualTuition),
        );
    }
}
