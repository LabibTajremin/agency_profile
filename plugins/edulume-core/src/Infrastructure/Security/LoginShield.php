<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Security;

use Edulume\Core\Domain\Security\AttemptLog;
use Edulume\Core\Domain\Security\ClientIp;
use Edulume\Core\Domain\Security\LockoutPolicy;
use Edulume\Core\Domain\Security\ShieldSettings;

/**
 * Moves the login page and slows down whoever keeps knocking.
 *
 * This is the module most likely to lock the site owner out of their own site, so the safety
 * rails come before the feature and none of them are optional:
 *
 *  - `EDULUME_SHIELD_DISABLE` in `wp-config.php` turns the whole thing off. It is checked first,
 *    before anything is hooked, so a site that is locked out is one File Manager edit from being
 *    open again.
 *  - Multisite bails outright. Half-supporting network login is worse than not supporting it.
 *  - `admin-ajax.php`, `wp-cron.php`, `wp-json`, `robots.txt` and the sitemaps are never blocked
 *    however this is configured. Blocking `admin-ajax.php` is the classic version of this bug
 *    and it takes the front end down with it.
 *  - A logged-in request is never challenged. The person who just moved their own login is not
 *    the person this is for.
 *
 * A blocked request gets a real 404, not a redirect. A redirect to the home page confirms that
 * something was there, which is the one thing a hidden path must not do.
 */
final class LoginShield
{
    public const DISABLE_CONSTANT = 'EDULUME_SHIELD_DISABLE';
    public const OPTION = 'edulume_shield';
    public const LOG_OPTION = 'edulume_shield_log';
    public const HONEYPOT_FIELD = 'website_url';

    private const ATTEMPT_TRANSIENT = 'edulume_att_';
    private const OFFENCE_TRANSIENT = 'edulume_off_';
    private const OFFENCE_MEMORY_SECONDS = 604800;

    private ?ShieldSettings $settings = null;

    public function register(): void
    {
        if (defined(self::DISABLE_CONSTANT) && constant(self::DISABLE_CONSTANT)) {
            return;
        }

        if (is_multisite()) {
            add_action('admin_notices', [$this, 'renderMultisiteNotice']);

            return;
        }

        add_action('plugins_loaded', [$this, 'boot'], 5);
    }

    public function boot(): void
    {
        $settings = $this->settings();

        if (!$settings->enabled) {
            return;
        }

        if ($settings->disableXmlrpc) {
            add_filter('xmlrpc_enabled', static fn (): bool => false);
        }

        add_action('init', [$this, 'guard'], 1);
        add_action('login_form', [$this, 'renderHoneypot']);
        add_action('wp_login_failed', [$this, 'recordFailure'], 10, 1);
        add_action('wp_login', [$this, 'clearFailures'], 10, 1);
        add_filter('authenticate', [$this, 'refuseLockedOut'], 30, 1);

        if ($settings->hideLoginErrors) {
            add_filter('login_errors', [$this, 'genericError']);
        }

        foreach (['login_url', 'lostpassword_url', 'logout_url'] as $filter) {
            add_filter($filter, [$this, 'rewriteLoginUrl'], 10, 1);
        }

        add_filter('site_url', [$this, 'rewriteSiteUrl'], 10, 2);
        add_filter('network_site_url', [$this, 'rewriteSiteUrl'], 10, 2);
    }

    /**
     * The gate. Runs on `init`, early, before anything has drawn.
     */
    public function guard(): void
    {
        $path = $this->requestPath();

        if ($this->isNeverBlocked($path) || is_user_logged_in()) {
            return;
        }

        if ($this->matchesLoginSlug($path)) {
            $this->serveLogin();

            return;
        }

        if ($this->isLoginRequest($path)) {
            $this->serveNotFound();
        }
    }

    /**
     * Renders the honeypot: a field a person never sees and a bot fills in because it is named
     * plausibly.
     *
     * Hidden with CSS rather than `type="hidden"` — a bot that reads the type attribute skips
     * hidden fields, and one that does not is exactly the one this catches. `tabindex="-1"` and
     * `autocomplete="off"` keep it away from anyone using a keyboard or a password manager.
     */
    public function renderHoneypot(): void
    {
        if (!$this->settings()->honeypot) {
            return;
        }

        printf(
            '<div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">'
            . '<label for="%1$s">%2$s</label>'
            . '<input type="text" name="%1$s" id="%1$s" value="" tabindex="-1" autocomplete="off" />'
            . '</div>',
            esc_attr(self::HONEYPOT_FIELD),
            esc_html__('Leave this field empty', 'edulume')
        );
    }

    /**
     * @param mixed $user whatever the previous filter in the chain produced
     */
    public function refuseLockedOut(mixed $user): mixed
    {
        $ip = $this->clientIp();

        if ($ip === '' || $this->settings()->allows($ip)) {
            return $user;
        }

        // A filled honeypot is answered with the same 404 an unknown path gets, and is not
        // counted: counting it would let a bot lock out the address it is pretending to be.
        if ($this->honeypotWasFilled()) {
            $this->serveNotFound();
        }

        if (!$this->isLockedOut($ip)) {
            return $user;
        }

        return new \WP_Error(
            'edulume_locked_out',
            esc_html__('Too many attempts. Try again later.', 'edulume')
        );
    }

    public function recordFailure(string $username = ''): void
    {
        $ip = $this->clientIp();
        $settings = $this->settings();

        if ($ip === '' || $settings->allows($ip)) {
            return;
        }

        $failures = $this->failureCount($ip) + 1;
        $policy = new LockoutPolicy($settings);
        $offences = (int) get_transient(self::OFFENCE_TRANSIENT . $this->key($ip));

        set_transient(
            self::ATTEMPT_TRANSIENT . $this->key($ip),
            $failures,
            $policy->durationMinutes($offences) * 60
        );

        $this->appendToLog($ip, $username);

        if (!$policy->isLockedOut($failures, $offences)) {
            return;
        }

        set_transient(self::OFFENCE_TRANSIENT . $this->key($ip), $offences + 1, self::OFFENCE_MEMORY_SECONDS);

        if ($settings->emailAlerts) {
            $this->alert($ip, $failures);
        }
    }

    public function clearFailures(string $username = ''): void
    {
        unset($username);

        $ip = $this->clientIp();

        if ($ip !== '') {
            delete_transient(self::ATTEMPT_TRANSIENT . $this->key($ip));
        }
    }

    /**
     * One message for every failure mode.
     *
     * WordPress's own errors distinguish "unknown username" from "wrong password", which tells
     * whoever is guessing exactly which half they got right.
     */
    public function genericError(string $error): string
    {
        unset($error);

        return esc_html__('Invalid credentials.', 'edulume');
    }

    public function rewriteLoginUrl(string $url): string
    {
        return str_replace('wp-login.php', $this->settings()->slug, $url);
    }

    public function rewriteSiteUrl(string $url, string $path = ''): string
    {
        if (!str_contains($path, 'wp-login.php')) {
            return $url;
        }

        return $this->rewriteLoginUrl($url);
    }

    public function renderMultisiteNotice(): void
    {
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html__(
                'The Edulume login shield does not run on a multisite network, so it is switched off here.',
                'edulume'
            )
        );
    }

    public function settings(): ShieldSettings
    {
        if ($this->settings === null) {
            $stored = get_option(self::OPTION, []);
            $this->settings = ShieldSettings::fromArray(is_array($stored) ? $stored : []);
        }

        return $this->settings;
    }

    /**
     * Paths that are never touched, whatever the settings say.
     */
    private function isNeverBlocked(string $path): bool
    {
        foreach (['admin-ajax.php', 'wp-cron.php', 'wp-json', 'robots.txt', 'sitemap'] as $safe) {
            if (str_contains($path, $safe)) {
                return true;
            }
        }

        return defined('DOING_AJAX') || defined('DOING_CRON') || defined('REST_REQUEST');
    }

    private function isLoginRequest(string $path): bool
    {
        return str_contains($path, 'wp-login.php') || str_starts_with(ltrim($path, '/'), 'wp-admin');
    }

    private function matchesLoginSlug(string $path): bool
    {
        $slug = $this->settings()->slug;

        return $slug !== '' && trim(strtok($path, '?') ?: '', '/') === $slug;
    }

    private function serveLogin(): void
    {
        // A constant rather than a query flag: `wp-login.php` reads it, and anything set in the
        // request is something the client could have set for itself.
        if (!defined('EDULUME_SHIELD_DOING_LOGIN')) {
            define('EDULUME_SHIELD_DOING_LOGIN', true);
        }

        global $pagenow;
        $pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

        require_once ABSPATH . 'wp-login.php';

        exit;
    }

    private function serveNotFound(): void
    {
        global $wp_query;

        if (isset($wp_query) && $wp_query instanceof \WP_Query) {
            $wp_query->set_404();
        }

        status_header(404);
        nocache_headers();

        $template = get_404_template();

        if (is_string($template) && $template !== '') {
            require $template;
        }

        exit;
    }

    private function honeypotWasFilled(): bool
    {
        if (!$this->settings()->honeypot) {
            return false;
        }

        // No nonce: this is the login form, which WordPress itself posts without one. Sanitised
        // anyway even though the value is only ever tested for emptiness — an unsanitised read
        // of request data is a pattern nobody should have to judge case by case.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $value = isset($_POST[self::HONEYPOT_FIELD])
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            ? sanitize_text_field(wp_unslash((string) $_POST[self::HONEYPOT_FIELD]))
            : '';

        return trim($value) !== '';
    }

    private function isLockedOut(string $ip): bool
    {
        $offences = (int) get_transient(self::OFFENCE_TRANSIENT . $this->key($ip));

        return (new LockoutPolicy($this->settings()))->isLockedOut($this->failureCount($ip), $offences);
    }

    private function failureCount(string $ip): int
    {
        $stored = get_transient(self::ATTEMPT_TRANSIENT . $this->key($ip));

        return is_numeric($stored) ? (int) $stored : 0;
    }

    private function appendToLog(string $ip, string $username): void
    {
        $stored = get_option(self::LOG_OPTION, []);
        $log = AttemptLog::fromArray(is_array($stored) ? $stored : []);

        update_option(
            self::LOG_OPTION,
            $log->with($ip, $username, gmdate('c'))->toArray(),
            // Never autoloaded. A log read on every page view is a tax the whole site pays for
            // a screen somebody opens twice a year.
            false
        );
    }

    private function alert(string $ip, int $failures): void
    {
        wp_mail(
            (string) get_option('admin_email', ''),
            esc_html__('Repeated failed logins on your site', 'edulume'),
            sprintf(
                /* translators: 1: an IP address, 2: how many failures were recorded. */
                esc_html__('%1$s has failed to sign in %2$d times and is now locked out.', 'edulume'),
                $ip,
                $failures
            )
        );
    }

    private function clientIp(): string
    {
        /** @var array<string, mixed> $server */
        $server = $_SERVER;

        return ClientIp::resolve($server, $this->settings()->trustedProxy);
    }

    /**
     * Transients are keyed by a hash of the address, not the address itself.
     *
     * An IPv6 address is longer than the option-name column allows, and a raw address in a key
     * is personal data sitting in a table nobody thinks to redact.
     */
    private function key(string $ip): string
    {
        return substr(hash('sha256', $ip), 0, 32);
    }

    private function requestPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        if (!is_string($uri)) {
            return '';
        }

        return (string) wp_parse_url(wp_unslash($uri), PHP_URL_PATH);
    }
}
