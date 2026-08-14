<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Security;

/**
 * Who may store custom CSS and JavaScript.
 *
 * Administrators only, and `unfiltered_html` is not enough on its own — a Site Manager who can
 * paste a `<script>` into a settings field can escalate to Administrator the next time one logs
 * in and views the page. The configurator is powerful precisely because it removes the need for
 * custom code, so restricting it costs almost nothing.
 */
final class CustomCodePolicy
{
    /** The capability WordPress grants only to Administrators on a single site. */
    public const CAPABILITY = 'edit_themes';

    /**
     * @param list<string> $capabilities the capabilities the current user holds
     */
    public static function allows(array $capabilities): bool
    {
        return in_array(self::CAPABILITY, $capabilities, true);
    }

    /**
     * Patterns that never belong in a custom stylesheet.
     *
     * CSS is not inert: `expression()` executes in old Internet Explorer, `@import` fetches
     * from a third party on every page load, and `javascript:` in a `url()` is a script tag
     * with extra steps.
     *
     * @return array<string, string>
     */
    public static function forbiddenCssPatterns(): array
    {
        return [
            '/expression\s*\(/i' => 'CSS expressions execute script.',
            '/@import\b/i' => 'An @import fetches from a third party on every page load.',
            '/javascript\s*:/i' => 'A javascript: URL is a script tag with extra steps.',
            '/behaviou?r\s*:/i' => 'A behavior property binds script to an element.',
            '/-moz-binding/i' => 'XBL bindings execute script.',
        ];
    }

    /**
     * @return list<string> the reasons the stylesheet was refused; empty means accepted
     */
    public static function reasonsToRefuseCss(string $css): array
    {
        $reasons = [];

        foreach (self::forbiddenCssPatterns() as $pattern => $reason) {
            if (preg_match($pattern, $css) === 1) {
                $reasons[] = $reason;
            }
        }

        return $reasons;
    }

    /**
     * Whether a stored snippet is safe to print into `<head>`.
     *
     * Custom CSS is stored as CSS and printed inside a `<style>` element, so a closing `</style>`
     * inside it would break out into markup. Refusing the sequence is simpler and safer than
     * trying to escape it.
     */
    public static function breaksOutOfStyleElement(string $css): bool
    {
        return preg_match('#</\s*style#i', $css) === 1;
    }
}
