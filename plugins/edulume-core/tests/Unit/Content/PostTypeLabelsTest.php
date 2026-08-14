<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Content;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Content\PostTypeDefinition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The label set every post type hands to WordPress.
 *
 * `labels()` was the single largest untested method in the domain — sixteen lines that decide
 * what an editor reads on every admin screen for all sixteen post types. Nothing here throws
 * when it is wrong; it just says "Add Post" above a Course, or pluralises an archive heading
 * with the singular noun, and it does that in production until somebody notices.
 */
final class PostTypeLabelsTest extends TestCase
{
    private static function course(): PostTypeDefinition
    {
        return PostTypeDefinition::of('course', 'Course', 'Courses', 'courses', 'dashicons-welcome-learn-more');
    }

    #[Test]
    public function it_uses_the_singular_noun_for_the_screens_that_act_on_one_record(): void
    {
        $labels = self::course()->labels();

        self::assertSame('Add Course', $labels['add_new_item']);
        self::assertSame('Edit Course', $labels['edit_item']);
        self::assertSame('New Course', $labels['new_item']);
        self::assertSame('View Course', $labels['view_item']);
        self::assertSame('Course Archives', $labels['archives']);
        self::assertSame('Course', $labels['singular_name']);
    }

    #[Test]
    public function it_uses_the_plural_noun_for_the_screens_that_list_many(): void
    {
        $labels = self::course()->labels();

        self::assertSame('Courses', $labels['name']);
        self::assertSame('Courses', $labels['menu_name']);
        self::assertSame('View Courses', $labels['view_items']);
        self::assertSame('Search Courses', $labels['search_items']);
        self::assertSame('All Courses', $labels['all_items']);
    }

    /**
     * "No Courses found" reads as a sentence; "No Courses found" with the noun capitalised
     * mid-sentence reads as a bug, which is why the empty-state labels lowercase the plural.
     */
    #[Test]
    public function the_empty_states_lowercase_the_plural_because_they_are_sentences(): void
    {
        $labels = self::course()->labels();

        self::assertSame('No courses found', $labels['not_found']);
        self::assertSame('No courses found in Trash', $labels['not_found_in_trash']);
    }

    /**
     * @return list<array{PostTypeDefinition}>
     */
    public static function postTypes(): array
    {
        return array_map(
            static fn (PostTypeDefinition $type): array => [$type],
            ContentModel::postTypes(),
        );
    }

    #[Test]
    #[DataProvider('postTypes')]
    public function every_shipped_post_type_produces_a_complete_label_set(PostTypeDefinition $type): void
    {
        $labels = $type->labels();

        foreach (
            [
                'name', 'singular_name', 'add_new_item', 'edit_item', 'new_item', 'view_item',
                'view_items', 'search_items', 'not_found', 'not_found_in_trash', 'all_items',
                'archives', 'menu_name',
            ] as $key
        ) {
            self::assertArrayHasKey($key, $labels, $type->key);
            self::assertNotSame('', $labels[$key], $type->key . '.' . $key);
        }
    }

    #[Test]
    #[DataProvider('postTypes')]
    public function no_label_leaks_an_empty_noun_from_a_missing_singular_or_plural(
        PostTypeDefinition $type
    ): void {
        foreach ($type->labels() as $key => $label) {
            self::assertStringNotContainsString('  ', $label, $type->key . '.' . $key);
        }
    }
}
