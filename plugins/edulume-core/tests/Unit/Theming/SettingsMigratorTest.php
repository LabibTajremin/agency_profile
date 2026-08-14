<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\SettingsMigrator;
use Edulume\Core\Domain\Theming\ThemeMode;
use Edulume\Core\Domain\Theming\ThemeSettings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SettingsMigratorTest extends TestCase
{
    private SettingsMigrator $migrator;

    protected function setUp(): void
    {
        $this->migrator = new SettingsMigrator();
    }

    /**
     * @return array<string, mixed>
     */
    private static function versionOneBlob(): array
    {
        return [
            'schemaVersion' => 1,
            'accentSlug' => 'gulf-gold',
            'themePreference' => 'dark',
            'patternRotation' => 45,
            'pattern' => [
                'patternSlug' => 'compass-rose',
                'opacity' => 0.09,
                'scale' => 1.5,
            ],
            'typography' => ['pairingSlug' => 'gulf-premium'],
        ];
    }

    #[Test]
    public function it_reports_that_a_version_one_blob_needs_migrating(): void
    {
        $this->assertTrue($this->migrator->needsMigration(self::versionOneBlob()));
    }

    #[Test]
    public function it_reports_that_a_current_blob_does_not_need_migrating(): void
    {
        $this->assertFalse($this->migrator->needsMigration(ThemeSettings::defaults()->toArray()));
    }

    #[Test]
    public function it_treats_a_blob_with_no_version_as_the_first_schema(): void
    {
        $this->assertTrue($this->migrator->needsMigration(['accentSlug' => 'oxford-blue']));
    }

    /**
     * A stored value that is not an array at all is treated as unmigrated rather than
     * refused: migrating it yields a versioned empty blob, which loads as the defaults.
     */
    #[Test]
    public function it_treats_a_value_that_is_not_a_blob_as_unmigrated(): void
    {
        $this->assertTrue($this->migrator->needsMigration('not an array'));
    }

    #[Test]
    public function it_stamps_the_current_version_after_migrating(): void
    {
        $migrated = $this->migrator->migrate(self::versionOneBlob());

        $this->assertSame(ThemeSettings::CURRENT_SCHEMA_VERSION, $migrated['schemaVersion']);
        $this->assertFalse($this->migrator->needsMigration($migrated));
    }

    #[Test]
    public function it_splits_the_single_v1_opacity_across_both_modes(): void
    {
        $settings = ThemeSettings::fromArray($this->migrator->migrate(self::versionOneBlob()));

        $this->assertSame(0.09, $settings->pattern->opacityFor(ThemeMode::Light));
        $this->assertGreaterThan(
            $settings->pattern->opacityFor(ThemeMode::Light),
            $settings->pattern->opacityFor(ThemeMode::Dark),
        );
    }

    #[Test]
    public function it_never_lifts_the_dark_opacity_past_one(): void
    {
        $blob = self::versionOneBlob();
        $blob['pattern']['opacity'] = 0.9;

        $settings = ThemeSettings::fromArray($this->migrator->migrate($blob));

        $this->assertSame(1.0, $settings->pattern->opacityFor(ThemeMode::Dark));
    }

    #[Test]
    public function it_moves_the_top_level_rotation_into_the_pattern_block(): void
    {
        $migrated = $this->migrator->migrate(self::versionOneBlob());

        $this->assertArrayNotHasKey('patternRotation', $migrated);
        $this->assertSame(45, ThemeSettings::fromArray($migrated)->pattern->rotation);
    }

    #[Test]
    public function it_loses_nothing_else_on_the_way_forward(): void
    {
        $settings = ThemeSettings::fromArray($this->migrator->migrate(self::versionOneBlob()));

        $this->assertSame('gulf-gold', $settings->accentSlug);
        $this->assertSame('dark', $settings->themePreference->value);
        $this->assertSame('compass-rose', $settings->pattern->patternSlug);
        $this->assertSame(1.5, $settings->pattern->scale);
        $this->assertSame('gulf-premium', $settings->typography->pairingSlug);
    }

    #[Test]
    public function it_leaves_a_current_blob_untouched(): void
    {
        $current = ThemeSettings::defaults()->toArray();

        $this->assertSame($current, $this->migrator->migrate($current));
    }

    #[Test]
    public function it_tolerates_a_v1_blob_with_no_pattern_block_at_all(): void
    {
        $migrated = $this->migrator->migrate(['schemaVersion' => 1, 'accentSlug' => 'ivy-green']);

        $this->assertSame('ivy-green', ThemeSettings::fromArray($migrated)->accentSlug);
        $this->assertArrayNotHasKey('pattern', $migrated);
    }

    #[Test]
    public function it_tolerates_a_version_from_the_future(): void
    {
        $blob = ThemeSettings::defaults()->toArray();
        $blob['schemaVersion'] = 99;

        $this->assertSame(
            ThemeSettings::CURRENT_SCHEMA_VERSION,
            $this->migrator->migrate($blob)['schemaVersion'],
        );
    }
}
