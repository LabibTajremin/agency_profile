<?php

/**
 * Runs, locally, exactly what the pull-request gate runs on GitHub.
 *
 * The point is to find a red check before pushing rather than after. Every step here mirrors a
 * step in .github/workflows/ci.yml; when one changes, the other has to. Steps that need
 * Docker are reported as skipped with the reason, never silently dropped — a green run must
 * never mean "did not run".
 *
 * Usage:
 *   php bin/ci-local.php            run everything available
 *   php bin/ci-local.php --quick    skip the slow coverage pass
 *
 * @package Edulume
 */

declare(strict_types=1);

const CI_EXIT_SUCCESS = 0;
const CI_EXIT_FAILURE = 1;

/**
 * @return list<array{name:string, command:string, job:string, needsDocker:bool, slow:bool}>
 */
function steps(): array
{
    return [
        ['name' => 'PHP lint (PHPCS)', 'command' => 'composer lint', 'job' => 'php-quality',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'Static analysis (PHPStan)', 'command' => 'composer analyse', 'job' => 'php-quality',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'Theme and text-domain audit', 'command' => 'composer audit:theme', 'job' => 'php-quality',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'Design-token audit', 'command' => 'composer audit:tokens', 'job' => 'php-quality',
            'needsDocker' => false, 'slow' => false],
        /*
         * Regenerates the template and fails if regenerating changed it. This runs in CI and did
         * not run here, which is exactly how a pull request went red on a stale `.pot` after a
         * translatable string was added.
         *
         * Compared against the file on disk rather than against HEAD, which is what CI does. CI
         * checks out clean, so `git diff` there means "regenerating changed something"; run
         * locally it also means "you have uncommitted work", and a gate that fails whenever you
         * have unfinished edits is a gate you learn to ignore.
         */
        ['name' => 'Translation template is current',
            'command' => 'php -r \'$f = "languages/edulume.pot"; $before = md5_file($f);'
                . ' exec("composer make:pot 2>&1", $o, $c);'
                . ' if ($c !== 0) { fwrite(STDERR, implode("\n", $o) . "\n"); exit(1); }'
                . ' if ($before === md5_file($f)) { exit(0); }'
                . ' fwrite(STDERR, "The shipped .pot was stale and has been regenerated."'
                . ' . " Commit it.\n"); exit(1);\'',
            'job' => 'php-quality', 'needsDocker' => false, 'slow' => false],
        // Catches a feature that is declared and unreachable — a script bound to markup nobody
        // emits, or a module declared with no file behind it. Both shipped, both silent.
        ['name' => 'Wiring audit', 'command' => 'composer audit:wiring', 'job' => 'php-quality',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'Unbounded-query audit', 'command' => 'composer audit:queries', 'job' => 'php-quality',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'Logical-CSS and text-domain audit', 'command' => 'composer audit:i18n', 'job' => 'php-quality',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'Security audit', 'command' => 'composer audit:security', 'job' => 'php-quality',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'WPCS security ruleset', 'command' => 'vendor/bin/phpcs --standard=phpcs-security.xml.dist',
            'job' => 'wordpress-standards', 'needsDocker' => true, 'slow' => true],
        ['name' => 'Plugin Check', 'command' => 'npx wp-env run cli wp plugin check edulume-core --severity=5',
            'job' => 'wordpress-standards', 'needsDocker' => true, 'slow' => true],
        ['name' => 'PHP unit tests', 'command' => 'composer test:unit', 'job' => 'php-unit',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'Coverage + gate', 'command' => 'composer test:coverage && composer coverage:gate',
            'job' => 'php-coverage', 'needsDocker' => false, 'slow' => true],
        ['name' => 'Lighthouse budget', 'command' => 'npx --yes @lhci/cli@0.13.x autorun --config=lighthouserc.json',
            'job' => 'site-audits', 'needsDocker' => true, 'slow' => true],
        ['name' => 'axe sweep (every template, both modes)', 'command' => 'node tools/axe/run.mjs',
            'job' => 'site-audits', 'needsDocker' => true, 'slow' => true],
        ['name' => 'End-to-end journeys', 'command' => 'npm run test:e2e',
            'job' => 'site-audits', 'needsDocker' => true, 'slow' => true],
        ['name' => 'JS lint and format', 'command' => 'npm run lint', 'job' => 'js-quality',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'JS unit tests', 'command' => 'npm test', 'job' => 'js-quality',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'Admin bundle build', 'command' => 'npm run build', 'job' => 'js-quality',
            'needsDocker' => false, 'slow' => false],
        ['name' => 'WordPress integration tests', 'command' => 'npx wp-env start && composer test:integration',
            'job' => 'php-integration', 'needsDocker' => true, 'slow' => true],
    ];
}

function dockerIsRunning(): bool
{
    exec('docker info 2>/dev/null', $output, $status);

    return $status === 0;
}

/**
 * @param array{name:string, command:string, job:string, needsDocker:bool, slow:bool} $step
 */
function run(array $step): bool
{
    printf("\n\033[1m→ %s\033[0m  (%s)\n", $step['name'], $step['job']);

    passthru($step['command'], $status);

    if ($status === 0) {
        printf("\033[32m  passed\033[0m\n");

        return true;
    }

    printf("\033[31m  FAILED (exit %d)\033[0m\n", $status);

    return false;
}

$isQuick = in_array('--quick', $argv, true);
$hasDocker = dockerIsRunning();

$failed = [];
$skipped = [];

foreach (steps() as $step) {
    if ($step['needsDocker'] && !$hasDocker) {
        $skipped[] = sprintf(
            '%s — no Docker daemon; this runs in the %s CI job',
            $step['name'],
            $step['job']
        );

        continue;
    }

    if ($step['slow'] && $isQuick) {
        $skipped[] = $step['name'] . ' — skipped by --quick';

        continue;
    }

    if (!run($step)) {
        $failed[] = $step['name'];
    }
}

print "\n" . str_repeat('=', 72) . "\n";

foreach ($skipped as $reason) {
    printf("\033[33mSKIPPED\033[0m %s\n", $reason);
}

if ($failed === []) {
    printf("\033[32mAll runnable checks passed.\033[0m\n");

    if ($skipped !== []) {
        print "Some checks did not run here — they still have to pass on the pull request.\n";
    }

    exit(CI_EXIT_SUCCESS);
}

fwrite(STDERR, sprintf("\033[31m%d check(s) failed:\033[0m\n", count($failed)));

foreach ($failed as $name) {
    fwrite(STDERR, '  - ' . $name . "\n");
}

exit(CI_EXIT_FAILURE);
