<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Support\Guard;

/**
 * Page measurements: content width, gutters, vertical rhythm, corner radius and border
 * weight.
 *
 * Widths are stored in pixels because that is what a site owner types into a width field.
 * They are emitted as rem by the token compiler, so a visitor's larger base font size still
 * scales the layout with it.
 */
final class LayoutSettings
{
    public const MINIMUM_CONTENT_WIDTH_PIXELS = 640;
    public const MAXIMUM_CONTENT_WIDTH_PIXELS = 1600;
    public const MAXIMUM_WIDE_WIDTH_PIXELS = 1920;
    public const MAXIMUM_GUTTER_PIXELS = 96;
    public const MAXIMUM_SECTION_SPACING_PIXELS = 240;
    public const MAXIMUM_CORNER_RADIUS_PIXELS = 48;
    public const MAXIMUM_BORDER_WIDTH_PIXELS = 8;

    private const DEFAULT_CONTENT_WIDTH_PIXELS = 1200;
    private const DEFAULT_WIDE_WIDTH_PIXELS = 1440;
    private const DEFAULT_GUTTER_PIXELS = 24;
    private const DEFAULT_SECTION_SPACING_PIXELS = 96;
    private const DEFAULT_CORNER_RADIUS_PIXELS = 12;
    private const DEFAULT_BORDER_WIDTH_PIXELS = 1;

    private function __construct(
        public readonly int $contentWidthPixels,
        public readonly int $wideWidthPixels,
        public readonly int $gutterPixels,
        public readonly int $sectionSpacingPixels,
        public readonly int $cornerRadiusPixels,
        public readonly int $borderWidthPixels,
        public readonly LayoutDensity $density,
    ) {
    }

    public static function defaults(): self
    {
        return new self(
            self::DEFAULT_CONTENT_WIDTH_PIXELS,
            self::DEFAULT_WIDE_WIDTH_PIXELS,
            self::DEFAULT_GUTTER_PIXELS,
            self::DEFAULT_SECTION_SPACING_PIXELS,
            self::DEFAULT_CORNER_RADIUS_PIXELS,
            self::DEFAULT_BORDER_WIDTH_PIXELS,
            LayoutDensity::Comfortable,
        );
    }

    public static function of(
        int $contentWidthPixels,
        int $wideWidthPixels,
        int $gutterPixels,
        int $sectionSpacingPixels,
        int $cornerRadiusPixels,
        int $borderWidthPixels,
        LayoutDensity $density
    ): self {
        $content = min(max($contentWidthPixels, self::MINIMUM_CONTENT_WIDTH_PIXELS), self::MAXIMUM_CONTENT_WIDTH_PIXELS);

        return new self(
            $content,
            min(max($wideWidthPixels, $content), self::MAXIMUM_WIDE_WIDTH_PIXELS),
            min(max($gutterPixels, 0), self::MAXIMUM_GUTTER_PIXELS),
            min(max($sectionSpacingPixels, 0), self::MAXIMUM_SECTION_SPACING_PIXELS),
            min(max($cornerRadiusPixels, 0), self::MAXIMUM_CORNER_RADIUS_PIXELS),
            min(max($borderWidthPixels, 0), self::MAXIMUM_BORDER_WIDTH_PIXELS),
            $density,
        );
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $defaults = self::defaults();

        return self::of(
            Guard::toInt($stored['contentWidthPixels'] ?? null, $defaults->contentWidthPixels),
            Guard::toInt($stored['wideWidthPixels'] ?? null, $defaults->wideWidthPixels),
            Guard::toInt($stored['gutterPixels'] ?? null, $defaults->gutterPixels),
            Guard::toInt($stored['sectionSpacingPixels'] ?? null, $defaults->sectionSpacingPixels),
            Guard::toInt($stored['cornerRadiusPixels'] ?? null, $defaults->cornerRadiusPixels),
            Guard::toInt($stored['borderWidthPixels'] ?? null, $defaults->borderWidthPixels),
            Guard::toEnum(LayoutDensity::class, $stored['density'] ?? null, $defaults->density),
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'contentWidthPixels' => $this->contentWidthPixels,
            'wideWidthPixels' => $this->wideWidthPixels,
            'gutterPixels' => $this->gutterPixels,
            'sectionSpacingPixels' => $this->sectionSpacingPixels,
            'cornerRadiusPixels' => $this->cornerRadiusPixels,
            'borderWidthPixels' => $this->borderWidthPixels,
            'density' => $this->density->value,
        ];
    }
}
