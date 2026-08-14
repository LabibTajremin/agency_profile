<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Support\Guard;

/**
 * A sparse set of per-section overrides. Every key is optional; an absent key means inherit.
 *
 * Inheritance is stored as **absence**, never as a copy of the global value. That is what
 * makes a later global change propagate into untouched sections, and what lets the admin show
 * an accurate modified-indicator dot rather than one that lights up for values the user never
 * touched.
 */
final class SectionOverride
{
    /**
     * @param array<string, bool|float|int|string> $values keyed by override key
     */
    private function __construct(private readonly array $values)
    {
    }

    public static function inheritEverything(): self
    {
        return new self([]);
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $override = self::inheritEverything();

        foreach (SectionOverrideKey::cases() as $key) {
            if (array_key_exists($key->value, $stored) && $stored[$key->value] !== null) {
                $override = $override->with($key, $stored[$key->value]);
            }
        }

        return $override;
    }

    /**
     * @return array<string, bool|float|int|string>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    public function isInheritingEverything(): bool
    {
        return $this->values === [];
    }

    public function has(SectionOverrideKey $key): bool
    {
        return array_key_exists($key->value, $this->values);
    }

    /**
     * Exactly the keys the user changed, in declaration order.
     *
     * @return list<SectionOverrideKey>
     */
    public function divergedKeys(): array
    {
        $diverged = [];

        foreach (SectionOverrideKey::cases() as $key) {
            if ($this->has($key)) {
                $diverged[] = $key;
            }
        }

        return $diverged;
    }

    /**
     * Values are coerced on the way in, so a stored override can never carry a type the
     * resolver would have to defend against later.
     */
    public function with(SectionOverrideKey $key, mixed $value): self
    {
        $values = $this->values;
        $values[$key->value] = self::coerce($key, $value);

        return new self($values);
    }

    /**
     * Restores inheritance for one key alone by removing it.
     */
    public function without(SectionOverrideKey $key): self
    {
        $values = $this->values;
        unset($values[$key->value]);

        return new self($values);
    }

    public function stringOr(SectionOverrideKey $key, string $inherited): string
    {
        return is_string($this->values[$key->value] ?? null) ? (string) $this->values[$key->value] : $inherited;
    }

    public function floatOr(SectionOverrideKey $key, float $inherited): float
    {
        return $this->has($key) ? Guard::toFloat($this->values[$key->value], $inherited) : $inherited;
    }

    public function intOr(SectionOverrideKey $key, int $inherited): int
    {
        return $this->has($key) ? Guard::toInt($this->values[$key->value], $inherited) : $inherited;
    }

    public function boolOr(SectionOverrideKey $key, bool $inherited): bool
    {
        return $this->has($key) ? Guard::toBool($this->values[$key->value], $inherited) : $inherited;
    }

    /**
     * @template TEnum of \BackedEnum
     *
     * @param class-string<TEnum> $enumClass
     * @param TEnum $inherited
     *
     * @return TEnum
     */
    public function enumOr(SectionOverrideKey $key, string $enumClass, \BackedEnum $inherited): \BackedEnum
    {
        return $this->has($key) ? Guard::toEnum($enumClass, $this->values[$key->value], $inherited) : $inherited;
    }

    private static function coerce(SectionOverrideKey $key, mixed $value): bool|float|int|string
    {
        return match ($key) {
            SectionOverrideKey::PatternEnabled,
            SectionOverrideKey::MotionEnabled => Guard::toBool($value),

            SectionOverrideKey::PatternLightModeOpacity,
            SectionOverrideKey::PatternDarkModeOpacity => Guard::toFloat($value, 0.0, 0.0, 1.0),

            SectionOverrideKey::PatternScale => Guard::toFloat(
                $value,
                PatternSettings::MINIMUM_SCALE,
                PatternSettings::MINIMUM_SCALE,
                PatternSettings::MAXIMUM_SCALE,
            ),

            SectionOverrideKey::SectionSpacingPixels => Guard::toInt(
                $value,
                0,
                0,
                LayoutSettings::MAXIMUM_SECTION_SPACING_PIXELS,
            ),

            SectionOverrideKey::CornerRadiusPixels => Guard::toInt(
                $value,
                0,
                0,
                LayoutSettings::MAXIMUM_CORNER_RADIUS_PIXELS,
            ),

            default => Guard::toString($value),
        };
    }
}
