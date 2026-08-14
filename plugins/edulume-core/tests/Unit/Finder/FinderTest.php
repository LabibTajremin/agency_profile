<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Finder;

use Edulume\Core\Domain\Finder\ComparisonSet;
use Edulume\Core\Domain\Finder\FacetDefinition;
use Edulume\Core\Domain\Finder\FacetSource;
use Edulume\Core\Domain\Finder\FinderCatalogue;
use Edulume\Core\Domain\Finder\FinderQuery;
use Edulume\Core\Domain\Finder\FinderSort;
use Edulume\Core\Domain\Finder\Shortlist;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FinderTest extends TestCase
{
    /**
     * @return list<FacetDefinition>
     */
    private function facets(): array
    {
        return FinderCatalogue::facetsFor('edulume_course');
    }

    #[Test]
    public function a_facet_keeps_only_the_values_it_declares(): void
    {
        $facet = new FacetDefinition('duration', 'Duration', FacetSource::Meta, false, ['6', '12']);

        self::assertSame(['6'], $facet->accept(['6', '999']));
        self::assertSame([], $facet->accept('999'));
        self::assertSame(['12'], $facet->accept('12'));
    }

    #[Test]
    public function a_multiple_facet_deduplicates_and_a_single_one_takes_the_first(): void
    {
        $multiple = new FacetDefinition('level', 'Study level', FacetSource::Taxonomy);
        $single = new FacetDefinition('coverage', 'Coverage', FacetSource::Meta, false);

        self::assertSame(['masters', 'phd'], $multiple->accept(['masters', 'phd', 'masters']));
        self::assertSame(['full'], $single->accept(['full', 'partial']));
    }

    #[Test]
    public function a_facet_ignores_empty_and_non_scalar_values(): void
    {
        $facet = new FacetDefinition('level', 'Study level', FacetSource::Taxonomy);

        self::assertSame([], $facet->accept(['', '   ', ['nested'], null]));
    }

    #[Test]
    public function a_query_built_from_a_request_keeps_only_declared_facets(): void
    {
        $query = FinderQuery::fromRequest('edulume_course', $this->facets(), [
            'level' => ['masters'],
            'field' => 'engineering',
            'not_a_facet' => 'ignored',
            'q' => '  data science ',
        ]);

        self::assertSame(['level' => ['masters'], 'field' => ['engineering']], $query->selections);
        self::assertSame('data science', $query->search);
        self::assertTrue($query->hasSelections());
    }

    #[Test]
    public function a_hostile_page_size_is_clamped_rather_than_rejected(): void
    {
        $query = FinderQuery::fromRequest('edulume_course', [], ['per_page' => '100000', 'page' => '-5']);

        self::assertSame(FinderQuery::MAX_PER_PAGE, $query->perPage);
        self::assertSame(1, $query->page);
    }

    #[Test]
    public function a_non_numeric_page_falls_back_to_the_first_one(): void
    {
        $query = FinderQuery::fromRequest('edulume_course', [], ['page' => 'nine', 'per_page' => 'lots']);

        self::assertSame(1, $query->page);
        self::assertSame(FinderQuery::DEFAULT_PER_PAGE, $query->perPage);
    }

    #[Test]
    public function the_offset_is_derived_from_the_page(): void
    {
        $query = new FinderQuery('edulume_course', page: 3, perPage: 20);

        self::assertSame(40, $query->offset());
        self::assertSame(0, (new FinderQuery('edulume_course'))->offset());
    }

    #[Test]
    public function the_state_round_trips_through_the_url_in_a_stable_order(): void
    {
        $first = new FinderQuery('edulume_course', ['field' => ['b', 'a'], 'level' => ['masters']], 'x', FinderSort::Newest, 2);
        $second = new FinderQuery('edulume_course', ['level' => ['masters'], 'field' => ['a', 'b']], 'x', FinderSort::Newest, 2);

        self::assertSame($first->toQueryParameters(), $second->toQueryParameters());
        self::assertSame('field=a%2Cb&level=masters&q=x&sort=newest&page=2', $first->toQueryString());
    }

    #[Test]
    public function a_default_query_produces_a_clean_url(): void
    {
        self::assertSame('', (new FinderQuery('edulume_course'))->toQueryString());
    }

    #[Test]
    public function a_url_reproduces_the_filter_that_created_it(): void
    {
        $original = new FinderQuery(
            'edulume_course',
            ['level' => ['masters'], 'duration' => ['12']],
            'engineering',
            FinderSort::TuitionLowest,
            2,
            48,
        );

        parse_str($original->toQueryString(), $parsed);

        $rebuilt = FinderQuery::fromRequest('edulume_course', $this->facets(), array_map(
            static fn (mixed $value): mixed => is_string($value) && str_contains($value, ',')
                ? explode(',', $value)
                : $value,
            $parsed,
        ));

        self::assertEquals($original, $rebuilt);
    }

    #[Test]
    public function changing_a_filter_returns_to_the_first_page(): void
    {
        $query = (new FinderQuery('edulume_course', page: 9))->withSelection('level', ['phd']);

        self::assertSame(1, $query->page);
        self::assertSame(['level' => ['phd']], $query->selections);
        self::assertSame(1, $query->withSort(FinderSort::Newest)->page);
    }

    #[Test]
    public function clearing_a_facet_removes_it_rather_than_storing_an_empty_list(): void
    {
        $query = (new FinderQuery('edulume_course', ['level' => ['phd']]))->withSelection('level', []);

        self::assertSame([], $query->selections);
        self::assertFalse($query->hasSelections());
    }

    #[Test]
    public function clearing_the_whole_query_keeps_the_sort_but_drops_the_filters(): void
    {
        $cleared = (new FinderQuery('edulume_course', ['level' => ['phd']], 'x', FinderSort::Newest, 4))->cleared();

        self::assertSame([], $cleared->selections);
        self::assertSame('', $cleared->search);
        self::assertSame(FinderSort::Newest, $cleared->sort);
        self::assertSame(1, $cleared->page);
        self::assertSame(3, (new FinderQuery('edulume_course'))->withPage(3)->page);
    }

    #[Test]
    public function every_sort_maps_to_an_indexed_column_or_meta_key(): void
    {
        foreach (FinderSort::cases() as $sort) {
            self::assertNotSame('', $sort->label());
        }

        self::assertSame('edulume_deadline', FinderSort::DeadlineSoonest->metaKey());
        self::assertSame('edulume_tuition_amount', FinderSort::TuitionLowest->metaKey());
        self::assertNull(FinderSort::TitleAsc->metaKey());
        self::assertTrue(FinderSort::TuitionHighest->isDescending());
        self::assertFalse(FinderSort::TuitionLowest->isDescending());
        self::assertTrue(FinderSort::Newest->isDescending());
        self::assertTrue(FinderSort::TitleDesc->isDescending());
    }

    #[Test]
    public function a_finder_only_offers_sorts_its_content_can_satisfy(): void
    {
        self::assertContains(FinderSort::TuitionLowest, FinderSort::forPostType('edulume_course'));
        self::assertNotContains(FinderSort::DeadlineSoonest, FinderSort::forPostType('edulume_course'));
        self::assertContains(FinderSort::DeadlineSoonest, FinderSort::forPostType('edulume_scholarship'));
        self::assertSame(4, count(FinderSort::forPostType('edulume_institution')));
    }

    #[Test]
    public function the_catalogue_declares_facets_for_the_three_finders_and_nothing_else(): void
    {
        self::assertSame(['edulume_course', 'edulume_institution', 'edulume_scholarship'], FinderCatalogue::postTypes());
        self::assertTrue(FinderCatalogue::supports('edulume_course'));
        self::assertFalse(FinderCatalogue::supports('edulume_testimonial'));
        self::assertSame([], FinderCatalogue::facetsFor('edulume_testimonial'));

        foreach (FinderCatalogue::postTypes() as $postType) {
            $facets = FinderCatalogue::facetsFor($postType);

            self::assertNotSame([], $facets, $postType);

            foreach ($facets as $facet) {
                self::assertNotSame('', $facet->label, $facet->key);
            }
        }
    }

    #[Test]
    public function a_comparison_holds_between_two_and_four_items(): void
    {
        $set = ComparisonSet::empty()->add(1)->add(2);

        self::assertFalse(ComparisonSet::empty()->add(1)->isComparable());
        self::assertTrue($set->isComparable());
        self::assertSame(2, $set->count());
    }

    #[Test]
    public function a_comparison_refuses_a_fifth_item_rather_than_dropping_the_first(): void
    {
        $full = ComparisonSet::of([1, 2, 3, 4]);

        self::assertTrue($full->isFull());
        self::assertSame([1, 2, 3, 4], $full->add(5)->itemIds);
    }

    #[Test]
    public function a_comparison_ignores_duplicates_and_invalid_ids(): void
    {
        self::assertSame([7], ComparisonSet::of([7, 7, 0, -3])->itemIds);
        self::assertSame([7], ComparisonSet::empty()->add(7)->add(7)->add(0)->itemIds);
        self::assertTrue(ComparisonSet::of([7])->contains(7));
    }

    #[Test]
    public function a_comparison_round_trips_through_its_url_parameter(): void
    {
        $set = ComparisonSet::of([12, 45, 78]);

        self::assertSame('12,45,78', $set->toParameter());
        self::assertEquals($set, ComparisonSet::fromParameter('12,45,78'));
        self::assertEquals($set, ComparisonSet::fromParameter(' 12 , 45 ,78 '));
        self::assertSame([], ComparisonSet::fromParameter('nope,-1,')->itemIds);
        self::assertSame([45], $set->remove(12)->remove(78)->itemIds);
    }

    #[Test]
    public function a_shortlist_toggles_rather_than_adding_twice(): void
    {
        $shortlist = Shortlist::empty()->toggle(5);

        self::assertTrue($shortlist->contains(5));
        self::assertFalse($shortlist->toggle(5)->contains(5));
        self::assertSame(1, $shortlist->count());
        self::assertTrue(Shortlist::empty()->isEmpty());
        self::assertSame([], Shortlist::empty()->toggle(0)->itemIds);
    }

    #[Test]
    public function a_shortlist_is_bounded_so_a_script_cannot_fill_a_visitors_storage(): void
    {
        $shortlist = Shortlist::of(range(1, Shortlist::MAXIMUM + 20));

        self::assertSame(Shortlist::MAXIMUM, $shortlist->count());
    }

    #[Test]
    public function a_shortlist_round_trips_through_storage(): void
    {
        $shortlist = Shortlist::of([3, 9, 27]);

        self::assertSame('[3,9,27]', $shortlist->toJson());
        self::assertEquals($shortlist, Shortlist::fromJson('[3,9,27]'));
        self::assertEquals($shortlist, Shortlist::fromJson('["3","9","27"]'));
    }

    #[Test]
    public function unreadable_storage_reads_as_an_empty_shortlist(): void
    {
        self::assertTrue(Shortlist::fromJson('not json')->isEmpty());
        self::assertTrue(Shortlist::fromJson('"a string"')->isEmpty());
        self::assertSame([4], Shortlist::fromJson('[4, "x", null, 0]')->itemIds);
    }
}
