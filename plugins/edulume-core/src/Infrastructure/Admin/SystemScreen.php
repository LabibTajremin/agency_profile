<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Lead\LeadQuery;
use Edulume\Core\Domain\Theming\AccentLibrary;
use Edulume\Core\Infrastructure\Security\LoginShield;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * What this install actually is, in one place somebody can paste into a support ticket.
 *
 * Every row here is something that has caused a real support round-trip: the PHP version, the
 * permalink structure, whether the stylesheet was ever compiled, whether the theme is even
 * active. Reading it should replace the first three messages of every conversation.
 *
 * Nothing on this screen writes anything.
 */
final class SystemScreen
{
    public function __construct(
        private readonly Container $container,
        private readonly string $version,
    ) {
    }

    public function register(): void
    {
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            return;
        }

        $rows = $this->rows();

        $this->renderWarnings();

        echo '<table class="widefat striped edulume-report"><tbody>';

        foreach ($rows as $label => $value) {
            printf(
                '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
                esc_html($label),
                esc_html($value)
            );
        }

        echo '</tbody></table>';

        printf(
            '<p><textarea class="edulume-report__text" rows="8" readonly'
            . ' aria-label="%1$s" data-edulume-report>%2$s</textarea></p>'
            . '<p><button type="button" class="button" data-edulume-copy-report>%3$s</button></p>',
            esc_attr__('The report as plain text', 'edulume'),
            esc_textarea($this->asText($rows)),
            esc_html__('Copy report', 'edulume')
        );
    }

    /**
     * The things that are wrong and worth acting on, rather than the whole report again.
     */
    private function renderWarnings(): void
    {
        foreach ($this->warnings() as $warning) {
            printf('<div class="notice notice-warning inline"><p>%s</p></div>', esc_html($warning));
        }
    }

    /**
     * @return list<string>
     */
    private function warnings(): array
    {
        $warnings = [];

        if (get_option('permalink_structure') === '') {
            $warnings[] = __(
                'Permalinks are set to plain, so every archive answers 404. Settings → Permalinks → Post name.',
                'edulume'
            );
        }

        if (!$this->themeIsActive()) {
            $warnings[] = __('The Edulume theme is not the active theme, so none of its templates render.', 'edulume');
        }

        if ($this->container->formRepository()->all() === []) {
            $warnings[] = __('No forms are defined, so every enquiry the site takes is refused.', 'edulume');
        }

        if ($this->publishedItems() === 0) {
            $warnings[] = __('No Edulume content is published yet. Starter demos fills the site in one go.', 'edulume');
        }

        return $warnings;
    }

    /**
     * @return array<string, string>
     */
    private function rows(): array
    {
        $settings = $this->container->settingsRepository()->load();
        $theme = wp_get_theme();

        return [
            __('Plugin version', 'edulume') => $this->version,
            __('Active theme', 'edulume') => $theme->get('Name') . ' ' . $theme->get('Version'),
            __('WordPress', 'edulume') => get_bloginfo('version'),
            __('PHP', 'edulume') => PHP_VERSION,
            __('Server', 'edulume') => $this->serverSoftware(),
            __('Memory limit', 'edulume') => (string) ini_get('memory_limit'),
            __('Max execution time', 'edulume') => (string) ini_get('max_execution_time'),
            __('Upload limit', 'edulume') => size_format((int) wp_max_upload_size()),
            __('Permalinks', 'edulume') => get_option('permalink_structure') === ''
                ? __('Plain — archives will 404', 'edulume')
                : (string) get_option('permalink_structure'),
            __('Site address', 'edulume') => home_url('/'),
            __('Accent', 'edulume') => AccentLibrary::get($settings->accentSlug)->name,
            __('Published Edulume items', 'edulume') => (string) $this->publishedItems(),
            __('Enquiries stored', 'edulume') => (string) $this->leadCount(),
            __('Login shield', 'edulume') => (new LoginShield())->settings()->enabled
                ? __('On', 'edulume')
                : __('Off', 'edulume'),
            __('Multisite', 'edulume') => is_multisite() ? __('Yes', 'edulume') : __('No', 'edulume'),
            __('Debug mode', 'edulume') => defined('WP_DEBUG') && WP_DEBUG
                ? __('On', 'edulume')
                : __('Off', 'edulume'),
        ];
    }

    private function themeIsActive(): bool
    {
        return str_contains(strtolower((string) wp_get_theme()->get('TextDomain')), 'edulume');
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

    private function leadCount(): int
    {
        return $this->container->leadRepository()->count(LeadQuery::all());
    }

    private function serverSoftware(): string
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Sanitised
        // in the same expression; there is nowhere earlier to validate a server global.
        $software = isset($_SERVER['SERVER_SOFTWARE'])
            ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']))
            : '';

        return $software === '' ? __('unknown', 'edulume') : $software;
    }

    /**
     * @param array<string, string> $rows
     */
    private function asText(array $rows): string
    {
        $lines = [];

        foreach ($rows as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }

        return implode("\n", $lines);
    }
}
