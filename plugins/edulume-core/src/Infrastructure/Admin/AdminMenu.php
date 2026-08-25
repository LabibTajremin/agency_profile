<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Admin\ScreenHelp;
use Edulume\Core\Infrastructure\Wp\Capabilities;

/**
 * The admin menu.
 *
 * One top-level entry with sub-pages rather than several top-level ones. A plugin that plants
 * four items in the admin sidebar is a plugin the site owner resents by the second week, and it
 * makes the product look like four products.
 *
 * Every page is capability-gated at registration. A page registered without a capability is
 * reachable by anyone who can guess its slug.
 */
final class AdminMenu
{
    public const SLUG = 'edulume';

    public function __construct(private readonly ?ScreenRegistry $screens = null)
    {
    }

    /**
     * The pages, in sidebar order, each with the capability that opens it.
     *
     * @return array<string, array{title: string, capability: string}>
     */
    public static function pages(): array
    {
        return [
            'edulume' => ['title' => 'Dashboard', 'capability' => Capabilities::MANAGE_THEME],
            'edulume-design' => ['title' => 'Design', 'capability' => Capabilities::MANAGE_THEME],
            'edulume-sections' => ['title' => 'Home sections', 'capability' => Capabilities::MANAGE_THEME],
            'edulume-videos' => ['title' => 'Videos', 'capability' => Capabilities::MANAGE_THEME],
            'edulume-leads' => ['title' => 'Leads', 'capability' => Capabilities::MANAGE_LEADS],
            'edulume-forms' => ['title' => 'Forms', 'capability' => Capabilities::MANAGE_LEADS],
            'edulume-content' => ['title' => 'Content tools', 'capability' => Capabilities::MANAGE_CONTENT],
            'edulume-demos' => ['title' => 'Starter demos', 'capability' => Capabilities::IMPORT_DEMO_CONTENT],
            'edulume-safety' => ['title' => 'Safety', 'capability' => Capabilities::MANAGE_THEME],
            'edulume-licence' => ['title' => 'Licence', 'capability' => Capabilities::MANAGE_THEME],
            'edulume-system' => ['title' => 'System', 'capability' => Capabilities::MANAGE_THEME],
        ];
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addPages']);
        add_action('current_screen', [$this, 'addContextualHelp']);
    }

    public function addPages(): void
    {
        $pages = self::pages();
        $dashboard = $pages[self::SLUG];

        add_menu_page(
            __('Edulume', 'edulume'),
            __('Edulume', 'edulume'),
            $dashboard['capability'],
            self::SLUG,
            [$this, 'renderApp'],
            'dashicons-art',
            58
        );

        foreach ($pages as $slug => $page) {
            add_submenu_page(
                self::SLUG,
                $page['title'],
                $page['title'],
                $page['capability'],
                $slug,
                [$this, 'renderApp']
            );
        }
    }

    /**
     * Every page draws its own screen.
     *
     * Server-rendered, all of them. The pages used to print a mount point for a configurator
     * application that was never enqueued and whose entry file exported a library without ever
     * looking for a root element — so six of the eleven pages rendered their help panel and
     * nothing else. A settings screen that needs a bundle to draw is a settings screen that is
     * blank when the bundle fails, and on this product's hosting it fails often enough to
     * matter.
     */
    public function renderApp(): void
    {
        $route = $this->currentRoute();

        echo '<div class="wrap edulume-screen">';
        $this->renderHelp($route);

        $screen = $this->screens?->find($route);

        if ($screen !== null && method_exists($screen, 'render')) {
            $screen->render();
        }

        echo '</div>';
    }

    /**
     * The plain-language explanation of the screen, on the screen.
     *
     * WordPress's own Help tab is collapsed by default and in the top-right corner, which is to
     * say almost nobody opens it. Someone who has never built a website needs to know what a
     * screen is for while they are looking at it, so this renders inline and the same text is
     * also registered as a Help tab for people who already know to look there.
     */
    private function renderHelp(string $route): void
    {
        $help = ScreenHelp::find($route);

        if ($help === null) {
            return;
        }

        echo '<div class="edulume-help">';
        printf('<h2>%s</h2>', esc_html($help->title));
        printf('<p>%s</p>', esc_html($help->summary));

        if ($help->steps !== []) {
            echo '<ul>';

            foreach ($help->steps as $step) {
                printf('<li>%s</li>', esc_html($step));
            }

            echo '</ul>';
        }

        echo '</div>';
    }

    /**
     * Mirrors the same text into WordPress's contextual Help tab.
     */
    public function addContextualHelp(): void
    {
        $screen = get_current_screen();

        if ($screen === null) {
            return;
        }

        $help = ScreenHelp::find($this->currentRoute());

        if ($help === null) {
            return;
        }

        $steps = '';

        foreach ($help->steps as $step) {
            $steps .= '<li>' . esc_html($step) . '</li>';
        }

        $screen->add_help_tab([
            'id' => 'edulume-' . $help->slug,
            'title' => $help->title,
            'content' => '<p>' . esc_html($help->summary) . '</p><ul>' . $steps . '</ul>',
        ]);
    }

    private function currentRoute(): string
    {
        // No nonce, and there could not be one: this is WordPress's own admin routing
        // parameter, read to decide which screen to draw. The capability check that matters
        // already happened when the page was registered.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : self::SLUG;

        // Whitelisted against the registered pages rather than echoed back. The value reaches a
        // data attribute the app reads, and an unchecked one is a route the app was never
        // written to handle.
        return array_key_exists($page, self::pages()) ? $page : self::SLUG;
    }
}
