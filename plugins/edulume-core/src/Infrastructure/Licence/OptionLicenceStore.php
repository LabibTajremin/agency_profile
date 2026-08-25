<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Licence;

use DateTimeImmutable;
use Edulume\Core\Domain\Licence\Licence;

/**
 * Where the licence lives between requests.
 *
 * The domain object is a value object with no idea what a database is, and it deliberately has
 * no `toArray()` — the serialised shape is a storage concern that would otherwise leak into a
 * class the contract tests treat as pure. So the mapping lives here, in the one place that
 * actually talks to an option.
 *
 * Not autoloaded: the licence is read on the licence screen and on the daily re-check, not on
 * every front-end page, and an autoloaded option is fetched on all of them.
 */
final class OptionLicenceStore
{
    public const OPTION = 'edulume_licence';

    public function load(): Licence
    {
        $stored = get_option(self::OPTION, []);

        if (!is_array($stored) || !is_string($stored['key'] ?? null)) {
            return Licence::none();
        }

        return new Licence(
            $stored['key'],
            $this->date($stored['expiresAt'] ?? null),
            is_int($stored['siteLimit'] ?? null) ? $stored['siteLimit'] : 1,
            is_int($stored['activationCount'] ?? null) ? $stored['activationCount'] : 0,
            (bool) ($stored['isRevoked'] ?? false),
            (bool) ($stored['isActivatedHere'] ?? false),
            $this->date($stored['lastCheckedAt'] ?? null),
        );
    }

    public function save(Licence $licence): void
    {
        update_option(self::OPTION, [
            'key' => $licence->key,
            'expiresAt' => $licence->expiresAt?->format(DATE_ATOM),
            'siteLimit' => $licence->siteLimit,
            'activationCount' => $licence->activationCount,
            'isRevoked' => $licence->isRevoked,
            'isActivatedHere' => $licence->isActivatedHere,
            'lastCheckedAt' => $licence->lastCheckedAt?->format(DATE_ATOM),
        ], false);
    }

    private function date(mixed $stored): ?DateTimeImmutable
    {
        if (!is_string($stored) || $stored === '') {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat(DATE_ATOM, $stored);

        return $parsed === false ? null : $parsed;
    }
}
