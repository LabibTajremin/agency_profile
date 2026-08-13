<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The page regions that can be re-themed independently of the global settings.
 */
enum SectionId: string
{
    case Header = 'header';
    case Hero = 'hero';
    case Highlights = 'highlights';
    case Destinations = 'destinations';
    case Courses = 'courses';
    case Services = 'services';
    case Scholarships = 'scholarships';
    case Testimonials = 'testimonials';
    case Statistics = 'statistics';
    case Events = 'events';
    case Team = 'team';
    case CallToAction = 'call-to-action';
    case Blog = 'blog';
    case Footer = 'footer';
}
