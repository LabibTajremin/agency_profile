<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * Spam checks that work with nothing configured.
 *
 * A honeypot and a time trap cost the visitor nothing, need no third-party account, and stop
 * the overwhelming majority of automated submissions. A captcha is an optional extra on top —
 * never the thing the protection depends on, because a site owner who has not signed up for
 * one must not be left unprotected.
 */
final class SpamPolicy
{
    public const HONEYPOT_FIELD = 'edulume_website';

    private const MINIMUM_SECONDS_ON_FORM = 3;
    private const MAXIMUM_SECONDS_ON_FORM = 86400;

    /**
     * @param array<string, string> $answers
     */
    public function honeypotWasFilled(array $answers): bool
    {
        return trim($answers[self::HONEYPOT_FIELD] ?? '') !== '';
    }

    /**
     * A form completed faster than a human can read it, or opened a day ago, is not a person.
     */
    public function wasCompletedTooFast(int $secondsOnForm): bool
    {
        return $secondsOnForm < self::MINIMUM_SECONDS_ON_FORM;
    }

    public function formWasStale(int $secondsOnForm): bool
    {
        return $secondsOnForm > self::MAXIMUM_SECONDS_ON_FORM;
    }

    /**
     * @param array<string, string> $answers
     */
    public function reasonToDiscard(array $answers, int $secondsOnForm, bool $captchaPassed): ?string
    {
        if ($this->honeypotWasFilled($answers)) {
            return 'honeypot';
        }

        if ($this->wasCompletedTooFast($secondsOnForm)) {
            return 'time-trap';
        }

        if ($this->formWasStale($secondsOnForm)) {
            return 'stale-form';
        }

        return $captchaPassed ? null : 'captcha';
    }
}
