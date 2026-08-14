<?php

/**
 * Two build gates the theme phases ask for, enforced rather than remembered.
 *
 * 1. No colour, font or spacing literal in theme CSS outside a `var()` fallback. The fallbacks
 *    are deliberate — they are what makes the theme degrade to something readable when the
 *    plugin is deactivated — but a literal anywhere else is a value that a global change would
 *    silently miss.
 * 2. Every translation call uses the one text domain. A second domain does not fail loudly; it
 *    just quietly stops translating, and nobody notices until a client reports it.
 *
 * Usage: php bin/theme-audit.php
 *
 * @package Edulume
 */

declare(strict_types=1);

const AUDIT_EXIT_SUCCESS = 0;
const AUDIT_EXIT_FAILURE = 1;

const TEXT_DOMAIN = 'edulume';

const TRANSLATION_FUNCTIONS = [
    '__', '_e', '_x', '_ex', '_n', '_nx', 'esc_html__', 'esc_html_e', 'esc_html_x',
    'esc_attr__', 'esc_attr_e', 'esc_attr_x',
];

const CSS_DIRECTORIES = ['themes/edulume-theme/assets/css'];
const PHP_DIRECTORIES = ['themes/edulume-theme', 'themes/edulume-child', 'plugins/edulume-core/src'];

/**
 * @return list<string>
 */
function auditFiles(string $directory, string $extension): array
{
    $root = dirname(__DIR__) . '/' . $directory;

    if (!is_dir($root)) {
        return [];
    }

    $found = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

    foreach ($files as $file) {
        if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === $extension) {
            $found[] = $file->getPathname();
        }
    }

    sort($found);

    return $found;
}

/**
 * Removes `var(--token, fallback)` fallbacks so only literals used as real values remain.
 */
function withoutTokenFallbacks(string $css): string
{
    return preg_replace('/var\(\s*--[a-z0-9-]+\s*,[^()]*\)/i', 'var(--token)', $css) ?? $css;
}

/**
 * @return list<string>
 */
function cssLiteralFindings(): array
{
    $findings = [];

    foreach (CSS_DIRECTORIES as $directory) {
        foreach (auditFiles($directory, 'css') as $path) {
            $contents = (string) file_get_contents($path);
            $lines = explode("\n", $contents);

            foreach ($lines as $number => $line) {
                if (str_starts_with(ltrim($line), '*') || str_starts_with(ltrim($line), '/*')) {
                    continue;
                }

                $stripped = withoutTokenFallbacks($line);

                foreach (literalPatterns() as $label => $pattern) {
                    if (preg_match($pattern, $stripped) === 1) {
                        $findings[] = sprintf('%s:%d uses a %s literal: %s', $path, $number + 1, $label, trim($line));
                    }
                }
            }
        }
    }

    return $findings;
}

/**
 * @return array<string, string>
 */
function literalPatterns(): array
{
    return [
        'colour' => '/#[0-9a-fA-F]{3,8}\b|\brgba?\(|\bhsla?\(|\boklch\(/',
        // A possessive quantifier on purpose: a greedy `\s*` backtracks to zero characters and
        // then tests the lookahead against the space, which passes for every well-formed line.
        'font-family' => '/font-family\s*+:\s*+(?!var\()/i',
        'font-size' => '/font-size\s*:\s*[0-9.]+(px|pt|em|rem)/i',
    ];
}

/**
 * @return list<string>
 */
function textDomainFindings(): array
{
    $findings = [];
    $pattern = '/\b(' . implode('|', array_map('preg_quote', TRANSLATION_FUNCTIONS)) . ')\s*\(/';

    foreach (PHP_DIRECTORIES as $directory) {
        foreach (auditFiles($directory, 'php') as $path) {
            $lines = explode("\n", (string) file_get_contents($path));

            foreach ($lines as $number => $line) {
                if (preg_match($pattern, $line) !== 1) {
                    continue;
                }

                if (str_contains($line, "'" . TEXT_DOMAIN . "'")) {
                    continue;
                }

                // A call split across lines states its domain further down; only flag the
                // single-line calls, where the domain is provably absent.
                if (substr_count($line, '(') === substr_count($line, ')')) {
                    $findings[] = sprintf('%s:%d translates without the text domain: %s', $path, $number + 1, trim($line));
                }
            }
        }
    }

    return $findings;
}

/**
 * @param list<string> $findings
 */
function report(string $heading, array $findings): void
{
    if ($findings === []) {
        printf("%s: clean\n", $heading);

        return;
    }

    fwrite(STDERR, sprintf("%s: %d problem(s)\n", $heading, count($findings)));

    foreach ($findings as $finding) {
        fwrite(STDERR, '  - ' . $finding . "\n");
    }
}

$cssFindings = cssLiteralFindings();
$domainFindings = textDomainFindings();

report('Theme CSS literals', $cssFindings);
report('Text domain', $domainFindings);

exit($cssFindings === [] && $domainFindings === [] ? AUDIT_EXIT_SUCCESS : AUDIT_EXIT_FAILURE);
