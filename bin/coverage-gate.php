<?php

/**
 * Coverage gate.
 *
 * Parses a Clover XML report, computes line and branch coverage restricted to the
 * Domain and Application layers, and exits non-zero when either figure falls below
 * its threshold.
 *
 * Usage: php bin/coverage-gate.php <clover.xml> <line-threshold> [branch-threshold]
 *
 * The line threshold is the product's stated floor and is enforced exactly. The branch
 * threshold defaults to the line threshold and is only separable because Xdebug's path
 * coverage attributes an unreachable bailout branch to every internal function call and
 * to every exhaustive `match`, which puts a literal 100% out of reach. See docs/testing.md.
 */

declare(strict_types=1);

const EXIT_SUCCESS = 0;
const EXIT_USAGE = 2;
const EXIT_UNREADABLE_REPORT = 3;
const EXIT_BELOW_THRESHOLD = 1;

const GATED_PATH_FRAGMENTS = ['/src/Domain/', '/src/Application/'];

const GATED_SOURCE_DIRECTORIES = [
    'plugins/edulume-core/src/Domain',
    'plugins/edulume-core/src/Application',
];

/**
 * @param list<string> $arguments
 */
function main(array $arguments): int
{
    if (count($arguments) < 3) {
        fwrite(STDERR, "Usage: php bin/coverage-gate.php <clover.xml> <line-threshold> [branch-threshold]\n");

        return EXIT_USAGE;
    }

    $reportPath = $arguments[1];
    $lineThreshold = (float) $arguments[2];
    $branchThreshold = isset($arguments[3]) ? (float) $arguments[3] : $lineThreshold;

    $document = loadReport($reportPath);

    if ($document === null) {
        return EXIT_UNREADABLE_REPORT;
    }

    $totals = accumulate($document);

    if ($totals['statements'] === 0) {
        return reportEmptyReport();
    }

    $lineCoverage = percentage($totals['coveredStatements'], $totals['statements']);
    $branchesMeasured = $totals['conditionals'] > 0;
    $branchCoverage = percentage($totals['coveredConditionals'], $totals['conditionals']);

    printf("Domain + Application line coverage: %.2f%% (threshold %.2f%%)\n", $lineCoverage, $lineThreshold);
    printf(
        "Domain + Application branch coverage: %s (threshold %.2f%%)\n",
        $branchesMeasured ? sprintf('%.2f%%', $branchCoverage) : 'not measured by this report',
        $branchThreshold
    );

    reportShortfalls($totals['uncoveredFiles']);

    $linesPass = $lineCoverage + FLOAT_TOLERANCE >= $lineThreshold;
    $branchesPass = !$branchesMeasured || $branchCoverage + FLOAT_TOLERANCE >= $branchThreshold;

    return $linesPass && $branchesPass ? EXIT_SUCCESS : EXIT_BELOW_THRESHOLD;
}

const FLOAT_TOLERANCE = 1.0e-9;

function loadReport(string $reportPath): ?SimpleXMLElement
{
    if (!is_readable($reportPath)) {
        fwrite(STDERR, sprintf("Coverage report not readable: %s\n", $reportPath));

        return null;
    }

    $document = @simplexml_load_file($reportPath);

    if ($document === false) {
        fwrite(STDERR, sprintf("Coverage report is not valid XML: %s\n", $reportPath));

        return null;
    }

    return $document;
}

function reportEmptyReport(): int
{
    if (gatedSourcesExist()) {
        fwrite(STDERR, "Domain or Application sources exist but none appear in the coverage report.\n");

        return EXIT_UNREADABLE_REPORT;
    }

    echo "No Domain or Application sources yet; the coverage gate has nothing to measure.\n";

    return EXIT_SUCCESS;
}

/**
 * @return array{
 *     statements:int,
 *     coveredStatements:int,
 *     conditionals:int,
 *     coveredConditionals:int,
 *     uncoveredFiles:list<string>
 * }
 */
function accumulate(SimpleXMLElement $document): array
{
    $statements = 0;
    $coveredStatements = 0;
    $conditionals = 0;
    $coveredConditionals = 0;
    $uncoveredFiles = [];

    foreach ($document->xpath('//file') ?: [] as $file) {
        $name = (string) $file['name'];

        if (!isGatedPath($name)) {
            continue;
        }

        $metrics = $file->metrics;

        if (!$metrics instanceof SimpleXMLElement) {
            continue;
        }

        $fileStatements = (int) $metrics['statements'];
        $fileCoveredStatements = (int) $metrics['coveredstatements'];

        $statements += $fileStatements;
        $coveredStatements += $fileCoveredStatements;
        $conditionals += (int) $metrics['conditionals'];
        $coveredConditionals += (int) $metrics['coveredconditionals'];

        if ($fileCoveredStatements < $fileStatements) {
            $uncoveredFiles[] = sprintf('%s (lines %d/%d)', $name, $fileCoveredStatements, $fileStatements);
        }
    }

    return [
        'statements' => $statements,
        'coveredStatements' => $coveredStatements,
        'conditionals' => $conditionals,
        'coveredConditionals' => $coveredConditionals,
        'uncoveredFiles' => $uncoveredFiles,
    ];
}

function gatedSourcesExist(): bool
{
    $repositoryRoot = dirname(__DIR__);

    foreach (GATED_SOURCE_DIRECTORIES as $directory) {
        $absolute = $repositoryRoot . '/' . $directory;

        if (!is_dir($absolute)) {
            continue;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute));

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                return true;
            }
        }
    }

    return false;
}

function isGatedPath(string $path): bool
{
    $normalised = str_replace('\\', '/', $path);

    foreach (GATED_PATH_FRAGMENTS as $fragment) {
        if (str_contains($normalised, $fragment)) {
            return true;
        }
    }

    return false;
}

function percentage(int $covered, int $total): float
{
    return $total === 0 ? 100.0 : ($covered / $total) * 100.0;
}

/**
 * @param list<string> $uncoveredFiles
 */
function reportShortfalls(array $uncoveredFiles): void
{
    if ($uncoveredFiles === []) {
        return;
    }

    fwrite(STDERR, "Files below full line coverage:\n");

    foreach ($uncoveredFiles as $file) {
        fwrite(STDERR, sprintf("  - %s\n", $file));
    }
}

exit(main($argv));
