<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Security;

/**
 * Strips everything executable out of an uploaded SVG.
 *
 * An SVG is a document, not an image: it can carry `<script>`, event handlers, external
 * references and embedded foreign objects. Allowing uploads without this is handing every
 * contributor a stored-XSS primitive.
 *
 * The approach is an allowlist. A denylist of known-bad tags is a promise to keep up with
 * every attribute the specification adds, and that promise always gets broken.
 */
final class SvgSanitiser
{
    private const ALLOWED_ELEMENTS = [
        'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'defs', 'title', 'desc', 'linearGradient', 'radialGradient', 'stop', 'clipPath',
        'mask', 'pattern', 'use', 'symbol', 'text', 'tspan',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'id', 'class', 'd', 'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width',
        'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset',
        'stroke-opacity', 'opacity', 'transform', 'viewBox', 'width', 'height', 'x', 'y',
        'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'points', 'offset',
        'stop-color', 'stop-opacity', 'gradientUnits', 'gradientTransform', 'patternUnits',
        'clip-path', 'mask', 'xmlns', 'xmlns:xlink', 'preserveAspectRatio', 'text-anchor',
        'font-size', 'font-family', 'font-weight', 'aria-hidden', 'role', 'focusable',
    ];

    /**
     * A `use` or `image` reference may point inside the same document and nowhere else.
     */
    private const REFERENCE_ATTRIBUTES = ['href', 'xlink:href'];

    public function sanitise(string $markup): string
    {
        $document = $this->parse($markup);

        if ($document === null) {
            return '';
        }

        $root = $document->documentElement;

        if ($root === null || strtolower($root->nodeName) !== 'svg') {
            return '';
        }

        $this->cleanElement($root);

        return (string) $document->saveXML($root);
    }

    public function isSafe(string $markup): bool
    {
        $sanitised = $this->sanitise($markup);

        return $sanitised !== '' && $this->normalise($sanitised) === $this->normalise($markup);
    }

    private function parse(string $markup): ?\DOMDocument
    {
        if (trim($markup) === '' || str_contains(strtolower($markup), '<!doctype')) {
            return null;
        }

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        $loaded = $document->loadXML($markup, LIBXML_NONET | LIBXML_NOENT);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $document : null;
    }

    private function cleanElement(\DOMElement $element): void
    {
        foreach (iterator_to_array($element->childNodes) as $child) {
            if ($child instanceof \DOMElement) {
                if (!in_array($child->nodeName, self::ALLOWED_ELEMENTS, true)) {
                    $element->removeChild($child);

                    continue;
                }

                $this->cleanElement($child);

                continue;
            }

            if ($child instanceof \DOMProcessingInstruction || $child instanceof \DOMComment) {
                $element->removeChild($child);
            }
        }

        $this->cleanAttributes($element);
    }

    private function cleanAttributes(\DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            if (!$attribute instanceof \DOMAttr) {
                continue;
            }

            $name = $attribute->nodeName;

            if (in_array($name, self::REFERENCE_ATTRIBUTES, true)) {
                if (!str_starts_with(trim($attribute->nodeValue ?? ''), '#')) {
                    $element->removeAttribute($name);
                }

                continue;
            }

            if (!in_array($name, self::ALLOWED_ATTRIBUTES, true) || $this->carriesAScript($attribute)) {
                $element->removeAttribute($name);
            }
        }
    }

    /**
     * `fill="url(javascript:…)"` and its relatives: an allowed attribute holding a disallowed
     * value.
     */
    private function carriesAScript(\DOMAttr $attribute): bool
    {
        $value = strtolower(preg_replace('/\s+/', '', $attribute->nodeValue ?? '') ?? '');

        foreach (['javascript:', 'data:text/html', 'vbscript:', '&#106;avascript'] as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalise(string $markup): string
    {
        return preg_replace('/\s+/', ' ', trim($markup)) ?? $markup;
    }
}
