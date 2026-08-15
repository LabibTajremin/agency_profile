<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Theming\AdminTheme;
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

    public function __construct(
        private readonly Container $container,
        private readonly AdminTheme $adminTheme,
    ) {
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        wp_register_style(self::HANDLE, false, [], null);
        wp_enqueue_style(self::HANDLE);
        wp_add_inline_style(self::HANDLE, $this->css());
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
CSS;
}
