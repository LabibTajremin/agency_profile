<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Demo;

/**
 * The four starter demos.
 *
 * Chosen to cover the four businesses that actually buy a theme like this, rather than four
 * colour schemes: a premium Gulf consultancy, a consultancy that also runs a test-prep centre,
 * an SEO-led guide site whose traffic is the product, and a boutique practice that wants to
 * look small on purpose.
 */
final class DemoLibrary
{
    /**
     * @return list<DemoDefinition>
     */
    public static function all(): array
    {
        return [
            new DemoDefinition(
                'gulf-premium',
                'Gulf premium consultancy',
                'A large consultancy with branches, a full course catalogue and a formal tone.',
                [
                    'edulume_destination' => 12,
                    'edulume_institution' => 40,
                    'edulume_course' => 180,
                    'edulume_service' => 8,
                    'edulume_scholarship' => 24,
                    'edulume_event' => 6,
                    'edulume_team-member' => 14,
                    'edulume_testimonial' => 18,
                    'edulume_story' => 12,
                    'edulume_branch' => 5,
                    'edulume_faq' => 22,
                    'edulume_partner' => 16,
                ],
                ['accent' => 'deep-teal', 'layout' => ['density' => 'comfortable'], 'motion' => ['preset' => 'subtle']],
                ['primary', 'footer', 'utility'],
                'Study abroad with confidence',
            ),
            new DemoDefinition(
                'test-prep',
                'Consultancy and test-prep centre',
                'Counselling plus IELTS, PTE and TOEFL classes, with timetables and intake dates.',
                [
                    'edulume_destination' => 8,
                    'edulume_institution' => 24,
                    'edulume_course' => 90,
                    'edulume_service' => 10,
                    'edulume_test-prep' => 12,
                    'edulume_event' => 10,
                    'edulume_team-member' => 10,
                    'edulume_testimonial' => 20,
                    'edulume_story' => 8,
                    'edulume_branch' => 3,
                    'edulume_faq' => 18,
                ],
                ['accent' => 'signal-orange', 'layout' => ['density' => 'compact'], 'motion' => ['preset' => 'balanced']],
                ['primary', 'footer', 'utility'],
                'Get the score you need',
            ),
            new DemoDefinition(
                'guide-site',
                'Content and SEO-led guide site',
                'Long-form country and course guides, with the finders as the primary navigation.',
                [
                    'edulume_destination' => 24,
                    'edulume_institution' => 120,
                    'edulume_course' => 600,
                    'edulume_scholarship' => 80,
                    'edulume_resource' => 40,
                    'edulume_faq' => 60,
                    'edulume_service' => 6,
                    'edulume_testimonial' => 10,
                ],
                ['accent' => 'ink-blue', 'layout' => ['density' => 'comfortable'], 'motion' => ['preset' => 'none']],
                ['primary', 'footer'],
                'Everything you need to study abroad',
            ),
            new DemoDefinition(
                'boutique',
                'Boutique practice',
                'A small, deliberately quiet site: few pages, a lot of white space, one destination focus.',
                [
                    'edulume_destination' => 4,
                    'edulume_institution' => 12,
                    'edulume_course' => 30,
                    'edulume_service' => 5,
                    'edulume_team-member' => 4,
                    'edulume_testimonial' => 8,
                    'edulume_story' => 6,
                    'edulume_faq' => 10,
                ],
                ['accent' => 'warm-sand', 'layout' => ['density' => 'spacious'], 'motion' => ['preset' => 'subtle']],
                ['primary', 'footer'],
                'Personal guidance, start to finish',
            ),
        ];
    }

    public static function find(string $slug): ?DemoDefinition
    {
        foreach (self::all() as $demo) {
            if ($demo->slug === $slug) {
                return $demo;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_map(static fn (DemoDefinition $demo): string => $demo->slug, self::all());
    }
}
