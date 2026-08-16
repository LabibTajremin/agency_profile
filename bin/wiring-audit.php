<?php

/**
 * Proves that everything the product declares is actually reachable.
 *
 * Three wiring bugs shipped in this codebase, and all three failed the same way: something was
 * declared, nothing implemented it, and no error was raised because the code that would have
 * used it politely skipped what it could not find.
 *
 *   - `compare.js` bound to `[data-edulume-compare]`; no template emitted that attribute. A
 *     working, tested feature that was not on the site.
 *   - Nine script modules were declared; five had no file. The enqueue loop skips a module it
 *     cannot read, so the eligibility check, the calculator, the forms, the carousels and every
 *     motion effect silently did nothing.
 *   - Four block features — tabs, counters, before-after, video — resolved to no module at all.
 *
 * None of these are catchable by a unit test of either side, because both sides are correct in
 * isolation. What is wrong is the gap between them, which is what this reads.
 *
 * Usage: php bin/wiring-audit.php
 *
 * @package Edulume
 */

declare(strict_types=1);

const WIRING_EXIT_SUCCESS = 0;
const WIRING_EXIT_FAILURE = 1;

const THEME_ROOT = 'themes/edulume-theme';

function repositoryPath(string $relative): string
{
    return dirname(__DIR__) . '/' . ltrim($relative, '/');
}

/**
 * The module names `edulume_conditional_modules()` declares.
 *
 * Read out of the source rather than by loading it, because loading it needs WordPress.
 *
 * @return list<string>
 */
function declaredModules(): array
{
    $source = (string) file_get_contents(repositoryPath(THEME_ROOT . '/inc/assets.php'));
    $start = strpos($source, 'function edulume_conditional_modules');

    if ($start === false) {
        return [];
    }

    $end = strpos($source, "\n}", $start);
    $body = substr($source, $start, $end === false ? null : $end - $start);

    preg_match_all("/^\s*'([a-z-]+)'\s*=>/m", $body, $matches);

    return array_values(array_unique($matches[1]));
}

/**
 * @return list<string>
 */
function themeFiles(string $extension): array
{
    $root = repositoryPath(THEME_ROOT);

    if (!is_dir($root)) {
        return [];
    }

    $found = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
        if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === $extension) {
            $found[] = $file->getPathname();
        }
    }

    sort($found);

    return $found;
}

/**
 * Every module declared must have a file behind it.
 *
 * @return list<string>
 */
function missingModuleFindings(): array
{
    $findings = [];

    foreach (declaredModules() as $module) {
        $path = repositoryPath(THEME_ROOT . '/assets/js/' . $module . '.js');

        if (!is_readable($path)) {
            $findings[] = sprintf(
                'the "%s" module is declared in edulume_conditional_modules() and has no file — '
                . 'the enqueue loop skips it in silence',
                $module
            );
        }
    }

    return $findings;
}

/**
 * Every `data-edulume-*` hook a script binds to must be emitted by some template or block.
 *
 * @return list<string>
 */
function orphanedHookFindings(): array
{
    $bound = [];

    foreach (themeFiles('js') as $path) {
        $source = (string) file_get_contents($path);

        /*
         * Only hooks used as a *selector* count. A script that calls
         * `setAttribute('data-edulume-motion-active', …)` is writing the attribute, and one that
         * calls `getAttribute('data-edulume-motion-delay')` is reading an optional one — neither
         * is a thing a template has to emit for the feature to work. The first version of this
         * check did not distinguish them and reported ten findings, none of which were bugs.
         */
        preg_match_all('/\[\s*(data-edulume-[a-z-]+)/', $source, $matches);

        foreach ($matches[1] as $hook) {
            $bound[$hook] = true;
        }
    }

    $emitted = '';

    foreach (themeFiles('php') as $path) {
        $emitted .= (string) file_get_contents($path);
    }

    $findings = [];

    foreach (array_keys($bound) as $hook) {
        if (str_contains($emitted, $hook) || in_array($hook, blockAuthoredHooks(), true)) {
            continue;
        }

        $findings[] = sprintf(
            '%s is bound by a script and emitted by no template — the feature cannot run',
            $hook
        );
    }

    return $findings;
}

/**
 * Hooks that live in editor-authored block content rather than in a theme template.
 *
 * A block renders whatever was saved inside it, so these attributes arrive from the post
 * content and can never be found by grepping the theme. Listed explicitly, and kept short, so
 * that "no template emits this" stays a finding rather than becoming a shrug.
 *
 * @return list<string>
 */
function blockAuthoredHooks(): array
{
    return [
        'data-edulume-block',
        'data-edulume-tabs',
        'data-edulume-counter',
        'data-edulume-before-after',
        'data-edulume-video',
        'data-edulume-video-play',
    ];
}

/**
 * @param list<string> $findings
 */
function reportWiring(string $heading, array $findings): bool
{
    if ($findings === []) {
        printf("%s: clean\n", $heading);

        return true;
    }

    printf("%s: %d problem(s)\n", $heading, count($findings));

    foreach ($findings as $finding) {
        printf("  - %s\n", $finding);
    }

    return false;
}

$ok = reportWiring('Declared modules', missingModuleFindings());
$ok = reportWiring('Script hooks', orphanedHookFindings()) && $ok;

exit($ok ? WIRING_EXIT_SUCCESS : WIRING_EXIT_FAILURE);
