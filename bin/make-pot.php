<?php

/**
 * Generates `languages/edulume.pot` from the source.
 *
 * Written here rather than delegated to `wp i18n make-pot` because that needs WP-CLI, and the
 * template has to be regenerable in CI — a `.pot` that is only ever produced on one maintainer's
 * laptop drifts out of date the first week they are on holiday.
 *
 * Usage: php bin/make-pot.php [output-path]
 *
 * @package Edulume
 */

declare(strict_types=1);

const POT_TEXT_DOMAIN = 'edulume';

const POT_SOURCES = [
    'plugins/edulume-core/src',
    'plugins/edulume-core/edulume-core.php',
    'themes/edulume-theme',
    'themes/edulume-child',
];

/**
 * Each translation function, and which argument positions carry translatable text.
 *
 * The plural and context forms take more than one, and getting the positions wrong silently
 * exports the context string as if it were the message.
 *
 * @return array<string, array{singular: int, plural?: int, context?: int}>
 */
function translationFunctions(): array
{
    return [
        '__' => ['singular' => 0],
        '_e' => ['singular' => 0],
        'esc_html__' => ['singular' => 0],
        'esc_html_e' => ['singular' => 0],
        'esc_attr__' => ['singular' => 0],
        'esc_attr_e' => ['singular' => 0],
        '_x' => ['singular' => 0, 'context' => 1],
        '_ex' => ['singular' => 0, 'context' => 1],
        'esc_html_x' => ['singular' => 0, 'context' => 1],
        'esc_attr_x' => ['singular' => 0, 'context' => 1],
        '_n' => ['singular' => 0, 'plural' => 1],
        '_nx' => ['singular' => 0, 'plural' => 1, 'context' => 3],
    ];
}

/**
 * @return list<string>
 */
function sourceFiles(): array
{
    $files = [];

    foreach (POT_SOURCES as $source) {
        $path = dirname(__DIR__) . '/' . $source;

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

/**
 * The literal string arguments of a translation call, in order.
 *
 * Returns null the moment an argument is not a plain literal: `__($label, 'edulume')` cannot be
 * extracted, and guessing would put a variable name in the template.
 *
 * @param list<array{int, string, int}|string> $tokens
 *
 * @return array{args: list<string>, line: int}|null
 */
function literalArguments(array $tokens, int $start): ?array
{
    $args = [];
    $depth = 0;
    $current = null;
    $line = is_array($tokens[$start]) ? $tokens[$start][2] : 0;

    for ($index = $start + 1, $count = count($tokens); $index < $count; $index++) {
        $token = $tokens[$index];
        $text = is_array($token) ? $token[1] : $token;

        if ($text === '(') {
            $depth++;

            if ($depth === 1) {
                continue;
            }
        }

        if ($text === ')') {
            $depth--;

            if ($depth === 0) {
                if ($current !== null) {
                    $args[] = $current;
                }

                return ['args' => $args, 'line' => $line];
            }
        }

        if ($depth !== 1) {
            continue;
        }

        if ($text === ',') {
            $args[] = $current ?? '';
            $current = null;

            continue;
        }

        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $current = stripcslashes(substr($token[1], 1, -1));

            continue;
        }

        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        // Anything else in argument position means the call is not statically extractable.
        return null;
    }

    return null;
}

/**
 * @return array<string, array{singular: string, plural: ?string, context: ?string, references: list<string>}>
 */
function extractEntries(): array
{
    $entries = [];
    $functions = translationFunctions();
    $root = dirname(__DIR__) . '/';

    foreach (sourceFiles() as $path) {
        $tokens = token_get_all((string) file_get_contents($path));

        foreach ($tokens as $index => $token) {
            if (!is_array($token) || $token[0] !== T_STRING || !isset($functions[$token[1]])) {
                continue;
            }

            $shape = $functions[$token[1]];
            $call = literalArguments($tokens, $index);

            if ($call === null || !isset($call['args'][$shape['singular']])) {
                continue;
            }

            $singular = $call['args'][$shape['singular']];
            $plural = isset($shape['plural']) ? ($call['args'][$shape['plural']] ?? null) : null;
            $context = isset($shape['context']) ? ($call['args'][$shape['context']] ?? null) : null;
            $key = ($context ?? '') . "\x04" . $singular . "\x04" . ($plural ?? '');
            $reference = str_replace($root, '', $path) . ':' . $call['line'];

            if (!isset($entries[$key])) {
                $entries[$key] = [
                    'singular' => $singular,
                    'plural' => $plural,
                    'context' => $context,
                    'references' => [],
                ];
            }

            $entries[$key]['references'][] = $reference;
        }
    }

    ksort($entries);

    return $entries;
}

function poString(string $value): string
{
    return '"' . addcslashes($value, "\0..\37\"\\") . '"';
}

/**
 * @param array<string, array{singular: string, plural: ?string, context: ?string, references: list<string>}> $entries
 */
function renderPot(array $entries): string
{
    $out = <<<POT
    # Copyright (C) Edulume
    # This file is distributed under the same licence as the Edulume package.
    msgid ""
    msgstr ""
    "Project-Id-Version: Edulume\\n"
    "Report-Msgid-Bugs-To: https://example.test/support\\n"
    "MIME-Version: 1.0\\n"
    "Content-Type: text/plain; charset=UTF-8\\n"
    "Content-Transfer-Encoding: 8bit\\n"
    "X-Domain: edulume\\n"

    POT;

    foreach ($entries as $entry) {
        $out .= "\n";

        foreach ($entry['references'] as $reference) {
            $out .= '#: ' . $reference . "\n";
        }

        if ($entry['context'] !== null) {
            $out .= 'msgctxt ' . poString($entry['context']) . "\n";
        }

        $out .= 'msgid ' . poString($entry['singular']) . "\n";

        if ($entry['plural'] !== null) {
            $out .= 'msgid_plural ' . poString($entry['plural']) . "\n";
            $out .= "msgstr[0] \"\"\n";
            $out .= "msgstr[1] \"\"\n";

            continue;
        }

        $out .= "msgstr \"\"\n";
    }

    return $out;
}

$output = $argv[1] ?? dirname(__DIR__) . '/languages/edulume.pot';
$entries = extractEntries();

if (!is_dir(dirname($output))) {
    mkdir(dirname($output), 0o755, true);
}

file_put_contents($output, renderPot($entries));

printf("Wrote %d string(s) to %s\n", count($entries), $output);
