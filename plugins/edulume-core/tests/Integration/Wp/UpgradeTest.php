<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Integration\Wp;

use Edulume\Core\Infrastructure\Lead\LeadTableMigrator;
use Edulume\Core\Infrastructure\Wp\Activation;
use WP_UnitTestCase;

/**
 * Uploading a new version over an existing install migrates itself.
 *
 * `register_activation_hook` fires once, on activation, and never again. Every real deployment
 * route — FTP, cPanel File Manager, the WordPress updater, a git pull on the server — replaces
 * the files of an already-active plugin without touching that hook. New code then runs against
 * an old schema, and nobody finds out until something fails to save.
 *
 * These tests are the reason a site owner does not have to be told "remember to deactivate and
 * reactivate after uploading", an instruction that is forgotten precisely when it matters.
 */
final class UpgradeTest extends WP_UnitTestCase
{
    private const OLD_VERSION = '0.0.9-test';
    private const NEW_VERSION = '0.1.0-test';

    public function set_up(): void
    {
        parent::set_up();

        delete_option(Activation::VERSION_OPTION);
    }

    /** @test */
    public function an_install_that_has_never_activated_reports_an_upgrade_as_pending(): void
    {
        $this->assertTrue(Activation::isUpgradePending(self::NEW_VERSION));
    }

    /** @test */
    public function an_install_left_on_an_older_version_reports_an_upgrade_as_pending(): void
    {
        update_option(Activation::VERSION_OPTION, self::OLD_VERSION, true);

        $this->assertTrue(Activation::isUpgradePending(self::NEW_VERSION));
    }

    /** @test */
    public function an_install_already_on_this_version_reports_nothing_pending(): void
    {
        Activation::run(self::NEW_VERSION);

        $this->assertFalse(Activation::isUpgradePending(self::NEW_VERSION));
    }

    /** @test */
    public function running_the_upgrade_records_the_version_that_ran_it(): void
    {
        update_option(Activation::VERSION_OPTION, self::OLD_VERSION, true);

        Activation::run(self::NEW_VERSION);

        $this->assertSame(self::NEW_VERSION, get_option(Activation::VERSION_OPTION));
        $this->assertFalse(Activation::isUpgradePending(self::NEW_VERSION));
    }

    /** @test */
    public function the_lead_tables_exist_after_an_upgrade_from_an_older_version(): void
    {
        update_option(Activation::VERSION_OPTION, self::OLD_VERSION, true);

        Activation::run(self::NEW_VERSION);

        $this->assertTrue((new LeadTableMigrator())->tablesExist());
    }

    /**
     * The check runs on every admin request, so being safe to repeat is not a nicety.
     */
    /** @test */
    public function running_it_twice_changes_nothing_the_second_time(): void
    {
        Activation::run(self::NEW_VERSION);
        $activatedAt = get_option(Activation::ACTIVATED_AT_OPTION);

        Activation::run(self::NEW_VERSION);

        $this->assertSame($activatedAt, get_option(Activation::ACTIVATED_AT_OPTION));
        $this->assertTrue((new LeadTableMigrator())->tablesExist());
    }
}
