<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Support\Guard;

/**
 * A complete look, stored as a partial settings blob rather than a full one.
 *
 * Partial matters: a preset that carried every key would silently reset the things it has no
 * opinion about — the site's chosen subsets, the Google-CDN toggle, a per-role font override
 * someone set deliberately. Only the keys a preset actually declares are replaced.
 */
final class StylePreset
{
    private function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $description,
        public readonly bool $isBuiltIn,
        /** @var array<string, mixed> */
        private readonly array $values,
    ) {
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function of(
        string $slug,
        string $name,
        string $description,
        array $values,
        bool $isBuiltIn = false
    ): self {
        return new self($slug, $name, $description, $isBuiltIn, $values);
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        /** @var array<string, mixed> $values */
        $values = Guard::toArray($stored['values'] ?? null);

        return new self(
            Guard::toString($stored['slug'] ?? null),
            Guard::toString($stored['name'] ?? null),
            Guard::toString($stored['description'] ?? null),
            Guard::toBool($stored['isBuiltIn'] ?? null),
            $values,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'isBuiltIn' => $this->isBuiltIn,
            'values' => $this->values,
        ];
    }

    /**
     * Export encodes with JSON_PRESERVE_ZERO_FRACTION. Without it a stored 1.0 comes back as
     * an integer 1, and a re-imported preset is no longer byte-identical to the one exported.
     */
    public function toJson(): string
    {
        $encoded = json_encode($this->toArray(), JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * Coerces rather than throws, like every other import path: a truncated or hand-edited
     * export file yields an empty preset, not a fatal error in the admin.
     */
    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);

        return self::fromArray(is_array($decoded) ? $decoded : []);
    }

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return $this->values;
    }

    public function applyTo(ThemeSettings $current): ThemeSettings
    {
        return ThemeSettings::fromArray(self::merge($current->toArray(), $this->values));
    }

    /**
     * Captures the current settings as a preset the user can name and re-apply later.
     */
    public static function capturedFrom(
        string $slug,
        string $name,
        string $description,
        ThemeSettings $settings
    ): self {
        return new self($slug, $name, $description, false, $settings->toArray());
    }

    /**
     * A deep merge that replaces lists wholesale. Merging a list element by element would
     * leave a longer stored list poking out of the end of a shorter preset one.
     *
     * @param array<array-key, mixed> $base
     * @param array<array-key, mixed> $overlay
     *
     * @return array<array-key, mixed>
     */
    private static function merge(array $base, array $overlay): array
    {
        foreach ($overlay as $key => $value) {
            $existing = $base[$key] ?? null;

            $base[$key] = is_array($value) && is_array($existing) && !array_is_list($value)
                ? self::merge($existing, $value)
                : $value;
        }

        return $base;
    }
}
