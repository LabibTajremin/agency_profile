<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Theming;

use Edulume\Core\Domain\Theming\SectionId;
use Edulume\Core\Domain\Theming\SectionOrder;
use Edulume\Core\Domain\Theming\SectionResolver;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * Answers "should this section render?" for the theme.
 *
 * The theme is presentation only and never reads an option key directly, so it needs a
 * documented function to ask. This is that function's implementation, plus the filter the
 * theme actually calls.
 *
 * Resolved through `SectionResolver` rather than by reading the stored value, so the rules
 * about what may be switched off live in one place. The header and footer answer true here
 * however they are stored, because a page with no navigation and no privacy link is broken
 * rather than minimal.
 */
final class SectionVisibility
{
    public const FILTER = 'edulume_section_is_enabled';
    public const ORDER_FILTER = 'edulume_enabled_home_sections';
    public const LIST_FILTER = 'edulume_home_sections';

    public function __construct(
        private readonly Container $container,
        private readonly SectionResolver $resolver = new SectionResolver(),
    ) {
    }

    public function register(): void
    {
        add_filter(self::FILTER, [$this, 'isEnabled'], 10, 2);
        add_filter(self::ORDER_FILTER, [$this, 'filterOrder'], 10, 1);
        add_filter(self::LIST_FILTER, [$this, 'orderedSections'], 10, 1);
    }

    /**
     * The home page's sections, in the order the owner arranged them.
     *
     * Slugs the plugin does not know about are kept and appended: a child theme that adds a
     * section through this same filter must not lose it because the admin's drag list has never
     * heard of it.
     *
     * @param list<string> $slugs
     *
     * @return list<string>
     */
    public function orderedSections(array $slugs): array
    {
        $ordered = SectionOrder::reconcile($this->container->settingsRepository()->loadSectionOrder());

        $foreign = array_values(array_filter(
            $slugs,
            static fn (string $slug): bool => SectionId::tryFrom($slug) === null
        ));

        return array_merge($ordered, $foreign);
    }

    /**
     * @param bool $enabled the default the theme passes, used when the slug is not one of ours
     */
    public function isEnabled(bool $enabled, string $slug = ''): bool
    {
        $section = SectionId::tryFrom($slug);

        if ($section === null) {
            return $enabled;
        }

        return $this->resolve($section);
    }

    /**
     * Filters a list of section slugs down to the ones switched on, preserving order.
     *
     * The theme renders whatever this returns, so an unknown slug survives: a child theme that
     * adds its own section must not have it silently dropped by a plugin that has never heard
     * of it.
     *
     * @param list<string> $slugs
     *
     * @return list<string>
     */
    public function filterOrder(array $slugs): array
    {
        return array_values(array_filter(
            $slugs,
            fn (string $slug): bool => $this->isEnabled(true, $slug),
        ));
    }

    private function resolve(SectionId $section): bool
    {
        $overrides = $this->container->settingsRepository()->loadSectionOverrides();
        $override = $overrides[$section->value] ?? null;

        if ($override === null) {
            return $section->isEnabledByDefault();
        }

        return $this->resolver->resolve(
            $section,
            $this->container->settingsRepository()->load(),
            $override,
        )->isEnabled;
    }
}
