<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * One column of the uploaded file, and what it means.
 */
final class CsvColumnMapping
{
    private function __construct(
        public readonly string $header,
        public readonly CsvTarget $target,
        public readonly string $key,
        public readonly bool $isRequired,
    ) {
    }

    public static function of(string $header, CsvTarget $target, string $key = '', bool $isRequired = false): self
    {
        return new self($header, $target->needsKey() && $key === '' ? CsvTarget::Ignore : $target, $key, $isRequired);
    }

    public static function ignored(string $header): self
    {
        return new self($header, CsvTarget::Ignore, '', false);
    }
}
