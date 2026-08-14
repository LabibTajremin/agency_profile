<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\AccentPalette;
use Edulume\Core\Domain\Theming\ThemeMode;

/**
 * The HTML sent back to the person who enquired.
 *
 * Colours are inlined from the site's own palette rather than hard-coded, so the email looks
 * like the site it came from. They have to be inlined: no email client of consequence supports
 * custom properties or an external stylesheet, which is the one place the token rule cannot
 * apply.
 */
final class AutoresponderTemplate
{
    private const NAME_PLACEHOLDER = '{{name}}';
    private const SITE_PLACEHOLDER = '{{site}}';
    private const BODY_PLACEHOLDER = '{{body}}';

    public function __construct(
        private readonly AccentPalette $palette,
        private readonly string $siteName,
    ) {
    }

    public function render(Lead $lead, string $bodyText): string
    {
        $accent = $this->palette->fill(ThemeMode::Light);
        $onAccent = $this->palette->onFill(ThemeMode::Light);
        $surface = $this->palette->step(0);
        $ink = $this->palette->foregroundOn(0);

        // The template body is admin-authored and may legitimately contain markup; only the
        // values substituted into it come from a visitor, so only those are escaped.
        $body = str_replace(
            [self::NAME_PLACEHOLDER, self::SITE_PLACEHOLDER],
            [$this->escape($lead->name), $this->escape($this->siteName)],
            $bodyText,
        );

        return $this->shell($accent, $onAccent, $surface, $ink, $body);
    }

    /**
     * @return list<string>
     */
    public function placeholders(): array
    {
        return [self::NAME_PLACEHOLDER, self::SITE_PLACEHOLDER, self::BODY_PLACEHOLDER];
    }

    private function shell(Srgb $accent, Srgb $onAccent, Srgb $surface, Srgb $ink, string $body): string
    {
        $siteName = $this->escape($this->siteName);

        return <<<HTML
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               style="background:{$surface->toHex()};padding:24px 0;">
          <tr>
            <td align="center">
              <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                     style="background:#ffffff;border-radius:12px;overflow:hidden;">
                <tr>
                  <td style="background:{$accent->toHex()};color:{$onAccent->toHex()};padding:20px 24px;
                             font-family:Helvetica,Arial,sans-serif;font-size:18px;">{$siteName}</td>
                </tr>
                <tr>
                  <td style="padding:24px;color:{$ink->toHex()};font-family:Helvetica,Arial,sans-serif;
                             font-size:15px;line-height:1.6;">{$body}</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
        HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
