<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Admin;

use Edulume\Core\Domain\Admin\ScreenHelp;
use Edulume\Core\Infrastructure\Admin\AdminMenu;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every admin screen explains itself, in language a non-technical person can act on.
 *
 * The vocabulary check is the point of this file. This codebase says "tokens", "overrides",
 * "schema" and "resolver" everywhere, and every one of those words is meaningless to the person
 * who actually runs the consultancy. A help panel written in the implementation's vocabulary is
 * worse than no help panel, because it looks like an answer.
 */
final class ScreenHelpTest extends TestCase
{
    #[Test]
    public function every_admin_page_has_help(): void
    {
        foreach (array_keys(AdminMenu::pages()) as $slug) {
            self::assertNotNull(ScreenHelp::find($slug), $slug . ' has no help text');
        }
    }

    #[Test]
    public function no_help_describes_a_page_that_does_not_exist(): void
    {
        foreach (ScreenHelp::slugs() as $slug) {
            self::assertArrayHasKey($slug, AdminMenu::pages(), $slug . ' is help for nothing');
        }
    }

    /**
     * @return list<array{ScreenHelp}>
     */
    public static function screens(): array
    {
        return array_map(static fn (ScreenHelp $help): array => [$help], ScreenHelp::all());
    }

    #[Test]
    #[DataProvider('screens')]
    public function a_summary_says_what_the_screen_is_for(ScreenHelp $help): void
    {
        self::assertNotSame('', trim($help->summary), $help->slug);
        self::assertGreaterThan(40, strlen($help->summary), $help->slug . ' says too little');
    }

    #[Test]
    #[DataProvider('screens')]
    public function the_steps_tell_someone_what_to_do(ScreenHelp $help): void
    {
        self::assertGreaterThanOrEqual(2, count($help->steps), $help->slug);

        foreach ($help->steps as $step) {
            self::assertNotSame('', trim($step), $help->slug);
        }
    }

    /**
     * Words that mean something to this codebase and nothing to a site owner.
     */
    #[Test]
    #[DataProvider('screens')]
    public function the_help_avoids_the_implementation_vocabulary(ScreenHelp $help): void
    {
        /*
         * "filter" is not on this list on purpose: people filter email and spreadsheets, so in
         * a sentence about a list of enquiries it is ordinary English rather than our jargon.
         */
        $jargon = [
            'token', 'override', 'schema', 'resolver', 'payload', 'endpoint', 'rest',
            'serialise', 'serialize', 'cursor', 'aggregate', 'repository', 'idempotent',
            'cpt', 'taxonomy', 'nonce', 'transient', 'webhook', 'middleware',
        ];

        $haystack = strtolower($help->summary . ' ' . implode(' ', $help->steps));

        foreach ($jargon as $word) {
            // Whole words. Substring matching flagged "REST" inside "restoring" and "reset",
            // which would have had me rewriting correct English to satisfy a broken check.
            self::assertDoesNotMatchRegularExpression(
                '/\b' . preg_quote($word, '/') . '\b/',
                $haystack,
                sprintf('%s uses "%s", which a site owner would have to look up', $help->slug, $word),
            );
        }
    }

    /**
     * A title a person can match to the menu item they clicked.
     */
    #[Test]
    #[DataProvider('screens')]
    public function every_screen_has_a_title(ScreenHelp $help): void
    {
        self::assertNotSame('', trim($help->title), $help->slug);
    }

    /**
     * The admin renders the help panel only when there is one, so an unrecognised route has to
     * answer null rather than throw — a screen with no help must still draw.
     */
    #[Test]
    public function an_unknown_screen_has_no_help_rather_than_an_error(): void
    {
        self::assertNull(ScreenHelp::find('edulume-not-a-screen'));
        self::assertNull(ScreenHelp::find(''));
    }

    #[Test]
    public function the_help_covers_the_screens_a_first_time_user_needs_most(): void
    {
        foreach (['edulume', 'edulume-design', 'edulume-demos', 'edulume-leads'] as $slug) {
            $help = ScreenHelp::find($slug);

            self::assertNotNull($help, $slug);
            self::assertGreaterThanOrEqual(3, count($help->steps), $slug . ' needs more guidance');
        }
    }
}
