<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Theming\SectionId;
use Edulume\Core\Domain\Theming\SectionOrder;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\SectionOverrideKey;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * Switching home-page sections on and off, and putting them in order.
 *
 * Server-rendered and posted to `admin-post.php`, like the starter-content screen and for the
 * same reason: this is the screen an owner reaches on day one, and a settings page that needs a
 * JavaScript bundle to render is a settings page that is blank when the bundle fails.
 *
 * Order is a plain number input per row, which is keyboard-operable and works with no script at
 * all. The drag handles are an enhancement layered on top — they move the row and rewrite the
 * numbers, so both routes produce the same submission.
 */
final class SectionsScreen
{
    public const ACTION = 'edulume_save_sections';
    public const NOTICE_PARAMETER = 'edulume_sections_notice';

    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handleSave']);
    }

    public function handleSave(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            wp_die(esc_html__('You are not allowed to change the home page.', 'edulume'), '', ['response' => 403]);
        }

        check_admin_referer(self::ACTION);

        $this->saveEnabledStates($this->submitted('enabled'));
        $this->saveOrder($this->submitted('position'));

        wp_safe_redirect(add_query_arg(
            [
                'page' => 'edulume-sections',
                self::NOTICE_PARAMETER => rawurlencode(__('Home page updated.', 'edulume')),
            ],
            admin_url('admin.php')
        ));

        exit;
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            return;
        }

        $this->renderNotice();

        $order = SectionOrder::reconcile($this->container->settingsRepository()->loadSectionOrder());
        $overrides = $this->container->settingsRepository()->loadSectionOverrides();

        printf(
            '<form method="post" action="%s" class="edulume-sections">',
            esc_url(admin_url('admin-post.php'))
        );

        // Called as a statement: it prints the field itself, and a generated hidden input
        // passed through printf reads as unescaped output to a reviewer and to WPCS alike.
        wp_nonce_field(self::ACTION);

        printf('<input type="hidden" name="action" value="%s" />', esc_attr(self::ACTION));

        echo '<ol class="edulume-sections__list" data-edulume-sortable>';

        foreach ($order as $position => $slug) {
            $section = SectionId::tryFrom($slug);

            if ($section === null) {
                continue;
            }

            $this->renderRow($section, $position, $this->isEnabled($section, $overrides[$slug] ?? null));
        }

        echo '</ol>';

        printf(
            '<p><button type="submit" class="button button-primary">%s</button></p></form>',
            esc_html__('Save home page', 'edulume')
        );
    }

    private function isEnabled(SectionId $section, ?SectionOverride $override): bool
    {
        if (!$section->canBeSwitchedOff()) {
            return true;
        }

        return $override === null
            ? $section->isEnabledByDefault()
            : $override->boolOr(SectionOverrideKey::Enabled, $section->isEnabledByDefault());
    }

    private function renderRow(SectionId $section, int $position, bool $enabled): void
    {
        printf(
            '<li class="edulume-sections__row" data-edulume-section-row>'
            . '<span class="edulume-sections__handle" aria-hidden="true" data-edulume-drag-handle></span>'
            . '<label class="edulume-sections__toggle">'
            . '<input type="checkbox" name="enabled[%1$s]" value="1"%2$s /> %3$s</label>'
            . '<label class="edulume-sections__position">'
            . '<span class="screen-reader-text">%4$s</span>'
            . '<input type="number" name="position[%1$s]" value="%5$s" min="1" step="1" '
            . 'data-edulume-position /></label></li>',
            esc_attr($section->value),
            $enabled ? ' checked' : '',
            esc_html($section->label()),
            esc_html(sprintf(
                /* translators: %s: the name of a home page section. */
                __('Position of %s', 'edulume'),
                $section->label()
            )),
            esc_attr((string) ($position + 1))
        );
    }

    /**
     * Everything that can be switched off, switched to what was submitted.
     *
     * A checkbox that is off is absent from the submission, so the loop walks the sections
     * rather than the payload — otherwise switching one off would post nothing and change
     * nothing, which is the classic settings-page bug.
     *
     * @param array<string, mixed> $submitted
     */
    private function saveEnabledStates(array $submitted): void
    {
        $repository = $this->container->settingsRepository();
        $overrides = $repository->loadSectionOverrides();

        foreach (SectionId::switchable() as $section) {
            $override = $overrides[$section->value] ?? SectionOverride::inheritEverything();
            $enabled = array_key_exists($section->value, $submitted);

            $repository->saveSectionOverride(
                $section->value,
                $override->with(SectionOverrideKey::Enabled, $enabled)
            );
        }
    }

    /**
     * @param array<string, mixed> $submitted
     */
    private function saveOrder(array $submitted): void
    {
        $positions = [];

        foreach ($submitted as $slug => $value) {
            if (SectionId::tryFrom($slug) !== null) {
                $positions[$slug] = (int) $value;
            }
        }

        asort($positions);

        $this->container->settingsRepository()->saveSectionOrder(
            SectionOrder::reconcile(array_keys($positions))
        );
    }

    /**
     * One submitted map, sanitised.
     *
     * @return array<string, string>
     */
    private function submitted(string $field): array
    {
        // Unslashed and sanitised in the same expression as the read. Assigning first and
        // cleaning afterwards leaves a variable holding raw request data, which is the shape
        // the sniff exists to stop regardless of what happens on the next line.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- handleSave() checked both.
        $raw = isset($_POST[$field]) && is_array($_POST[$field])
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
            ? map_deep(wp_unslash($_POST[$field]), 'sanitize_text_field')
            : [];
        $clean = [];

        foreach ($raw as $key => $value) {
            if (is_scalar($value)) {
                $clean[sanitize_key((string) $key)] = (string) $value;
            }
        }

        return $clean;
    }

    private function renderNotice(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $notice = isset($_GET[self::NOTICE_PARAMETER])
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? sanitize_text_field(wp_unslash((string) $_GET[self::NOTICE_PARAMETER]))
            : '';

        if ($notice === '') {
            return;
        }

        printf('<div class="notice notice-success"><p>%s</p></div>', esc_html($notice));
    }
}
