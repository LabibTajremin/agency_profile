<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Chrome;

/**
 * Where a logo is being asked for. Each slot has different constraints — the mobile slot is
 * usually a mark rather than a wordmark, the footer one often reversed — so each gets a slot
 * rather than everyone scaling the same file.
 */
enum LogoSlot: string
{
    case Header = 'header';
    case Mobile = 'mobile';
    case Footer = 'footer';
}
