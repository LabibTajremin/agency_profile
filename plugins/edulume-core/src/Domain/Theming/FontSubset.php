<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * Unicode ranges a family can be subset to. Shipping only the subsets a site needs is the
 * difference between a 40 KB font budget and a 400 KB one.
 */
enum FontSubset: string
{
    case Latin = 'latin';
    case LatinExtended = 'latin-ext';
    case Cyrillic = 'cyrillic';
    case Greek = 'greek';
    case Vietnamese = 'vietnamese';
    case Arabic = 'arabic';
    case Bengali = 'bengali';
    case Devanagari = 'devanagari';
}
