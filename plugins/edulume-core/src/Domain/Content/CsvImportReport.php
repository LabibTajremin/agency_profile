<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * What happened during one import pass: where it got to, and everything that went wrong.
 */
final class CsvImportReport
{
    /**
     * @param list<CsvRowError> $errors
     */
    private function __construct(
        public readonly CsvImportCursor $cursor,
        public readonly array $errors,
    ) {
    }

    /**
     * @param list<CsvRowError> $errors
     */
    public static function of(CsvImportCursor $cursor, array $errors): self
    {
        return new self($cursor, $errors);
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return list<string>
     */
    public function errorMessages(): array
    {
        return array_map(static fn (CsvRowError $error): string => $error->toString(), $this->errors);
    }
}
