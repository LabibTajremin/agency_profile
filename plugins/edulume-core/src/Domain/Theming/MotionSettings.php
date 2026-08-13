<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Support\Guard;

/**
 * A preset plus granular per-effect overrides and an optional custom easing curve.
 *
 * Switching motion off globally zeroes every derived value and empties the active-effects
 * list, so no stored duration can leak back into the page through a token that was not
 * zeroed. That list is also what decides which JavaScript modules load at all.
 */
final class MotionSettings
{
    /**
     * @param array<string, bool> $effectOverrides keyed by effect value
     */
    private function __construct(
        public readonly bool $enabled,
        public readonly MotionPreset $preset,
        private readonly array $effectOverrides,
        public readonly ?CubicBezier $customEasing,
    ) {
    }

    public static function defaults(): self
    {
        return new self(true, MotionPreset::Refined, [], null);
    }

    public static function off(): self
    {
        return new self(false, MotionPreset::None, [], null);
    }

    /**
     * @param array<string, bool> $effectOverrides keyed by effect value; unknown keys are dropped
     */
    public static function of(
        MotionPreset $preset,
        array $effectOverrides = [],
        ?CubicBezier $customEasing = null,
        bool $enabled = true
    ): self {
        return new self($enabled, $preset, self::coerceOverrides($effectOverrides), $customEasing);
    }

    /**
     * A custom curve that would be rejected is dropped rather than emitted, because a browser
     * discards the whole declaration and the site silently loses its transitions.
     *
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        return self::of(
            Guard::toEnum(MotionPreset::class, $stored['preset'] ?? null, MotionPreset::Refined),
            Guard::toBoolMap($stored['effectOverrides'] ?? null),
            self::coerceCustomEasing($stored['customEasing'] ?? null),
            Guard::toBool($stored['enabled'] ?? null, true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'preset' => $this->preset->value,
            'effectOverrides' => $this->effectOverrides,
            'customEasing' => $this->customEasing === null ? null : [
                $this->customEasing->firstControlPointX,
                $this->customEasing->firstControlPointY,
                $this->customEasing->secondControlPointX,
                $this->customEasing->secondControlPointY,
            ],
        ];
    }

    private static function coerceCustomEasing(mixed $stored): ?CubicBezier
    {
        $points = Guard::toArray($stored);

        if (count($points) !== 4) {
            return null;
        }

        $firstX = Guard::toFloat($points[0] ?? null);
        $firstY = Guard::toFloat($points[1] ?? null);
        $secondX = Guard::toFloat($points[2] ?? null);
        $secondY = Guard::toFloat($points[3] ?? null);

        if (!CubicBezier::isValid($firstX, $firstY, $secondX, $secondY)) {
            return null;
        }

        return CubicBezier::of($firstX, $firstY, $secondX, $secondY);
    }

    public function timing(): MotionTiming
    {
        if (!$this->enabled) {
            return MotionTiming::still();
        }

        return $this->preset->timing();
    }

    public function easing(): CubicBezier
    {
        if (!$this->enabled) {
            return MotionEasing::Linear->toCubicBezier();
        }

        return $this->customEasing ?? $this->preset->timing()->easing->toCubicBezier();
    }

    public function isEffectActive(MotionEffect $effect): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $override = $this->effectOverrides[$effect->value] ?? null;

        if ($override !== null) {
            return $override;
        }

        return in_array($effect, $this->preset->defaultEffects(), true);
    }

    public function isEffectOverridden(MotionEffect $effect): bool
    {
        return array_key_exists($effect->value, $this->effectOverrides);
    }

    /**
     * @return list<MotionEffect>
     */
    public function activeEffects(): array
    {
        $active = [];

        foreach (MotionEffect::cases() as $effect) {
            if ($this->isEffectActive($effect)) {
                $active[] = $effect;
            }
        }

        return $active;
    }

    /**
     * The modules to load, derived from the active effects and nothing else.
     *
     * @return list<MotionModule>
     */
    public function modulesToLoad(): array
    {
        $modules = [];

        foreach ($this->activeEffects() as $effect) {
            $module = $effect->module();
            $modules[$module->value] = $module;
        }

        return array_values($modules);
    }

    public function loadsHeavyModule(): bool
    {
        foreach ($this->modulesToLoad() as $module) {
            if ($module->isHeavy()) {
                return true;
            }
        }

        return false;
    }

    public function withEffect(MotionEffect $effect, bool $active): self
    {
        $overrides = $this->effectOverrides;
        $overrides[$effect->value] = $active;

        return new self($this->enabled, $this->preset, $overrides, $this->customEasing);
    }

    /**
     * Restores the preset's own choice for one effect by removing the override.
     */
    public function withoutEffectOverride(MotionEffect $effect): self
    {
        $overrides = $this->effectOverrides;
        unset($overrides[$effect->value]);

        return new self($this->enabled, $this->preset, $overrides, $this->customEasing);
    }

    public function withPreset(MotionPreset $preset): self
    {
        return new self($this->enabled, $preset, $this->effectOverrides, $this->customEasing);
    }

    public function disabled(): self
    {
        return new self(false, $this->preset, $this->effectOverrides, $this->customEasing);
    }

    /**
     * @param array<string, bool> $effectOverrides
     *
     * @return array<string, bool>
     */
    private static function coerceOverrides(array $effectOverrides): array
    {
        $coerced = [];

        foreach (MotionEffect::cases() as $effect) {
            if (array_key_exists($effect->value, $effectOverrides)) {
                $coerced[$effect->value] = $effectOverrides[$effect->value];
            }
        }

        return $coerced;
    }
}
