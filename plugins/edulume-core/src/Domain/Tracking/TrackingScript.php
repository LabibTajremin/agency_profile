<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Tracking;

/**
 * One third-party tracker, and the consent category it belongs to.
 */
final class TrackingScript
{
    private function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly ConsentCategory $category,
        public readonly string $measurementId,
    ) {
    }

    public static function of(string $id, string $label, ConsentCategory $category, string $measurementId): self
    {
        return new self($id, $label, $category, trim($measurementId));
    }

    public function isConfigured(): bool
    {
        return $this->measurementId !== '';
    }

    public function mayLoadGiven(ConsentState $consent): bool
    {
        return $this->isConfigured() && $consent->allows($this->category);
    }
}
