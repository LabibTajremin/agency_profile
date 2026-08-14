<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Application\Theming\ApplyStylePreset;
use Edulume\Core\Domain\Theming\FontRole;
use Edulume\Core\Domain\Theming\FontSubset;
use Edulume\Core\Domain\Theming\StylePreset;
use Edulume\Core\Domain\Theming\StylePresetLibrary;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\TypeScale;
use Edulume\Core\Domain\Theming\TypographySettings;
use Edulume\Core\Domain\Theming\UnknownStylePresetException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApplyStylePresetTest extends TestCase
{
    private ApplyStylePreset $applyStylePreset;

    protected function setUp(): void
    {
        $this->applyStylePreset = new ApplyStylePreset();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function presetProvider(): array
    {
        $cases = [];

        foreach (StylePresetLibrary::slugs() as $slug) {
            $cases[$slug] = [$slug];
        }

        return $cases;
    }

    #[Test]
    public function it_ships_ten_complete_looks(): void
    {
        $this->assertCount(StylePresetLibrary::PRESET_COUNT, StylePresetLibrary::all());
    }

    #[Test]
    #[DataProvider('presetProvider')]
    public function it_applies_every_preset_to_a_full_valid_settings_object(string $slug): void
    {
        $applied = $this->applyStylePreset->preview(ThemeSettings::defaults(), StylePresetLibrary::get($slug));

        $this->assertSame(ThemeSettings::CURRENT_SCHEMA_VERSION, $applied->toArray()['schemaVersion']);
        $this->assertSame($applied->toArray(), ThemeSettings::fromArray($applied->toArray())->toArray());
    }

    #[Test]
    #[DataProvider('presetProvider')]
    public function it_actually_changes_something_for_every_preset(string $slug): void
    {
        $before = ThemeSettings::defaults();
        $after = $this->applyStylePreset->preview($before, StylePresetLibrary::get($slug));

        $this->assertNotSame($before->toArray(), $after->toArray());
    }

    #[Test]
    #[DataProvider('presetProvider')]
    public function it_gives_every_preset_a_name_and_a_description(string $slug): void
    {
        $preset = StylePresetLibrary::get($slug);

        $this->assertNotSame('', $preset->name);
        $this->assertNotSame('', $preset->description);
        $this->assertTrue($preset->isBuiltIn);
    }

    #[Test]
    public function it_leaves_the_current_settings_untouched_while_previewing(): void
    {
        $current = ThemeSettings::defaults();
        $before = $current->toArray();

        $this->applyStylePreset->preview($current, StylePresetLibrary::get('gulf-premium'));

        $this->assertSame($before, $current->toArray());
    }

    #[Test]
    #[DataProvider('presetProvider')]
    public function it_restores_the_prior_state_byte_for_byte_on_undo(string $slug): void
    {
        $before = ThemeSettings::defaults();

        $application = $this->applyStylePreset->apply($before, StylePresetLibrary::get($slug));

        $this->assertSame($before->toArray(), $application->undo()->toArray());
        $this->assertSame($before->toArray(), $application->snapshot());
    }

    #[Test]
    public function it_undoes_correctly_from_a_configuration_that_was_not_the_defaults(): void
    {
        $customised = $this->applyStylePreset->preview(
            ThemeSettings::defaults(),
            StylePresetLibrary::get('bold-brutalist'),
        );

        $application = $this->applyStylePreset->apply($customised, StylePresetLibrary::get('nordic-minimal'));

        $this->assertSame($customised->toArray(), $application->undo()->toArray());
    }

    #[Test]
    public function it_carries_the_preset_it_applied(): void
    {
        $application = $this->applyStylePreset->apply(
            ThemeSettings::defaults(),
            StylePresetLibrary::get('midnight-pro'),
        );

        $this->assertSame('midnight-pro', $application->preset->slug);
        $this->assertSame('dark', $application->settings->themePreference->value);
    }

    /**
     * A preset must not quietly reset the things it has no opinion about.
     */
    #[Test]
    public function it_leaves_keys_the_preset_does_not_declare_alone(): void
    {
        $current = ThemeSettings::fromArray(array_merge(ThemeSettings::defaults()->toArray(), [
            'typography' => TypographySettings::of(
                'modern-clarity',
                [FontRole::Code->value => 'jetbrains-mono'],
                TypeScale::of(1.25),
                [FontSubset::Latin, FontSubset::Arabic],
                true,
            )->toArray(),
        ]));

        $applied = $this->applyStylePreset->preview($current, StylePresetLibrary::get('oxford-classic'));

        $this->assertSame('oxford-editorial', $applied->typography->pairingSlug);
        $this->assertSame('jetbrains-mono', $applied->typography->familyFor(FontRole::Code)->slug);
        $this->assertTrue($applied->typography->usesGoogleFontsCdn);
        $this->assertSame([FontSubset::Latin, FontSubset::Arabic], $applied->typography->subsets());
    }

    #[Test]
    public function it_replaces_a_stored_list_wholesale_rather_than_merging_it(): void
    {
        $current = ThemeSettings::fromArray(array_merge(ThemeSettings::defaults()->toArray(), [
            'typography' => TypographySettings::of(
                'modern-clarity',
                [],
                TypeScale::of(1.25),
                [FontSubset::Latin, FontSubset::Arabic, FontSubset::Cyrillic],
            )->toArray(),
        ]));

        $preset = StylePreset::of('latin-only', 'Latin Only', 'Ships one subset.', [
            'typography' => ['subsets' => ['latin']],
        ]);

        $this->assertSame([FontSubset::Latin], $preset->applyTo($current)->typography->subsets());
    }

    #[Test]
    public function it_captures_the_current_settings_as_a_user_preset(): void
    {
        $settings = $this->applyStylePreset->preview(
            ThemeSettings::defaults(),
            StylePresetLibrary::get('vibrant-youth'),
        );

        $captured = StylePreset::capturedFrom('house-style', 'House Style', 'Ours.', $settings);

        $this->assertFalse($captured->isBuiltIn);
        $this->assertSame($settings->toArray(), $captured->applyTo(ThemeSettings::defaults())->toArray());
    }

    #[Test]
    public function it_exports_and_re_imports_a_user_preset_losslessly(): void
    {
        $captured = StylePreset::capturedFrom('house-style', 'House Style', 'Ours.', ThemeSettings::defaults());

        $reimported = StylePreset::fromJson($captured->toJson());

        $this->assertSame($captured->toArray(), $reimported->toArray());
        $this->assertSame($captured->values(), $reimported->values());
        $this->assertSame($captured->toJson(), $reimported->toJson());
    }

    #[Test]
    public function it_re_imports_a_captured_preset_to_the_same_settings(): void
    {
        $settings = $this->applyStylePreset->preview(
            ThemeSettings::defaults(),
            StylePresetLibrary::get('gulf-premium'),
        );

        $reimported = StylePreset::fromJson(
            StylePreset::capturedFrom('house-style', 'House Style', 'Ours.', $settings)->toJson(),
        );

        $this->assertSame($settings->toArray(), $reimported->applyTo(ThemeSettings::defaults())->toArray());
    }

    #[Test]
    public function it_yields_an_empty_preset_from_an_unreadable_export(): void
    {
        $preset = StylePreset::fromJson('{ this is not json');

        $this->assertSame('', $preset->slug);
        $this->assertSame([], $preset->values());
    }

    #[Test]
    public function it_reimports_a_preset_stored_with_missing_keys(): void
    {
        $preset = StylePreset::fromArray(['slug' => 'partial']);

        $this->assertSame('partial', $preset->slug);
        $this->assertSame('', $preset->name);
        $this->assertFalse($preset->isBuiltIn);
        $this->assertSame([], $preset->values());
    }

    #[Test]
    public function it_reports_whether_a_slug_is_shipped(): void
    {
        $this->assertTrue(StylePresetLibrary::has('oxford-classic'));
        $this->assertFalse(StylePresetLibrary::has('neon-catastrophe'));
    }

    #[Test]
    public function it_rejects_a_preset_it_does_not_ship(): void
    {
        $this->expectException(UnknownStylePresetException::class);

        StylePresetLibrary::get('neon-catastrophe');
    }
}
