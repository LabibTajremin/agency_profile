<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

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
     * Every page renders the same mount point.
     *
     * The admin is one application with a route per page, not nine screens: that is what lets
     * the live preview stay mounted while you move between panels, and what makes the settings
     * search able to jump to a control in a panel you have not opened.
     */
    public function renderApp(): void
    {
        printf(
            '<div class="wrap"><div id="edulume-admin-root" data-edulume-route="%s"></div></div>',
            esc_attr($this->currentRoute())
        );
    }

    private function currentRoute(): string
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : self::SLUG;

        // Whitelisted against the registered pages rather than echoed back. The value reaches a
        // data attribute the app reads, and an unchecked one is a route the app was never
        // written to handle.
        return array_key_exists($page, self::pages()) ? $page : self::SLUG;
    }
}
