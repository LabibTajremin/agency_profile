<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\MotionPreset;
use Edulume\Core\Domain\Theming\SectionBackgroundTone;
use Edulume\Core\Domain\Theming\SectionId;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\SectionOverrideKey;
use Edulume\Core\Domain\Theming\SectionResolver;
use Edulume\Core\Domain\Theming\ThemeMode;
use Edulume\Core\Domain\Theming\ThemeSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SectionResolverTest extends TestCase
{
    private SectionResolver $resolver;
    private ThemeSettings $global;

    protected function setUp(): void
    {
        $this->resolver = new SectionResolver();
        $this->global = ThemeSettings::defaults();
    }

    /**
     * @return array<string, array{SectionOverrideKey, bool|float|int|string}>
     */
    public static function overrideProvider(): array
    {
        return [
            'accent slug' => [SectionOverrideKey::AccentSlug, 'ivy-green'],
            'custom accent' => [SectionOverrideKey::CustomAccent, '#7a2e6b'],
            'pattern enabled' => [SectionOverrideKey::PatternEnabled, false],
            'pattern slug' => [SectionOverrideKey::PatternSlug, 'compass-rose'],
            'pattern light opacity' => [SectionOverrideKey::PatternLightModeOpacity, 0.2],
            'pattern dark opacity' => [SectionOverrideKey::PatternDarkModeOpacity, 0.3],
            'pattern scale' => [SectionOverrideKey::PatternScale, 2.5],
            'background tone' => [SectionOverrideKey::BackgroundTone, 'inverted'],
            'motion enabled' => [SectionOverrideKey::MotionEnabled, false],
            'motion preset' => [SectionOverrideKey::MotionPreset, 'cinematic'],
            'typography pairing' => [SectionOverrideKey::TypographyPairingSlug, 'oxford-editorial'],
            'section spacing' => [SectionOverrideKey::SectionSpacingPixels, 160],
            'corner radius' => [SectionOverrideKey::CornerRadiusPixels, 32],
        ];
    }

    #[Test]
    public function it_resolves_an_empty_override_identically_to_the_global_settings(): void
    {
        $resolved = $this->resolver->resolve(SectionId::Hero, $this->global, SectionOverride::inheritEverything());

        $this->assertSame($this->global->accentSeed()->toHex(), $resolved->accentSeed->toHex());
        $this->assertSame($this->global->pattern->toArray(), $resolved->pattern->toArray());
        $this->assertSame($this->global->motion->toArray(), $resolved->motion->toArray());
        $this->assertSame($this->global->typography->toArray(), $resolved->typography->toArray());
        $this->assertSame($this->global->layout->sectionSpacingPixels, $resolved->sectionSpacingPixels);
        $this->assertSame($this->global->layout->cornerRadiusPixels, $resolved->cornerRadiusPixels);
        $this->assertSame(SectionBackgroundTone::Surface, $resolved->backgroundTone);
    }

    #[Test]
    public function it_resolves_every_section_identically_when_nothing_is_overridden(): void
    {
        $baseline = $this->resolver
            ->resolve(SectionId::Hero, $this->global, SectionOverride::inheritEverything())
            ->toComparableArray();

        foreach (SectionId::cases() as $section) {
            $resolved = $this->resolver->resolve($section, $this->global, SectionOverride::inheritEverything());

            $this->assertSame($baseline, $resolved->toComparableArray());
            $this->assertSame($section, $resolved->id);
        }
    }

    #[Test]
    #[DataProvider('overrideProvider')]
    public function it_changes_exactly_one_thing_per_override(
        SectionOverrideKey $key,
        bool|float|int|string $value
    ): void {
        $inherited = $this->resolver
            ->resolve(SectionId::Hero, $this->global, SectionOverride::inheritEverything())
            ->toComparableArray();

        $overridden = $this->resolver
            ->resolve(SectionId::Hero, $this->global, SectionOverride::inheritEverything()->with($key, $value))
            ->toComparableArray();

        $this->assertNotSame($inherited, $overridden, sprintf('Overriding %s changed nothing.', $key->value));
    }

    #[Test]
    #[DataProvider('overrideProvider')]
    public function it_reports_exactly_the_key_the_user_changed(
        SectionOverrideKey $key,
        bool|float|int|string $value
    ): void {
        $override = SectionOverride::inheritEverything()->with($key, $value);

        $this->assertSame([$key], $override->divergedKeys());
        $this->assertTrue($override->has($key));
        $this->assertFalse($override->isInheritingEverything());
    }

    #[Test]
    #[DataProvider('overrideProvider')]
    public function it_restores_inheritance_for_one_key_alone(
        SectionOverrideKey $key,
        bool|float|int|string $value
    ): void {
        $override = SectionOverride::inheritEverything()
            ->with(SectionOverrideKey::SectionSpacingPixels, 200)
            ->with($key, $value);

        $reset = $override->without($key);

        if ($key === SectionOverrideKey::SectionSpacingPixels) {
            $this->assertSame([], $reset->divergedKeys());

            return;
        }

        $this->assertSame([SectionOverrideKey::SectionSpacingPixels], $reset->divergedKeys());
        $this->assertFalse($reset->has($key));
    }

    #[Test]
    public function it_reports_nothing_diverged_when_everything_is_inherited(): void
    {
        $override = SectionOverride::inheritEverything();

        $this->assertTrue($override->isInheritingEverything());
        $this->assertSame([], $override->divergedKeys());
        $this->assertSame([], $override->toArray());
    }

    #[Test]
    public function it_lets_a_later_global_change_reach_an_untouched_key(): void
    {
        $override = SectionOverride::inheritEverything()->with(SectionOverrideKey::CornerRadiusPixels, 32);

        $before = $this->resolver->resolve(SectionId::Hero, $this->global, $override);

        $movedGlobal = ThemeSettings::fromArray(array_merge($this->global->toArray(), [
            'accentSlug' => 'passport-violet',
        ]));

        $after = $this->resolver->resolve(SectionId::Hero, $movedGlobal, $override);

        $this->assertNotSame($before->accentSeed->toHex(), $after->accentSeed->toHex());
        $this->assertSame(32, $after->cornerRadiusPixels);
    }

    #[Test]
    public function it_prefers_a_custom_accent_over_an_overridden_slug(): void
    {
        $override = SectionOverride::inheritEverything()
            ->with(SectionOverrideKey::AccentSlug, 'ivy-green')
            ->with(SectionOverrideKey::CustomAccent, '#7a2e6b');

        $resolved = $this->resolver->resolve(SectionId::Hero, $this->global, $override);

        $this->assertSame('#7a2e6b', $resolved->accentSeed->toHex());
    }

    #[Test]
    public function it_ignores_a_custom_accent_that_is_not_a_hex(): void
    {
        $override = SectionOverride::inheritEverything()
            ->with(SectionOverrideKey::AccentSlug, 'ivy-green')
            ->with(SectionOverrideKey::CustomAccent, 'not a colour');

        $resolved = $this->resolver->resolve(SectionId::Hero, $this->global, $override);

        $this->assertSame('#1f5c3d', $resolved->accentSeed->toHex());
    }

    #[Test]
    public function it_ignores_an_empty_custom_accent(): void
    {
        $override = SectionOverride::inheritEverything()->with(SectionOverrideKey::CustomAccent, '  ');

        $resolved = $this->resolver->resolve(SectionId::Hero, $this->global, $override);

        $this->assertSame($this->global->accentSeed()->toHex(), $resolved->accentSeed->toHex());
    }

    #[Test]
    public function it_ignores_an_accent_slug_it_does_not_curate(): void
    {
        $override = SectionOverride::inheritEverything()->with(SectionOverrideKey::AccentSlug, 'chartreuse-of-doom');

        $resolved = $this->resolver->resolve(SectionId::Hero, $this->global, $override);

        $this->assertSame($this->global->accentSeed()->toHex(), $resolved->accentSeed->toHex());
    }

    #[Test]
    public function it_switches_a_sections_pattern_off_without_touching_the_global_one(): void
    {
        $override = SectionOverride::inheritEverything()->with(SectionOverrideKey::PatternEnabled, false);

        $resolved = $this->resolver->resolve(SectionId::Footer, $this->global, $override);

        $this->assertFalse($resolved->pattern->enabled);
        $this->assertTrue($this->global->pattern->enabled);
    }

    #[Test]
    public function it_switches_a_sections_motion_off_without_touching_the_global_one(): void
    {
        $override = SectionOverride::inheritEverything()->with(SectionOverrideKey::MotionEnabled, false);

        $resolved = $this->resolver->resolve(SectionId::Hero, $this->global, $override);

        $this->assertTrue($resolved->motion->timing()->isStill());
        $this->assertFalse($this->global->motion->timing()->isStill());
    }

    #[Test]
    public function it_keeps_the_inherited_pattern_presentation_choices(): void
    {
        $override = SectionOverride::inheritEverything()->with(SectionOverrideKey::PatternSlug, 'compass-rose');

        $resolved = $this->resolver->resolve(SectionId::Hero, $this->global, $override);

        $this->assertSame('compass-rose', $resolved->pattern->patternSlug);
        $this->assertSame($this->global->pattern->blendMode, $resolved->pattern->blendMode);
        $this->assertSame($this->global->pattern->rotation, $resolved->pattern->rotation);
        $this->assertSame(
            $this->global->pattern->opacityFor(ThemeMode::Dark),
            $resolved->pattern->opacityFor(ThemeMode::Dark),
        );
    }

    #[Test]
    public function it_swaps_the_typography_pairing_while_keeping_the_scale(): void
    {
        $override = SectionOverride::inheritEverything()
            ->with(SectionOverrideKey::TypographyPairingSlug, 'oxford-editorial');

        $resolved = $this->resolver->resolve(SectionId::Blog, $this->global, $override);

        $this->assertSame('oxford-editorial', $resolved->typography->pairingSlug);
        $this->assertSame($this->global->typography->scale->ratio, $resolved->typography->scale->ratio);
    }

    #[Test]
    public function it_raises_the_motion_preset_for_one_section(): void
    {
        $override = SectionOverride::inheritEverything()->with(SectionOverrideKey::MotionPreset, 'cinematic');

        $resolved = $this->resolver->resolve(SectionId::Hero, $this->global, $override);

        $this->assertSame(MotionPreset::Cinematic, $resolved->motion->preset);
        $this->assertSame(MotionPreset::Refined, $this->global->motion->preset);
    }

    #[Test]
    public function it_clamps_an_override_value_on_the_way_in(): void
    {
        $override = SectionOverride::inheritEverything()
            ->with(SectionOverrideKey::PatternScale, 99.0)
            ->with(SectionOverrideKey::PatternLightModeOpacity, 4.0)
            ->with(SectionOverrideKey::CornerRadiusPixels, -20)
            ->with(SectionOverrideKey::SectionSpacingPixels, 9999);

        $stored = $override->toArray();

        $this->assertSame(3.0, $stored['patternScale']);
        $this->assertSame(1.0, $stored['patternLightModeOpacity']);
        $this->assertSame(0, $stored['cornerRadiusPixels']);
        $this->assertSame(240, $stored['sectionSpacingPixels']);
    }

    #[Test]
    #[DataProvider('overrideProvider')]
    public function it_round_trips_an_override_through_storage(
        SectionOverrideKey $key,
        bool|float|int|string $value
    ): void {
        $override = SectionOverride::inheritEverything()->with($key, $value);

        $this->assertSame($override->toArray(), SectionOverride::fromArray($override->toArray())->toArray());
    }

    #[Test]
    public function it_treats_a_stored_null_as_inheritance(): void
    {
        $override = SectionOverride::fromArray([
            'cornerRadiusPixels' => null,
            'sectionSpacingPixels' => 160,
            'notAKey' => 'ignored',
        ]);

        $this->assertSame([SectionOverrideKey::SectionSpacingPixels], $override->divergedKeys());
    }
}
