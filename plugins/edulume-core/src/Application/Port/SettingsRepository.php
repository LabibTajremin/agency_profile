<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\ThemeSettings;

/**
 * Where theme settings live, expressed without a word about WordPress.
 *
 * The Application layer depends on this; only Infrastructure knows it is an option row.
 * That is what lets the use cases be tested against an in-memory fake in milliseconds.
 */
interface SettingsRepository
{
    public function load(): ThemeSettings;

    public function save(ThemeSettings $settings): void;

    /**
     * @return array<string, SectionOverride> keyed by section id; sections that inherit
     *                                        everything are absent rather than present-and-empty
     */
    public function loadSectionOverrides(): array;

    public function saveSectionOverride(string $sectionId, SectionOverride $override): void;

    public function deleteSectionOverride(string $sectionId): void;
}
