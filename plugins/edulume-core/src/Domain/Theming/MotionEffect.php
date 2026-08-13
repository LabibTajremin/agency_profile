<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The seventeen individually toggleable motion effects.
 *
 * Each maps to exactly one front-end module, so the active set of effects decides which
 * JavaScript is loaded at all. Smooth scroll and the custom cursor ship off: both fight the
 * visitor's own input, and both are the first things an accessibility audit flags.
 */
enum MotionEffect: string
{
    case FadeIn = 'fade-in';
    case FadeInUp = 'fade-in-up';
    case ScaleIn = 'scale-in';
    case StaggerChildren = 'stagger-children';
    case ParallaxBackground = 'parallax-background';
    case ParallaxImage = 'parallax-image';
    case CounterRoll = 'counter-roll';
    case Marquee = 'marquee';
    case HoverLift = 'hover-lift';
    case HoverTilt = 'hover-tilt';
    case MagneticButtons = 'magnetic-buttons';
    case StickyHeaderShrink = 'sticky-header-shrink';
    case ScrollProgressBar = 'scroll-progress-bar';
    case TextSplitReveal = 'text-split-reveal';
    case ImageMaskReveal = 'image-mask-reveal';
    case SmoothScroll = 'smooth-scroll';
    case CustomCursor = 'custom-cursor';

    public function module(): MotionModule
    {
        return match ($this) {
            self::FadeIn, self::FadeInUp, self::ScaleIn => MotionModule::Reveal,
            self::StaggerChildren => MotionModule::Stagger,
            self::ParallaxBackground, self::ParallaxImage => MotionModule::Parallax,
            self::CounterRoll => MotionModule::Counters,
            self::Marquee => MotionModule::Marquee,
            self::HoverLift => MotionModule::Hover,
            self::HoverTilt => MotionModule::Tilt,
            self::MagneticButtons => MotionModule::Magnetic,
            self::StickyHeaderShrink => MotionModule::Header,
            self::ScrollProgressBar => MotionModule::Progress,
            self::TextSplitReveal, self::ImageMaskReveal => MotionModule::Timeline,
            self::SmoothScroll => MotionModule::SmoothScroll,
            self::CustomCursor => MotionModule::Cursor,
        };
    }

    /**
     * Effects that hijack the visitor's own input never ship on by default, whatever preset
     * is chosen.
     */
    public function shipsOff(): bool
    {
        return $this === self::SmoothScroll || $this === self::CustomCursor;
    }
}
