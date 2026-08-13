<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Support\Guard;

/**
 * A pairing, sparse per-role overrides, the type scale, and the subsets to ship.
 *
 * Overrides are stored as absence: a role with no entry follows the pairing. That is what
 * lets a later pairing change propagate into every role the user never touched.
 */
final class TypographySettings
{
    /**
     * @param array<string, string> $roleOverrides keyed by role value
     * @param non-empty-list<FontSubset> $subsets
     */
    private function __construct(
        public readonly string $pairingSlug,
        private readonly array $roleOverrides,
        public readonly TypeScale $scale,
        private readonly array $subsets,
        public readonly bool $usesGoogleFontsCdn,
    ) {
    }

    public static function defaults(): self
    {
        return new self(
            FontPairingLibrary::DEFAULT_PAIRING_SLUG,
            [],
            TypeScale::of(TypeScale::DEFAULT_RATIO),
            [FontSubset::Latin, FontSubset::LatinExtended],
            false,
        );
    }

    /**
     * @param array<string, string> $roleOverrides keyed by role value; unknown roles and
     *                                             unknown family slugs are dropped
     * @param list<FontSubset> $subsets
     */
    public static function of(
        string $pairingSlug,
        array $roleOverrides,
        TypeScale $scale,
        array $subsets,
        bool $usesGoogleFontsCdn = false
    ): self {
        return new self(
            FontPairingLibrary::has($pairingSlug) ? $pairingSlug : FontPairingLibrary::DEFAULT_PAIRING_SLUG,
            self::coerceOverrides($roleOverrides),
            $scale,
            self::coerceSubsets($subsets),
            $usesGoogleFontsCdn,
        );
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $defaults = self::defaults();
        $scale = Guard::toArray($stored['scale'] ?? null);
        $subsets = Guard::toEnumList(FontSubset::class, $stored['subsets'] ?? null);

        return self::of(
            Guard::toString($stored['pairingSlug'] ?? null, $defaults->pairingSlug),
            Guard::toStringMap($stored['roleOverrides'] ?? null),
            TypeScale::of(
                Guard::toFloat($scale['ratio'] ?? null, TypeScale::DEFAULT_RATIO),
                Guard::toFloat($scale['baseSizeRem'] ?? null, TypeScale::DEFAULT_BASE_REM),
            ),
            $subsets === [] ? $defaults->subsets() : $subsets,
            Guard::toBool($stored['usesGoogleFontsCdn'] ?? null, $defaults->usesGoogleFontsCdn),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pairingSlug' => $this->pairingSlug,
            'roleOverrides' => $this->roleOverrides,
            'scale' => [
                'ratio' => $this->scale->ratio,
                'baseSizeRem' => $this->scale->baseSizeRem,
            ],
            'subsets' => array_map(static fn (FontSubset $subset): string => $subset->value, $this->subsets),
            'usesGoogleFontsCdn' => $this->usesGoogleFontsCdn,
        ];
    }

    public function pairing(): FontPairing
    {
        return FontPairingLibrary::get($this->pairingSlug);
    }

    public function familyFor(FontRole $role): FontFamily
    {
        $override = $this->roleOverrides[$role->value] ?? null;

        if ($override !== null) {
            return FontLibrary::get($override);
        }

        return $this->pairing()->familyFor($role);
    }

    public function isOverridden(FontRole $role): bool
    {
        return array_key_exists($role->value, $this->roleOverrides);
    }

    /**
     * @return list<FontRole>
     */
    public function overriddenRoles(): array
    {
        $roles = [];

        foreach (FontRole::cases() as $role) {
            if ($this->isOverridden($role)) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    /**
     * Only the families actually resolved by a role need enqueueing. Shipping the whole
     * library because it is bundled is how a font budget turns into half a megabyte.
     *
     * @return list<FontFamily>
     */
    public function familiesInUse(): array
    {
        $families = [];

        foreach (FontRole::cases() as $role) {
            $family = $this->familyFor($role);

            if (!array_key_exists($family->slug, $families)) {
                $families[$family->slug] = $family;
            }
        }

        return array_values($families);
    }

    /**
     * @return list<FontSubset>
     */
    public function subsets(): array
    {
        return $this->subsets;
    }

    /**
     * The subsets each family in use can actually serve.
     *
     * @return array<string, list<FontSubset>>
     */
    public function subsetsToShip(): array
    {
        $shipped = [];

        foreach ($this->familiesInUse() as $family) {
            $shipped[$family->slug] = $family->subsetsWithin(...$this->subsets);
        }

        return $shipped;
    }

    public function withRoleOverride(FontRole $role, string $familySlug): self
    {
        $overrides = $this->roleOverrides;
        $overrides[$role->value] = $familySlug;

        return new self(
            $this->pairingSlug,
            self::coerceOverrides($overrides),
            $this->scale,
            $this->subsets,
            $this->usesGoogleFontsCdn,
        );
    }

    /**
     * Restores inheritance for one role by removing the override, never by copying the
     * pairing's current value into it.
     */
    public function withoutRoleOverride(FontRole $role): self
    {
        $overrides = $this->roleOverrides;
        unset($overrides[$role->value]);

        return new self($this->pairingSlug, $overrides, $this->scale, $this->subsets, $this->usesGoogleFontsCdn);
    }

    /**
     * @param array<string, string> $roleOverrides
     *
     * @return array<string, string>
     */
    private static function coerceOverrides(array $roleOverrides): array
    {
        $coerced = [];

        foreach (FontRole::cases() as $role) {
            $slug = $roleOverrides[$role->value] ?? null;

            if ($slug !== null && FontLibrary::has($slug)) {
                $coerced[$role->value] = $slug;
            }
        }

        return $coerced;
    }

    /**
     * @param list<FontSubset> $subsets
     *
     * @return non-empty-list<FontSubset>
     */
    private static function coerceSubsets(array $subsets): array
    {
        $unique = [];

        foreach ($subsets as $subset) {
            $unique[$subset->value] = $subset;
        }

        if ($unique === []) {
            return [FontSubset::Latin];
        }

        return array_values($unique);
    }
}
