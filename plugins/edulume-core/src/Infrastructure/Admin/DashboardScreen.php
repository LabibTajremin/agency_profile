<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Lead\LeadQuery;
use Edulume\Core\Domain\Lead\LeadStatus;
use Edulume\Core\Infrastructure\Security\LoginShield;
use Edulume\Core\Infrastructure\Settings\OptionSettingsRepository;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * The first screen, and the only one whose job is to tell somebody what to do next.
 *
 * The checklist is derived, not stored. A stored checklist goes stale the moment somebody does
 * one of the steps outside this screen — imports the demo over WP-CLI, sets permalinks in
 * Settings — and then spends the rest of the site's life telling its owner to do something they
 * already did.
 */
final class DashboardScreen
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            return;
        }

        $this->renderChecklist();
        $this->renderCounts();
        $this->renderShortcuts();
    }

    private function renderChecklist(): void
    {
        $steps = $this->steps();
        $remaining = count(array_filter($steps, static fn (array $step): bool => !$step['done']));

        printf(
            '<section class="edulume-group"><h2 class="edulume-group__title">%1$s</h2>'
            . '<p class="edulume-group__summary">%2$s</p><ul class="edulume-checklist">',
            esc_html__('Getting this site live', 'edulume'),
            esc_html(
                $remaining === 0
                    ? __('Everything here is done.', 'edulume')
                    : sprintf(
                        /* translators: %d: how many setup steps are still outstanding. */
                        _n('%d thing left to do.', '%d things left to do.', $remaining, 'edulume'),
                        $remaining
                    )
            )
        );

        foreach ($steps as $step) {
            printf(
                '<li class="edulume-checklist__item%1$s"><span class="edulume-checklist__mark" aria-hidden="true">'
                . '</span><span class="edulume-checklist__label">%2$s</span> '
                . '<a class="edulume-checklist__link" href="%3$s">%4$s</a></li>',
                $step['done'] ? ' is-done' : '',
                esc_html($step['label']),
                esc_url($step['url']),
                esc_html($step['done'] ? __('Review', 'edulume') : __('Do this', 'edulume'))
            );
        }

        echo '</ul></section>';
    }

    /**
     * @return list<array{label: string, done: bool, url: string}>
     */
    private function steps(): array
    {
        return [
            [
                'label' => __('Publish some content — the starter demo fills the whole site in one go', 'edulume'),
                'done' => $this->publishedItems() > 0,
                'url' => admin_url('admin.php?page=edulume-demos'),
            ],
            [
                'label' => __('Set permalinks to Post name, or every archive answers 404', 'edulume'),
                'done' => get_option('permalink_structure') !== '',
                'url' => admin_url('options-permalink.php'),
            ],
            [
                'label' => __('Create the forms the theme asks for, so enquiries are accepted', 'edulume'),
                'done' => $this->container->formRepository()->all() !== [],
                'url' => admin_url('admin.php?page=edulume-forms'),
            ],
            [
                'label' => __('Choose the colour and type the site runs on', 'edulume'),
                'done' => get_option(OptionSettingsRepository::SETTINGS_OPTION, null) !== null,
                'url' => admin_url('admin.php?page=edulume-design'),
            ],
            [
                'label' => __('Decide which sections the home page shows', 'edulume'),
                'done' => $this->container->settingsRepository()->loadSectionOrder() !== [],
                'url' => admin_url('admin.php?page=edulume-sections'),
            ],
            [
                'label' => __('Turn the login shield on before the site goes public', 'edulume'),
                'done' => get_option(LoginShield::OPTION, null) !== null,
                'url' => admin_url('admin.php?page=edulume-safety'),
            ],
        ];
    }

    private function renderCounts(): void
    {
        printf(
            '<section class="edulume-group"><h2 class="edulume-group__title">%s</h2><div class="edulume-tiles">',
            esc_html__('What is on the site', 'edulume')
        );

        $tiles = [];

        foreach (ContentModel::postTypes() as $definition) {
            $counts = wp_count_posts($definition->key);
            $published = is_object($counts) && isset($counts->publish) ? (int) $counts->publish : 0;

            if ($published > 0) {
                $tiles[$definition->pluralLabel] = [
                    $published,
                    admin_url('edit.php?post_type=' . $definition->key),
                ];
            }
        }

        if (current_user_can(Capabilities::MANAGE_LEADS)) {
            $tiles[__('Enquiries', 'edulume')] = [
                $this->container->leadRepository()->count(LeadQuery::all()),
                admin_url('admin.php?page=edulume-leads'),
            ];
            $tiles[__('New enquiries', 'edulume')] = [
                $this->container->leadRepository()->count(LeadQuery::of(LeadStatus::New)),
                admin_url('admin.php?page=edulume-leads&status=new'),
            ];
        }

        if ($tiles === []) {
            printf(
                '</div><p class="edulume-empty">%s</p></section>',
                esc_html__('Nothing published yet.', 'edulume')
            );

            return;
        }

        foreach ($tiles as $label => $tile) {
            printf(
                '<a class="edulume-tile" href="%1$s"><span class="edulume-tile__value">%2$d</span>'
                . '<span class="edulume-tile__label">%3$s</span></a>',
                esc_url((string) $tile[1]),
                (int) $tile[0],
                esc_html((string) $label)
            );
        }

        echo '</div></section>';
    }

    private function renderShortcuts(): void
    {
        printf(
            '<section class="edulume-group"><h2 class="edulume-group__title">%s</h2><div class="edulume-tiles">',
            esc_html__('Where things are', 'edulume')
        );

        $shortcuts = [
            __('Design', 'edulume') => 'edulume-design',
            __('Home sections', 'edulume') => 'edulume-sections',
            __('Videos', 'edulume') => 'edulume-videos',
            __('Enquiries', 'edulume') => 'edulume-leads',
            __('Forms', 'edulume') => 'edulume-forms',
            __('Content tools', 'edulume') => 'edulume-content',
            __('Starter demos', 'edulume') => 'edulume-demos',
            __('Safety', 'edulume') => 'edulume-safety',
            __('System', 'edulume') => 'edulume-system',
        ];

        foreach ($shortcuts as $label => $slug) {
            printf(
                '<a class="edulume-tile edulume-tile--link" href="%1$s"><span class="edulume-tile__label">%2$s</span></a>',
                esc_url(admin_url('admin.php?page=' . $slug)),
                esc_html((string) $label)
            );
        }

        echo '</div></section>';
    }

    private function publishedItems(): int
    {
        $total = 0;

        foreach (ContentModel::postTypes() as $definition) {
            $counts = wp_count_posts($definition->key);
            $total += is_object($counts) && isset($counts->publish) ? (int) $counts->publish : 0;
        }

        return $total;
    }
}
