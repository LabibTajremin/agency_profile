<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Wp;

use Edulume\Core\Infrastructure\Blocks\BlockRegistrar;
use Edulume\Core\Infrastructure\Content\ContentRegistrar;
use Edulume\Core\Infrastructure\Rest\RestHandlers;
use Edulume\Core\Infrastructure\Rest\RestRegistrar;
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
        register_activation_hook($this->entryFile, fn (): null => $this->activate());
        register_deactivation_hook($this->entryFile, static fn (): null => self::deactivate());

        add_action('init', [$this, 'loadTextDomain']);

        $this->stylesheetEnqueuer()->register();
        (new ContentRegistrar())->register();
        (new BlockRegistrar())->register();
        (new RestRegistrar(new RestHandlers($this->container)))->register();
    }

    public function activate(): null
    {
        Activation::run($this->version);

        return null;
    }

    public static function deactivate(): null
    {
        Deactivation::run();

        return null;
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
