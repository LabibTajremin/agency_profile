<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Theming;

use Edulume\Core\Application\Port\SettingsRepository;
use Edulume\Core\Application\Theming\CompileStylesheet;
use Edulume\Core\Application\Theming\PublishStylesheet;
use Edulume\Core\Infrastructure\Settings\OptionSettingsRepository;

/**
 * Enqueues the published stylesheet, and republishes it when settings are saved.
 *
 * Rendering never compiles. The front end looks up a stored URL and enqueues it; compiling
 * happens on save, which is the only moment the output can have changed.
 */
final class StylesheetEnqueuer
{
    public const HANDLE = 'edulume-tokens';
    public const PUBLISHED_URL_OPTION = 'edulume_stylesheet_url';
    public const PUBLISHED_VERSION_OPTION = 'edulume_stylesheet_version';

    public function __construct(
        private readonly PublishStylesheet $publishStylesheet,
        private readonly SettingsRepository $settingsRepository,
        private readonly CompileStylesheet $compileStylesheet,
    ) {
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue']);
        add_action('update_option_' . OptionSettingsRepository::SETTINGS_OPTION, [$this, 'republish']);
        add_action('update_option_' . OptionSettingsRepository::SECTION_OVERRIDES_OPTION, [$this, 'republish']);
    }

    public function enqueue(): void
    {
        $url = (string) get_option(self::PUBLISHED_URL_OPTION, '');

        if ($url === '') {
            $url = $this->republish();
        }

        wp_enqueue_style(
            self::HANDLE,
            $url,
            [],
            (string) get_option(self::PUBLISHED_VERSION_OPTION, '')
        );
    }

    public function republish(): string
    {
        $published = ($this->publishStylesheet)();

        update_option(self::PUBLISHED_URL_OPTION, $published->url, true);
        update_option(self::PUBLISHED_VERSION_OPTION, $published->version(), true);

        return $published->url;
    }

    /**
     * The version the current settings would compile to, without writing anything. Used by the
     * health panel to tell a site owner their published file is stale.
     */
    public function pendingVersion(): string
    {
        $settings = $this->settingsRepository->load();

        return ($this->compileStylesheet)($settings, $this->settingsRepository->loadSectionOverrides())->hash;
    }
}
