<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Theming\AdminTheme;
use Edulume\Core\Infrastructure\Admin\DemoImportAjax;
use Edulume\Core\Domain\Theming\ThemeMode;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * Paints wp-admin in the site's own accent.
 *
 * `AdminTheme` has existed since the theming phase and was wired to nothing, so the admin
 * stayed WordPress grey however the site was configured. That is a bigger deal than decoration:
 * an agency running six client sites has six identical admin panels, and the only thing telling
 * them which one they are editing is the browser tab.
 *
 * Every colour comes from `AdminTheme`, which resolves each foreground through the contrast
 * engine. That is the reason this can be applied to a real admin screen at all — a naive
 * accent-tinting of wp-admin produces unreadable menu text the first time somebody picks a
 * pale yellow.
 */
final class AdminAssets
{
    public const HANDLE = 'edulume-admin';
    public const SCREENS_HANDLE = 'edulume-admin-screens';

    public function __construct(
        private readonly Container $container,
        private readonly AdminTheme $adminTheme,
        private readonly string $version,
    ) {
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        /*
         * Versioned with the plugin's own version rather than `null`.
         *
         * There is no file behind this handle — it exists only to hang inline CSS on — so a
         * cache-busting query string changes nothing about what is fetched. It is set anyway
         * because `null` means "WordPress's version", which drifts with core updates and is a
         * lie about what produced this stylesheet.
         *
         * Passed in rather than read from the entry file's constant: a constant defined in a
         * file nothing else loads is invisible to static analysis, and reaching for a global
         * from an injected class is the kind of shortcut that makes it untestable.
         */
        wp_register_style(self::HANDLE, false, [], $this->version);
        wp_enqueue_style(self::HANDLE);
        wp_add_inline_style(self::HANDLE, $this->css());

        $this->enqueueScreens();
    }

    /**
     * The enhancement layer for the two server-rendered screens.
     *
     * Loaded only on those screens. A plugin that puts its script on every admin page is a
     * plugin that shows up in every other developer's bug report.
     */
    private function enqueueScreens(): void
    {
        $screen = get_current_screen();
        $id = $screen === null ? '' : (string) $screen->id;

        $screens = ['edulume-demos', 'edulume-sections', 'edulume-safety', 'edulume-videos'];
        $wanted = array_filter($screens, static fn (string $slug): bool => str_contains($id, $slug));

        if ($wanted === []) {
            return;
        }

        wp_enqueue_script(
            self::SCREENS_HANDLE,
            plugins_url('assets/js/screens.js', dirname(__DIR__, 2) . '/edulume-core.php'),
            [],
            $this->version,
            true
        );

        wp_localize_script(self::SCREENS_HANDLE, 'edulumeScreens', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'importAction' => DemoImportAjax::ACTION,
            'importNonce' => wp_create_nonce(DemoImportAjax::NONCE),
            'strings' => [
                'importing' => __('Importing…', 'edulume'),
                'imported' => __('Done. Reloading…', 'edulume'),
                'importFailed' => __('That did not finish. Try again.', 'edulume'),
                'confirmShield' => __('Please confirm you have saved your sign-in address.', 'edulume'),
            ],
        ]);
    }

    private function css(): string
    {
        $settings = $this->container->settingsRepository()->load();
        $tokens = $this->adminTheme->compile($settings->accentSeed(), ThemeMode::Light);

        $declarations = '';

        foreach ($tokens as $name => $value) {
            $declarations .= sprintf("  %s: %s;\n", $name, $value);
        }

        return ":root {\n" . $declarations . "}\n" . self::RULES;
    }

    /**
     * The rules that spend those tokens.
     *
     * Deliberately narrow. Restyling all of wp-admin is how a plugin breaks on the next
     * WordPress release and fights every other plugin's screens on the way there; this colours
     * the chrome a person looks at to know where they are — the menu, the buttons, the focus
     * ring — and leaves the rest of the admin alone.
     */
    private const RULES = <<<'CSS'
#adminmenu,
#adminmenuwrap,
#adminmenuback {
  background: var(--edulume-admin-surface-raised);
}

#adminmenu a {
  color: var(--edulume-admin-ink);
}

#adminmenu div.wp-menu-image::before {
  color: var(--edulume-admin-ink-muted);
}

#adminmenu li.menu-top:hover,
#adminmenu li.opensub > a.menu-top,
#adminmenu li > a.menu-top:focus {
  background: var(--edulume-admin-accent);
  color: var(--edulume-admin-on-accent);
}

#adminmenu li.menu-top:hover div.wp-menu-image::before,
#adminmenu li > a.menu-top:focus div.wp-menu-image::before {
  color: var(--edulume-admin-on-accent);
}

#adminmenu .wp-has-current-submenu .wp-submenu,
#adminmenu .wp-submenu {
  background: var(--edulume-admin-surface);
}

#adminmenu li.wp-has-current-submenu > a.wp-has-current-submenu,
#adminmenu li.current > a.menu-top,
#adminmenu .wp-submenu li.current a {
  background: var(--edulume-admin-accent);
  color: var(--edulume-admin-on-accent);
}

#adminmenu li.wp-has-current-submenu div.wp-menu-image::before {
  color: var(--edulume-admin-on-accent);
}

.wp-core-ui .button-primary {
  background: var(--edulume-admin-accent);
  border-color: var(--edulume-admin-accent);
  color: var(--edulume-admin-on-accent);
}

.wp-core-ui .button-primary:hover,
.wp-core-ui .button-primary:focus {
  background: var(--edulume-admin-accent);
  border-color: var(--edulume-admin-ink);
  color: var(--edulume-admin-on-accent);
}

.wp-core-ui .button-link,
a {
  color: var(--edulume-admin-accent-text);
}

/*
 * One visible focus ring across the whole admin, at the non-text threshold. WordPress's own
 * varies by control and disappears entirely on a few of them.
 */
.wp-core-ui :where(a, button, input, select, textarea, [tabindex]):focus-visible {
  outline: 2px solid var(--edulume-admin-focus-ring);
  outline-offset: 1px;
  box-shadow: none;
}

/* The Edulume screens themselves, which are ours to style completely. */
.edulume-help {
  max-inline-size: 46rem;
  margin-block: 1rem 1.5rem;
  padding: 1rem 1.25rem;
  border-inline-start: 4px solid var(--edulume-admin-accent);
  border-radius: 4px;
  background: var(--edulume-admin-surface-raised);
  color: var(--edulume-admin-ink);
}

.edulume-help h2 {
  margin-block: 0 0.35rem;
  font-size: 1.05rem;
}

.edulume-help p {
  margin-block: 0 0.6rem;
  color: var(--edulume-admin-ink-muted);
}

.edulume-help ul {
  margin: 0;
  padding-inline-start: 1.1rem;
  color: var(--edulume-admin-ink-muted);
}

.edulume-help li {
  margin-block-end: 0.3rem;
}

.edulume-demos {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));
  max-inline-size: 60rem;
}

.edulume-demo-card {
  padding: 1rem 1.25rem;
  border: 1px solid var(--edulume-admin-border);
  border-radius: 6px;
  background: var(--edulume-admin-surface-raised);
}

.edulume-demo-card h3 {
  margin-block-start: 0;
}

.edulume-demo-card__count {
  color: var(--edulume-admin-ink-muted);
}

.edulume-progress {
  block-size: 6px;
  margin-block-start: 0.75rem;
  border-radius: 999px;
  background: var(--edulume-admin-surface);
  overflow: hidden;
}

.edulume-progress__bar {
  display: block;
  block-size: 100%;
  inline-size: 0;
  background: var(--edulume-admin-accent);
  transition: inline-size 200ms linear;
}

.edulume-progress__status {
  margin-block: 0.4rem 0;
  color: var(--edulume-admin-ink-muted);
  min-block-size: 1.2em;
}

.edulume-sections__list {
  max-inline-size: 40rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.edulume-sections__row {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  padding: 0.6rem 0.85rem;
  margin-block-end: 0.4rem;
  border: 1px solid var(--edulume-admin-border);
  border-radius: 6px;
  background: var(--edulume-admin-surface-raised);
}

.edulume-sections__handle {
  inline-size: 1rem;
  block-size: 1rem;
  flex: 0 0 auto;
  cursor: grab;
  /* Three bars drawn in the border colour: no icon font, no image request. */
  background-image: linear-gradient(
    to bottom,
    var(--edulume-admin-ink-muted) 0 2px,
    transparent 2px 5px,
    var(--edulume-admin-ink-muted) 5px 7px,
    transparent 7px 10px,
    var(--edulume-admin-ink-muted) 10px 12px
  );
}

.edulume-sections__toggle {
  flex: 1 1 auto;
}

.edulume-sections__position input {
  inline-size: 4.5rem;
}

.edulume-shield {
  max-inline-size: 40rem;
}

.edulume-shield__field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-block: 0 1rem;
}

.edulume-shield__field label {
  font-weight: 600;
}

.edulume-shield__field input[type='checkbox'] + * {
  font-weight: 400;
}

.edulume-shield__confirm {
  padding: 1rem 1.25rem;
  margin-block: 1.5rem;
  border: 1px solid var(--edulume-admin-accent);
  border-radius: 6px;
  background: var(--edulume-admin-surface-raised);
}

.edulume-shield__confirm code {
  display: inline-block;
  margin-block-start: 0.25rem;
  word-break: break-all;
}

.edulume-videos__rows {
  max-inline-size: 60rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.edulume-videos__row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
  padding: 0.6rem 0.85rem;
  margin-block-end: 0.4rem;
  border: 1px solid var(--edulume-admin-border);
  border-radius: 6px;
  background: var(--edulume-admin-surface-raised);
}

.edulume-videos__row input[type='url'] {
  flex: 1 1 18rem;
  min-inline-size: 0;
}

/* The hint takes the whole second line: it is a sentence, not a field label. */
.edulume-videos__hint {
  flex: 1 0 100%;
  color: var(--edulume-admin-ink-muted);
}
CSS;
}
