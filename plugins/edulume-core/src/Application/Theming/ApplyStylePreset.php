<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Theming;

use Edulume\Core\Domain\Theming\StylePreset;
use Edulume\Core\Domain\Theming\ThemeSettings;

/**
 * Applies a style preset, preview first.
 *
 * `preview()` computes what the preset would do and changes nothing, so the admin can render
 * the result before the site owner commits to it. `apply()` does the same computation and
 * additionally hands back a snapshot of the state it replaced, so the whole thing can be
 * undone in the session. Neither call writes anything: persistence belongs to the caller.
 */
final class ApplyStylePreset
{
    public function preview(ThemeSettings $current, StylePreset $preset): ThemeSettings
    {
        return $preset->applyTo($current);
    }

    public function apply(ThemeSettings $current, StylePreset $preset): PresetApplication
    {
        return PresetApplication::of($preset, $current, $this->preview($current, $preset));
    }
}
