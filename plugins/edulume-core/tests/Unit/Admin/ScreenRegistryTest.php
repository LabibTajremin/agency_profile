<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Admin;

use Edulume\Core\Infrastructure\Admin\AdminMenu;
use Edulume\Core\Infrastructure\Admin\ScreenRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every registered page has a class that draws it.
 *
 * This is the test that was missing. Eleven pages were registered in the admin menu and four
 * screens existed; the other seven printed a mount point for a configurator application that was
 * never enqueued and whose entry file exported a library without ever looking for a root
 * element. Every one of them rendered its help panel and then nothing — Design, Dashboard,
 * Leads, Forms, Content tools, Licence and System — and the whole suite stayed green, because
 * nothing anywhere asserted that a page and a screen were the same list.
 */
#[CoversClass(ScreenRegistry::class)]
final class ScreenRegistryTest extends TestCase
{
    #[Test]
    public function every_registered_page_has_a_screen_behind_it(): void
    {
        $pages = array_keys(AdminMenu::pages());
        $screens = array_keys(ScreenRegistry::classes());

        sort($pages);
        sort($screens);

        self::assertSame($pages, $screens, 'a page without a screen renders nothing but its help panel');
    }

    #[Test]
    public function every_screen_can_actually_draw(): void
    {
        foreach (ScreenRegistry::classes() as $slug => $class) {
            self::assertTrue(class_exists($class), $slug . ' names a class that does not exist');
            self::assertTrue(
                method_exists($class, 'render'),
                $slug . ' has a screen with no render method, so the page would come out blank'
            );
        }
    }

    /**
     * A screen that writes anything has to be reachable from `admin-post.php`, which is its own
     * request with no screen context — so its handlers are hooked from the registry rather than
     * when its page is viewed.
     */
    #[Test]
    public function every_screen_that_handles_a_post_can_register_its_hooks(): void
    {
        foreach (ScreenRegistry::classes() as $slug => $class) {
            $handles = false;

            foreach (get_class_methods($class) as $method) {
                $handles = $handles || str_starts_with($method, 'handle');
            }

            if (!$handles) {
                continue;
            }

            self::assertTrue(
                method_exists($class, 'register'),
                $slug . ' has a handler nothing hooks, so its form posts to a dead action'
            );
        }
    }
}
