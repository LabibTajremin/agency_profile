<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Content;

use Edulume\Core\Domain\Content\DemoContent;
use Edulume\Core\Domain\Support\NestedArray;
use Edulume\Core\Domain\Theming\SectionId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DemoContent::class)]
final class DemoContentTest extends TestCase
{
    /**
     * The one property that matters: no section arrives on the page with a blank slot.
     *
     * @param list<string> $paths
     */
    #[DataProvider('populatedPaths')]
    public function testEveryDeclaredSubElementHasCopy(string $section, array $paths): void
    {
        $content = DemoContent::all();

        self::assertArrayHasKey($section, $content, $section . ' has no demo copy at all');

        foreach ($paths as $path) {
            $value = NestedArray::get($content, $section . '.' . $path);

            self::assertNotNull($value, $section . '.' . $path . ' is missing');
            self::assertNotSame('', $value, $section . '.' . $path . ' is empty');
        }
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function populatedPaths(): array
    {
        return [
            'hero' => ['hero', [
                'headline', 'subheadline', 'searchPlaceholder', 'image',
                'primaryCta.label', 'primaryCta.url', 'secondaryCta.label', 'secondaryCta.url',
                'badges.2.label', 'chips.1.value', 'chips.1.label',
            ]],
            'statistics' => ['statistics', ['title', 'items.3.value', 'items.3.label', 'items.0.icon']],
            'services' => ['services', ['title', 'intro', 'items.5.title', 'items.5.blurb', 'items.5.url']],
            'destinations' => ['destinations', [
                'title', 'intro', 'items.7.name', 'items.7.hook', 'items.7.tuition',
                'items.7.image', 'items.7.universities',
            ]],
            'universities' => ['universities', [
                'title', 'intro', 'items.11.name', 'items.11.country', 'items.11.ranking', 'items.11.crest',
            ]],
            'highlights' => ['highlights', [
                'title', 'image', 'callout.value', 'callout.label', 'items.3.title', 'items.3.blurb',
            ]],
            'process' => ['process', ['title', 'intro', 'items.5.title', 'items.5.blurb']],
            'intakes' => ['intakes', [
                'title', 'items.2.month', 'items.2.year', 'items.2.countries',
                'items.2.deadline', 'items.2.cta.label',
            ]],
            'scholarships' => ['scholarships', [
                'title', 'items.3.name', 'items.3.amount', 'items.3.eligibility',
                'items.3.country', 'items.3.deadline',
            ]],
            'testimonials' => ['testimonials', [
                'title', 'items.5.name', 'items.5.university', 'items.5.country',
                'items.5.avatar', 'items.5.quote',
            ]],
            'team' => ['team', ['title', 'items.5.name', 'items.5.role', 'items.5.languages', 'items.5.photo']],
            'events' => ['events', ['title', 'items.2.day', 'items.2.month', 'items.2.title', 'items.2.venue']],
            'blog' => ['blog', [
                'title', 'items.2.category', 'items.2.title', 'items.2.excerpt',
                'items.2.date', 'items.2.readTime', 'items.2.image',
            ]],
            'faq' => ['faq', ['title', 'items.7.question', 'items.7.answer']],
            'cta' => ['cta', ['title', 'blurb', 'image']],
            'footer' => ['footer', [
                'about', 'hours', 'phone', 'email', 'branches.1.address',
                'links.4.label', 'socials.3.url', 'newsletter.buttonLabel',
            ]],
        ];
    }

    public function testTheSectionCountsMatchWhatTheBriefAsksFor(): void
    {
        $content = DemoContent::all();

        $counts = [
            'statistics.items' => 4,
            'services.items' => 6,
            'destinations.items' => 8,
            'universities.items' => 12,
            'highlights.items' => 4,
            'process.items' => 6,
            'intakes.items' => 3,
            'scholarships.items' => 4,
            'testimonials.items' => 6,
            'team.items' => 6,
            'events.items' => 3,
            'blog.items' => 3,
            'faq.items' => 8,
        ];

        foreach ($counts as $path => $expected) {
            self::assertCount($expected, NestedArray::get($content, $path, []), $path);
        }
    }

    public function testTheFoundersPageIsFullyWrittenToo(): void
    {
        $pages = DemoContent::pages();

        foreach ([
            'founders.title',
            'founders.lead',
            'founders.visionTitle',
            'founders.vision',
            'founders.goalsTitle',
            'founders.goalsYear',
            'founders.milestonesTitle',
            'founders.ctaTitle',
            'founders.ctaBlurb',
            'founders.people.1.name',
            'founders.people.1.designation',
            'founders.people.1.tagline',
            'founders.people.1.photo',
            'founders.people.1.bio',
            'founders.people.1.languages',
            'founders.people.1.expertise',
            'founders.people.1.education.1.degree',
            'founders.people.1.education.1.institution',
            'founders.people.1.education.1.year',
            'founders.people.1.education.1.country',
            'founders.people.1.experience.1.role',
            'founders.people.1.experience.1.org',
            'founders.people.1.experience.1.years',
            'founders.people.1.socials.linkedin',
            'founders.goals.3.value',
            'founders.goals.3.label',
            'founders.milestones.5.year',
            'founders.milestones.5.event',
        ] as $path) {
            $value = NestedArray::get($pages, $path);

            self::assertNotNull($value, $path . ' is missing');
            self::assertNotSame('', $value, $path . ' is empty');
        }

        self::assertCount(2, NestedArray::get($pages, 'founders.people', []));
        self::assertCount(4, NestedArray::get($pages, 'founders.goals', []));
        self::assertCount(6, NestedArray::get($pages, 'founders.milestones', []));
    }

    public function testPageCopyIsKeptOutOfTheSectionList(): void
    {
        // A page has no toggle and no place in the home-page order, so it must not appear in the
        // list the sections screen and the front page both walk.
        self::assertSame([], array_intersect_key(DemoContent::all(), DemoContent::pages()));
    }

    public function testEveryCopyKeyIsASectionTheProductKnowsAbout(): void
    {
        foreach (array_keys(DemoContent::all()) as $key) {
            self::assertNotNull(
                SectionId::tryFrom((string) $key),
                $key . ' has demo copy but no SectionId case'
            );
        }
    }
}
