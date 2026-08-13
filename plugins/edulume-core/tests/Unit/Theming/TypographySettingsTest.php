<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\FontLibrary;
use Edulume\Core\Domain\Theming\FontPairingLibrary;
use Edulume\Core\Domain\Theming\FontRole;
use Edulume\Core\Domain\Theming\FontSubset;
use Edulume\Core\Domain\Theming\InvalidTypographyException;
use Edulume\Core\Domain\Theming\TypeScale;
use Edulume\Core\Domain\Theming\TypographySettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypographySettingsTest extends TestCase
{
    #[Test]
    public function it_ships_ten_curated_pairings(): void
    {
        $this->assertCount(FontPairingLibrary::PAIRING_COUNT, FontPairingLibrary::all());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pairingProvider(): array
    {
        $cases = [];

        foreach (FontPairingLibrary::slugs() as $slug) {
            $cases[$slug] = [$slug];
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('pairingProvider')]
    public function it_points_every_pairing_at_bundled_families(string $slug): void
    {
        $pairing = FontPairingLibrary::get($slug);

        $this->assertTrue(FontLibrary::has($pairing->headingFamilySlug));
        $this->assertTrue(FontLibrary::has($pairing->bodyFamilySlug));
        $this->assertSame($pairing->headingFamilySlug, $pairing->headingFamily()->slug);
        $this->assertSame($pairing->bodyFamilySlug, $pairing->bodyFamily()->slug);
    }

    #[Test]
    public function it_rejects_a_pairing_it_does_not_curate(): void
    {
        $this->expectException(InvalidTypographyException::class);

        FontPairingLibrary::get('papyrus-and-friends');
    }

    #[Test]
    public function it_reports_whether_a_pairing_is_curated(): void
    {
        $this->assertTrue(FontPairingLibrary::has('modern-clarity'));
        $this->assertFalse(FontPairingLibrary::has('papyrus-and-friends'));
    }

    #[Test]
    public function it_follows_the_pairing_for_every_role_by_default(): void
    {
        $settings = TypographySettings::defaults();
        $pairing = FontPairingLibrary::get(FontPairingLibrary::DEFAULT_PAIRING_SLUG);

        $this->assertSame($pairing->headingFamilySlug, $settings->familyFor(FontRole::Heading)->slug);
        $this->assertSame($pairing->headingFamilySlug, $settings->familyFor(FontRole::Display)->slug);
        $this->assertSame($pairing->bodyFamilySlug, $settings->familyFor(FontRole::Body)->slug);
        $this->assertSame($pairing->bodyFamilySlug, $settings->familyFor(FontRole::Interface)->slug);
        $this->assertSame($pairing->bodyFamilySlug, $settings->familyFor(FontRole::Quote)->slug);
        $this->assertSame($pairing->bodyFamilySlug, $settings->familyFor(FontRole::Code)->slug);
    }

    #[Test]
    public function it_overrides_one_role_without_touching_the_others(): void
    {
        $settings = TypographySettings::defaults()->withRoleOverride(FontRole::Code, 'jetbrains-mono');

        $this->assertSame('jetbrains-mono', $settings->familyFor(FontRole::Code)->slug);
        $this->assertTrue($settings->isOverridden(FontRole::Code));
        $this->assertFalse($settings->isOverridden(FontRole::Body));
        $this->assertSame([FontRole::Code], $settings->overriddenRoles());
    }

    #[Test]
    public function it_restores_inheritance_by_removing_the_override(): void
    {
        $settings = TypographySettings::defaults()
            ->withRoleOverride(FontRole::Code, 'jetbrains-mono')
            ->withoutRoleOverride(FontRole::Code);

        $this->assertFalse($settings->isOverridden(FontRole::Code));
        $this->assertSame($settings->pairing()->bodyFamilySlug, $settings->familyFor(FontRole::Code)->slug);
        $this->assertSame([], $settings->overriddenRoles());
    }

    #[Test]
    public function it_lets_a_pairing_change_reach_every_role_that_was_never_touched(): void
    {
        $settings = TypographySettings::of(
            'gulf-premium',
            [FontRole::Code->value => 'jetbrains-mono'],
            TypeScale::of(1.25),
            [FontSubset::Latin],
        );

        $this->assertSame('fraunces', $settings->familyFor(FontRole::Heading)->slug);
        $this->assertSame('dm-sans', $settings->familyFor(FontRole::Body)->slug);
        $this->assertSame('jetbrains-mono', $settings->familyFor(FontRole::Code)->slug);
    }

    #[Test]
    public function it_drops_an_override_pointing_at_a_family_it_does_not_bundle(): void
    {
        $settings = TypographySettings::of(
            'modern-clarity',
            [FontRole::Heading->value => 'comic-neue-extreme', 'not-a-role' => 'inter'],
            TypeScale::of(1.25),
            [FontSubset::Latin],
        );

        $this->assertFalse($settings->isOverridden(FontRole::Heading));
        $this->assertSame([], $settings->overriddenRoles());
    }

    #[Test]
    public function it_falls_back_to_the_default_pairing_when_the_slug_is_unknown(): void
    {
        $settings = TypographySettings::of('papyrus-and-friends', [], TypeScale::of(1.25), [FontSubset::Latin]);

        $this->assertSame(FontPairingLibrary::DEFAULT_PAIRING_SLUG, $settings->pairingSlug);
    }

    #[Test]
    public function it_reports_only_the_families_actually_in_use(): void
    {
        $inUse = TypographySettings::defaults()->familiesInUse();
        $slugs = array_map(static fn ($family): string => $family->slug, $inUse);

        $this->assertSame(['plus-jakarta-sans', 'inter'], $slugs);
    }

    #[Test]
    public function it_counts_an_overridden_role_as_another_family_to_enqueue(): void
    {
        $settings = TypographySettings::defaults()->withRoleOverride(FontRole::Code, 'jetbrains-mono');

        $this->assertCount(3, $settings->familiesInUse());
    }

    #[Test]
    public function it_ships_only_subsets_the_family_can_serve(): void
    {
        $settings = TypographySettings::of(
            'modern-clarity',
            [],
            TypeScale::of(1.25),
            [FontSubset::Latin, FontSubset::Arabic],
        );

        $this->assertSame([FontSubset::Latin], $settings->subsetsToShip()['inter']);
    }

    #[Test]
    public function it_deduplicates_the_requested_subsets(): void
    {
        $settings = TypographySettings::of(
            'modern-clarity',
            [],
            TypeScale::of(1.25),
            [FontSubset::Latin, FontSubset::Latin, FontSubset::LatinExtended],
        );

        $this->assertSame([FontSubset::Latin, FontSubset::LatinExtended], $settings->subsets());
    }

    #[Test]
    public function it_falls_back_to_latin_when_no_subset_is_requested(): void
    {
        $settings = TypographySettings::of('modern-clarity', [], TypeScale::of(1.25), []);

        $this->assertSame([FontSubset::Latin], $settings->subsets());
    }

    #[Test]
    public function it_keeps_the_google_cdn_switched_off_by_default(): void
    {
        $this->assertFalse(TypographySettings::defaults()->usesGoogleFontsCdn);
        $this->assertTrue(
            TypographySettings::of('modern-clarity', [], TypeScale::of(1.25), [FontSubset::Latin], true)
                ->usesGoogleFontsCdn,
        );
    }

    #[Test]
    public function it_carries_the_scale_it_was_given(): void
    {
        $settings = TypographySettings::of('modern-clarity', [], TypeScale::of(1.618), [FontSubset::Latin]);

        $this->assertSame(1.618, $settings->scale->ratio);
    }
}
