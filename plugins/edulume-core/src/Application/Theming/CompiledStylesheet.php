<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Theming;

/**
 * The compiled stylesheet text together with the content hash that names its file.
 *
 * The hash is derived from the CSS itself, not from a timestamp or a settings blob, so the
 * filename changes when and only when the output changes. That is what lets the file be
 * served with a far-future cache header and still update the moment a setting moves.
 */
final class CompiledStylesheet
{
    public const FILE_NAME_PREFIX = 'edulume-';
    public const FILE_EXTENSION = '.css';

    private const HASH_ALGORITHM = 'xxh128';

    private function __construct(
        public readonly string $css,
        public readonly string $hash,
    ) {
    }

    public static function of(string $css): self
    {
        return new self($css, hash(self::HASH_ALGORITHM, $css));
    }

    public function fileName(): string
    {
        return self::FILE_NAME_PREFIX . $this->hash . self::FILE_EXTENSION;
    }

    public function byteLength(): int
    {
        return strlen($this->css);
    }
}
