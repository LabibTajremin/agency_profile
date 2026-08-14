<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\CubicBezier;
use Edulume\Core\Domain\Theming\InvalidMotionException;
use Edulume\Core\Domain\Theming\MotionEasing;
use Edulume\Core\Domain\Theming\MotionEffect;
use Edulume\Core\Domain\Theming\MotionModule;
use Edulume\Core\Domain\Theming\MotionPreset;
use Edulume\Core\Domain\Theming\MotionSettings;
use Edulume\Core\Domain\Theming\MotionTiming;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MotionSettingsTest extends TestCase
{
    #[Test]
    public function it_offers_seventeen_individually_toggleable_effects(): void
    {
        $this->assertCount(17, MotionEffect::cases());
    }

    #[Test]
    public function it_maps_every_effect_to_exactly_one_module(): void
    {
        foreach (MotionEffect::cases() as $effect) {
            $this->assertInstanceOf(MotionModule::class, $effect->module());
        }
    }

    /**
     * @return array<string, array{MotionPreset}>
     */
    public static function presetProvider(): array
    {
        $cases = [];

        foreach (MotionPreset::cases() as $preset) {
            $cases[$preset->value] = [$preset];
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('presetProvider')]
    public function it_gives_every_preset_a_complete_timing_set(MotionPreset $preset): void
    {
        $timing = $preset->timing();

        $this->assertGreaterThanOrEqual(0, $timing->fastMilliseconds);
        $this->assertGreaterThanOrEqual($timing->fastMilliseconds, $timing->baseMilliseconds);
        $this->assertGreaterThanOrEqual($timing->baseMilliseconds, $timing->slowMilliseconds);
    }

    #[Test]
    #[DataProvider('presetProvider')]
    public function it_never_ships_smooth_scroll_or_a_custom_cursor_on(MotionPreset $preset): void
    {
        $this->assertNotContains(MotionEffect::SmoothScroll, $preset->defaultEffects());
        $this->assertNotContains(MotionEffect::CustomCursor, $preset->defaultEffects());
    }

    #[Test]
    public function it_leaves_the_none_preset_completely_still(): void
    {
        $this->assertTrue(MotionPreset::None->timing()->isStill());
        $this->assertSame([], MotionPreset::None->defaultEffects());
    }

    #[Test]
    public function it_pulls_the_heavy_timeline_module_only_for_cinematic(): void
    {
        foreach (MotionPreset::cases() as $preset) {
            $loadsTimeline = MotionSettings::of($preset)->loadsHeavyModule();

            $this->assertSame(
                $preset === MotionPreset::Cinematic,
                $loadsTimeline,
                sprintf('Preset %s disagreed about the heavy timeline module.', $preset->value),
            );
        }
    }

    #[Test]
    public function it_zeroes_every_derived_value_when_motion_is_switched_off(): void
    {
        $settings = MotionSettings::of(MotionPreset::Cinematic)->disabled();

        $this->assertTrue($settings->timing()->isStill());
        $this->assertSame([], $settings->activeEffects());
        $this->assertSame([], $settings->modulesToLoad());
        $this->assertFalse($settings->loadsHeavyModule());
        $this->assertSame('cubic-bezier(0, 0, 1, 1)', $settings->easing()->toCssValue());
    }

    #[Test]
    public function it_ignores_an_effect_override_while_motion_is_off(): void
    {
        $settings = MotionSettings::off()->withEffect(MotionEffect::FadeIn, true);

        $this->assertFalse($settings->isEffectActive(MotionEffect::FadeIn));
    }

    #[Test]
    public function it_lets_the_active_effect_list_drive_module_loading(): void
    {
        $settings = MotionSettings::of(MotionPreset::None)
            ->withEffect(MotionEffect::CounterRoll, true)
            ->withEffect(MotionEffect::Marquee, true);

        $this->assertSame(
            [MotionModule::Counters, MotionModule::Marquee],
            $settings->modulesToLoad(),
        );
    }

    #[Test]
    public function it_deduplicates_modules_shared_by_several_effects(): void
    {
        $settings = MotionSettings::of(MotionPreset::None)
            ->withEffect(MotionEffect::FadeIn, true)
            ->withEffect(MotionEffect::FadeInUp, true)
            ->withEffect(MotionEffect::ScaleIn, true);

        $this->assertSame([MotionModule::Reveal], $settings->modulesToLoad());
    }

    #[Test]
    public function it_turns_a_preset_effect_off_without_touching_the_others(): void
    {
        $settings = MotionSettings::of(MotionPreset::Refined)->withEffect(MotionEffect::FadeInUp, false);

        $this->assertFalse($settings->isEffectActive(MotionEffect::FadeInUp));
        $this->assertTrue($settings->isEffectActive(MotionEffect::FadeIn));
        $this->assertTrue($settings->isEffectOverridden(MotionEffect::FadeInUp));
        $this->assertFalse($settings->isEffectOverridden(MotionEffect::FadeIn));
    }

    #[Test]
    public function it_restores_the_presets_own_choice_when_an_override_is_removed(): void
    {
        $settings = MotionSettings::of(MotionPreset::Refined)
            ->withEffect(MotionEffect::FadeInUp, false)
            ->withoutEffectOverride(MotionEffect::FadeInUp);

        $this->assertTrue($settings->isEffectActive(MotionEffect::FadeInUp));
        $this->assertFalse($settings->isEffectOverridden(MotionEffect::FadeInUp));
    }

    #[Test]
    public function it_carries_overrides_across_a_preset_change(): void
    {
        $settings = MotionSettings::of(MotionPreset::Subtle)
            ->withEffect(MotionEffect::CustomCursor, true)
            ->withPreset(MotionPreset::Dynamic);

        $this->assertSame(MotionPreset::Dynamic, $settings->preset);
        $this->assertTrue($settings->isEffectActive(MotionEffect::CustomCursor));
    }

    #[Test]
    public function it_drops_an_override_for_something_that_is_not_an_effect(): void
    {
        $settings = MotionSettings::of(MotionPreset::Subtle, ['not-an-effect' => true]);

        $this->assertSame(MotionPreset::Subtle->defaultEffects(), $settings->activeEffects());
    }

    #[Test]
    public function it_uses_the_presets_easing_when_no_custom_curve_is_set(): void
    {
        $settings = MotionSettings::of(MotionPreset::Refined);

        $this->assertSame(MotionEasing::Decelerate->toCubicBezier()->toCssValue(), $settings->easing()->toCssValue());
    }

    #[Test]
    public function it_prefers_a_custom_curve_over_the_presets_easing(): void
    {
        $settings = MotionSettings::of(MotionPreset::Refined, [], CubicBezier::of(0.1, 0.9, 0.2, 1.0));

        $this->assertSame('cubic-bezier(0.1, 0.9, 0.2, 1)', $settings->easing()->toCssValue());
    }

    #[Test]
    public function it_defaults_to_refined_with_motion_on(): void
    {
        $settings = MotionSettings::defaults();

        $this->assertTrue($settings->enabled);
        $this->assertSame(MotionPreset::Refined, $settings->preset);
        $this->assertFalse($settings->timing()->isStill());
    }

    #[Test]
    public function it_keeps_the_timing_easing_replaceable(): void
    {
        $timing = MotionTiming::of(100, 200, 300, 40, 10, MotionEasing::Standard)
            ->withEasing(MotionEasing::Spring);

        $this->assertSame(MotionEasing::Spring, $timing->easing);
        $this->assertSame(200, $timing->baseMilliseconds);
    }

    #[Test]
    public function it_refuses_a_negative_duration(): void
    {
        $timing = MotionTiming::of(-10, -20, -30, -40, -50, MotionEasing::Standard);

        $this->assertTrue($timing->isStill());
    }
}
