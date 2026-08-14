<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Blocks;

use Edulume\Core\Domain\Blocks\BlockCatalogue;
use Edulume\Core\Domain\Blocks\BlockDefinition;
use Edulume\Core\Domain\Blocks\BlockGroup;
use Edulume\Core\Domain\Blocks\BlockVariation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BlockCatalogueTest extends TestCase
{
    #[Test]
    public function it_ships_at_least_the_thirty_five_blocks_the_brief_asks_for(): void
    {
        self::assertGreaterThanOrEqual(35, count(BlockCatalogue::all()));
    }

    /**
     * @return list<array{BlockDefinition}>
     */
    public static function blocks(): array
    {
        return array_map(
            static fn (BlockDefinition $block): array => [$block],
            BlockCatalogue::all(),
        );
    }

    #[Test]
    #[DataProvider('blocks')]
    public function every_block_ships_between_two_and_four_style_variations(BlockDefinition $block): void
    {
        self::assertGreaterThanOrEqual(2, count($block->variations), $block->slug);
        self::assertLessThanOrEqual(4, count($block->variations), $block->slug);
    }

    #[Test]
    #[DataProvider('blocks')]
    public function every_block_has_exactly_one_default_variation(BlockDefinition $block): void
    {
        $defaults = array_filter(
            $block->variations,
            static fn (BlockVariation $variation): bool => $variation->isDefault,
        );

        self::assertCount(1, $defaults, $block->slug);
        self::assertTrue($block->defaultVariation()->isDefault, $block->slug);
    }

    #[Test]
    #[DataProvider('blocks')]
    public function every_block_is_namespaced_titled_and_described(BlockDefinition $block): void
    {
        self::assertStringStartsWith('edulume/', $block->name());
        self::assertNotSame('', $block->title, $block->slug);
        self::assertGreaterThan(20, strlen($block->description), $block->slug);
    }

    #[Test]
    #[DataProvider('blocks')]
    public function every_variation_slug_is_unique_within_its_block(BlockDefinition $block): void
    {
        $slugs = $block->variationSlugs();

        self::assertSame($slugs, array_values(array_unique($slugs)), $block->slug);
    }

    #[Test]
    public function block_slugs_are_unique_across_the_whole_library(): void
    {
        $names = array_map(static fn (BlockDefinition $block): string => $block->name(), BlockCatalogue::all());

        self::assertSame($names, array_values(array_unique($names)));
    }

    #[Test]
    public function every_group_carries_blocks_and_a_distinct_category_slug(): void
    {
        $slugs = [];

        foreach (BlockGroup::all() as $group) {
            self::assertNotSame([], BlockCatalogue::inGroup($group), $group->value);
            self::assertNotSame('', $group->label());
            self::assertStringStartsWith('edulume-', $group->categorySlug());
            $slugs[] = $group->categorySlug();
        }

        self::assertSame($slugs, array_values(array_unique($slugs)));
    }

    #[Test]
    public function the_groups_partition_the_library_with_nothing_left_over(): void
    {
        $counted = 0;

        foreach (BlockGroup::all() as $group) {
            $counted += count(BlockCatalogue::inGroup($group));
        }

        self::assertSame(count(BlockCatalogue::all()), $counted);
    }

    #[Test]
    public function a_block_is_found_by_slug_or_by_full_name(): void
    {
        self::assertSame('hero', BlockCatalogue::find('hero')?->slug);
        self::assertSame('hero', BlockCatalogue::find('edulume/hero')?->slug);
        self::assertNull(BlockCatalogue::find('not-a-block'));
    }

    #[Test]
    public function the_variation_class_falls_back_to_the_default_for_an_unknown_variation(): void
    {
        $hero = BlockCatalogue::find('hero');

        self::assertNotNull($hero);
        self::assertSame('edulume-block edulume-block--hero is-style-split', $hero->classFor('split'));
        self::assertSame('edulume-block edulume-block--hero is-style-centred', $hero->classFor('nonsense'));
        self::assertTrue($hero->hasVariation('overlay'));
        self::assertFalse($hero->hasVariation('overlay-2'));
    }

    #[Test]
    public function blocks_are_accent_aware_by_default_and_opt_out_explicitly(): void
    {
        foreach (BlockCatalogue::all() as $block) {
            self::assertTrue($block->isAccentAware, $block->slug);
        }

        self::assertFalse(BlockCatalogue::find('divider')?->isMotionAware);
        self::assertTrue(BlockCatalogue::find('hero')?->isMotionAware);
    }

    #[Test]
    public function the_declared_features_are_the_modules_the_conditional_loader_can_be_asked_for(): void
    {
        $features = BlockCatalogue::features();

        self::assertSame($features, array_values(array_unique($features)));
        self::assertContains('carousel', $features);
        self::assertContains('form', $features);
        self::assertContains('eligibility', $features);
        self::assertSame($features, array_values($features));
    }

    #[Test]
    public function a_block_with_no_script_declares_no_feature(): void
    {
        self::assertSame([], BlockCatalogue::find('quote')?->features);
        self::assertSame(['carousel'], BlockCatalogue::find('testimonial-slider')?->features);
    }
}
