<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * A curated heading-and-body combination.
 *
 * Pairings exist because most people choose fonts badly and quickly. Two good choices behind
 * one click beats twenty-two families behind two dropdowns.
 */
final class FontPairing
{
    private function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $headingFamilySlug,
        public readonly string $bodyFamilySlug,
    ) {
    }

    public static function of(string $slug, string $name, string $headingFamilySlug, string $bodyFamilySlug): self
    {
        return new self($slug, $name, $headingFamilySlug, $bodyFamilySlug);
    }

    public function headingFamily(): FontFamily
    {
        return FontLibrary::get($this->headingFamilySlug);
    }

    public function bodyFamily(): FontFamily
    {
        return FontLibrary::get($this->bodyFamilySlug);
    }

    public function familyFor(FontRole $role): FontFamily
    {
        return $role->inheritsFrom() === FontRole::Heading ? $this->headingFamily() : $this->bodyFamily();
    }
}
