<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Support;

use BackedEnum;
use ReflectionEnum;

/**
 * The one place untrusted input is normalised.
 *
 * Settings arrive from the REST API, from an imported JSON file, and from rows written by an
 * older schema. Every helper here coerces rather than throws: one bad key must never
 * white-screen a live site, and a site owner who pasted a broken export deserves their
 * defaults back, not a stack trace.
 */
final class Guard
{
    public static function toString(mixed $value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $default;
    }

    public static function toInt(mixed $value, int $default = 0, ?int $minimum = null, ?int $maximum = null): int
    {
        $coerced = match (true) {
            is_int($value) => $value,
            is_float($value) && is_finite($value) => (int) round($value),
            is_bool($value) => (int) $value,
            is_string($value) && is_numeric(trim($value)) => (int) round((float) trim($value)),
            default => $default,
        };

        return self::clampInt($coerced, $minimum, $maximum);
    }

    public static function toFloat(
        mixed $value,
        float $default = 0.0,
        ?float $minimum = null,
        ?float $maximum = null
    ): float {
        $coerced = match (true) {
            is_float($value) && is_finite($value) => $value,
            is_int($value) => (float) $value,
            is_bool($value) => (float) $value,
            is_string($value) && is_numeric(trim($value)) => (float) trim($value),
            default => $default,
        };

        return self::clampFloat($coerced, $minimum, $maximum);
    }

    public static function toBool(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off', '' => false,
                default => $default,
            };
        }

        return $default;
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function toArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @return list<string>
     */
    public static function toStringList(mixed $value): array
    {
        $list = [];

        foreach (self::toArray($value) as $item) {
            if (is_string($item)) {
                $list[] = $item;
            }
        }

        return $list;
    }

    /**
     * @template TEnum of BackedEnum
     *
     * @param class-string<TEnum> $enumClass
     * @param TEnum $default
     *
     * @return TEnum
     */
    public static function toEnum(string $enumClass, mixed $value, BackedEnum $default): BackedEnum
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        return self::tryEnumFrom($enumClass, $value) ?? $default;
    }

    /**
     * @template TEnum of BackedEnum
     *
     * @param class-string<TEnum> $enumClass
     *
     * @return list<TEnum>
     */
    public static function toEnumList(string $enumClass, mixed $value): array
    {
        $list = [];

        foreach (self::toArray($value) as $item) {
            if ($item instanceof $enumClass) {
                $list[] = $item;
                continue;
            }

            $case = self::tryEnumFrom($enumClass, $item);

            if ($case !== null) {
                $list[] = $case;
            }
        }

        return $list;
    }

    /**
     * @return array<string, bool>
     */
    public static function toBoolMap(mixed $value): array
    {
        $map = [];

        foreach (self::toArray($value) as $key => $item) {
            if (is_string($key)) {
                $map[$key] = self::toBool($item);
            }
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    public static function toStringMap(mixed $value): array
    {
        $map = [];

        foreach (self::toArray($value) as $key => $item) {
            if (is_string($key) && is_string($item)) {
                $map[$key] = $item;
            }
        }

        return $map;
    }

    /**
     * A backed enum's `tryFrom()` throws rather than returning null when handed the wrong
     * scalar type, so the backing type is checked first.
     *
     * @template TEnum of BackedEnum
     *
     * @param class-string<TEnum> $enumClass
     *
     * @return TEnum|null
     */
    private static function tryEnumFrom(string $enumClass, mixed $value): ?BackedEnum
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $backingType = (new ReflectionEnum($enumClass))->getBackingType();

        if ($backingType === null || $backingType->getName() !== get_debug_type($value)) {
            return null;
        }

        return $enumClass::tryFrom($value);
    }

    private static function clampInt(int $value, ?int $minimum, ?int $maximum): int
    {
        if ($minimum !== null) {
            $value = max($minimum, $value);
        }

        return $maximum === null ? $value : min($maximum, $value);
    }

    private static function clampFloat(float $value, ?float $minimum, ?float $maximum): float
    {
        if ($minimum !== null) {
            $value = max($minimum, $value);
        }

        return $maximum === null ? $value : min($maximum, $value);
    }
}
