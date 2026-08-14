<?php

/**
 * The unbounded-query gate.
 *
 * `posts_per_page => -1` is the single most common reason a WordPress site that was fine with
 * two hundred rows falls over at three thousand. It never fails in development, because
 * development has forty courses; it fails on the client's site, months later, under load.
 *
 * So it is a build failure here rather than a review comment somebody might forget. The same
 * goes for `nopaging`, for `numberposts => -1`, and for a `LIMIT`-less `get_results` on a
 * custom table.
 *
 * Usage: php bin/query-audit.php
 *
 * @package Edulume
 */

declare(strict_types=1);

const QUERY_AUDIT_EXIT_SUCCESS = 0;
const QUERY_AUDIT_EXIT_FAILURE = 1;

const SCANNED_DIRECTORIES = [
    'plugins/edulume-core/src',
    'themes/edulume-theme',
    'themes/edulume-child',
];

/**
 * The patterns, and what each one means when it fires.
 *
 * @return array<string, string>
 */
function unboundedPatterns(): array
{
    return [
        '/[\'"]posts_per_page[\'"]\s*=>\s*-\s*1/i'
            => 'posts_per_page => -1 loads every row into memory',
        '/[\'"]numberposts[\'"]\s*=>\s*-\s*1/i'
            => 'numberposts => -1 loads every row into memory',
        '/[\'"]nopaging[\'"]\s*=>\s*true/i'
            => 'nopaging => true disables the limit entirely',
        '/[\'"]posts_per_page[\'"]\s*=>\s*[\'"]?-1/i'
            => 'posts_per_page => "-1" loads every row into memory',
    ];
}

/**
 * A `$wpdb->get_results()` whose statement carries no `LIMIT`.
 *
 * Matched on the statement rather than on the call, because the limit is often built a few
 * lines above; the check looks at the whole statement expression up to its terminating
 * semicolon.
 */
function unlimitedGetResults(string $contents): bool
{
    if (preg_match_all('/get_results\s*\((.*?)\);/s', $contents, $matches) === false) {
        return false;
    }

    foreach ($matches[1] as $arguments) {
        $statement = (string) $arguments;

        if (stripos($statement, 'limit') !== false || isUniqueKeyLookup($statement)) {
            continue;
        }

        return true;
    }

    return false;
}

/**
 * A lookup on a unique key cannot return an unbounded number of rows.
 *
 * `SELECT * FROM leads WHERE id = %d` is bounded by the key, not by a `LIMIT`, and demanding
 * `LIMIT 1` on it would be noise that teaches readers the gate does not understand SQL.
 */
function isUniqueKeyLookup(string $statement): bool
{
    return preg_match('/WHERE\s+`?id`?\s*=\s*%d/i', $statement) === 1;
}

/**
 * @return list<string>
 */
function phpFilesIn(string $directory): array
{
    $root = dirname(__DIR__) . '/' . $directory;

    if (!is_dir($root)) {
        return [];
    }

    $found = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

    foreach ($files as $file) {
        if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
            $found[] = $file->getPathname();
        }
    }

    sort($found);

    return $found;
}

/**
 * @return list<string>
 */
function queryFindings(): array
{
    $findings = [];

    foreach (SCANNED_DIRECTORIES as $directory) {
        foreach (phpFilesIn($directory) as $path) {
            $contents = (string) file_get_contents($path);
            $lines = explode("\n", $contents);

            foreach ($lines as $number => $line) {
                foreach (unboundedPatterns() as $pattern => $reason) {
                    if (preg_match($pattern, $line) === 1) {
                        $findings[] = sprintf('%s:%d — %s', $path, $number + 1, $reason);
                    }
                }
            }

            if (unlimitedGetResults($contents)) {
                $findings[] = sprintf('%s — a $wpdb->get_results() statement carries no LIMIT', $path);
            }
        }
    }

    return $findings;
}

$findings = queryFindings();

if ($findings === []) {
    echo "Unbounded queries: clean\n";

    exit(QUERY_AUDIT_EXIT_SUCCESS);
}

printf("Unbounded queries: %d problem(s)\n", count($findings));

foreach ($findings as $finding) {
    printf("  - %s\n", $finding);
}

exit(QUERY_AUDIT_EXIT_FAILURE);
