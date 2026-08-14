<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Security;

/**
 * What may be uploaded, and by whom.
 *
 * An allowlist, because a denylist of dangerous extensions is a promise to keep up with every
 * handler a server might be configured with, and `.phtml` alone has broken that promise on more
 * sites than anything else on the list.
 */
final class UploadPolicy
{
    /**
     * Extension mapped to the MIME type it must actually be.
     *
     * Both are checked. An extension alone is what the uploader claims; a MIME type alone can be
     * spoofed by the browser. Requiring them to agree with what the file sniffs as is what makes
     * `payload.php.jpg` a rejected upload rather than a stored one.
     *
     * @var array<string, string>
     */
    private const ALLOWED = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'csv' => 'text/csv',
        'woff2' => 'font/woff2',
    ];

    /** Ten megabytes. Larger than any document a lead form needs, smaller than a video. */
    public const MAX_BYTES = 10 * 1024 * 1024;

    /**
     * @return array<string, string>
     */
    public static function allowedTypes(): array
    {
        return self::ALLOWED;
    }

    public static function isAllowedExtension(string $filename): bool
    {
        return array_key_exists(self::extensionOf($filename), self::ALLOWED);
    }

    /**
     * Whether the claimed type matches the extension.
     */
    public static function typeMatches(string $filename, string $mimeType): bool
    {
        $extension = self::extensionOf($filename);

        return (self::ALLOWED[$extension] ?? null) === strtolower(trim($mimeType));
    }

    /**
     * The one file type that is a document pretending to be an image, and so needs sanitising
     * rather than merely accepting.
     */
    public static function needsSanitising(string $filename): bool
    {
        return self::extensionOf($filename) === 'svg';
    }

    /**
     * The full verdict on an upload.
     *
     * @return list<string> the reasons it was refused; empty means accepted
     */
    public static function reasonsToRefuse(string $filename, string $mimeType, int $bytes): array
    {
        $reasons = [];

        if (!self::isAllowedExtension($filename)) {
            $reasons[] = 'That file type is not allowed.';
        } elseif (!self::typeMatches($filename, $mimeType)) {
            // Only worth saying once the extension is known-good; otherwise it repeats the
            // first message in more confusing words.
            $reasons[] = 'The file contents do not match its extension.';
        }

        if ($bytes > self::MAX_BYTES) {
            $reasons[] = 'That file is larger than 10 MB.';
        }

        if ($bytes <= 0) {
            $reasons[] = 'That file is empty.';
        }

        return $reasons;
    }

    /**
     * The last extension, which is the only one the server acts on.
     *
     * `report.php.jpg` is a JPEG to every server that is configured correctly and a PHP script
     * to at least one that is not, so the check reads the last one and the MIME check catches
     * the rest.
     */
    private static function extensionOf(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return strtolower(is_string($extension) ? $extension : '');
    }
}
