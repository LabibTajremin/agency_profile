<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Chrome;

use DateTimeImmutable;

/**
 * The scheduled announcement bar.
 *
 * Scheduling is the whole point. An announcement bar someone has to remember to switch off is
 * an announcement bar that is still advertising the January intake in April, which is the most
 * common single thing wrong with sites in this niche.
 */
final class AnnouncementBar
{
    public function __construct(
        public readonly bool $isEnabled,
        public readonly string $message,
        public readonly string $linkUrl = '',
        public readonly string $linkLabel = '',
        public readonly ?DateTimeImmutable $startsAt = null,
        public readonly ?DateTimeImmutable $endsAt = null,
        public readonly bool $isDismissible = true,
    ) {
    }

    public static function disabled(): self
    {
        return new self(false, '');
    }

    /**
     * Whether the bar should render at the given moment.
     *
     * An absent start means "already running" and an absent end means "until switched off",
     * which is what makes the common case — turn it on now, forget about it later — need no
     * dates at all.
     */
    public function isVisibleAt(DateTimeImmutable $now): bool
    {
        if (!$this->isEnabled || trim($this->message) === '') {
            return false;
        }

        if ($this->startsAt !== null && $now < $this->startsAt) {
            return false;
        }

        return $this->endsAt === null || $now <= $this->endsAt;
    }

    public function hasLink(): bool
    {
        return trim($this->linkUrl) !== '' && trim($this->linkLabel) !== '';
    }

    /**
     * A stable identity for the message, so dismissing one announcement does not silently
     * dismiss the next one written in its place.
     */
    public function dismissalKey(): string
    {
        return substr(hash('sha256', $this->message . '|' . $this->linkUrl), 0, 12);
    }
}
