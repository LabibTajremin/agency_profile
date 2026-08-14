<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The front-end JavaScript modules an effect can pull in.
 *
 * One module per concern, loaded only when an effect that needs it is active. The timeline
 * module is the heavy one — it is the reason Cinematic is a deliberate choice rather than a
 * default.
 */
enum MotionModule: string
{
    case Reveal = 'reveal';
    case Stagger = 'stagger';
    case Parallax = 'parallax';
    case Counters = 'counters';
    case Marquee = 'marquee';
    case Hover = 'hover';
    case Tilt = 'tilt';
    case Magnetic = 'magnetic';
    case Header = 'header';
    case Progress = 'progress';
    case SmoothScroll = 'smooth-scroll';
    case Cursor = 'cursor';
    case Timeline = 'timeline';

    /**
     * Heavy modules are never loaded speculatively; the effect that needs one must be active.
     */
    public function isHeavy(): bool
    {
        return $this === self::Timeline;
    }
}
