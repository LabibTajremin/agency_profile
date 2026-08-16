<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Security\LoginSlug;
use Edulume\Core\Domain\Security\ShieldSettings;
use Edulume\Core\Infrastructure\Security\LoginShield;
use Edulume\Core\Infrastructure\Wp\Capabilities;

/**
 * The login shield's settings, and the confirmation that stands between the owner and locking
 * themselves out.
 *
 * Three things happen on the way to switching this on, and each exists because of a specific
 * way people lose access to their own sites:
 *
 *  1. The new login URL is printed on the page and must be acknowledged before the form will
 *     submit. Somebody who has not read the URL cannot agree to it.
 *  2. The URL is emailed to the site's admin address the moment the shield is enabled or the
 *     path changes, so it survives the browser tab being closed.
 *  3. The rescue constant is documented right here, not only in a readme nobody has open at the
 *     moment they need it.
 *
 * The slug is validated against reserved words and against the slugs the site has actually
 * claimed, and a bad one is rejected with a message rather than quietly corrected.
 */
final class ShieldScreen
{
    public const ACTION = 'edulume_save_shield';
    public const NOTICE_PARAMETER = 'edulume_shield_notice';

    public function __construct(private readonly LoginShield $shield)
    {
    }

    public function register(): void
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handleSave']);
    }

    public function handleSave(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            wp_die(esc_html__('You are not allowed to change security settings.', 'edulume'), '', ['response' => 403]);
        }

        check_admin_referer(self::ACTION);

        $current = $this->shield->settings();
        $proposed = LoginSlug::propose($this->text('slug', $current->slug), $this->takenSlugs());

        if (!$proposed->isUsable()) {
            $this->redirect($this->slugProblemMessage($proposed->problem ?? ''));

            return;
        }

        $settings = ShieldSettings::fromArray([
            'enabled' => $this->checkbox('enabled'),
            'slug' => $proposed->value,
            'limitAttempts' => $this->checkbox('limitAttempts'),
            'maxAttempts' => $this->text('maxAttempts', (string) $current->maxAttempts),
            'lockoutMinutes' => $this->text('lockoutMinutes', (string) $current->lockoutMinutes),
            'progressiveLockout' => $this->checkbox('progressiveLockout'),
            'honeypot' => $this->checkbox('honeypot'),
            'disableXmlrpc' => $this->checkbox('disableXmlrpc'),
            'hideLoginErrors' => $this->checkbox('hideLoginErrors'),
            'emailAlerts' => $this->checkbox('emailAlerts'),
            'allowlistIps' => $this->addresses(),
            'trustedProxy' => $this->checkbox('trustedProxy'),
        ]);

        update_option(LoginShield::OPTION, $settings->toArray(), false);

        $pathChanged = $settings->slug !== $current->slug;

        if ($pathChanged || $settings->enabled !== $current->enabled) {
            // Once, here, rather than on `init`. Flushing on every request is one of the most
            // expensive things a plugin can do to a site.
            flush_rewrite_rules(false);
        }

        if ($settings->enabled && ($pathChanged || !$current->enabled)) {
            $this->emailRescueUrl($settings->slug);
        }

        $this->redirect(__('Login settings saved.', 'edulume'));
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            return;
        }

        $this->renderNotice();

        $settings = $this->shield->settings();

        printf(
            '<form method="post" action="%s" class="edulume-shield" data-edulume-shield>%s'
            . '<input type="hidden" name="action" value="%s" />',
            esc_url(admin_url('admin-post.php')),
            wp_nonce_field(self::ACTION, '_wpnonce', true, false),
            esc_attr(self::ACTION)
        );

        $this->renderRescueNotice();

        $this->renderCheckbox('enabled', __('Move my login page', 'edulume'), $settings->enabled);
        $this->renderSlugField($settings->slug);

        $this->renderCheckbox('limitAttempts', __('Lock out repeated failures', 'edulume'), $settings->limitAttempts);
        $this->renderNumber('maxAttempts', __('Failures allowed', 'edulume'), $settings->maxAttempts);
        $this->renderNumber('lockoutMinutes', __('Lockout, in minutes', 'edulume'), $settings->lockoutMinutes);
        $this->renderCheckbox(
            'progressiveLockout',
            __('Double the lockout each time the same visitor comes back', 'edulume'),
            $settings->progressiveLockout
        );
        $this->renderCheckbox('honeypot', __('Trap automated sign-in attempts', 'edulume'), $settings->honeypot);
        $this->renderCheckbox('disableXmlrpc', __('Switch off XML-RPC', 'edulume'), $settings->disableXmlrpc);
        $this->renderCheckbox(
            'hideLoginErrors',
            __('Give the same message for every failed sign-in', 'edulume'),
            $settings->hideLoginErrors
        );
        $this->renderCheckbox('emailAlerts', __('Email me when someone is locked out', 'edulume'), $settings->emailAlerts);
        $this->renderCheckbox(
            'trustedProxy',
            __('This site sits behind Cloudflare or another proxy', 'edulume'),
            $settings->trustedProxy
        );
        $this->renderAllowlist($settings->allowlistIps);

        $this->renderConfirmation($settings->slug);

        printf(
            '<p><button type="submit" class="button button-primary">%s</button></p></form>',
            esc_html__('Save login settings', 'edulume')
        );
    }

    private function renderRescueNotice(): void
    {
        printf(
            '<div class="notice notice-info inline"><p>%s</p><p><code>%s</code></p></div>',
            esc_html__(
                'If you ever cannot sign in, open wp-config.php through your host’s file manager '
                . 'and add the line below. It switches all of this off immediately.',
                'edulume'
            ),
            esc_html("define( '" . LoginShield::DISABLE_CONSTANT . "', true );")
        );
    }

    private function renderConfirmation(string $slug): void
    {
        printf(
            '<div class="edulume-shield__confirm"><p><strong>%s</strong><br /><code>%s</code></p>'
            . '<label><input type="checkbox" data-edulume-shield-confirm /> %s</label></div>',
            esc_html__('Your sign-in page will be:', 'edulume'),
            esc_url(home_url('/' . $slug . '/')),
            esc_html__('I have saved this address somewhere I can find it.', 'edulume')
        );
    }

    private function renderSlugField(string $slug): void
    {
        printf(
            '<p class="edulume-shield__field"><label for="edulume-shield-slug">%s</label>'
            . '<input type="text" id="edulume-shield-slug" name="slug" value="%s" '
            . 'data-edulume-shield-slug /></p>',
            esc_html__('Sign-in address', 'edulume'),
            esc_attr($slug)
        );
    }

    private function renderCheckbox(string $field, string $label, bool $checked): void
    {
        printf(
            '<p class="edulume-shield__field"><label><input type="checkbox" name="%s" value="1"%s /> %s</label></p>',
            esc_attr($field),
            $checked ? ' checked' : '',
            esc_html($label)
        );
    }

    private function renderNumber(string $field, string $label, int $value): void
    {
        printf(
            '<p class="edulume-shield__field"><label for="edulume-shield-%1$s">%2$s</label>'
            . '<input type="number" id="edulume-shield-%1$s" name="%1$s" value="%3$d" min="1" /></p>',
            esc_attr($field),
            esc_html($label),
            $value
        );
    }

    /**
     * @param list<string> $addresses
     */
    private function renderAllowlist(array $addresses): void
    {
        printf(
            '<p class="edulume-shield__field"><label for="edulume-shield-allowlist">%s</label>'
            . '<textarea id="edulume-shield-allowlist" name="allowlistIps" rows="3">%s</textarea>'
            . '<span class="description">%s</span></p>',
            esc_html__('Addresses never locked out, one per line', 'edulume'),
            esc_textarea(implode("\n", $addresses)),
            esc_html__('Your own office address belongs here.', 'edulume')
        );
    }

    /**
     * Slugs the site has already claimed.
     *
     * Pages, post-type archive bases and taxonomy bases, because a login path colliding with any
     * of them makes one of the two unreachable.
     *
     * @return list<string>
     */
    private function takenSlugs(): array
    {
        $taken = [];

        foreach (get_post_types(['public' => true], 'objects') as $postType) {
            $rewrite = $postType->rewrite ?? null;

            if (is_array($rewrite) && is_string($rewrite['slug'] ?? null)) {
                $taken[] = $rewrite['slug'];
            }
        }

        foreach (get_taxonomies(['public' => true], 'objects') as $taxonomy) {
            $rewrite = $taxonomy->rewrite ?? null;

            if (is_array($rewrite) && is_string($rewrite['slug'] ?? null)) {
                $taken[] = $rewrite['slug'];
            }
        }

        foreach (get_posts(['post_type' => 'page', 'posts_per_page' => 200, 'no_found_rows' => true]) as $page) {
            $taken[] = $page->post_name;
        }

        return array_values(array_unique($taken));
    }

    private function slugProblemMessage(string $problem): string
    {
        return match ($problem) {
            'empty' => __('The sign-in address cannot be empty.', 'edulume'),
            'too-short' => __('The sign-in address is too short — use at least three characters.', 'edulume'),
            'too-long' => __('The sign-in address is too long — use fifty characters or fewer.', 'edulume'),
            'reserved' => __('That address is one WordPress uses itself. Pick another.', 'edulume'),
            default => __('Something on this site already uses that address. Pick another.', 'edulume'),
        };
    }

    private function emailRescueUrl(string $slug): void
    {
        wp_mail(
            (string) get_option('admin_email', ''),
            esc_html__('Your new sign-in address', 'edulume'),
            sprintf(
                /* translators: 1: the new sign-in URL, 2: the rescue line for wp-config.php. */
                esc_html__(
                    'Your sign-in page has moved to %1$s. Save this address. If you ever cannot '
                    . 'get in, add this line to wp-config.php: %2$s',
                    'edulume'
                ),
                home_url('/' . $slug . '/'),
                "define( '" . LoginShield::DISABLE_CONSTANT . "', true );"
            )
        );
    }

    private function checkbox(string $field): bool
    {
        // Nonce and capability were both checked in `handleSave` before anything is read.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return isset($_POST[$field]);
    }

    private function text(string $field, string $fallback): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST[$field])) {
            return $fallback;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return sanitize_text_field(wp_unslash((string) $_POST[$field]));
    }

    /**
     * @return list<string>
     */
    private function addresses(): array
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['allowlistIps'])) {
            return [];
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $raw = sanitize_textarea_field(wp_unslash((string) $_POST['allowlistIps']));

        return array_values(array_filter(array_map('trim', explode("\n", $raw))));
    }

    private function redirect(string $notice): void
    {
        wp_safe_redirect(add_query_arg(
            [
                'page' => 'edulume-safety',
                self::NOTICE_PARAMETER => rawurlencode($notice),
            ],
            admin_url('admin.php')
        ));

        exit;
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
