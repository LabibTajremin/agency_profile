<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Accessibility;

use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Color\Srgb;

/**
 * The accessibility rules the product enforces rather than documents.
 *
 * Everything here is a number a machine can check. The parts that need a person — a screen
 * reader pass, whether alt text actually describes the image — are in `docs/accessibility.md`,
 * because pretending a build can check them is worse than admitting it cannot.
 */
final class AccessibilityRules
{
    /** WCAG 2.2 target size (minimum), in CSS pixels. */
    public const TOUCH_TARGET_PX = 44;

    /** Non-text contrast, for the focus ring and for control borders. */
    public const NON_TEXT_CONTRAST = 3.0;

    /** Normal body text. */
    public const TEXT_CONTRAST = 4.5;

    /** Text at 24px, or 18.66px bold, and larger. */
    public const LARGE_TEXT_CONTRAST = 3.0;

    public function __construct(private readonly ContrastEngine $contrast = new ContrastEngine())
    {
    }

    /**
     * Whether a focus ring is visible against the surface it is drawn on.
     *
     * The ring is accent-coloured, and an accent chosen for a light surface can vanish on a dark
     * one — so this is checked per mode, against the surface actually behind the control, not
     * against a nominal page background.
     */
    public function focusRingIsVisible(Srgb $ring, Srgb $surface): bool
    {
        return $this->contrast->ratio($ring, $surface) >= self::NON_TEXT_CONTRAST;
    }

    public function textIsLegible(Srgb $ink, Srgb $surface, bool $isLargeText = false): bool
    {
        $required = $isLargeText ? self::LARGE_TEXT_CONTRAST : self::TEXT_CONTRAST;

        return $this->contrast->ratio($ink, $surface) >= $required;
    }

    /**
     * Whether an interactive target is big enough to hit.
     *
     * Both axes, because a 200×20 button fails for the same reason a 20×200 one does.
     */
    public static function targetIsBigEnough(int $width, int $height): bool
    {
        return $width >= self::TOUCH_TARGET_PX && $height >= self::TOUCH_TARGET_PX;
    }

    /**
     * Whether a heading sequence is well-formed.
     *
     * One h1, and no level skipped on the way down. A jump from h2 to h4 is not a style choice;
     * it tells a screen-reader user that a section they cannot see was left out.
     *
     * @param list<int> $levels heading levels in document order
     */
    public static function headingOrderIsValid(array $levels): bool
    {
        if ($levels === [] || $levels[0] !== 1) {
            return false;
        }

        if (count(array_filter($levels, static fn (int $level): bool => $level === 1)) !== 1) {
            return false;
        }

        $previous = 1;

        foreach ($levels as $level) {
            if ($level > $previous + 1) {
                return false;
            }

            $previous = $level;
        }

        return true;
    }

    /**
     * The landmarks every template must contain.
     *
     * @return list<string>
     */
    public static function requiredLandmarks(): array
    {
        return ['banner', 'main', 'contentinfo'];
    }

    /**
     * @param list<string> $present
     *
     * @return list<string> the landmarks that are missing
     */
    public static function missingLandmarks(array $present): array
    {
        return array_values(array_diff(self::requiredLandmarks(), $present));
    }

    /**
     * Whether an image's alt text is acceptable.
     *
     * A decorative image takes an empty alt and that is correct, not a gap — so the rule is
     * "decorative means empty, meaningful means non-empty", not "everything needs alt text".
     * Enforcing the latter is what produces `alt="image"` on ten thousand sites.
     */
    public static function altTextIsValid(string $alt, bool $isDecorative): bool
    {
        return $isDecorative ? trim($alt) === '' : trim($alt) !== '';
    }
}
