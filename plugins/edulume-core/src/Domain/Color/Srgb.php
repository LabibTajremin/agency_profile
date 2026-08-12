<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Color;

/**
 * A gamma-encoded sRGB colour, stored as three components in the closed range 0–1.
 *
 * Components are kept as floats rather than 0–255 integers so that colour-space
 * conversions do not quantise on every hop; `toHex()` is the only place rounding happens.
 */
final class Srgb
{
    public const CHANNEL_MAXIMUM = 255;

    private const SHORT_HEX_LENGTH = 3;
    private const FULL_HEX_LENGTH = 6;

    private const TRANSFER_LINEAR_THRESHOLD = 0.04045;
    private const TRANSFER_LINEAR_SLOPE = 12.92;
    private const TRANSFER_ENCODED_THRESHOLD = 0.0031308;
    private const TRANSFER_OFFSET = 0.055;
    private const TRANSFER_SCALE = 1.055;
    private const TRANSFER_EXPONENT = 2.4;

    private const LUMINANCE_WEIGHT_RED = 0.2126;
    private const LUMINANCE_WEIGHT_GREEN = 0.7152;
    private const LUMINANCE_WEIGHT_BLUE = 0.0722;

    private function __construct(
        public readonly float $red,
        public readonly float $green,
        public readonly float $blue,
    ) {
    }

    /**
     * @throws InvalidColorException when a component falls outside 0–1
     */
    public static function fromComponents(float $red, float $green, float $blue): self
    {
        self::guardComponent('red', $red);
        self::guardComponent('green', $green);
        self::guardComponent('blue', $blue);

        return new self($red, $green, $blue);
    }

    public static function fromClampedComponents(float $red, float $green, float $blue): self
    {
        return new self(self::clamp($red), self::clamp($green), self::clamp($blue));
    }

    public static function fromChannels(int $red, int $green, int $blue): self
    {
        return new self(
            self::clamp($red / self::CHANNEL_MAXIMUM),
            self::clamp($green / self::CHANNEL_MAXIMUM),
            self::clamp($blue / self::CHANNEL_MAXIMUM),
        );
    }

    /**
     * Accepts `#rgb`, `#rrggbb`, and either form without the leading hash.
     *
     * @throws InvalidColorException when the string is not a hex colour
     */
    public static function fromHex(string $hex): self
    {
        $digits = strtolower(ltrim(trim($hex), '#'));

        if (strlen($digits) === self::SHORT_HEX_LENGTH) {
            $digits = $digits[0] . $digits[0] . $digits[1] . $digits[1] . $digits[2] . $digits[2];
        }

        if (strlen($digits) !== self::FULL_HEX_LENGTH || preg_match('/^[0-9a-f]{6}$/', $digits) !== 1) {
            throw InvalidColorException::forHex($hex);
        }

        return self::fromChannels(
            (int) hexdec(substr($digits, 0, 2)),
            (int) hexdec(substr($digits, 2, 2)),
            (int) hexdec(substr($digits, 4, 2)),
        );
    }

    public function toHex(): string
    {
        return sprintf('#%02x%02x%02x', $this->redChannel(), $this->greenChannel(), $this->blueChannel());
    }

    public function redChannel(): int
    {
        return self::toChannel($this->red);
    }

    public function greenChannel(): int
    {
        return self::toChannel($this->green);
    }

    public function blueChannel(): int
    {
        return self::toChannel($this->blue);
    }

    /**
     * WCAG 2.x relative luminance, computed on linear-light components.
     */
    public function relativeLuminance(): float
    {
        return self::LUMINANCE_WEIGHT_RED * self::toLinear($this->red)
            + self::LUMINANCE_WEIGHT_GREEN * self::toLinear($this->green)
            + self::LUMINANCE_WEIGHT_BLUE * self::toLinear($this->blue);
    }

    /**
     * @return array{float, float, float}
     */
    public function toLinearComponents(): array
    {
        return [self::toLinear($this->red), self::toLinear($this->green), self::toLinear($this->blue)];
    }

    public function equals(self $other): bool
    {
        return $this->redChannel() === $other->redChannel()
            && $this->greenChannel() === $other->greenChannel()
            && $this->blueChannel() === $other->blueChannel();
    }

    public static function toLinear(float $encoded): float
    {
        if ($encoded <= self::TRANSFER_LINEAR_THRESHOLD) {
            return $encoded / self::TRANSFER_LINEAR_SLOPE;
        }

        return (($encoded + self::TRANSFER_OFFSET) / self::TRANSFER_SCALE) ** self::TRANSFER_EXPONENT;
    }

    public static function fromLinear(float $linear): float
    {
        if ($linear <= self::TRANSFER_ENCODED_THRESHOLD) {
            return $linear * self::TRANSFER_LINEAR_SLOPE;
        }

        return self::TRANSFER_SCALE * $linear ** (1 / self::TRANSFER_EXPONENT) - self::TRANSFER_OFFSET;
    }

    private static function toChannel(float $component): int
    {
        return (int) round($component * self::CHANNEL_MAXIMUM);
    }

    private static function clamp(float $component): float
    {
        return max(0.0, min(1.0, $component));
    }

    private static function guardComponent(string $name, float $value): void
    {
        if ($value < 0.0 || $value > 1.0) {
            throw InvalidColorException::forChannel($name, $value);
        }
    }
}
