<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

use Edulume\Core\Domain\Support\Guard;

/**
 * A prospective student, and everything that has happened to them.
 *
 * The entity owns its own pipeline rules: an illegal move is refused here, not validated in a
 * controller and hoped for elsewhere. Every state change appends to the note trail, so the
 * history explains itself without anyone having to reconstruct it from timestamps.
 */
final class Lead
{
    public const MAXIMUM_NAME_LENGTH = 200;
    public const MAXIMUM_EMAIL_LENGTH = 254;

    /**
     * @param array<string, string> $submission
     * @param list<LeadNote> $notes
     */
    private function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly LeadStatus $status,
        public readonly int $assignedToId,
        public readonly int $branchId,
        public readonly string $formId,
        public readonly array $submission,
        public readonly array $notes,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @param array<string, string> $submission
     */
    public static function captured(
        string $name,
        string $email,
        string $phone,
        string $formId,
        array $submission,
        string $capturedAt,
        int $branchId = 0,
        int $id = 0
    ): self {
        return new self(
            $id,
            self::truncate($name, self::MAXIMUM_NAME_LENGTH),
            self::truncate($email, self::MAXIMUM_EMAIL_LENGTH),
            trim($phone),
            LeadStatus::New,
            0,
            max(0, $branchId),
            $formId,
            $submission,
            [],
            $capturedAt,
        );
    }

    /**
     * @throws IllegalLeadTransitionException when the pipeline does not allow the move
     */
    public function movedTo(LeadStatus $status, int $actorId, string $movedAt): self
    {
        if ($status === $this->status) {
            return $this;
        }

        if (!$this->status->canTransitionTo($status)) {
            throw IllegalLeadTransitionException::between($this->status, $status);
        }

        return $this->with(
            status: $status,
            notes: [...$this->notes, LeadNote::system(
                sprintf('Moved from %s to %s.', $this->status->label(), $status->label()),
                $movedAt,
            )],
        );
    }

    public function assignedTo(int $counsellorId, string $assignedAt): self
    {
        if ($counsellorId === $this->assignedToId) {
            return $this;
        }

        return $this->with(
            assignedToId: max(0, $counsellorId),
            notes: [...$this->notes, LeadNote::system(
                $counsellorId > 0
                    ? sprintf('Assigned to user %d.', $counsellorId)
                    : 'Assignment cleared.',
                $assignedAt,
            )],
        );
    }

    public function annotated(LeadNote $note): self
    {
        return $this->with(notes: [...$this->notes, $note]);
    }

    public function isAssigned(): bool
    {
        return $this->assignedToId > 0;
    }

    public function isVisibleTo(int $counsellorId): bool
    {
        return $this->assignedToId === $counsellorId;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status->value,
            'assignedToId' => $this->assignedToId,
            'branchId' => $this->branchId,
            'formId' => $this->formId,
            'submission' => $this->submission,
            'notes' => array_map(static fn (LeadNote $note): array => $note->toArray(), $this->notes),
            'createdAt' => $this->createdAt,
        ];
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $notes = [];

        foreach (Guard::toArray($stored['notes'] ?? null) as $storedNote) {
            $note = Guard::toArray($storedNote);
            $body = Guard::toString($note['body'] ?? null);
            $recordedAt = Guard::toString($note['recordedAt'] ?? null);

            $notes[] = Guard::toBool($note['isSystemEntry'] ?? null)
                ? LeadNote::system($body, $recordedAt)
                : LeadNote::written(Guard::toInt($note['authorId'] ?? null), $body, $recordedAt);
        }

        return new self(
            Guard::toInt($stored['id'] ?? null, 0, 0),
            Guard::toString($stored['name'] ?? null),
            Guard::toString($stored['email'] ?? null),
            Guard::toString($stored['phone'] ?? null),
            Guard::toEnum(LeadStatus::class, $stored['status'] ?? null, LeadStatus::New),
            Guard::toInt($stored['assignedToId'] ?? null, 0, 0),
            Guard::toInt($stored['branchId'] ?? null, 0, 0),
            Guard::toString($stored['formId'] ?? null),
            Guard::toStringMap($stored['submission'] ?? null),
            $notes,
            Guard::toString($stored['createdAt'] ?? null),
        );
    }

    /**
     * @param list<LeadNote>|null $notes
     */
    private function with(
        ?LeadStatus $status = null,
        ?int $assignedToId = null,
        ?array $notes = null
    ): self {
        return new self(
            $this->id,
            $this->name,
            $this->email,
            $this->phone,
            $status ?? $this->status,
            $assignedToId ?? $this->assignedToId,
            $this->branchId,
            $this->formId,
            $this->submission,
            $notes ?? $this->notes,
            $this->createdAt,
        );
    }

    private static function truncate(string $value, int $length): string
    {
        return mb_substr(trim($value), 0, $length);
    }
}
