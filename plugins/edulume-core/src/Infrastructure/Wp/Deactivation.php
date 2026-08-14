<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Wp;

/**
 * Runs when the plugin is deactivated.
 *
 * Nothing is deleted here. A deactivation is usually a diagnosis step, not a decision to
 * throw away a configuration; destroying data belongs in uninstall, behind an explicit
 * delete.
 */
final class Deactivation
{
    public static function run(): void
    {
        flush_rewrite_rules();
    }
}
