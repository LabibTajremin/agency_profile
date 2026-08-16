<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\SectionId;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\SectionOverrideKey;
use Edulume\Core\Domain\Theming\SectionResolver;
use Edulume\Core\Domain\Theming\ThemeSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Switching a section off.
 *
 * The rule that matters most here is the default: a site that has never opened the panel has no
 * stored value for any section, and if absence meant "off" the release that introduced this
 * would have emptied every existing homepage on upgrade.
 */
final class SectionToggleTest extends TestCase
{
    private function resolve(SectionId $id, SectionOverride $override): bool
    {
        return (new SectionResolver())
            ->resolve($id, ThemeSettings::defaults(), $override)
            ->isEnabled;
    }

    #[Test]
    #[DataProvider('everySection')]
    public function a_section_with_no_stored_override_renders(SectionId $section): void
    {
        self::assertTrue($this->resolve($section, SectionOverride::inheritEverything()), $section->value);
    }

    /**
     * @return list<array{SectionId}>
     */
    public static function everySection(): array
    {
        return array_map(static fn (SectionId $id): array => [$id], SectionId::cases());
    }

    #[Test]
    public function switching_a_section_off_stops_it_rendering(): void
    {
        $off = SectionOverride::inheritEverything()->with(SectionOverrideKey::Enabled, false);

        self::assertFalse($this->resolve(SectionId::Testimonials, $off));
    }

    #[Test]
    public function switching_it_back_on_restores_it(): void
    {
        $on = SectionOverride::inheritEverything()->with(SectionOverrideKey::Enabled, true);

        self::assertTrue($this->resolve(SectionId::Testimonials, $on));
    }

    /**
     * Reverting the key is not the same as setting it to true: it restores inheritance, which
     * is what the per-key revert button in the admin does.
     */
    #[Test]
    public function reverting_the_key_restores_the_default_rather_than_pinning_it(): void
    {
        $override = SectionOverride::inheritEverything()
            ->with(SectionOverrideKey::Enabled, false)
            ->without(SectionOverrideKey::Enabled);

        self::assertTrue($this->resolve(SectionId::Testimonials, $override));
        self::assertTrue($override->isInheritingEverything());
    }

    /**
     * A page with no navigation and no privacy link is broken, not minimal. The rule is enforced
     * in the resolver rather than by hiding the control, because a value can arrive from
     * imported JSON or a REST call that never went near the admin.
     */
    #[Test]
    #[DataProvider('sectionsThatMustAlwaysRender')]
    public function the_header_and_footer_ignore_an_attempt_to_switch_them_off(SectionId $section): void
    {
        $off = SectionOverride::inheritEverything()->with(SectionOverrideKey::Enabled, false);

        self::assertTrue($this->resolve($section, $off), $section->value);
        self::assertFalse($section->canBeSwitchedOff(), $section->value);
    }

    /**
     * @return list<array{SectionId}>
     */
    public static function sectionsThatMustAlwaysRender(): array
    {
        return [[SectionId::Header], [SectionId::Footer]];
    }

    #[Test]
    public function every_other_section_can_be_switched_off(): void
    {
        $switchable = SectionId::switchable();

        self::assertCount(count(SectionId::cases()) - 2, $switchable);
        self::assertNotContains(SectionId::Header, $switchable);
        self::assertNotContains(SectionId::Footer, $switchable);

        $off = SectionOverride::inheritEverything()->with(SectionOverrideKey::Enabled, false);

        foreach ($switchable as $section) {
            self::assertFalse($this->resolve($section, $off), $section->value);
        }
    }

    /**
     * A stored `"false"` from a form post or imported JSON must mean off, not "a non-empty
     * string, therefore true".
     */
    #[Test]
    #[DataProvider('falsyStoredValues')]
    public function a_stored_value_is_coerced_rather_than_trusted(mixed $stored): void
    {
        $override = SectionOverride::inheritEverything()->with(SectionOverrideKey::Enabled, $stored);

        self::assertFalse($this->resolve(SectionId::Courses, $override));
    }

    /**
     * @return list<array{mixed}>
     */
    public static function falsyStoredValues(): array
    {
        return [[false], [0], ['0'], [''], ['false']];
    }

    #[Test]
    public function switching_a_section_off_leaves_its_other_overrides_alone(): void
    {
        $override = SectionOverride::inheritEverything()
            ->with(SectionOverrideKey::AccentSlug, 'ivy-green')
            ->with(SectionOverrideKey::Enabled, false);

        $resolved = (new SectionResolver())
            ->resolve(SectionId::Courses, ThemeSettings::defaults(), $override);

        self::assertFalse($resolved->isEnabled);
        self::assertSame('ivy-green', $override->stringOr(SectionOverrideKey::AccentSlug, ''));
    }

    #[Test]
    public function the_enabled_state_is_part_of_what_makes_two_resolutions_differ(): void
    {
        $resolver = new SectionResolver();
        $settings = ThemeSettings::defaults();

        $on = $resolver->resolve(SectionId::Events, $settings, SectionOverride::inheritEverything());
        $off = $resolver->resolve(
            SectionId::Events,
            $settings,
            SectionOverride::inheritEverything()->with(SectionOverrideKey::Enabled, false),
        );

        self::assertNotSame($on->toComparableArray(), $off->toComparableArray());
    }

    #[Test]
    public function every_home_page_section_has_an_identity_and_a_label(): void
    {
        foreach (SectionId::homePageOrder() as $section) {
            self::assertNotSame('', $section->label(), $section->value);
            self::assertTrue($section->canBeSwitchedOff(), $section->value);
        }

        self::assertCount(20, SectionId::homePageOrder());
    }
}
