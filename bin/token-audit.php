<?php

/**
 * Proves every custom property the theme reads is one something actually defines.
 *
 * This gate exists because the theme spent its whole life reading tokens that were never
 * emitted. `var(--edulume-surface-sunken, #f1f2f4)` looks correct and behaves correctly in
 * light mode — the fallback is a light grey — and it is silently, permanently wrong in dark
 * mode, because a fallback does not change with the mode. The compiler emitted
 * `--edulume-surface-subtle`; nothing emitted `--edulume-surface-sunken`; and the difference
 * was invisible until axe measured `#f9fafc on #f1f2f4 = 1.07:1`.
 *
 * A misspelt token is the worst kind of bug this codebase can have: the fallback makes it look
 * like it works. So the check asks the compiler what it emits rather than trusting a list
 * someone maintains by hand.
 *
 * Usage: php bin/token-audit.php
 *
 * @package Edulume
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Edulume\Core\Domain\Theming\ThemeMode;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\TokenCompiler;
use Edulume\Core\Domain\Theming\PaletteGenerator;
use Edulume\Core\Domain\Color\ContrastEngine;

const TOKEN_AUDIT_EXIT_SUCCESS = 0;
const TOKEN_AUDIT_EXIT_FAILURE = 1;

const TOKEN_CSS_DIRECTORIES = ['themes/edulume-theme/assets/css'];

/**
 * Every token the compiler produces, in both modes.
 *
 * Both, because a token emitted only in one mode is exactly the asymmetry this is looking for.
 *
 * @return list<string>
 */
function emittedTokens(): array
{
    $compiler = new TokenCompiler(new PaletteGenerator(new ContrastEngine()));
    $settings = ThemeSettings::defaults();
    $names = [];

    foreach ([ThemeMode::Light, ThemeMode::Dark] as $mode) {
        foreach (array_keys($compiler->compile($settings, $mode)) as $name) {
            $names[(string) $name] = true;
        }
    }

    return array_keys($names);
}

/**
 * @return list<string>
 */
function themeCssFiles(): array
{
    $found = [];

    foreach (TOKEN_CSS_DIRECTORIES as $directory) {
        $root = dirname(__DIR__) . '/' . $directory;

        if (!is_dir($root)) {
            continue;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'css') {
                $found[] = $file->getPathname();
            }
        }
    }

    sort($found);

    return $found;
}

/**
 * Tokens the theme defines for itself, which are as valid a source as the compiler.
 *
 * @param list<string> $files
 *
 * @return list<string>
 */
function themeDefinedTokens(array $files): array
{
    $defined = [];

    foreach ($files as $path) {
        // Comments stripped here too, and for the more dangerous reason: a token named in prose
        // must never be able to satisfy the gate for a token nothing actually declares.
        $contents = withoutComments((string) file_get_contents($path));

        if (preg_match_all('/^\s*(--edulume-[a-z0-9-]+)\s*:/mi', $contents, $matches) !== false) {
            foreach ($matches[1] as $name) {
                $defined[strtolower((string) $name)] = true;
            }
        }
    }

    return array_keys($defined);
}

/**
 * Blanks out comments, keeping every newline so reported line numbers still point at the source.
 *
 * A token named in prose is not a read. This gate documents itself by quoting the exact bug it
 * was written for — `var(--edulume-surface-sunken, #f1f2f4)` — and a scanner that cannot tell an
 * explanation from a declaration flags its own comment, which is a fine way to teach everyone
 * that the gate cries wolf.
 */
function withoutComments(string $css): string
{
    return (string) preg_replace_callback(
        '#/\*.*?\*/#s',
        static fn (array $match): string => preg_replace('/[^\n]/', ' ', (string) $match[0]) ?? '',
        $css
    );
}

/**
 * @param list<string> $known
 *
 * @return list<string>
 */
function unknownTokenFindings(array $known): array
{
    $findings = [];
    $lookup = array_flip(array_map('strtolower', $known));

    foreach (themeCssFiles() as $path) {
        $source = withoutComments((string) file_get_contents($path));

        foreach (explode("\n", $source) as $number => $line) {
            if (preg_match_all('/var\(\s*(--edulume-[a-z0-9-]+)/i', $line, $matches) === false) {
                continue;
            }

            foreach ($matches[1] as $name) {
                if (!array_key_exists(strtolower((string) $name), $lookup)) {
                    $findings[] = sprintf(
                        '%s:%d reads %s, which nothing defines',
                        $path,
                        $number + 1,
                        (string) $name
                    );
                }
            }
        }
    }

    return $findings;
}

$known = [...emittedTokens(), ...themeDefinedTokens(themeCssFiles())];
$findings = unknownTokenFindings($known);

if ($findings === []) {
    printf("Design tokens: clean (%d defined)\n", count($known));

    exit(TOKEN_AUDIT_EXIT_SUCCESS);
}

printf("Design tokens: %d problem(s)\n", count($findings));

foreach ($findings as $finding) {
    printf("  - %s\n", $finding);
}

print "\nA token nothing defines silently falls back to its literal, which does not change with "
    . "the mode.\n";

exit(TOKEN_AUDIT_EXIT_FAILURE);
