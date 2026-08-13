<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Theming;

use Edulume\Core\Domain\Theming\ResetScope;
use Edulume\Core\Domain\Theming\ThemeSettings;

/**
 * Puts part of a configuration back to its defaults, and nothing else.
 */
final class ResetSettings
{
    public function __invoke(ThemeSettings $current, ResetScope $scope): ThemeSettings
    {
        $stored = $current->toArray();
        $defaults = ThemeSettings::defaults()->toArray();

        foreach ($scope->keys() as $key) {
            $stored[$key] = $defaults[$key];
        }

        return ThemeSettings::fromArray($stored);
    }
}
