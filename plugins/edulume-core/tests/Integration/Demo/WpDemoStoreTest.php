<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Integration\Demo;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Infrastructure\Content\ContentRegistrar;
use Edulume\Core\Infrastructure\Demo\WpDemoStore;
use WP_UnitTestCase;

/**
 * Proves an imported item is wired to the items it names, not just created next to them.
 *
 * The country page finds its universities through `_edulume_rel_institution_destination`. The
 * pack used to declare that link as a taxonomy term instead, on a taxonomy nothing registers,
 * so the import succeeded and every country page rendered its empty state.
 */
final class WpDemoStoreTest extends WP_UnitTestCase
{
    private WpDemoStore $store;

    public function set_up(): void
    {
        parent::set_up();

        (new ContentRegistrar())->registerTaxonomies();
        (new ContentRegistrar())->registerPostTypes();
        (new ContentRegistrar())->registerRelationships();

        $this->store = new WpDemoStore();
    }

    /** @test */
    public function it_attaches_an_institution_to_the_destination_it_names(): void
    {
        $destinationId = $this->store->createItem(
            'boutique',
            ContentModel::postTypeKey('destination'),
            ['title' => 'United Kingdom']
        );

        $institutionId = $this->store->createItem(
            'boutique',
            ContentModel::postTypeKey('institution'),
            [
                'title' => 'University of Glasgow',
                'relationships' => ['institution_destination' => 'United Kingdom'],
            ]
        );

        $this->assertGreaterThan(0, $destinationId);
        $this->assertSame(
            (string) $destinationId,
            get_post_meta($institutionId, '_edulume_rel_institution_destination', true)
        );
    }

    /** @test */
    public function the_country_page_query_finds_what_the_import_attached(): void
    {
        $destinationId = $this->store->createItem(
            'boutique',
            ContentModel::postTypeKey('destination'),
            ['title' => 'Canada']
        );

        foreach (['University of Toronto', 'University of Waterloo'] as $title) {
            $this->store->createItem(
                'boutique',
                ContentModel::postTypeKey('institution'),
                ['title' => $title, 'relationships' => ['institution_destination' => 'Canada']]
            );
        }

        $found = get_posts([
            'post_type' => ContentModel::postTypeKey('institution'),
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'no_found_rows' => true,
            'meta_key' => '_edulume_rel_institution_destination',
            'meta_value' => (string) $destinationId,
        ]);

        $this->assertCount(2, $found);
    }

    /** @test */
    public function a_relationship_naming_something_absent_stores_nothing(): void
    {
        $institutionId = $this->store->createItem(
            'boutique',
            ContentModel::postTypeKey('institution'),
            [
                'title' => 'Somewhere Else',
                'relationships' => ['institution_destination' => 'Atlantis'],
            ]
        );

        $this->assertSame(
            '',
            get_post_meta($institutionId, '_edulume_rel_institution_destination', true)
        );
    }

    /** @test */
    public function a_relationship_declared_on_the_wrong_post_type_is_ignored(): void
    {
        $this->store->createItem('boutique', ContentModel::postTypeKey('destination'), ['title' => 'Japan']);

        $destinationId = $this->store->createItem(
            'boutique',
            ContentModel::postTypeKey('destination'),
            [
                'title' => 'Germany',
                'relationships' => ['institution_destination' => 'Japan'],
            ]
        );

        $this->assertSame(
            '',
            get_post_meta($destinationId, '_edulume_rel_institution_destination', true)
        );
    }
}
