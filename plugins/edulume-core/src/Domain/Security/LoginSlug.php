<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Security;

/**
 * Whether a proposed login path can be used.
 *
 * The rejections matter more than the acceptances. A slug that collides with a page, a post
 * type's archive base or one of WordPress's own directories produces a site where either the
 * login or the colliding thing is unreachable, and the owner discovers which one at the worst
 * possible moment. Silently accepting and hoping is not an option here: a shield whose front
 * door does not open has bricked the site.
 *
 * Taken words are passed in rather than looked up, because "what slugs exist on this site" is a
 * database question and this has to be answerable in a unit test.
 */
final class LoginSlug
{
    public const MINIMUM_LENGTH = 3;
    public const MAXIMUM_LENGTH = 50;

    /**
     * Paths that are either WordPress's own or so conventional that using one defeats the
     * point of a custom path in the first place.
     */
    public const RESERVED = [
        'wp-admin',
        'wp-content',
        'wp-includes',
        'wp-login',
        'wp-json',
        'wp-cron',
        'admin',
        'login',
        'signin',
        'sign-in',
        'dashboard',
        'feed',
        'rss',
        'rss2',
        'atom',
        'sitemap',
        'robots',
        'xmlrpc',
        'index',
        'author',
        'category',
        'tag',
        'page',
        'comments',
        'trackback',
        'embed',
    ];

    private function __construct(
        public readonly string $value,
        public readonly ?string $problem,
    ) {
    }

    /**
     * @param list<string> $taken slugs already claimed by pages, post types or taxonomies
     */
    public static function propose(string $candidate, array $taken = []): self
    {
        $slug = self::normalise($candidate);

        if ($slug === '') {
            return new self($slug, 'empty');
        }

        if (strlen($slug) < self::MINIMUM_LENGTH) {
            return new self($slug, 'too-short');
        }

        if (strlen($slug) > self::MAXIMUM_LENGTH) {
            return new self($slug, 'too-long');
        }

        if (in_array($slug, self::RESERVED, true)) {
            return new self($slug, 'reserved');
        }

        if (in_array($slug, array_map([self::class, 'normalise'], $taken), true)) {
            return new self($slug, 'taken');
        }

        return new self($slug, null);
    }

    public function isUsable(): bool
    {
        return $this->problem === null;
    }

    /**
     * Lower case, no path separators, no query, no surrounding slashes.
     *
     * Normalising before comparing is what stops `WP-Admin/`, `/wp-admin` and `wp-admin?x=1`
     * from walking past a reserved-word list that only knows `wp-admin`.
     */
    public static function normalise(string $candidate): string
    {
        $value = strtolower(trim($candidate));
        $value = (string) preg_replace('/[?#].*$/', '', $value);
        $value = trim($value, "/ \t\n\r\0\x0B");

        // Only the first path segment can be the login path; a two-level slug would need a
        // rewrite rule per level and gains nothing.
        $value = explode('/', $value)[0];

        return (string) preg_replace('/[^a-z0-9-]/', '', $value);
    }
}
