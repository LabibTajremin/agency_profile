<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * A complete timing set: three durations, the stagger step between siblings, and how far a
 * revealed element travels.
 */
final class MotionTiming
{
    private function __construct(
        public readonly int $fastMilliseconds,
        public readonly int $baseMilliseconds,
        public readonly int $slowMilliseconds,
        public readonly int $staggerStepMilliseconds,
        public readonly int $travelPixels,
        public readonly MotionEasing $easing,
    ) {
    }

    public static function of(
        int $fastMilliseconds,
        int $baseMilliseconds,
        int $slowMilliseconds,
        int $staggerStepMilliseconds,
        int $travelPixels,
        MotionEasing $easing
    ): self {
        return new self(
            max(0, $fastMilliseconds),
            max(0, $baseMilliseconds),
            max(0, $slowMilliseconds),
            max(0, $staggerStepMilliseconds),
            max(0, $travelPixels),
            $easing,
        );
    }

    /**
     * Every derived value at zero. Emitted whenever motion is off, so a stored duration can
     * never leak back into the page through a token that was not zeroed.
     */
    public static function still(): self
    {
        return new self(0, 0, 0, 0, 0, MotionEasing::Linear);
    }

    public function isStill(): bool
    {
        return $this->fastMilliseconds === 0
            && $this->baseMilliseconds === 0
            && $this->slowMilliseconds === 0
            && $this->staggerStepMilliseconds === 0
            && $this->travelPixels === 0;
    }

    public function withEasing(MotionEasing $easing): self
    {
        return new self(
            $this->fastMilliseconds,
            $this->baseMilliseconds,
            $this->slowMilliseconds,
            $this->staggerStepMilliseconds,
            $this->travelPixels,
            $easing,
        );
    }
}
