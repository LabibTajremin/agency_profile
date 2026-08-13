<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Wp;

/**
 * The capabilities the product adds, and who gets them on activation.
 *
 * Capabilities are named rather than reusing `manage_options` so a Site Manager can be given
 * the configurator without also being handed the whole of WordPress settings.
 */
final class Capabilities
{
    public const MANAGE_THEME = 'edulume_manage_theme';
    public const MANAGE_LEADS = 'edulume_manage_leads';
    public const EXPORT_LEADS = 'edulume_export_leads';
    public const MANAGE_CONTENT = 'edulume_manage_content';
    public const IMPORT_DEMO_CONTENT = 'edulume_import_demo_content';

    private const ADMINISTRATOR_ROLE = 'administrator';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::MANAGE_THEME,
            self::MANAGE_LEADS,
            self::EXPORT_LEADS,
            self::MANAGE_CONTENT,
            self::IMPORT_DEMO_CONTENT,
        ];
    }

    public static function grantToAdministrator(): void
    {
        $role = get_role(self::ADMINISTRATOR_ROLE);

        if ($role === null) {
            return;
        }

        foreach (self::all() as $capability) {
            $role->add_cap($capability);
        }
    }

    public static function revokeFromAllRoles(): void
    {
        foreach (wp_roles()->role_objects as $role) {
            foreach (self::all() as $capability) {
                $role->remove_cap($capability);
            }
        }
    }
}
