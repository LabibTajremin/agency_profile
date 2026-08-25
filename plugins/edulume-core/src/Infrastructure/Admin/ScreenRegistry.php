<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Infrastructure\Licence\OptionLicenceStore;
use Edulume\Core\Infrastructure\Security\LoginShield;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * Which class draws which admin page.
 *
 * One map rather than a branch per route in the menu and a matching `->register()` call in the
 * plugin bootstrap. Two lists that have to agree is how six of the eleven pages ended up
 * rendering nothing but their own help panel: the pages were registered, the screens were not,
 * and nothing anywhere could tell.
 *
 * The slug-to-class table is static so a test can check it against `AdminMenu::pages()` without
 * a WordPress install — which is the check that would have caught this.
 */
final class ScreenRegistry
{
    /** @var array<string, object>|null */
    private ?array $screens = null;

    public function __construct(
        private readonly Container $container,
        private readonly LoginShield $shield,
        private readonly string $version,
    ) {
    }

    /**
     * @return array<string, class-string>
     */
    public static function classes(): array
    {
        return [
            'edulume' => DashboardScreen::class,
            'edulume-design' => DesignScreen::class,
            'edulume-sections' => SectionsScreen::class,
            'edulume-videos' => VideoScreen::class,
            'edulume-leads' => LeadScreen::class,
            'edulume-forms' => FormScreen::class,
            'edulume-content' => ContentScreen::class,
            'edulume-demos' => DemoImportScreen::class,
            'edulume-safety' => ShieldScreen::class,
            'edulume-licence' => LicenceScreen::class,
            'edulume-system' => SystemScreen::class,
        ];
    }

    /**
     * @return array<string, object>
     */
    public function all(): array
    {
        if ($this->screens === null) {
            $this->screens = [
                'edulume' => new DashboardScreen($this->container),
                'edulume-design' => new DesignScreen($this->container),
                'edulume-sections' => new SectionsScreen($this->container),
                'edulume-videos' => new VideoScreen(),
                'edulume-leads' => new LeadScreen($this->container),
                'edulume-forms' => new FormScreen($this->container),
                'edulume-content' => new ContentScreen($this->container),
                'edulume-demos' => new DemoImportScreen($this->container),
                'edulume-safety' => new ShieldScreen($this->shield),
                'edulume-licence' => new LicenceScreen($this->container, new OptionLicenceStore()),
                'edulume-system' => new SystemScreen($this->container, $this->version),
            ];
        }

        return $this->screens;
    }

    public function find(string $slug): ?object
    {
        return $this->all()[$slug] ?? null;
    }

    /**
     * Hooks every screen's `admin_post` handlers.
     *
     * Called on every admin request, not only on the screen itself: `admin-post.php` is its own
     * request with no screen context, so a handler registered only when its page is being viewed
     * is a handler that never runs.
     */
    public function register(): void
    {
        foreach ($this->all() as $screen) {
            if (method_exists($screen, 'register')) {
                $screen->register();
            }
        }
    }
}
