<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Demo;

/**
 * How far a demo import got.
 *
 * The largest demo creates over a thousand items. Shared hosting caps execution at 30 seconds,
 * so an importer that cannot stop and resume simply cannot install that demo on the hosting
 * most of these sites run on — and a half-finished import that cannot be resumed is worse than
 * one that never started.
 */
final class DemoImportCursor
{
    /**
     * @param list<int> $createdIds every item created so far, so a rollback is exact
     */
    private function __construct(
        public readonly string $demoSlug,
        public readonly int $postTypeIndex,
        public readonly int $itemIndex,
        public readonly array $createdIds,
        public readonly bool $settingsApplied,
        public readonly bool $isComplete,
    ) {
    }

    public static function start(string $demoSlug): self
    {
        return new self($demoSlug, 0, 0, [], false, false);
    }

    /**
     * @param list<int> $createdIds
     */
    public static function of(
        string $demoSlug,
        int $postTypeIndex,
        int $itemIndex,
        array $createdIds,
        bool $settingsApplied,
        bool $isComplete,
    ): self {
        return new self(
            $demoSlug,
            max(0, $postTypeIndex),
            max(0, $itemIndex),
            array_values(array_filter($createdIds, static fn (int $id): bool => $id > 0)),
            $settingsApplied,
            $isComplete,
        );
    }

    /** Records one created item and moves to the next within the current post type. */
    public function withItem(int $createdId): self
    {
        return new self(
            $this->demoSlug,
            $this->postTypeIndex,
            $this->itemIndex + 1,
            $createdId > 0 ? [...$this->createdIds, $createdId] : $this->createdIds,
            $this->settingsApplied,
            false,
        );
    }

    /** Moves to the next post type, resetting the item counter. */
    public function withNextPostType(): self
    {
        return new self(
            $this->demoSlug,
            $this->postTypeIndex + 1,
            0,
            $this->createdIds,
            $this->settingsApplied,
            false,
        );
    }

    public function withSettingsApplied(): self
    {
        return new self(
            $this->demoSlug,
            $this->postTypeIndex,
            $this->itemIndex,
            $this->createdIds,
            true,
            $this->isComplete,
        );
    }

    public function completed(): self
    {
        return new self(
            $this->demoSlug,
            $this->postTypeIndex,
            $this->itemIndex,
            $this->createdIds,
            $this->settingsApplied,
            true,
        );
    }

    public function createdCount(): int
    {
        return count($this->createdIds);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'demoSlug' => $this->demoSlug,
            'postTypeIndex' => $this->postTypeIndex,
            'itemIndex' => $this->itemIndex,
            'createdIds' => $this->createdIds,
            'settingsApplied' => $this->settingsApplied,
            'isComplete' => $this->isComplete,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $ids = [];

        foreach (is_array($data['createdIds'] ?? null) ? $data['createdIds'] : [] as $candidate) {
            if (is_int($candidate) || (is_string($candidate) && ctype_digit($candidate))) {
                $ids[] = (int) $candidate;
            }
        }

        return self::of(
            is_string($data['demoSlug'] ?? null) ? $data['demoSlug'] : '',
            is_numeric($data['postTypeIndex'] ?? null) ? (int) $data['postTypeIndex'] : 0,
            is_numeric($data['itemIndex'] ?? null) ? (int) $data['itemIndex'] : 0,
            $ids,
            (bool) ($data['settingsApplied'] ?? false),
            (bool) ($data['isComplete'] ?? false),
        );
    }
}
