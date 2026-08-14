<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

use Edulume\Core\Infrastructure\Wp\Capabilities;

/**
 * The four roles the product adds, and exactly what each one may do.
 *
 * The matrix is stated as denials as much as grants. "A Content Editor cannot change the
 * colour scheme" is the property that matters, and it is easy to break by adding a capability
 * to the wrong row months later.
 */
enum StaffRole: string
{
    case SiteManager = 'edulume_site_manager';
    case ContentEditor = 'edulume_content_editor';
    case Counsellor = 'edulume_counsellor';
    case BranchManager = 'edulume_branch_manager';

    public function label(): string
    {
        return match ($this) {
            self::SiteManager => 'Site Manager',
            self::ContentEditor => 'Content Editor',
            self::Counsellor => 'Counsellor',
            self::BranchManager => 'Branch Manager',
        };
    }

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::SiteManager => [
                'read',
                'edit_posts',
                'publish_posts',
                'upload_files',
                Capabilities::MANAGE_THEME,
                Capabilities::MANAGE_CONTENT,
                Capabilities::MANAGE_LEADS,
                Capabilities::EXPORT_LEADS,
                Capabilities::IMPORT_DEMO_CONTENT,
            ],
            self::ContentEditor => [
                'read',
                'edit_posts',
                'publish_posts',
                'upload_files',
                Capabilities::MANAGE_CONTENT,
            ],
            self::Counsellor => [
                'read',
                Capabilities::MANAGE_LEADS,
            ],
            self::BranchManager => [
                'read',
                Capabilities::MANAGE_LEADS,
                Capabilities::EXPORT_LEADS,
            ],
        };
    }

    public function can(string $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    /**
     * Whether this role sees the whole inbox or only part of it.
     */
    public function leadScope(): LeadScope
    {
        return match ($this) {
            self::SiteManager => LeadScope::Everything,
            self::ContentEditor => LeadScope::Nothing,
            self::Counsellor => LeadScope::OwnAssignments,
            self::BranchManager => LeadScope::OwnBranch,
        };
    }
}
