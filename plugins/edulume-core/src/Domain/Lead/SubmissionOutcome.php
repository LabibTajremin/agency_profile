<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * What happened to one submission, and why.
 *
 * A rejection carries per-field messages rather than a single "something went wrong", because
 * a form that cannot say which field is wrong is a form people abandon.
 */
final class SubmissionOutcome
{
    /**
     * @param array<string, string> $fieldErrors
     */
    private function __construct(
        public readonly bool $isAccepted,
        public readonly array $fieldErrors,
        public readonly string $rejectionReason,
        public readonly bool $wasSilentlyDiscarded,
    ) {
    }

    public static function accepted(): self
    {
        return new self(true, [], '', false);
    }

    /**
     * @param array<string, string> $fieldErrors
     */
    public static function invalid(array $fieldErrors): self
    {
        return new self(false, $fieldErrors, 'Some answers need checking.', false);
    }

    /**
     * Spam is discarded without telling the sender anything useful. A bot that learns which
     * trap it tripped is a bot that comes back past it.
     */
    public static function discardedAsSpam(string $reason): self
    {
        return new self(false, [], $reason, true);
    }

    public static function rateLimited(): self
    {
        return new self(false, [], 'Too many submissions from this address. Try again shortly.', false);
    }
}
