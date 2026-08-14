<?php

/**
 * Builds the distributable ZIPs.
 *
 * An allowlist of what goes in, not a denylist of what stays out. A denylist ships the `.git`
 * directory the first time somebody adds a build artefact nobody thought to exclude — and a
 * shipped `.git` directory is the whole source history, including anything ever committed to it
 * by mistake.
 *
 * Usage:
 *   php bin/package.php              build into build/dist
 *   php bin/package.php --verify     build, then list what each archive contains
 *
 * @package Edulume
 */

declare(strict_types=1);

const PACKAGE_EXIT_SUCCESS = 0;
const PACKAGE_EXIT_FAILURE = 1;

/**
 * The three archives, and exactly what belongs in each.
 *
 * @return array<string, array{root: string, include: list<string>}>
 */
function packages(): array
{
    return [
        'edulume-core' => [
            'root' => 'plugins/edulume-core',
            'include' => [
                'edulume-core.php',
                'uninstall.php',
                'readme.txt',
                'src',
                'assets',
                'demos',
                'languages',
            ],
        ],
        'edulume-theme' => [
            'root' => 'themes/edulume-theme',
            'include' => [
                'style.css',
                'functions.php',
                'theme.json',
                'index.php',
                'singular.php',
                'page.php',
                'front-page.php',
                'header.php',
                'footer.php',
                'search.php',
                '404.php',
                'maintenance.php',
                'inc',
                'assets',
                'template-parts',
                'languages',
                'screenshot.png',
            ],
        ],
        'edulume-child' => [
            'root' => 'themes/edulume-child',
            'include' => ['style.css', 'functions.php', 'screenshot.png'],
        ],
    ];
}

/**
 * Files that must never enter an archive even when they sit inside an included directory.
 *
 * The allowlist above is the primary defence; this is the second one, because a directory is
 * included wholesale and a stray `.map` or `.DS_Store` inside it would otherwise ride along.
 *
 * @return list<string>
 */
function excludedNames(): array
{
    return ['.DS_Store', 'Thumbs.db', '.gitkeep', '.gitignore'];
}

function isExcluded(string $relativePath): bool
{
    if (in_array(basename($relativePath), excludedNames(), true)) {
        return true;
    }

    return str_ends_with($relativePath, '.map')
        || str_contains($relativePath, '/node_modules/')
        || str_contains($relativePath, '/vendor/')
        || str_contains($relativePath, '/tests/');
}

/**
 * @return list<array{absolute: string, relative: string}>
 */
function filesFor(string $root, string $entry): array
{
    $base = dirname(__DIR__) . '/' . $root;
    $path = $base . '/' . $entry;

    if (is_file($path)) {
        return isExcluded($entry) ? [] : [['absolute' => $path, 'relative' => $entry]];
    }

    if (!is_dir($path)) {
        return [];
    }

    $found = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $relative = substr($file->getPathname(), strlen($base) + 1);

        if (!isExcluded('/' . $relative)) {
            $found[] = ['absolute' => $file->getPathname(), 'relative' => $relative];
        }
    }

    usort($found, static fn (array $a, array $b): int => strcmp($a['relative'], $b['relative']));

    return $found;
}

/**
 * @param list<string> $include
 *
 * @return array{files: int, missing: list<string>}
 */
function build(string $name, string $root, array $include, string $outputDirectory): array
{
    $archivePath = $outputDirectory . '/' . $name . '.zip';
    $missing = [];
    $count = 0;

    if (file_exists($archivePath)) {
        unlink($archivePath);
    }

    $zip = new ZipArchive();

    if ($zip->open($archivePath, ZipArchive::CREATE) !== true) {
        throw new RuntimeException('Could not create ' . $archivePath);
    }

    foreach ($include as $entry) {
        $files = filesFor($root, $entry);

        if ($files === []) {
            // Reported rather than skipped silently: a missing `languages` directory means the
            // release would ship untranslatable, and finding that out from a customer is late.
            $missing[] = $entry;

            continue;
        }

        foreach ($files as $file) {
            // Every archive contains one top-level directory named after the plugin or theme,
            // because that is the directory name WordPress installs it under.
            $zip->addFile($file['absolute'], $name . '/' . $file['relative']);
            $count++;
        }
    }

    $zip->close();

    return ['files' => $count, 'missing' => $missing];
}

$outputDirectory = dirname(__DIR__) . '/build/dist';

if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0o755, true) && !is_dir($outputDirectory)) {
    fwrite(STDERR, "Could not create build/dist\n");

    exit(PACKAGE_EXIT_FAILURE);
}

$verify = in_array('--verify', $argv, true);
$hadMissing = false;

foreach (packages() as $name => $package) {
    $result = build($name, $package['root'], $package['include'], $outputDirectory);

    printf("%s.zip — %d file(s)\n", $name, $result['files']);

    foreach ($result['missing'] as $entry) {
        $hadMissing = true;

        printf("  missing: %s\n", $entry);
    }

    if (!$verify) {
        continue;
    }

    $zip = new ZipArchive();

    if ($zip->open($outputDirectory . '/' . $name . '.zip') === true) {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            printf("  %s\n", (string) $zip->getNameIndex($index));
        }

        $zip->close();
    }
}

if ($hadMissing) {
    fwrite(STDERR, "\nSome expected paths were not found. Fix them before releasing.\n");

    exit(PACKAGE_EXIT_FAILURE);
}

exit(PACKAGE_EXIT_SUCCESS);
