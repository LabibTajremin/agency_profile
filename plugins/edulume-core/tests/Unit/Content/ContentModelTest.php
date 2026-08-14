<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Content;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Content\PostTypeDefinition;
use Edulume\Core\Domain\Content\TaxonomyDefinition;
use Edulume\Core\Domain\Content\UnknownPostTypeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContentModelTest extends TestCase
{
    /**
     * WordPress truncates a post type key at 20 characters, and a key that is silently
     * truncated registers something nobody asked for.
     */
    private const MAXIMUM_KEY_LENGTH = 20;

    /** A taxonomy key is capped at 32 characters, for the same reason. */
    private const MAXIMUM_TAXONOMY_KEY_LENGTH = 32;

    /**
     * @return array<string, array{PostTypeDefinition}>
     */
    public static function postTypeProvider(): array
    {
        $cases = [];

        foreach (ContentModel::postTypes() as $postType) {
            $cases[$postType->key] = [$postType];
        }

        return $cases;
    }

    /**
     * @return array<string, array{TaxonomyDefinition}>
     */
    public static function taxonomyProvider(): array
    {
        $cases = [];

        foreach (ContentModel::taxonomies() as $taxonomy) {
            $cases[$taxonomy->key] = [$taxonomy];
        }

        return $cases;
    }

    #[Test]
    public function it_defines_sixteen_post_types(): void
    {
        $this->assertCount(ContentModel::POST_TYPE_COUNT, ContentModel::postTypes());
    }

    #[Test]
    #[DataProvider('postTypeProvider')]
    public function it_prefixes_every_post_type_key(PostTypeDefinition $postType): void
    {
        $this->assertStringStartsWith(PostTypeDefinition::KEY_PREFIX, $postType->key);
    }

    #[Test]
    #[DataProvider('postTypeProvider')]
    public function it_keeps_every_post_type_key_short_enough_for_wordpress(PostTypeDefinition $postType): void
    {
        $this->assertLessThanOrEqual(self::MAXIMUM_KEY_LENGTH, strlen($postType->key), $postType->key);
    }

    #[Test]
    #[DataProvider('postTypeProvider')]
    public function it_gives_every_post_type_labels_a_rewrite_base_and_an_icon(PostTypeDefinition $postType): void
    {
        $this->assertNotSame('', $postType->singularLabel);
        $this->assertNotSame('', $postType->pluralLabel);
        $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+)*$/', $postType->rewriteBase);
        $this->assertStringStartsWith('dashicons-', $postType->menuIcon);
        $this->assertNotSame($postType->singularLabel, '');
    }

    #[Test]
    #[DataProvider('postTypeProvider')]
    public function it_names_every_post_type_uniquely(PostTypeDefinition $postType): void
    {
        $matching = array_filter(
            ContentModel::postTypes(),
            static fn (PostTypeDefinition $candidate): bool => $candidate->key === $postType->key,
        );

        $this->assertCount(1, $matching);
    }

    #[Test]
    public function it_gives_every_post_type_a_distinct_rewrite_base(): void
    {
        $bases = array_map(
            static fn (PostTypeDefinition $postType): string => $postType->rewriteBase,
            ContentModel::postTypes(),
        );

        $this->assertSame($bases, array_values(array_unique($bases)));
    }

    #[Test]
    #[DataProvider('postTypeProvider')]
    public function it_always_supports_a_title_an_editor_and_a_thumbnail(PostTypeDefinition $postType): void
    {
        foreach (['title', 'editor', 'thumbnail', 'revisions'] as $feature) {
            $this->assertContains($feature, $postType->supports());
        }
    }

    #[Test]
    public function it_omits_the_excerpt_when_a_type_does_not_want_one(): void
    {
        $withoutExcerpt = PostTypeDefinition::of('x', 'X', 'Xs', 'xs', 'dashicons-x', [], true, false);

        $this->assertNotContains('excerpt', $withoutExcerpt->supports());
    }

    #[Test]
    #[DataProvider('postTypeProvider')]
    public function it_points_every_post_type_at_taxonomies_that_exist(PostTypeDefinition $postType): void
    {
        $known = array_map(
            static fn (TaxonomyDefinition $taxonomy): string => $taxonomy->key,
            ContentModel::taxonomies(),
        );

        foreach ($postType->taxonomyKeys as $taxonomyKey) {
            $this->assertContains($taxonomyKey, $known);
            $this->assertTrue($postType->hasTaxonomy($taxonomyKey));
        }

        $this->assertFalse($postType->hasTaxonomy('edulume_not-a-taxonomy'));
    }

    #[Test]
    #[DataProvider('taxonomyProvider')]
    public function it_attaches_every_taxonomy_to_at_least_one_post_type(TaxonomyDefinition $taxonomy): void
    {
        $attached = array_filter(
            ContentModel::postTypes(),
            static fn (PostTypeDefinition $postType): bool => $postType->hasTaxonomy($taxonomy->key),
        );

        $this->assertNotEmpty($attached, sprintf('%s is attached to nothing.', $taxonomy->key));
    }

    #[Test]
    #[DataProvider('taxonomyProvider')]
    public function it_keeps_every_taxonomy_key_short_enough_for_wordpress(TaxonomyDefinition $taxonomy): void
    {
        $this->assertLessThanOrEqual(self::MAXIMUM_TAXONOMY_KEY_LENGTH, strlen($taxonomy->key), $taxonomy->key);
    }

    #[Test]
    #[DataProvider('taxonomyProvider')]
    public function it_gives_every_taxonomy_labels_and_a_rewrite_base(TaxonomyDefinition $taxonomy): void
    {
        $this->assertStringStartsWith(TaxonomyDefinition::KEY_PREFIX, $taxonomy->key);
        $this->assertNotSame('', $taxonomy->labels()['name']);
        $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+)*$/', $taxonomy->rewriteBase);
    }

    #[Test]
    public function it_keeps_intakes_flat_and_everything_else_hierarchical(): void
    {
        foreach (ContentModel::taxonomies() as $taxonomy) {
            $expected = $taxonomy->key !== ContentModel::taxonomyKey('intake');

            $this->assertSame($expected, $taxonomy->isHierarchical, $taxonomy->key);
        }
    }

    #[Test]
    public function it_relates_courses_to_institutions_in_both_directions(): void
    {
        $relationships = ContentModel::relationshipsFor(ContentModel::postTypeKey('course'));

        $this->assertNotEmpty($relationships);

        $courseToInstitution = $relationships[0];

        $this->assertSame(
            ContentModel::postTypeKey('institution'),
            $courseToInstitution->otherEnd(ContentModel::postTypeKey('course')),
        );
        $this->assertSame(
            ContentModel::postTypeKey('course'),
            $courseToInstitution->otherEnd(ContentModel::postTypeKey('institution')),
        );
    }

    #[Test]
    public function it_points_every_relationship_at_post_types_that_exist(): void
    {
        foreach (ContentModel::relationships() as $relationship) {
            $this->assertSame($relationship->fromPostTypeKey, ContentModel::postType($relationship->fromPostTypeKey)->key);
            $this->assertSame($relationship->toPostTypeKey, ContentModel::postType($relationship->toPostTypeKey)->key);
            $this->assertStringStartsWith('_edulume_rel_', $relationship->metaKey());
        }
    }

    #[Test]
    public function it_allows_a_scholarship_to_span_several_destinations(): void
    {
        foreach (ContentModel::relationships() as $relationship) {
            if ($relationship->key === 'scholarship_destination') {
                $this->assertTrue($relationship->allowsMany);

                return;
            }
        }

        $this->fail('The scholarship-to-destination relationship is missing.');
    }

    #[Test]
    public function it_reports_a_relationship_it_does_not_connect(): void
    {
        $relationships = ContentModel::relationships();

        $this->assertFalse($relationships[0]->connects(ContentModel::postTypeKey('branch')));
    }

    #[Test]
    public function it_rejects_a_post_type_outside_the_model(): void
    {
        $this->expectException(UnknownPostTypeException::class);

        ContentModel::postType('page');
    }
}
