<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\SectionId;
use Edulume\Core\Domain\Theming\SectionOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SectionOrder::class)]
final class SectionOrderTest extends TestCase
{
    public function testNoStoredOrderGivesTheDefault(): void
    {
        $expected = array_map(
            static fn (SectionId $section): string => $section->value,
            SectionId::homePageOrder()
        );

        self::assertSame($expected, SectionOrder::reconcile([]));
        self::assertSame($expected, SectionOrder::default());
    }

    public function testTheStoredOrderIsHonoured(): void
    {
        $order = SectionOrder::reconcile(['cta', 'hero']);

        self::assertSame('cta', $order[0]);
        self::assertSame('hero', $order[1]);
    }

    public function testASlugTheProductNoLongerHasIsDropped(): void
    {
        self::assertNotContains('carousel-of-doom', SectionOrder::reconcile(['hero', 'carousel-of-doom']));
    }

    public function testDuplicatesAreCollapsed(): void
    {
        $order = SectionOrder::reconcile(['hero', 'hero', 'cta']);

        self::assertSame(1, count(array_keys($order, 'hero', true)));
        self::assertCount(count(SectionId::homePageOrder()), $order);
    }

    public function testEverySectionSurvivesReconciliationExactlyOnce(): void
    {
        $order = SectionOrder::reconcile(['faq', 'hero']);

        self::assertCount(count(SectionId::homePageOrder()), $order);
        self::assertSame($order, array_values(array_unique($order)));
    }

    public function testANewSectionArrivesBesideItsDefaultNeighbourNotAtTheEnd(): void
    {
        /*
         * The upgrade case. The owner's stored order predates `statistics`, which by default
         * sits between `trust-bar` and `services`. Appending it would drop the site's headline
         * numbers to the bottom of the page after an update nobody asked for.
         */
        $order = SectionOrder::reconcile(['hero', 'trust-bar', 'services', 'cta']);

        self::assertSame(
            2,
            array_search('statistics', $order, true),
            'a new section should land where its default position says'
        );
    }

    public function testASectionWithNoSurvivingPredecessorGoesFirst(): void
    {
        $order = SectionOrder::reconcile(['cta']);

        self::assertSame('hero', $order[0]);
        self::assertSame('cta', $order[count($order) - 1]);
    }
}
