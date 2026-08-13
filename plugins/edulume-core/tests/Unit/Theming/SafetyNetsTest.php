<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Application\Theming\ApplyStylePreset;
use Edulume\Core\Application\Theming\ImportSettings;
use Edulume\Core\Application\Theming\ResetSettings;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\ResetScope;
use Edulume\Core\Domain\Theming\SafeMode;
use Edulume\Core\Domain\Theming\SettingsDiff;
use Edulume\Core\Domain\Theming\SettingsSnapshot;
use Edulume\Core\Domain\Theming\SnapshotHistory;
use Edulume\Core\Domain\Theming\StylePresetLibrary;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\UndoStack;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SafetyNetsTest extends TestCase
{
    private const TAKEN_AT = '2026-03-01T09:00:00+00:00';

    private static function customised(): ThemeSettings
    {
        return (new ApplyStylePreset())->preview(ThemeSettings::defaults(), StylePresetLibrary::get('bold-brutalist'));
    }

    #[Test]
    public function it_reports_every_changed_key_before_an_import_is_applied(): void
    {
        $import = new ImportSettings();
        $preview = $import->preview(ThemeSettings::defaults(), $import->export(self::customised()));

        $this->assertTrue($preview->isReadable);
        $this->assertFalse($preview->changesNothing());
        $this->assertContains('accentSlug', $preview->diff()->changedKeys());
        $this->assertTrue($preview->diff()->touches('typography'));
        $this->assertTrue($preview->diff()->touches('layout.cornerRadiusPixels'));
    }

    #[Test]
    public function it_shows_the_before_and_after_of_each_change(): void
    {
        $import = new ImportSettings();
        $changes = $import->preview(ThemeSettings::defaults(), $import->export(self::customised()))->diff()->changes();

        $this->assertSame('oxford-blue', $changes['accentSlug']['before']);
        $this->assertSame('visa-crimson', $changes['accentSlug']['after']);
    }

    #[Test]
    public function it_changes_nothing_while_previewing(): void
    {
        $current = ThemeSettings::defaults();
        $before = $current->toArray();
        $import = new ImportSettings();

        $import->preview($current, $import->export(self::customised()));

        $this->assertSame($before, $current->toArray());
    }

    #[Test]
    public function it_applies_only_once_the_preview_is_accepted(): void
    {
        $import = new ImportSettings();
        $preview = $import->preview(ThemeSettings::defaults(), $import->export(self::customised()));

        $this->assertSame(self::customised()->toArray(), $preview->apply(ThemeSettings::defaults())->toArray());
    }

    #[Test]
    public function it_reports_an_unreadable_export_rather_than_failing(): void
    {
        $preview = (new ImportSettings())->preview(ThemeSettings::defaults(), '{ not json at all');

        $this->assertFalse($preview->isReadable);
        $this->assertTrue($preview->changesNothing());
        $this->assertSame(
            ThemeSettings::defaults()->toArray(),
            $preview->apply(ThemeSettings::defaults())->toArray(),
        );
    }

    #[Test]
    public function it_round_trips_an_export_without_loss(): void
    {
        $import = new ImportSettings();
        $settings = self::customised();

        $restored = $import->preview(ThemeSettings::defaults(), $import->export($settings))->apply(ThemeSettings::defaults());

        $this->assertSame($settings->toArray(), $restored->toArray());
    }

    #[Test]
    public function it_reports_nothing_changed_between_identical_configurations(): void
    {
        $diff = SettingsDiff::between(ThemeSettings::defaults()->toArray(), ThemeSettings::defaults()->toArray());

        $this->assertTrue($diff->isEmpty());
        $this->assertSame(0, $diff->count());
        $this->assertFalse($diff->touches('typography'));
    }

    /**
     * @return array<string, array{ResetScope, string, string}>
     */
    public static function scopeProvider(): array
    {
        return [
            'colors' => [ResetScope::Colors, 'accentSlug', 'typography'],
            'typography' => [ResetScope::Typography, 'typography', 'accentSlug'],
            'patterns' => [ResetScope::Patterns, 'pattern', 'accentSlug'],
            'motion' => [ResetScope::Motion, 'motion', 'accentSlug'],
            'layout' => [ResetScope::Layout, 'layout', 'accentSlug'],
        ];
    }

    #[Test]
    #[DataProvider('scopeProvider')]
    public function it_resets_only_what_the_scope_names(ResetScope $scope, string $reset, string $untouched): void
    {
        $customised = self::customised();

        $after = (new ResetSettings())($customised, $scope)->toArray();
        $defaults = ThemeSettings::defaults()->toArray();

        $this->assertSame($defaults[$reset], $after[$reset]);
        $this->assertSame($customised->toArray()[$untouched], $after[$untouched]);
    }

    #[Test]
    public function it_resets_everything_when_asked_to(): void
    {
        $after = (new ResetSettings())(self::customised(), ResetScope::Everything);

        $this->assertSame(ThemeSettings::defaults()->toArray(), $after->toArray());
    }

    #[Test]
    public function it_leaves_the_settings_alone_when_only_sections_are_reset(): void
    {
        $customised = self::customised();

        $this->assertSame($customised->toArray(), (new ResetSettings())($customised, ResetScope::Sections)->toArray());
        $this->assertTrue(ResetScope::Sections->clearsSectionOverrides());
        $this->assertTrue(ResetScope::Everything->clearsSectionOverrides());
        $this->assertFalse(ResetScope::Colors->clearsSectionOverrides());
    }

    /**
     * A confirmation dialog people click through without reading is not a confirmation.
     */
    #[Test]
    public function it_makes_the_person_type_the_scope_before_resetting(): void
    {
        $this->assertTrue(ResetScope::Everything->isConfirmedBy(' EVERYTHING '));
        $this->assertTrue(ResetScope::Typography->isConfirmedBy('typography'));
        $this->assertFalse(ResetScope::Everything->isConfirmedBy('yes'));
        $this->assertFalse(ResetScope::Everything->isConfirmedBy('typography'));
        $this->assertSame('motion', ResetScope::Motion->confirmationPhrase());
    }

    #[Test]
    public function it_keeps_the_last_ten_snapshots_and_no_more(): void
    {
        $history = SnapshotHistory::empty();

        for ($index = 1; $index <= 14; $index++) {
            $history = $history->with(
                SettingsSnapshot::of(ThemeSettings::defaults(), 'save ' . $index, self::TAKEN_AT),
            );
        }

        $this->assertSame(SnapshotHistory::MAXIMUM_SNAPSHOTS, $history->count());
        $this->assertSame('save 14', $history->mostRecent()?->reason);
        $this->assertSame('save 5', $history->at(SnapshotHistory::MAXIMUM_SNAPSHOTS - 1)?->reason);
        $this->assertNull($history->at(SnapshotHistory::MAXIMUM_SNAPSHOTS));
    }

    #[Test]
    public function it_restores_a_snapshot_exactly(): void
    {
        $customised = self::customised();
        $history = SnapshotHistory::empty()
            ->with(SettingsSnapshot::of($customised, 'before applying a preset', self::TAKEN_AT));

        $this->assertSame($customised->toArray(), $history->mostRecent()?->restore()->toArray());
        $this->assertSame('before applying a preset', $history->mostRecent()?->reason);
        $this->assertSame(self::TAKEN_AT, $history->mostRecent()?->takenAt);
    }

    #[Test]
    public function it_round_trips_the_snapshot_history_through_storage(): void
    {
        $history = SnapshotHistory::empty()
            ->with(SettingsSnapshot::of(ThemeSettings::defaults(), 'first', self::TAKEN_AT))
            ->with(SettingsSnapshot::of(self::customised(), 'second', self::TAKEN_AT));

        $this->assertSame($history->toArray(), SnapshotHistory::fromArray($history->toArray())->toArray());
        $this->assertTrue(SnapshotHistory::empty()->isEmpty());
        $this->assertNull(SnapshotHistory::empty()->mostRecent());
        $this->assertSame([], SnapshotHistory::empty()->all());
    }

    #[Test]
    public function it_never_loads_more_than_ten_snapshots_from_a_bloated_row(): void
    {
        $stored = [];

        for ($index = 0; $index < 40; $index++) {
            $stored[] = SettingsSnapshot::of(ThemeSettings::defaults(), 'x', self::TAKEN_AT)->toArray();
        }

        $this->assertSame(SnapshotHistory::MAXIMUM_SNAPSHOTS, SnapshotHistory::fromArray($stored)->count());
    }

    #[Test]
    public function it_undoes_and_redoes_within_a_session(): void
    {
        $stack = UndoStack::startingAt(ThemeSettings::defaults())
            ->push(self::customised())
            ->push(self::customised()->withCustomAccent(Srgb::fromHex('#7a2e6b')));

        $this->assertTrue($stack->canUndo());
        $this->assertFalse($stack->canRedo());
        $this->assertSame(2, $stack->depth());

        $undone = $stack->undo();

        $this->assertSame(self::customised()->toArray(), $undone->current()->toArray());
        $this->assertTrue($undone->canRedo());
        $this->assertSame('#7a2e6b', $undone->redo()->current()->customAccent?->toHex());
    }

    #[Test]
    public function it_abandons_the_redo_branch_once_something_new_is_pushed(): void
    {
        $stack = UndoStack::startingAt(ThemeSettings::defaults())
            ->push(self::customised())
            ->undo()
            ->push(self::customised()->withCustomAccent(Srgb::fromHex('#0f766e')));

        $this->assertFalse($stack->canRedo());
    }

    #[Test]
    public function it_ignores_a_push_that_changes_nothing(): void
    {
        $stack = UndoStack::startingAt(ThemeSettings::defaults())->push(ThemeSettings::defaults());

        $this->assertFalse($stack->canUndo());
        $this->assertSame(0, $stack->depth());
    }

    #[Test]
    public function it_does_nothing_when_there_is_nothing_to_undo_or_redo(): void
    {
        $stack = UndoStack::startingAt(ThemeSettings::defaults());

        $this->assertSame($stack, $stack->undo());
        $this->assertSame($stack, $stack->redo());
    }

    #[Test]
    public function it_bounds_the_undo_history(): void
    {
        $stack = UndoStack::startingAt(ThemeSettings::defaults());

        for ($index = 1; $index <= UndoStack::MAXIMUM_DEPTH + 20; $index++) {
            $stack = $stack->push(
                ThemeSettings::defaults()->withCustomAccent(Srgb::fromChannels($index % 256, 40, 60)),
            );
        }

        $this->assertSame(UndoStack::MAXIMUM_DEPTH, $stack->depth());
    }

    /**
     * The recovery path must not require database access, because the person who needs it is
     * usually the one who does not have any.
     */
    #[Test]
    public function it_renders_the_defaults_in_safe_mode_without_touching_what_is_stored(): void
    {
        $stored = self::customised();

        $this->assertTrue(SafeMode::isRequestedBy([SafeMode::QUERY_PARAMETER => '1']));
        $this->assertSame(
            ThemeSettings::defaults()->toArray(),
            SafeMode::settingsFor($stored, true)->toArray(),
        );
        $this->assertSame($stored->toArray(), SafeMode::settingsFor($stored, false)->toArray());
    }

    #[Test]
    public function it_drops_custom_css_in_safe_mode(): void
    {
        $this->assertFalse(SafeMode::allowsCustomCss(true));
        $this->assertTrue(SafeMode::allowsCustomCss(false));
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function safeModeParameterProvider(): array
    {
        return [
            'one' => ['1', true],
            'true' => ['TRUE', true],
            'yes' => ['yes', true],
            'on' => [' on ', true],
            'integer one' => [1, true],
            'zero' => ['0', false],
            'nonsense' => ['perhaps', false],
            'an array' => [['1'], false],
            'null' => [null, false],
        ];
    }

    #[Test]
    #[DataProvider('safeModeParameterProvider')]
    public function it_only_enters_safe_mode_when_actually_asked(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, SafeMode::isRequestedBy([SafeMode::QUERY_PARAMETER => $value]));
    }

    #[Test]
    public function it_stays_out_of_safe_mode_when_the_parameter_is_absent(): void
    {
        $this->assertFalse(SafeMode::isRequestedBy([]));
        $this->assertFalse(SafeMode::isRequestedBy(['something-else' => '1']));
    }
}
