<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Licence\Licence;
use Edulume\Core\Domain\Licence\LicenceStatus;
use Edulume\Core\Infrastructure\Licence\OptionLicenceStore;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * The licence key, and what it entitles this site to.
 *
 * The key is shown masked once stored. A support screenshot of a settings page is the most
 * common way a licence key ends up somewhere it should not be, and there is no reason to print
 * it back to somebody who already has it.
 *
 * An unreachable licence server is not an error here: `ManageLicence` stores the key
 * optimistically and re-checks it the next day, because refusing a key because the vendor was
 * down for ten minutes makes the first thing a customer does after paying fail.
 */
final class LicenceScreen
{
    public const ACTION = 'edulume_save_licence';
    public const NOTICE_PARAMETER = 'edulume_licence_notice';

    public function __construct(
        private readonly Container $container,
        private readonly OptionLicenceStore $store,
    ) {
    }

    public function register(): void
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handleSave']);
    }

    public function handleSave(): void
    {
        $this->assertCapability();
        check_admin_referer(self::ACTION);

        $manager = $this->container->manageLicence();
        $siteUrl = home_url('/');

        if (isset($_POST['deactivate'])) {
            $this->store->save($manager->deactivate($this->store->load(), $siteUrl));

            $this->redirect(__('Licence released from this site.', 'edulume'));
        }

        $key = isset($_POST['licence']) ? sanitize_text_field(wp_unslash($_POST['licence'])) : '';
        $licence = $manager->activate($key, $siteUrl);

        $this->store->save($licence);

        $this->redirect(
            $licence->hasKey()
                ? __('Licence saved.', 'edulume')
                : __('Enter the key you were sent.', 'edulume')
        );
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            return;
        }

        Notice::render(self::NOTICE_PARAMETER);

        $licence = $this->store->load();
        $status = $this->container->manageLicence()->statusOf($licence);

        $this->renderStatus($licence, $status);

        printf('<form method="post" action="%s" class="edulume-settings">', esc_url(admin_url('admin-post.php')));

        wp_nonce_field(self::ACTION);
        Field::hidden('action', self::ACTION);

        Field::openGroup(
            __('Licence key', 'edulume'),
            __('This is what keeps updates and support coming. One key, one site.', 'edulume')
        );

        Field::text(
            'licence',
            __('Key', 'edulume'),
            '',
            $licence->hasKey()
                ? sprintf(
                    /* translators: %s: the stored licence key with all but its last characters hidden. */
                    __('A key is stored: %s. Enter a new one to replace it.', 'edulume'),
                    $licence->maskedKey()
                )
                : __('Paste the key from your purchase email.', 'edulume'),
            'EDU-XXXX-XXXX-XXXX'
        );

        Field::closeGroup();

        printf(
            '<p class="edulume-settings__actions"><button type="submit" class="button button-primary">%s</button>',
            esc_html__('Activate', 'edulume')
        );

        if ($licence->hasKey()) {
            printf(
                ' <button type="submit" name="deactivate" value="1" class="button">%s</button>',
                esc_html__('Release this site', 'edulume')
            );
        }

        echo '</p></form>';

        printf(
            '<p class="edulume-muted">%s</p>',
            esc_html__('Moving to a new domain? Release this site first, then activate on the new one.', 'edulume')
        );
    }

    private function renderStatus(Licence $licence, LicenceStatus $status): void
    {
        $tone = match ($status) {
            LicenceStatus::Active => 'success',
            LicenceStatus::Grace => 'warning',
            LicenceStatus::Unlicensed => 'info',
            default => 'error',
        };

        printf(
            '<div class="notice notice-%1$s inline"><p><strong>%2$s</strong></p>',
            esc_attr($tone),
            esc_html($status->label())
        );

        if ($licence->hasKey()) {
            printf(
                '<p class="edulume-muted">%s</p>',
                esc_html(sprintf(
                    /* translators: 1: seats used, 2: seats included in the licence. */
                    __('Sites using this key: %1$d of %2$d.', 'edulume'),
                    $licence->activationCount,
                    $licence->siteLimit
                ))
            );

            if ($licence->expiresAt !== null) {
                printf(
                    '<p class="edulume-muted">%s</p>',
                    esc_html(sprintf(
                        /* translators: %s: the date the licence runs out, in the site's format. */
                        __('Runs until %s.', 'edulume'),
                        date_i18n(get_option('date_format'), $licence->expiresAt->getTimestamp())
                    ))
                );
            }
        }

        echo '</div>';
    }

    private function redirect(string $notice): never
    {
        Notice::redirect('edulume-licence', self::NOTICE_PARAMETER, $notice);
    }

    private function assertCapability(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            wp_die(esc_html__('You are not allowed to change the licence.', 'edulume'), '', ['response' => 403]);
        }
    }
}
