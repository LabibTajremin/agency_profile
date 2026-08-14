<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Blocks;

/**
 * The block library.
 *
 * Thirty-eight blocks, each with two to four style variations. Every one is accent-aware and
 * motion-aware by default, which means it reads the compiled tokens rather than carrying its
 * own colours — a block with a hard-coded brand colour is a block that survives exactly one
 * rebrand.
 *
 * Blocks that need a script declare the feature they need, so the conditional loader can put
 * one carousel module on the one page that has a carousel instead of on all of them.
 */
final class BlockCatalogue
{
    /**
     * @return list<BlockDefinition>
     */
    public static function all(): array
    {
        return [
            ...self::layoutBlocks(),
            ...self::contentBlocks(),
            ...self::dataBlocks(),
            ...self::mediaBlocks(),
            ...self::conversionBlocks(),
        ];
    }

    public static function find(string $slug): ?BlockDefinition
    {
        foreach (self::all() as $block) {
            if ($block->slug === $slug || $block->name() === $slug) {
                return $block;
            }
        }

        return null;
    }

    /**
     * @return list<BlockDefinition>
     */
    public static function inGroup(BlockGroup $group): array
    {
        return array_values(array_filter(
            self::all(),
            static fn (BlockDefinition $block): bool => $block->group === $group,
        ));
    }

    /**
     * Every module any block on the site could ask for.
     *
     * @return list<string>
     */
    public static function features(): array
    {
        $features = [];

        foreach (self::all() as $block) {
            foreach ($block->features as $feature) {
                if (!in_array($feature, $features, true)) {
                    $features[] = $feature;
                }
            }
        }

        sort($features);

        return $features;
    }

    /**
     * @param list<array{0: string, 1: string}> $variations
     *
     * @return list<BlockVariation>
     */
    private static function styles(array $variations): array
    {
        $built = [];

        foreach ($variations as $index => [$slug, $label]) {
            $built[] = $index === 0
                ? BlockVariation::default($slug, $label)
                : new BlockVariation($slug, $label);
        }

        return $built;
    }

    /**
     * @return list<BlockDefinition>
     */
    private static function layoutBlocks(): array
    {
        $group = BlockGroup::Layout;

        return [
            new BlockDefinition(
                'section',
                'Section',
                'A page section with its own background, tone and spacing.',
                $group,
                self::styles([
                    ['plain', 'Plain'],
                    ['tinted', 'Tinted'],
                    ['patterned', 'Patterned'],
                    ['full-bleed', 'Full bleed'],
                ]),
            ),
            new BlockDefinition(
                'container',
                'Container',
                'Constrains content to the reading width inside a full-width section.',
                $group,
                self::styles([
                    ['default', 'Default'],
                    ['narrow', 'Narrow'],
                    ['wide', 'Wide'],
                ]),
            ),
            new BlockDefinition(
                'split',
                'Split columns',
                'Two columns with a controllable ratio that stacks on narrow viewports.',
                $group,
                self::styles([
                    ['even', 'Even'],
                    ['content-led', 'Content led'],
                    ['media-led', 'Media led'],
                ]),
            ),
            new BlockDefinition(
                'divider',
                'Divider',
                'A rule or a spacer between sections.',
                $group,
                self::styles([
                    ['rule', 'Rule'],
                    ['space', 'Space'],
                    ['accent-rule', 'Accent rule'],
                ]),
                true,
                false,
            ),
            new BlockDefinition(
                'sticky-aside',
                'Sticky aside',
                'A column that stays in view while the main column scrolls.',
                $group,
                self::styles([
                    ['start', 'Start side'],
                    ['end', 'End side'],
                ]),
                true,
                true,
                ['carousel'],
            ),
            new BlockDefinition(
                'tabs',
                'Tabs',
                'Tabbed panels with keyboard-operable tab controls.',
                $group,
                self::styles([
                    ['underline', 'Underline'],
                    ['pill', 'Pill'],
                    ['vertical', 'Vertical'],
                ]),
                true,
                true,
                ['tabs'],
            ),
            new BlockDefinition(
                'accordion',
                'Accordion',
                'Disclosure panels built on details and summary.',
                $group,
                self::styles([
                    ['bordered', 'Bordered'],
                    ['plain', 'Plain'],
                    ['card', 'Card'],
                ]),
            ),
        ];
    }

    /**
     * @return list<BlockDefinition>
     */
    private static function contentBlocks(): array
    {
        $group = BlockGroup::Content;

        return [
            new BlockDefinition(
                'hero',
                'Hero',
                'The first screen: heading, supporting line and up to two actions.',
                $group,
                self::styles([
                    ['centred', 'Centred'],
                    ['split', 'Split'],
                    ['overlay', 'Image overlay'],
                    ['minimal', 'Minimal'],
                ]),
            ),
            new BlockDefinition(
                'page-header',
                'Page header',
                'A title band with breadcrumbs for interior pages.',
                $group,
                self::styles([
                    ['plain', 'Plain'],
                    ['tinted', 'Tinted'],
                    ['image', 'With image'],
                ]),
            ),
            new BlockDefinition(
                'feature-grid',
                'Feature grid',
                'A grid of features, each with an icon, a title and a line of copy.',
                $group,
                self::styles([
                    ['cards', 'Cards'],
                    ['bare', 'Bare'],
                    ['bordered', 'Bordered'],
                    ['numbered', 'Numbered'],
                ]),
            ),
            new BlockDefinition(
                'icon-list',
                'Icon list',
                'A list where each item leads with an icon.',
                $group,
                self::styles([
                    ['check', 'Check marks'],
                    ['bullets', 'Icon bullets'],
                    ['inline', 'Inline'],
                ]),
            ),
            new BlockDefinition(
                'steps',
                'Steps',
                'A numbered process, which is most of what a consultancy sells.',
                $group,
                self::styles([
                    ['horizontal', 'Horizontal'],
                    ['vertical', 'Vertical'],
                    ['connected', 'Connected'],
                ]),
            ),
            new BlockDefinition(
                'quote',
                'Quote',
                'A pull quote with optional attribution.',
                $group,
                self::styles([
                    ['bordered', 'Bordered'],
                    ['large', 'Large'],
                    ['card', 'Card'],
                ]),
            ),
            new BlockDefinition(
                'stat-grid',
                'Statistics',
                'Headline numbers with labels.',
                $group,
                self::styles([
                    ['plain', 'Plain'],
                    ['cards', 'Cards'],
                    ['counters', 'Animated counters'],
                ]),
                true,
                true,
                ['counters'],
            ),
            new BlockDefinition(
                'timeline',
                'Timeline',
                'Dated milestones down a single axis.',
                $group,
                self::styles([
                    ['line', 'Line'],
                    ['alternating', 'Alternating'],
                    ['compact', 'Compact'],
                ]),
            ),
            new BlockDefinition(
                'team-grid',
                'Team grid',
                'Counsellors and staff, pulled from the team content type.',
                $group,
                self::styles([
                    ['cards', 'Cards'],
                    ['portraits', 'Portraits'],
                    ['list', 'List'],
                ]),
            ),
            new BlockDefinition(
                'faq-list',
                'FAQ list',
                'Questions and answers, emitting FAQPage structured data once per page.',
                $group,
                self::styles([
                    ['accordion', 'Accordion'],
                    ['open', 'Always open'],
                    ['two-column', 'Two column'],
                ]),
            ),
        ];
    }

    /**
     * @return list<BlockDefinition>
     */
    private static function dataBlocks(): array
    {
        $group = BlockGroup::Data;

        return [
            new BlockDefinition(
                'course-grid',
                'Course grid',
                'Courses, filtered and paginated. Never unbounded.',
                $group,
                self::styles([
                    ['cards', 'Cards'],
                    ['rows', 'Rows'],
                    ['compact', 'Compact'],
                ]),
            ),
            new BlockDefinition(
                'course-table',
                'Course table',
                'Courses as a sortable table for comparison-minded visitors.',
                $group,
                self::styles([
                    ['striped', 'Striped'],
                    ['bordered', 'Bordered'],
                ]),
                true,
                false,
            ),
            new BlockDefinition(
                'institution-grid',
                'Institution grid',
                'Institutions with their destination and ranking.',
                $group,
                self::styles([
                    ['cards', 'Cards'],
                    ['logos', 'Logo led'],
                    ['rows', 'Rows'],
                ]),
            ),
            new BlockDefinition(
                'destination-grid',
                'Destination grid',
                'Study destinations with their headline facts.',
                $group,
                self::styles([
                    ['cards', 'Cards'],
                    ['tiles', 'Image tiles'],
                    ['list', 'List'],
                ]),
            ),
            new BlockDefinition(
                'scholarship-list',
                'Scholarship list',
                'Scholarships ordered by deadline, with the closed ones marked rather than hidden.',
                $group,
                self::styles([
                    ['rows', 'Rows'],
                    ['cards', 'Cards'],
                    ['deadline', 'Deadline led'],
                ]),
            ),
            new BlockDefinition(
                'event-list',
                'Event list',
                'Upcoming open days and fairs, with past events dropped automatically.',
                $group,
                self::styles([
                    ['rows', 'Rows'],
                    ['cards', 'Cards'],
                    ['calendar', 'Calendar'],
                ]),
            ),
            new BlockDefinition(
                'comparison-table',
                'Comparison table',
                'Two to four items side by side, driven by the visitor’s selection.',
                $group,
                self::styles([
                    ['plain', 'Plain'],
                    ['highlight-differences', 'Highlight differences'],
                ]),
                true,
                false,
                ['compare'],
            ),
            new BlockDefinition(
                'ranking-badge',
                'Ranking badge',
                'An institution’s ranking, stated with its source and year.',
                $group,
                self::styles([
                    ['badge', 'Badge'],
                    ['inline', 'Inline'],
                ]),
                true,
                false,
            ),
            new BlockDefinition(
                'intake-calendar',
                'Intake calendar',
                'Which intakes are open, and when they close.',
                $group,
                self::styles([
                    ['grid', 'Grid'],
                    ['list', 'List'],
                    ['timeline', 'Timeline'],
                ]),
            ),
        ];
    }

    /**
     * @return list<BlockDefinition>
     */
    private static function mediaBlocks(): array
    {
        $group = BlockGroup::Media;

        return [
            new BlockDefinition(
                'gallery-grid',
                'Gallery',
                'Images in a grid, with a lightbox that is keyboard-operable.',
                $group,
                self::styles([
                    ['grid', 'Grid'],
                    ['masonry', 'Masonry'],
                    ['carousel', 'Carousel'],
                ]),
                true,
                true,
                ['lightbox', 'carousel'],
            ),
            new BlockDefinition(
                'media-text',
                'Media and text',
                'An image beside copy, with the image on either side.',
                $group,
                self::styles([
                    ['media-start', 'Media first'],
                    ['media-end', 'Text first'],
                    ['overlap', 'Overlapping'],
                ]),
            ),
            new BlockDefinition(
                'video',
                'Video',
                'A video that loads its player only once someone asks for it.',
                $group,
                self::styles([
                    ['inline', 'Inline'],
                    ['poster', 'Poster first'],
                    ['background', 'Background'],
                ]),
                true,
                true,
                ['video'],
            ),
            new BlockDefinition(
                'logo-wall',
                'Logo wall',
                'Partner and accreditation logos, greyscale until hovered.',
                $group,
                self::styles([
                    ['grid', 'Grid'],
                    ['marquee', 'Marquee'],
                    ['rows', 'Rows'],
                ]),
                true,
                true,
                ['marquee'],
            ),
            new BlockDefinition(
                'before-after',
                'Before and after',
                'Two images behind a draggable divider.',
                $group,
                self::styles([
                    ['slider', 'Slider'],
                    ['side-by-side', 'Side by side'],
                ]),
                true,
                true,
                ['before-after'],
            ),
            new BlockDefinition(
                'testimonial-slider',
                'Testimonial slider',
                'Testimonials in a carousel that pauses on focus and on hover.',
                $group,
                self::styles([
                    ['cards', 'Cards'],
                    ['quotes', 'Quotes'],
                    ['single', 'One at a time'],
                ]),
                true,
                true,
                ['carousel'],
            ),
        ];
    }

    /**
     * @return list<BlockDefinition>
     */
    private static function conversionBlocks(): array
    {
        $group = BlockGroup::Conversion;

        return [
            new BlockDefinition(
                'cta-banner',
                'Call to action',
                'A closing banner with one action, or two at most.',
                $group,
                self::styles([
                    ['accent', 'Accent'],
                    ['tinted', 'Tinted'],
                    ['bordered', 'Bordered'],
                    ['split', 'Split'],
                ]),
            ),
            new BlockDefinition(
                'lead-form',
                'Lead form',
                'Any built form, placed inline.',
                $group,
                self::styles([
                    ['card', 'Card'],
                    ['plain', 'Plain'],
                    ['inline', 'Inline'],
                ]),
                true,
                true,
                ['form'],
            ),
            new BlockDefinition(
                'eligibility-quiz',
                'Eligibility check',
                'The multi-step quiz that matches courses and captures the lead.',
                $group,
                self::styles([
                    ['card', 'Card'],
                    ['full', 'Full width'],
                ]),
                true,
                true,
                ['eligibility', 'form'],
            ),
            new BlockDefinition(
                'cost-calculator',
                'Cost calculator',
                'Tuition, living, visa, flights and insurance, converted once at the end.',
                $group,
                self::styles([
                    ['card', 'Card'],
                    ['inline', 'Inline'],
                ]),
                true,
                true,
                ['calculator'],
            ),
            new BlockDefinition(
                'booking',
                'Book counselling',
                'A booking prompt that carries the page it was clicked from into the lead.',
                $group,
                self::styles([
                    ['banner', 'Banner'],
                    ['card', 'Card'],
                    ['inline', 'Inline'],
                ]),
                true,
                true,
                ['form'],
            ),
            new BlockDefinition(
                'newsletter',
                'Newsletter signup',
                'One field and a consent checkbox.',
                $group,
                self::styles([
                    ['inline', 'Inline'],
                    ['stacked', 'Stacked'],
                    ['card', 'Card'],
                ]),
                true,
                true,
                ['form'],
            ),
        ];
    }
}
