<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * Where a form appears. Six placements, because a consultancy site asks for the enquiry in
 * six different moments.
 */
enum FormPlacement: string
{
    case Inline = 'inline';
    case Popup = 'popup';
    case SlideIn = 'slide-in';
    case StickyBar = 'sticky-bar';
    case Sidebar = 'sidebar';
    case Footer = 'footer';

    public function interruptsTheVisitor(): bool
    {
        return $this === self::Popup || $this === self::SlideIn;
    }
}
