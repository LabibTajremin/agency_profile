<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Integration\Settings;

use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\SectionOverrideKey;
use Edulume\Core\Domain\Theming\SettingsMigrator;
use Edulume\Core\Domain\Theming\ThemeMode;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Infrastructure\Settings\OptionSettingsRepository;
use WP_UnitTestCase;

/**
 * Proves the repository against real option storage: real serialisation, real autoload flags,
 * real round trips through the database.
 */
final class OptionSettingsRepositoryTest extends WP_UnitTestCase
{
    private OptionSettingsRepository $repository;

    public function set_up(): void
    {
        parent::set_up();

        $this->repository = new OptionSettingsRepository(new SettingsMigrator());

        delete_option(OptionSettingsRepository::SETTINGS_OPTION);
        delete_option(OptionSettingsRepository::SECTION_OVERRIDES_OPTION);
    }

    /** @test */
    public function it_returns_the_defaults_when_nothing_has_been_saved(): void
    {
        $this->assertSame(ThemeSettings::defaults()->toArray(), $this->repository->load()->toArray());
    }

    /** @test */
    public function it_persists_settings_and_reloads_them_unchanged(): void
    {
        $settings = ThemeSettings::defaults()->withCustomAccent(Srgb::fromHex('#7a2e6b'));

        $this->repository->save($settings);

        $this->assertSame($settings->toArray(), $this->repository->load()->toArray());
    }

    /** @test */
    public function it_survives_a_full_option_cache_flush(): void
    {
        $settings = ThemeSettings::defaults()->withCustomAccent(Srgb::fromHex('#0f766e'));

        $this->repository->save($settings);
        wp_cache_flush();

        $this->assertSame($settings->toArray(), $this->repository->load()->toArray());
    }

    /** @test */
    public function it_migrates_an_older_blob_on_read_and_writes_the_result_back(): void
    {
        update_option(OptionSettingsRepository::SETTINGS_OPTION, [
            'schemaVersion' => 1,
            'accentSlug' => 'gulf-gold',
            'patternRotation' => 45,
            'pattern' => ['patternSlug' => 'compass-rose', 'opacity' => 0.09],
        ], false);

        $loaded = $this->repository->load();

        $this->assertSame('gulf-gold', $loaded->accentSlug);
        $this->assertSame(45, $loaded->pattern->rotation);
        $this->assertSame(0.09, $loaded->pattern->opacityFor(ThemeMode::Light));

        $stored = get_option(OptionSettingsRepository::SETTINGS_OPTION);

        $this->assertIsArray($stored);
        $this->assertSame(ThemeSettings::CURRENT_SCHEMA_VERSION, $stored['schemaVersion']);
    }

    /** @test */
    public function it_loads_defaults_from_a_corrupt_option_rather_than_failing(): void
    {
        update_option(OptionSettingsRepository::SETTINGS_OPTION, 'not an array at all', false);

        $this->assertSame(ThemeSettings::defaults()->accentSlug, $this->repository->load()->accentSlug);
    }

    /** @test */
    public function it_persists_a_section_override(): void
    {
        $override = SectionOverride::inheritEverything()->with(SectionOverrideKey::AccentSlug, 'ivy-green');

        $this->repository->saveSectionOverride('hero', $override);

        $loaded = $this->repository->loadSectionOverrides();

        $this->assertArrayHasKey('hero', $loaded);
        $this->assertSame($override->toArray(), $loaded['hero']->toArray());
    }

    /** @test */
    public function it_stores_inheritance_as_absence_rather_than_an_empty_row(): void
    {
        $this->repository->saveSectionOverride('hero', SectionOverride::inheritEverything());

        $this->assertSame([], $this->repository->loadSectionOverrides());
    }

    /** @test */
    public function it_removes_an_override_when_the_last_key_is_reset(): void
    {
        $this->repository->saveSectionOverride(
            'footer',
            SectionOverride::inheritEverything()->with(SectionOverrideKey::CornerRadiusPixels, 0),
        );

        $this->repository->deleteSectionOverride('footer');

        $this->assertSame([], $this->repository->loadSectionOverrides());
    }

    /** @test */
    public function it_ignores_a_stored_override_for_something_that_is_not_a_section(): void
    {
        update_option(OptionSettingsRepository::SECTION_OVERRIDES_OPTION, [
            'hero' => ['cornerRadiusPixels' => 24],
            'not-a-section' => ['cornerRadiusPixels' => 8],
        ], false);

        $this->assertSame(['hero'], array_keys($this->repository->loadSectionOverrides()));
    }

    /** @test */
    public function it_deletes_nothing_when_asked_to_remove_an_override_that_is_not_there(): void
    {
        $this->repository->saveSectionOverride(
            'hero',
            SectionOverride::inheritEverything()->with(SectionOverrideKey::CornerRadiusPixels, 24),
        );

        $this->repository->deleteSectionOverride('footer');

        $this->assertSame(['hero'], array_keys($this->repository->loadSectionOverrides()));
    }

    /** @test */
    public function it_keeps_the_settings_out_of_the_autoloaded_option_set(): void
    {
        $this->repository->save(ThemeSettings::defaults());

        global $wpdb;

        $autoload = $wpdb->get_var($wpdb->prepare(
            "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s",
            OptionSettingsRepository::SETTINGS_OPTION
        ));

        $this->assertNotSame('yes', $autoload, 'A multi-kilobyte blob must not load on every request.');
    }
}
