<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Integration\Content;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Infrastructure\Content\ContentRegistrar;
use Edulume\Core\Infrastructure\Content\PostTypeAccentOverride;
use PHPUnit\Framework\Attributes\Test;
use WP_UnitTestCase;

/**
 * Proves the content model actually registers: correct labels, correct rewrite rules,
 * relationships that resolve in both directions, and content that survives a theme switch.
 */
final class ContentRegistrarTest extends WP_UnitTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        (new ContentRegistrar())->registerTaxonomies();
        (new ContentRegistrar())->registerPostTypes();
        (new ContentRegistrar())->registerRelationships();
        (new ContentRegistrar())->registerAccentOverrides();
    }

    #[Test]
    public function it_registers_every_post_type_with_its_labels_and_rewrite_rules(): void
    {
        foreach (ContentModel::postTypes() as $definition) {
            $registered = get_post_type_object($definition->key);

            $this->assertNotNull($registered, sprintf('%s did not register.', $definition->key));
            $this->assertSame($definition->pluralLabel, $registered->labels->name);
            $this->assertSame($definition->singularLabel, $registered->labels->singular_name);
            $this->assertTrue($registered->show_in_rest);
            $this->assertTrue((bool) $registered->has_archive);
            $this->assertSame($definition->rewriteBase, $registered->rewrite['slug']);
        }
    }

    #[Test]
    public function it_registers_every_taxonomy_against_its_post_types(): void
    {
        foreach (ContentModel::taxonomies() as $definition) {
            $registered = get_taxonomy($definition->key);

            $this->assertNotFalse($registered, sprintf('%s did not register.', $definition->key));
            $this->assertSame($definition->isHierarchical, $registered->hierarchical);
            $this->assertNotEmpty($registered->object_type);
        }
    }

    #[Test]
    public function it_resolves_a_relationship_in_both_directions(): void
    {
        $institution = self::factory()->post->create(['post_type' => ContentModel::postTypeKey('institution')]);
        $course = self::factory()->post->create(['post_type' => ContentModel::postTypeKey('course')]);

        $relationship = ContentModel::relationshipsFor(ContentModel::postTypeKey('course'))[0];

        update_post_meta($course, $relationship->metaKey(), $institution);

        $this->assertSame($institution, (int) get_post_meta($course, $relationship->metaKey(), true));

        $coursesAtInstitution = get_posts([
            'post_type' => ContentModel::postTypeKey('course'),
            'posts_per_page' => 10,
            'meta_key' => $relationship->metaKey(),
            'meta_value' => $institution,
            'fields' => 'ids',
        ]);

        $this->assertSame([$course], $coursesAtInstitution);
    }

    #[Test]
    public function it_keeps_content_when_the_theme_changes(): void
    {
        $course = self::factory()->post->create([
            'post_type' => ContentModel::postTypeKey('course'),
            'post_title' => 'MSc Data Science',
        ]);

        switch_theme('twentytwentyfour');
        (new ContentRegistrar())->registerPostTypes();

        $this->assertSame('MSc Data Science', get_the_title($course));
    }

    #[Test]
    public function it_stores_a_curated_accent_override_on_an_item(): void
    {
        $course = self::factory()->post->create(['post_type' => ContentModel::postTypeKey('course')]);

        update_post_meta($course, PostTypeAccentOverride::META_KEY, 'ivy-green');

        $this->assertSame('ivy-green', get_post_meta($course, PostTypeAccentOverride::META_KEY, true));
    }

    #[Test]
    public function it_normalises_a_custom_accent_hex(): void
    {
        $this->assertSame('#7a2e6b', PostTypeAccentOverride::sanitize('#7A2E6B'));
        $this->assertSame('ivy-green', PostTypeAccentOverride::sanitize(' ivy-green '));
        $this->assertSame('', PostTypeAccentOverride::sanitize('not a colour'));
        $this->assertSame('', PostTypeAccentOverride::sanitize(42));
        $this->assertSame('', PostTypeAccentOverride::sanitize(''));
    }

    #[Test]
    public function it_lets_a_site_owner_localise_a_rewrite_base(): void
    {
        add_filter(
            ContentRegistrar::REWRITE_BASE_FILTER,
            static fn (string $base, string $key): string
                => $key === ContentModel::postTypeKey('course') ? 'kurse' : $base,
            10,
            2,
        );

        (new ContentRegistrar())->registerPostTypes();

        $registered = get_post_type_object(ContentModel::postTypeKey('course'));

        $this->assertNotNull($registered);
        $this->assertSame('kurse', $registered->rewrite['slug']);
    }
}
