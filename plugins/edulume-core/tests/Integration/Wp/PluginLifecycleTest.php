<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Integration\Wp;

use Edulume\Core\Application\Port\SettingsRepository;
use Edulume\Core\Application\Port\StylesheetWriter;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Infrastructure\Settings\OptionSettingsRepository;
use Edulume\Core\Infrastructure\Theming\UploadsStylesheetWriter;
use Edulume\Core\Infrastructure\Wp\Activation;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;
use Edulume\Core\Infrastructure\Wp\Deactivation;
use PHPUnit\Framework\Attributes\Test;
use WP_UnitTestCase;

/**
 * Proves the plugin activates and deactivates cleanly on a real install, and that a
 * deactivation loses nothing.
 */
final class PluginLifecycleTest extends WP_UnitTestCase
{
    private const TEST_VERSION = '0.1.0-test';

    public function set_up(): void
    {
        parent::set_up();

        Capabilities::revokeFromAllRoles();
        delete_option(Activation::VERSION_OPTION);
        delete_option(Activation::ACTIVATED_AT_OPTION);
    }

    #[Test]
    public function it_grants_every_capability_to_the_administrator_on_activation(): void
    {
        Activation::run(self::TEST_VERSION);

        $administrator = get_role('administrator');

        $this->assertNotNull($administrator);

        foreach (Capabilities::all() as $capability) {
            $this->assertTrue($administrator->has_cap($capability), sprintf('%s was not granted.', $capability));
        }
    }

    #[Test]
    public function it_never_grants_a_capability_to_a_subscriber(): void
    {
        Activation::run(self::TEST_VERSION);

        $subscriber = get_role('subscriber');

        $this->assertNotNull($subscriber);

        foreach (Capabilities::all() as $capability) {
            $this->assertFalse($subscriber->has_cap($capability), sprintf('%s leaked to subscribers.', $capability));
        }
    }

    #[Test]
    public function it_records_the_version_it_activated_at(): void
    {
        Activation::run(self::TEST_VERSION);

        $this->assertSame(self::TEST_VERSION, get_option(Activation::VERSION_OPTION));
        $this->assertNotSame('', (string) get_option(Activation::ACTIVATED_AT_OPTION));
    }

    #[Test]
    public function it_keeps_the_original_activation_timestamp_across_a_reactivation(): void
    {
        Activation::run(self::TEST_VERSION);
        $first = get_option(Activation::ACTIVATED_AT_OPTION);

        Activation::run('0.2.0-test');

        $this->assertSame($first, get_option(Activation::ACTIVATED_AT_OPTION));
        $this->assertSame('0.2.0-test', get_option(Activation::VERSION_OPTION));
    }

    #[Test]
    public function it_writes_no_settings_on_activation(): void
    {
        delete_option(OptionSettingsRepository::SETTINGS_OPTION);

        Activation::run(self::TEST_VERSION);

        $this->assertFalse(Activation::hasStoredSettings());
    }

    #[Test]
    public function it_loses_no_configuration_when_deactivated(): void
    {
        $repository = new OptionSettingsRepository(new \Edulume\Core\Domain\Theming\SettingsMigrator());
        $settings = ThemeSettings::defaults()->withCustomAccent(Srgb::fromHex('#7a2e6b'));

        Activation::run(self::TEST_VERSION);
        $repository->save($settings);

        Deactivation::run();

        $this->assertSame($settings->toArray(), $repository->load()->toArray());
    }

    #[Test]
    public function it_removes_every_capability_when_they_are_revoked(): void
    {
        Activation::run(self::TEST_VERSION);
        Capabilities::revokeFromAllRoles();

        $administrator = get_role('administrator');

        $this->assertNotNull($administrator);

        foreach (Capabilities::all() as $capability) {
            $this->assertFalse($administrator->has_cap($capability));
        }
    }

    #[Test]
    public function it_wires_the_container_to_the_wordpress_implementations(): void
    {
        $container = new Container();

        $this->assertInstanceOf(OptionSettingsRepository::class, $container->settingsRepository());
        $this->assertInstanceOf(UploadsStylesheetWriter::class, $container->stylesheetWriter());
        $this->assertInstanceOf(SettingsRepository::class, $container->settingsRepository());
        $this->assertInstanceOf(StylesheetWriter::class, $container->stylesheetWriter());
    }

    #[Test]
    public function it_builds_each_service_once(): void
    {
        $container = new Container();

        $this->assertSame($container->settingsRepository(), $container->settingsRepository());
        $this->assertSame($container->tokenCompiler(), $container->tokenCompiler());
        $this->assertSame($container->publishStylesheet(), $container->publishStylesheet());
        $this->assertSame($container->applyStylePreset(), $container->applyStylePreset());
        $this->assertSame($container->accentReviewer(), $container->accentReviewer());
    }
}
