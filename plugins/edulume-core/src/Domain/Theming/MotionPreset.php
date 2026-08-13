<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * Five complete motion characters, each a full timing set and a default effect list.
 *
 * Cinematic is the only preset that turns on an effect needing the heavy timeline module, so
 * choosing it is a deliberate decision with a visible cost rather than an accident.
 */
enum MotionPreset: string
{
    case None = 'none';
    case Subtle = 'subtle';
    case Refined = 'refined';
    case Dynamic = 'dynamic';
    case Cinematic = 'cinematic';

    public function timing(): MotionTiming
    {
        return match ($this) {
            self::None => MotionTiming::still(),
            self::Subtle => MotionTiming::of(120, 200, 320, 40, 8, MotionEasing::Standard),
            self::Refined => MotionTiming::of(160, 280, 460, 60, 16, MotionEasing::Decelerate),
            self::Dynamic => MotionTiming::of(200, 360, 600, 80, 28, MotionEasing::Emphasized),
            self::Cinematic => MotionTiming::of(260, 520, 900, 110, 44, MotionEasing::Spring),
        };
    }

    /**
     * @return list<MotionEffect>
     */
    public function defaultEffects(): array
    {
        return match ($this) {
            self::None => [],
            self::Subtle => [MotionEffect::FadeIn, MotionEffect::HoverLift],
            self::Refined => [
                MotionEffect::FadeIn,
                MotionEffect::FadeInUp,
                MotionEffect::StaggerChildren,
                MotionEffect::HoverLift,
                MotionEffect::StickyHeaderShrink,
            ],
            self::Dynamic => [
                MotionEffect::FadeIn,
                MotionEffect::FadeInUp,
                MotionEffect::ScaleIn,
                MotionEffect::StaggerChildren,
                MotionEffect::ParallaxBackground,
                MotionEffect::CounterRoll,
                MotionEffect::HoverLift,
                MotionEffect::HoverTilt,
                MotionEffect::MagneticButtons,
                MotionEffect::StickyHeaderShrink,
                MotionEffect::ScrollProgressBar,
            ],
            self::Cinematic => [
                MotionEffect::FadeIn,
                MotionEffect::FadeInUp,
                MotionEffect::ScaleIn,
                MotionEffect::StaggerChildren,
                MotionEffect::ParallaxBackground,
                MotionEffect::ParallaxImage,
                MotionEffect::CounterRoll,
                MotionEffect::Marquee,
                MotionEffect::HoverLift,
                MotionEffect::HoverTilt,
                MotionEffect::MagneticButtons,
                MotionEffect::StickyHeaderShrink,
                MotionEffect::ScrollProgressBar,
                MotionEffect::TextSplitReveal,
                MotionEffect::ImageMaskReveal,
            ],
        };
    }
}
