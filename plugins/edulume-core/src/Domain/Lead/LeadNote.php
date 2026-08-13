<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * One entry in a lead's note trail.
 *
 * Notes are append-only and carry who wrote them and when. A pipeline whose history can be
 * edited is a pipeline nobody trusts in a dispute.
 */
final class LeadNote
{
    private function __construct(
        public readonly int $authorId,
        public readonly string $body,
        public readonly string $recordedAt,
        public readonly bool $isSystemEntry,
    ) {
    }

    public static function written(int $authorId, string $body, string $recordedAt): self
    {
        return new self($authorId, trim($body), $recordedAt, false);
    }

    public static function system(string $body, string $recordedAt): self
    {
        return new self(0, trim($body), $recordedAt, true);
    }

    /**
     * @return array<string, bool|int|string>
     */
    public function toArray(): array
    {
        return [
            'authorId' => $this->authorId,
            'body' => $this->body,
            'recordedAt' => $this->recordedAt,
            'isSystemEntry' => $this->isSystemEntry,
        ];
    }
}
