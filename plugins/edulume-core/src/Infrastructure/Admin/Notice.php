<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

/**
 * "Saved." — carried from the handler back to the screen.
 *
 * Every screen posts to `admin-post.php` and is redirected back, so the message cannot simply
 * be printed by the thing that did the work. It travels in the query string, which is why it is
 * read here rather than trusted: it is the one value on these screens that an attacker can put
 * in front of an administrator by sending them a link.
 */
final class Notice
{
    public static function redirect(string $page, string $parameter, string $notice): never
    {
        wp_safe_redirect(add_query_arg(
            ['page' => $page, $parameter => rawurlencode($notice)],
            admin_url('admin.php')
        ));

        exit;
    }

    public static function render(string $parameter): void
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nothing is written
        // on this path. The value is sanitised on the way in and escaped on the way out; a
        // nonce would only prove who sent the link, which changes nothing about either.
        $notice = isset($_GET[$parameter]) ? sanitize_text_field(wp_unslash($_GET[$parameter])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ($notice === '') {
            return;
        }

        printf('<div class="notice notice-success"><p>%s</p></div>', esc_html($notice));
    }
}
