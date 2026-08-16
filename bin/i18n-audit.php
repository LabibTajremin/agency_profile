<?php

/**
 * Two gates that keep the product translatable and mirrorable.
 *
 * 1. **No physical CSS direction.** `margin-left` is correct in English and wrong in Arabic.
 *    Logical properties — `margin-inline-start` — are correct in both, which is why the rule is
 *    "never a physical property" rather than "add an RTL stylesheet": a second stylesheet is a
 *    second thing to keep in step, and it always falls behind.
 *
 * 2. **No user-facing string outside a translation call.** A string that reaches the page
 *    without `__()` around it cannot be translated, and nothing at runtime complains — the
 *    site simply stays English for everyone.
 *
 * Usage: php bin/i18n-audit.php
 *
 * @package Edulume
 */

declare(strict_types=1);

const I18N_EXIT_SUCCESS = 0;
const I18N_EXIT_FAILURE = 1;

const CSS_DIRECTORIES = ['themes/edulume-theme/assets/css'];
const PHP_DIRECTORIES = ['themes/edulume-theme', 'themes/edulume-child'];

/**
 * Physical properties, and the logical property to use instead.
 *
 * `left`/`right` as box-offset properties are included; as *values* (`text-align: left`) they
 * are caught by the second table below.
 *
 * @return array<string, string>
 */
function physicalProperties(): array
{
    return [
        'margin-left' => 'margin-inline-start',
        'margin-right' => 'margin-inline-end',
        'padding-left' => 'padding-inline-start',
        'padding-right' => 'padding-inline-end',
        'border-left' => 'border-inline-start',
        'border-right' => 'border-inline-end',
        'border-top-left-radius' => 'border-start-start-radius',
        'border-top-right-radius' => 'border-start-end-radius',
        'border-bottom-left-radius' => 'border-end-start-radius',
        'border-bottom-right-radius' => 'border-end-end-radius',
        'left' => 'inset-inline-start',
        'right' => 'inset-inline-end',
        'width' => 'inline-size',
        'height' => 'block-size',
        'max-width' => 'max-inline-size',
        'min-width' => 'min-inline-size',
        'max-height' => 'max-block-size',
        'min-height' => 'min-block-size',
    ];
}

/**
 * Physical values, and the logical value to use instead.
 *
 * @return array<string, string>
 */
function physicalValues(): array
{
    return [
        'text-align: left' => 'text-align: start',
        'text-align: right' => 'text-align: end',
        'float: left' => 'float: inline-start',
        'float: right' => 'float: inline-end',
        'clear: left' => 'clear: inline-start',
        'clear: right' => 'clear: inline-end',
    ];
}

/**
 * Properties whose physical form is legitimate because they are not about direction.
 *
 * `width` on a media query, and the sizing shorthands inside a `grid-template`, describe a
 * viewport or a track, not an inline axis that mirrors.
 *
 * @return list<string>
 */
function exemptContexts(): array
{
    return ['@media', '@container', '@supports', 'grid-template', 'aspect-ratio', 'viewport'];
}

/**
 * @return list<string>
 */
function filesIn(string $directory, string $extension): array
{
    $root = dirname(__DIR__) . '/' . $directory;

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

function isComment(string $line): bool
{
    $trimmed = ltrim($line);

    return $trimmed === ''
        || str_starts_with($trimmed, '*')
        || str_starts_with($trimmed, '/*')
        || str_starts_with($trimmed, '//');
}

function isExempt(string $line): bool
{
    foreach (exemptContexts() as $context) {
        if (str_contains($line, $context)) {
            return true;
        }
    }

    return false;
}

/**
 * @return list<string>
 */
function logicalPropertyFindings(): array
{
    $findings = [];

    foreach (CSS_DIRECTORIES as $directory) {
        foreach (filesIn($directory, 'css') as $path) {
            foreach (explode("\n", (string) file_get_contents($path)) as $number => $line) {
                if (isComment($line) || isExempt($line)) {
                    continue;
                }

                foreach (physicalProperties() as $physical => $logical) {
                    // Anchored on the property position so `margin-left` does not also match
                    // inside `--edulume-margin-left-token`, and `left` does not match `left`
                    // appearing in a value such as `to left`.
                    if (preg_match('/(^|[;{\s])' . preg_quote($physical, '/') . '\s*:/i', $line) === 1) {
                        $findings[] = sprintf(
                            '%s:%d uses %s; use %s',
                            $path,
                            $number + 1,
                            $physical,
                            $logical
                        );
                    }
                }

                foreach (physicalValues() as $physical => $logical) {
                    if (stripos($line, $physical) !== false) {
                        $findings[] = sprintf(
                            '%s:%d uses "%s"; use "%s"',
                            $path,
                            $number + 1,
                            $physical,
                            $logical
                        );
                    }
                }
            }
        }
    }

    return $findings;
}

/**
 * Text a template writes straight to the page without a translation call around it.
 *
 * Tokenised rather than pattern-matched across raw lines. A line-based scan cannot tell markup
 * from a PHP string that happens to contain angle brackets, and the false positives it produces
 * — every `printf('<span>%s</span>', …)` in the codebase — are how a gate earns a reputation
 * for crying wolf and stops being read.
 *
 * @return list<string>
 */
function unwrappedStringFindings(): array
{
    $findings = [];

    foreach (PHP_DIRECTORIES as $directory) {
        foreach (filesIn($directory, 'php') as $path) {
            foreach (translatableTextIn(markupSkeletonOf($path)) as [$line, $text]) {
                $findings[] = sprintf('%s:%d has an untranslated string: "%s"', $path, $line, $text);
            }
        }
    }

    return $findings;
}

/**
 * The file's literal markup with every PHP region replaced by a placeholder.
 *
 * Reassembling the whole document matters: a template's tags are routinely split across PHP —
 * `<nav aria-label="<?php … ?>">` — so scanning the inline-HTML chunks separately leaves half
 * a tag in each and reports the attribute name as prose. Newlines inside each PHP region are
 * preserved so reported line numbers stay true.
 */
function markupSkeletonOf(string $path): string
{
    $skeleton = '';

    foreach (token_get_all((string) file_get_contents($path)) as $token) {
        if (is_array($token) && $token[0] === T_INLINE_HTML) {
            $skeleton .= $token[1];

            continue;
        }

        $source = is_array($token) ? $token[1] : $token;

        $skeleton .= '{{php}}' . str_repeat("\n", substr_count($source, "\n"));
    }

    return $skeleton;
}

/**
 * The text nodes in a document skeleton that a person would read.
 *
 * Two letters or more: one stray letter is punctuation or part of an entity, and flagging those
 * trains people to ignore the gate.
 *
 * @return list<array{int, string}> line number and text
 */
function translatableTextIn(string $skeleton): array
{
    $texts = [];
    $withoutTags = preg_replace('/<[^<>]*>/s', '', $skeleton) ?? $skeleton;

    foreach (explode("\n", $withoutTags) as $number => $candidate) {
        $stripped = trim(html_entity_decode(str_replace('{{php}}', ' ', $candidate), ENT_QUOTES | ENT_HTML5));

        if ($stripped !== '' && preg_match('/[A-Za-z]{2,}/', $stripped) === 1) {
            $texts[] = [$number + 1, $stripped];
        }
    }

    return $texts;
}

/**
 * User-facing English written directly into a script module.
 *
 * The audit read PHP only, and a string in a `.js` file is invisible to it and to `make:pot`
 * alike. The finder announced "Filtering…" and "3 results" to a screen reader in English on
 * every site, in every language, and nothing in the pipeline could see it.
 *
 * Scripts get their words from `window.edulumeStrings`, populated server-side. This catches the
 * next module that forgets.
 *
 * @return list<string>
 */
function untranslatedScriptFindings(): array
{
    $findings = [];

    foreach (filesIn('themes/edulume-theme/assets/js', 'js') as $path) {
        foreach (explode("\n", (string) file_get_contents($path)) as $number => $line) {
            if (isComment($line)) {
                continue;
            }

            // Only assignments that reach a person: text nodes, announcements, labels and
            // titles. A string used as a key, a selector or a storage name is not copy.
            $isUserFacing = preg_match(
                '/\b(textContent|innerText|announce|setAttribute\(\s*[\'"]aria-label[\'"]|title)\s*[=(]/',
                $line
            ) === 1;

            if (!$isUserFacing) {
                continue;
            }

            /*
             * Prose looks like a sentence: it either contains a space, or it starts with a
             * capital and continues in lower case. Attribute names and selectors — `aria-label`,
             * `data-edulume-finder`, `.edulume-card` — do neither.
             *
             * The first version of this test required a space, and so missed the exact line it
             * was written for: `announce('Filtering…')` is a single word. A gate that does not
             * fail on the bug that motivated it is decoration, which is why it gets run against
             * that bug before it is trusted.
             */
            $looksLikeProse = preg_match('/[\'"][^\'"]* [^\'"]*[\'"]/', $line) === 1
                || preg_match('/[\'"][A-Z][a-z]{2,}[^\'"]*[\'"]/', $line) === 1;

            if (!$looksLikeProse) {
                continue;
            }

            if (str_contains($line, 'edulumeStrings') || str_contains($line, 'text(')) {
                continue;
            }

            $findings[] = sprintf('%s:%d writes English into the page: %s', $path, $number + 1, trim($line));
        }
    }

    return $findings;
}

$sections = [
    'Logical CSS properties' => logicalPropertyFindings(),
    'Translated strings' => unwrappedStringFindings(),
    'Script strings' => untranslatedScriptFindings(),
];

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

exit($failed ? I18N_EXIT_FAILURE : I18N_EXIT_SUCCESS);
