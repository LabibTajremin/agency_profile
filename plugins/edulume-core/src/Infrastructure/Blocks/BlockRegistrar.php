<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Blocks;

use Edulume\Core\Domain\Blocks\BlockCatalogue;
use Edulume\Core\Domain\Blocks\BlockDefinition;
use Edulume\Core\Domain\Blocks\BlockGroup;
use Edulume\Core\Domain\Blocks\BlockVariation;

/**
 * Registers the block library with WordPress.
 *
 * Dynamic blocks with a server-side render callback, not saved markup. Saved markup means every
 * change to a block's HTML invalidates every post that used it — the "this block contains
 * unexpected or invalid content" dialog — and a product that ships updates cannot afford that.
 */
final class BlockRegistrar
{
    public function register(): void
    {
        add_filter('block_categories_all', [$this, 'addCategories']);
        add_action('init', [$this, 'registerBlocks']);
    }

    /**
     * @param array<int, array{slug: string, title: string, icon?: string|null}> $categories
     *
     * @return array<int, array{slug: string, title: string, icon?: string|null}>
     */
    public function addCategories(array $categories): array
    {
        $ours = [];

        foreach (BlockGroup::all() as $group) {
            $ours[] = [
                'slug' => $group->categorySlug(),
                'title' => $group->label(),
                'icon' => null,
            ];
        }

        return [...$ours, ...$categories];
    }

    public function registerBlocks(): void
    {
        foreach (BlockCatalogue::all() as $block) {
            register_block_type($block->name(), [
                'api_version' => 3,
                'title' => $block->title,
                'description' => $block->description,
                'category' => $block->group->categorySlug(),
                'attributes' => $this->attributesFor($block),
                'supports' => [
                    'html' => false,
                    'anchor' => true,
                    'align' => ['wide', 'full'],
                ],
                'render_callback' => fn (array $attributes, string $content): string
                    => $this->render($block, $attributes, $content),
            ]);

            $this->registerStyles($block);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function attributesFor(BlockDefinition $block): array
    {
        return [
            'variation' => [
                'type' => 'string',
                'default' => $block->defaultVariation()->slug,
            ],
            // An accent set on the block wins over the section's, which wins over the global
            // one. Empty means inherit — the same absence-is-inheritance rule as everywhere else.
            'accent' => [
                'type' => 'string',
                'default' => '',
            ],
        ];
    }

    private function registerStyles(BlockDefinition $block): void
    {
        foreach ($block->variations as $variation) {
            register_block_style($block->name(), [
                'name' => $variation->slug,
                'label' => $variation->label,
                'is_default' => $variation->isDefault,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function render(BlockDefinition $block, array $attributes, string $content): string
    {
        foreach ($block->features as $feature) {
            // Declared as the block renders, so the footer only enqueues a module for a block
            // that is actually on this page — including ones inside reusable blocks and
            // template parts, which scanning post content would miss.
            do_action('edulume_block_requires_feature', $feature);
        }

        $variation = is_string($attributes['variation'] ?? null) ? $attributes['variation'] : '';
        $accent = is_string($attributes['accent'] ?? null) ? $attributes['accent'] : '';

        $wrapper = get_block_wrapper_attributes([
            'class' => $block->classFor($variation),
            'data-edulume-block' => $block->slug,
        ]);

        return sprintf(
            '<div %s%s>%s</div>',
            $wrapper,
            $accent === '' ? '' : sprintf(' data-edulume-accent="%s"', esc_attr($accent)),
            $content
        );
    }

    /**
     * The variation list as the editor's style switcher needs it.
     *
     * @return array<string, list<string>>
     */
    public static function styleMap(): array
    {
        $map = [];

        foreach (BlockCatalogue::all() as $block) {
            $map[$block->name()] = array_map(
                static fn (BlockVariation $variation): string => $variation->slug,
                $block->variations,
            );
        }

        return $map;
    }
}
