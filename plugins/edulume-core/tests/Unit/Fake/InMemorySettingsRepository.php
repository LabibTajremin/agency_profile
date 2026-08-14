<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\SettingsRepository;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\ThemeSettings;

/**
 * A real, tiny implementation of the repository port.
 *
 * Fakes over mocks: a suite built on mock expectations breaks on every refactor and teaches
 * the maintainer nothing about how the port is meant to behave. This one round-trips through
 * arrays exactly as the WordPress implementation does, so a test that passes here is testing
 * the same contract.
 */
final class InMemorySettingsRepository implements SettingsRepository
{
    /** @var array<string, mixed> */
    private array $settings;

    /** @var array<string, array<string, mixed>> */
    private array $sectionOverrides = [];

    public int $saveCount = 0;

    public function __construct(?ThemeSettings $initial = null)
    {
        $this->settings = ($initial ?? ThemeSettings::defaults())->toArray();
    }

    public function load(): ThemeSettings
    {
        return ThemeSettings::fromArray($this->settings);
    }

    public function save(ThemeSettings $settings): void
    {
        $this->settings = $settings->toArray();
        $this->saveCount++;
    }

    public function loadSectionOverrides(): array
    {
        $overrides = [];

        foreach ($this->sectionOverrides as $sectionId => $stored) {
            $overrides[$sectionId] = SectionOverride::fromArray($stored);
        }

        return $overrides;
    }

    public function saveSectionOverride(string $sectionId, SectionOverride $override): void
    {
        if ($override->isInheritingEverything()) {
            $this->deleteSectionOverride($sectionId);

            return;
        }

        $this->sectionOverrides[$sectionId] = $override->toArray();
    }

    public function deleteSectionOverride(string $sectionId): void
    {
        unset($this->sectionOverrides[$sectionId]);
    }
}
