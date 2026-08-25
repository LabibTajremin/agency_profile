<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Wp;

use Edulume\Core\Infrastructure\Blocks\BlockRegistrar;
use Edulume\Core\Infrastructure\Content\ContentRegistrar;
use Edulume\Core\Infrastructure\Content\DestinationPage;
use Edulume\Core\Infrastructure\Content\FoundersProvider;
use Edulume\Core\Infrastructure\Content\SiteContent;
use Edulume\Core\Infrastructure\Content\VideoRailProvider;
use Edulume\Core\Infrastructure\Admin\AdminAssets;
use Edulume\Core\Infrastructure\Admin\AdminMenu;
use Edulume\Core\Infrastructure\Admin\DemoImportAjax;
use Edulume\Core\Infrastructure\Admin\DemoImportScreen;
use Edulume\Core\Infrastructure\Admin\SectionsScreen;
use Edulume\Core\Infrastructure\Admin\ShieldScreen;
use Edulume\Core\Infrastructure\Admin\VideoScreen;
use Edulume\Core\Infrastructure\Demo\DemoCliCommand;
use Edulume\Core\Infrastructure\Rest\RestHandlers;
use Edulume\Core\Infrastructure\Security\LoginShield;
use Edulume\Core\Infrastructure\Rest\RestRegistrar;
use Edulume\Core\Infrastructure\Theming\SectionVisibility;
use Edulume\Core\Infrastructure\Theming\StylesheetEnqueuer;

/**
 * The plugin, as an object.
 *
 * The entry file holds a header and one call; everything that actually happens happens here,
 * so the bootstrap stays something a maintainer can read in ten seconds and this stays
 * something they can test.
 */
final class Plugin
{
    public const TEXT_DOMAIN = 'edulume';

    private static ?self $instance = null;

    private function __construct(
        public readonly string $version,
        public readonly string $entryFile,
        private readonly Container $container,
    ) {
    }

    public static function boot(string $version, string $entryFile): self
    {
        if (self::$instance === null) {
            self::$instance = new self($version, $entryFile, new Container());
            self::$instance->register();
        }

        return self::$instance;
    }

    public function container(): Container
    {
        return $this->container;
    }

    private function register(): void
    {
        // `void`, not the standalone `null` return type: that is PHP 8.2 syntax and this
        // plugin declares PHP 8.1. It parses fine on a newer runtime, so nothing caught it
        // until the plugin ran on the version it claims to support.
        register_activation_hook($this->entryFile, function (): void {
            $this->activate();
        });
        register_deactivation_hook($this->entryFile, static function (): void {
            self::deactivate();
        });

        add_action('init', [$this, 'loadTextDomain']);

        /*
         * Upgrades run themselves.
         *
         * `register_activation_hook` fires on activation and never again — so uploading a new
         * version over an existing install, whether by FTP, cPanel or the WordPress updater,
         * runs no migration at all. The plugin then executes new code against an old schema,
         * which is the failure nobody sees until a lead cannot be saved.
         *
         * Comparing the stored version against the shipped one on `admin_init` closes that.
         * `admin_init` rather than `plugins_loaded` because `dbDelta()` lives in an admin
         * include and because a schema change has no business running on a front-end request.
         * `Activation::run()` is written to be safe repeatedly: `dbDelta` is declarative, and
         * capabilities and options are set to a known value rather than appended to.
         */
        add_action('admin_init', [$this, 'runPendingUpgrade']);

        $this->stylesheetEnqueuer()->register();
        (new ContentRegistrar())->register();
        (new SiteContent())->register();
        (new VideoRailProvider())->register();
        (new DestinationPage())->register();
        (new FoundersProvider())->register();
        (new BlockRegistrar())->register();
        (new RestRegistrar(new RestHandlers($this->container)))->register();
        (new AdminMenu($this->container))->register();
        (new DemoImportScreen($this->container))->register();
        (new SectionsScreen($this->container))->register();
        (new DemoImportAjax($this->container))->register();
        (new VideoScreen())->register();

        $shield = new LoginShield();
        $shield->register();
        (new ShieldScreen($shield))->register();
        (new AdminAssets($this->container, $this->container->adminTheme(), $this->version))->register();
        (new SectionVisibility($this->container))->register();
        DemoCliCommand::register($this->container);
    }

    public function activate(): void
    {
        Activation::run($this->version);
    }

    /**
     * Brings the database up to the version of the code that is running.
     *
     * Cheap on every request but the first after an upgrade: one option read, then a string
     * comparison. It writes nothing when the versions already agree.
     */
    public function runPendingUpgrade(): void
    {
        if (!Activation::isUpgradePending($this->version)) {
            return;
        }

        Activation::run($this->version);
    }

    public static function deactivate(): void
    {
        Deactivation::run();
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname(plugin_basename($this->entryFile)) . '/languages'
        );
    }

    private function stylesheetEnqueuer(): StylesheetEnqueuer
    {
        return new StylesheetEnqueuer(
            $this->container->publishStylesheet(),
            $this->container->settingsRepository(),
            $this->container->compileStylesheet(),
        );
    }
}
