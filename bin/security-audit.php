<?php

/**
 * The security gate.
 *
 * Four rules, each chosen because breaking it is silent — the site keeps working, and nobody
 * finds out until someone is looking for a way in:
 *
 * 1. No `eval`, no `create_function`, no executing a `base64_decode`, no fetching code at
 *    runtime. Legitimate WordPress products do not need any of them, and every one of them is
 *    on the list of things a compromised plugin is found doing.
 * 2. Every `$wpdb` query goes through `prepare()`. Interpolating a variable into SQL is the
 *    bug that keeps coming back.
 * 3. Every REST route declares a `permission_callback`. WordPress will happily register a
 *    route without one, which is a public endpoint nobody meant to publish.
 * 4. Nothing reads `$_GET`, `$_POST`, `$_REQUEST` or `$_COOKIE` without a sanitiser on the same
 *    line.
 *
 * Usage: php bin/security-audit.php
 *
 * @package Edulume
 */

declare(strict_types=1);

const SECURITY_EXIT_SUCCESS = 0;
const SECURITY_EXIT_FAILURE = 1;

const SECURITY_DIRECTORIES = [
    'plugins/edulume-core/src',
    'plugins/edulume-core/edulume-core.php',
    'plugins/edulume-core/uninstall.php',
    'themes/edulume-theme',
    'themes/edulume-child',
];

/**
 * @return array<string, string>
 */
function forbiddenConstructs(): array
{
    return [
        '/\beval\s*\(/i' => 'eval() executes arbitrary code',
        '/\bcreate_function\s*\(/i' => 'create_function() is eval() wearing a hat',
        '/\bassert\s*\(\s*[\'"]/i' => 'assert() on a string executes it',
        '/(eval|include|require)\s*\(\s*base64_decode/i' => 'executing a base64_decode payload',
        '/\b(?:file_get_contents|curl_exec)\s*\([^)]*\)\s*\)?\s*;\s*(?:eval|include)/i'
            => 'fetching and executing code at runtime',
        '/\bextract\s*\(\s*\$_(?:GET|POST|REQUEST)/i' => 'extract() over request data creates arbitrary variables',
        '/\bunserialize\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE)/i' => 'unserialize() of request data is object injection',
    ];
}

/**
 * @return list<string>
 */
function securityFiles(): array
{
    $files = [];

    foreach (SECURITY_DIRECTORIES as $entry) {
        $path = dirname(__DIR__) . '/' . $entry;

        if (is_file($path)) {
            $files[] = $path;

            continue;
        }

        if (!is_dir($path)) {
            continue;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    sort($files);

    return $files;
}

function isSecurityComment(string $line): bool
{
    $trimmed = ltrim($line);

    return str_starts_with($trimmed, '*')
        || str_starts_with($trimmed, '/*')
        || str_starts_with($trimmed, '//');
}

/**
 * @return list<string>
 */
function forbiddenConstructFindings(string $path, string $contents): array
{
    $findings = [];

    foreach (explode("\n", $contents) as $number => $line) {
        if (isSecurityComment($line)) {
            continue;
        }

        foreach (forbiddenConstructs() as $pattern => $reason) {
            if (preg_match($pattern, $line) === 1) {
                $findings[] = sprintf('%s:%d — %s', $path, $number + 1, $reason);
            }
        }
    }

    return $findings;
}

/**
 * A `$wpdb` query method called with something other than a `prepare()` result or a bare
 * literal.
 *
 * @return list<string>
 */
function unpreparedQueryFindings(string $path, string $contents): array
{
    $findings = [];
    $methods = 'query|get_results|get_row|get_col|get_var';

    if (preg_match_all('/\$wpdb->(' . $methods . ')\s*\((.*?)\);/s', $contents, $matches, PREG_OFFSET_CAPTURE) === false) {
        return $findings;
    }

    foreach ($matches[2] as $index => [$arguments, $offset]) {
        $statement = (string) $arguments;

        if (str_contains($statement, '$wpdb->prepare')) {
            continue;
        }

        // A statement with no variable in it cannot carry injected input. Table names built
        // from `$wpdb->prefix` are the common case and are not attacker-controlled.
        $withoutTable = str_replace(['$wpdb->prefix', '$table', '{$table}'], '', $statement);

        if (!str_contains($withoutTable, '$')) {
            continue;
        }

        $line = substr_count(substr($contents, 0, (int) $offset), "\n") + 1;
        $method = (string) ($matches[1][$index][0] ?? 'query');

        $findings[] = sprintf('%s:%d — $wpdb->%s() interpolates a variable without prepare()', $path, $line, $method);
    }

    return $findings;
}

/**
 * @return list<string>
 */
function unprotectedRouteFindings(string $path, string $contents): array
{
    $findings = [];

    if (preg_match_all('/register_rest_route\s*\((.*?)\);/s', $contents, $matches, PREG_OFFSET_CAPTURE) === false) {
        return $findings;
    }

    foreach ($matches[1] as [$arguments, $offset]) {
        if (str_contains((string) $arguments, 'permission_callback')) {
            continue;
        }

        $line = substr_count(substr($contents, 0, (int) $offset), "\n") + 1;

        $findings[] = sprintf('%s:%d — register_rest_route() with no permission_callback', $path, $line);
    }

    return $findings;
}

/**
 * Superglobal reads with no sanitiser on the same line.
 *
 * @return list<string>
 */
function unsanitisedInputFindings(string $path, string $contents): array
{
    $findings = [];
    $sanitisers = 'sanitize_|wp_unslash|absint|intval|floatval|filter_var|wp_verify_nonce'
        . '|check_admin_referer|check_ajax_referer|esc_|isset|empty|array_key_exists|wp_kses';

    foreach (explode("\n", $contents) as $number => $line) {
        if (isSecurityComment($line) || preg_match('/\$_(GET|POST|REQUEST|COOKIE)\b/', $line) !== 1) {
            continue;
        }

        if (preg_match('/(' . $sanitisers . ')/', $line) === 1) {
            continue;
        }

        $findings[] = sprintf('%s:%d — request data read without a sanitiser', $path, $number + 1);
    }

    return $findings;
}

/**
 * A bare variable handed to `printf`, `sprintf` or `echo` in theme markup.
 *
 * This mirrors `WordPress.Security.EscapeOutput.OutputNotEscaped`, which lives in WPCS — and
 * WPCS needs a network install that is not available in every environment this repository is
 * worked in. Twice now a pull request has gone red on this one sniff after a local gate said
 * green, which is a round trip for a rule that can be approximated here in thirty lines.
 *
 * Approximated, not reimplemented: it only looks at arguments that are a lone variable, which
 * is the shape that actually reaches production unescaped. `printf('%d', $id)` is safe by
 * conversion and still flagged, because the reader has to know printf's rules to see that and
 * the sniff never will.
 *
 * @return list<string>
 */
function unescapedOutputFindings(string $path, string $contents): array
{
    if (!str_contains($path, '/themes/')) {
        return [];
    }

    $findings = [];
    $tokens = @token_get_all($contents);
    $count = count($tokens);

    for ($index = 0; $index < $count; $index++) {
        $opening = printfCallOpensAt($tokens, $index, $count);

        if ($opening === null) {
            continue;
        }

        $arguments = callArguments($tokens, $opening, $count);

        if ($arguments === [] || !formatEmitsMarkup($arguments[0])) {
            continue;
        }

        foreach (array_slice($arguments, 1) as $candidate) {
            $bare = bareVariableIn($candidate);

            if ($bare !== null) {
                $findings[] = sprintf(
                    '%s:%d — output argument is not escaped: %s',
                    $path,
                    $bare[1],
                    $bare[0]
                );
            }
        }
    }

    return $findings;
}

/**
 * The offset of the `(` when the token at `$index` starts a `printf`-family call.
 *
 * @param list<array{0:int,1:string,2:int}|string> $tokens
 */
function printfCallOpensAt(array $tokens, int $index, int $count): ?int
{
    $token = $tokens[$index];

    if (!is_array($token) || $token[0] !== T_STRING) {
        return null;
    }

    if (!in_array(strtolower($token[1]), ['printf', 'sprintf', 'vsprintf'], true)) {
        return null;
    }

    $cursor = $index + 1;

    while ($cursor < $count && is_array($tokens[$cursor]) && $tokens[$cursor][0] === T_WHITESPACE) {
        $cursor++;
    }

    return $cursor < $count && $tokens[$cursor] === '(' ? $cursor : null;
}

/**
 * Splits a call's arguments at the commas that belong to it, ignoring nested ones.
 *
 * @param list<array{0:int,1:string,2:int}|string> $tokens
 *
 * @return list<list<array{0:int,1:string,2:int}|string>>
 */
function callArguments(array $tokens, int $opening, int $count): array
{
    $depth = 0;
    $argument = [];
    $arguments = [];

    for ($cursor = $opening; $cursor < $count; $cursor++) {
        $token = $tokens[$cursor];

        if ($token === '(' || $token === '[') {
            $depth++;

            if ($depth === 1) {
                continue;
            }
        }

        if ($token === ')' || $token === ']') {
            $depth--;

            if ($depth === 0) {
                $arguments[] = $argument;

                return $arguments;
            }
        }

        if ($depth === 1 && $token === ',') {
            $arguments[] = $argument;
            $argument = [];

            continue;
        }

        $argument[] = $token;
    }

    return $arguments;
}

/**
 * Whether a format string emits markup, which is the only case this rule is about.
 *
 * `sprintf(__('Footer column %d'), $column)` builds a sidebar name, not a page.
 *
 * @param list<array{0:int,1:string,2:int}|string> $argument
 */
function formatEmitsMarkup(array $argument): bool
{
    $format = '';

    foreach ($argument as $piece) {
        if (is_array($piece) && $piece[0] === T_CONSTANT_ENCAPSED_STRING) {
            $format .= $piece[1];
        }
    }

    return str_contains($format, '<');
}

/**
 * The variable and line, when an argument is nothing but a variable.
 *
 * Comments are dropped alongside whitespace. Leaving them in put a `T_COMMENT` first in the
 * token list whenever somebody explained an argument above it — which is exactly what the
 * escaping fix in `chrome.php` did, so the gate read its own explanation and found nothing.
 *
 * @param list<array{0:int,1:string,2:int}|string> $argument
 *
 * @return array{0:string,1:int}|null
 */
function bareVariableIn(array $argument): ?array
{
    $meaningful = array_values(array_filter(
        $argument,
        static fn ($piece): bool => !is_array($piece)
            || !in_array($piece[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
    ));

    if ($meaningful === []) {
        return null;
    }

    foreach ($meaningful as $piece) {
        // Any function call in the argument means somebody chose a treatment for it.
        if (is_array($piece) && $piece[0] === T_STRING) {
            return null;
        }
    }

    $first = $meaningful[0];

    return is_array($first) && $first[0] === T_VARIABLE ? [$first[1], $first[2]] : null;
}

$sections = [
    'Forbidden constructs' => [],
    'Prepared statements' => [],
    'REST permissions' => [],
    'Input sanitisation' => [],
    'Escaped output' => [],
];

foreach (securityFiles() as $path) {
    $contents = (string) file_get_contents($path);

    $sections['Forbidden constructs'] = [
        ...$sections['Forbidden constructs'],
        ...forbiddenConstructFindings($path, $contents),
    ];
    $sections['Prepared statements'] = [
        ...$sections['Prepared statements'],
        ...unpreparedQueryFindings($path, $contents),
    ];
    $sections['Escaped output'] = [
        ...$sections['Escaped output'],
        ...unescapedOutputFindings($path, $contents),
    ];
    $sections['REST permissions'] = [
        ...$sections['REST permissions'],
        ...unprotectedRouteFindings($path, $contents),
    ];
    $sections['Input sanitisation'] = [
        ...$sections['Input sanitisation'],
        ...unsanitisedInputFindings($path, $contents),
    ];
}

$failed = false;

foreach ($sections as $label => $findings) {
    if ($findings === []) {
        printf("%s: clean\n", $label);

        continue;
    }

    $failed = true;

    printf("%s: %d problem(s)\n", $label, count($findings));

    foreach ($findings as $finding) {
        printf("  - %s\n", $finding);
    }
}

exit($failed ? SECURITY_EXIT_FAILURE : SECURITY_EXIT_SUCCESS);
