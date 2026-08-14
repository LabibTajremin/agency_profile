<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Tracking;

use Edulume\Core\Domain\Tracking\ConsentCategory;
use Edulume\Core\Domain\Tracking\ConsentState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConsentCookieTest extends TestCase
{
    #[Test]
    public function an_absent_cookie_means_undecided_rather_than_rejected(): void
    {
        $state = ConsentState::fromCookie('');

        self::assertFalse($state->hasBeenAnswered());
        self::assertFalse($state->allows(ConsentCategory::Analytics));
        self::assertTrue($state->allows(ConsentCategory::Necessary));
    }

    #[Test]
    public function an_unreadable_cookie_also_means_undecided(): void
    {
        self::assertFalse(ConsentState::fromCookie('nonsense,garbage')->hasBeenAnswered());
        self::assertFalse(ConsentState::fromCookie('   ')->hasBeenAnswered());
    }

    #[Test]
    public function the_rejection_marker_is_an_answer_not_an_absence(): void
    {
        $state = ConsentState::fromCookie('none');

        self::assertTrue($state->hasBeenAnswered());
        self::assertFalse($state->allows(ConsentCategory::Analytics));
        self::assertFalse($state->allows(ConsentCategory::Marketing));
        self::assertTrue($state->allows(ConsentCategory::Necessary));
    }

    #[Test]
    public function granted_categories_round_trip_through_the_cookie(): void
    {
        $state = ConsentState::granting([ConsentCategory::Analytics, ConsentCategory::Preferences]);
        $cookie = $state->toCookie();

        self::assertSame('analytics,preferences', $cookie);
        self::assertEquals($state, ConsentState::fromCookie($cookie));
    }

    #[Test]
    public function a_rejection_round_trips_as_the_marker(): void
    {
        self::assertSame('none', ConsentState::rejectingEverything()->toCookie());
        self::assertEquals(
            ConsentState::rejectingEverything(),
            ConsentState::fromCookie(ConsentState::rejectingEverything()->toCookie()),
        );
    }

    #[Test]
    public function an_undecided_state_stores_no_cookie_at_all(): void
    {
        self::assertSame('', ConsentState::undecided()->toCookie());
    }

    #[Test]
    public function the_necessary_category_is_never_written_to_the_cookie(): void
    {
        $cookie = ConsentState::granting([ConsentCategory::Necessary, ConsentCategory::Analytics])->toCookie();

        self::assertSame('analytics', $cookie);
    }

    #[Test]
    public function whitespace_around_a_category_is_tolerated(): void
    {
        self::assertTrue(ConsentState::fromCookie(' analytics , marketing ')->allows(ConsentCategory::Marketing));
    }

    #[Test]
    public function every_category_explains_itself_in_the_visitors_terms(): void
    {
        foreach (ConsentCategory::cases() as $category) {
            self::assertNotSame('', $category->label(), $category->value);
            self::assertGreaterThan(20, strlen($category->description()), $category->value);
        }
    }
}
