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

    /*
     * The remaining home-page sections, named with the slugs the theme's template parts already
     * use. They were missing, which meant five of the eleven sections the home page actually
     * renders had no identity here at all — so they could be neither re-themed nor switched off.
     */
    case TrustBar = 'trust-bar';
    case Eligibility = 'eligibility';
    case SuccessStories = 'success-stories';
    case Faq = 'faq';
    case Cta = 'cta';

    /*
     * The sections the feature round adds. `universities`, `process` and `intakes` had no
     * identity anywhere; `video-rail` is the promo row.
     */
    case Universities = 'universities';
    case Process = 'process';
    case Intakes = 'intakes';
    case VideoRail = 'video-rail';

    /**
     * The header and the footer cannot be switched off.
     *
     * Not a policy decision so much as an admission of what they are: the header carries the
     * site's navigation and the skip link, the footer carries the legal and privacy links. A
     * page with neither is not a minimal page, it is a broken one — no way out and nothing that
     * satisfies WCAG 2.4.1. Everything between them is genuinely optional.
     */
    public function canBeSwitchedOff(): bool
    {
        return $this !== self::Header && $this !== self::Footer;
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }

    /**
     * A human label for the toggle list, so the admin does not have to title-case a slug and
     * get `Call-to-action` wrong.
     */
    public function label(): string
    {
        /*
         * A lookup rather than a `match` with nineteen arms, which trips the complexity gate for
         * no benefit: every arm is a constant, so there is no branching logic to read here, only
         * a table pretending to be control flow.
         */
        return self::LABELS[$this->value] ?? ucfirst(str_replace('-', ' ', $this->value));
    }

    private const LABELS = [
        'header' => 'Header',
        'hero' => 'Hero',
        'highlights' => 'Highlights',
        'destinations' => 'Destinations',
        'courses' => 'Courses',
        'services' => 'Services',
        'scholarships' => 'Scholarships',
        'testimonials' => 'Testimonials',
        'statistics' => 'Statistics',
        'events' => 'Events',
        'team' => 'Team',
        'call-to-action' => 'Call to action',
        'blog' => 'Blog',
        'footer' => 'Footer',
        'trust-bar' => 'Trust bar',
        'eligibility' => 'Eligibility checker',
        'success-stories' => 'Success stories',
        'faq' => 'FAQ',
        'cta' => 'Closing call to action',
        'universities' => 'Partner universities',
        'process' => 'How it works',
        'intakes' => 'Open intakes',
        'video-rail' => 'Video rail',
    ];

    /**
     * The home page's sections, in the order they render.
     *
     * The theme owns the order and can filter it; this is the default the filter starts from,
     * kept here so the admin's toggle list and the page agree without either guessing.
     *
     * @return list<self>
     */
    public static function homePageOrder(): array
    {
        return [
            self::Hero,
            self::TrustBar,
            self::Statistics,
            self::Services,
            self::VideoRail,
            self::Destinations,
            self::Universities,
            self::Highlights,
            self::Process,
            self::Courses,
            self::Intakes,
            self::Eligibility,
            self::Scholarships,
            self::SuccessStories,
            self::Team,
            self::Events,
            self::Testimonials,
            self::Blog,
            self::Faq,
            self::Cta,
        ];
    }

    /**
     * @return list<self>
     */
    public static function switchable(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $section): bool => $section->canBeSwitchedOff(),
        ));
    }
}
