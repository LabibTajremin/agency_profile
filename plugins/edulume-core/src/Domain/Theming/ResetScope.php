<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * How much of a configuration a reset throws away.
 *
 * Granular scopes exist because "reset everything" is almost never what someone means. They
 * usually mean "put the fonts back", and losing an afternoon of colour work to get there is
 * why people stop trusting a reset button.
 */
enum ResetScope: string
{
    case Everything = 'everything';
    case Colors = 'colors';
    case Typography = 'typography';
    case Patterns = 'patterns';
    case Motion = 'motion';
    case Layout = 'layout';
    case Sections = 'sections';

    /**
     * The settings keys this scope replaces with their defaults.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return match ($this) {
            self::Everything => ['accentSlug', 'customAccent', 'themePreference', 'offersModeToggle',
                'typography', 'pattern', 'motion', 'layout'],
            self::Colors => ['accentSlug', 'customAccent', 'themePreference', 'offersModeToggle'],
            self::Typography => ['typography'],
            self::Patterns => ['pattern'],
            self::Motion => ['motion'],
            self::Layout => ['layout'],
            self::Sections => [],
        };
    }

    public function clearsSectionOverrides(): bool
    {
        return $this === self::Everything || $this === self::Sections;
    }

    /**
     * A reset is irreversible from the visitor's point of view, so the admin makes the person
     * type the scope's name. A confirmation dialog people click through without reading is not
     * a confirmation.
     */
    public function confirmationPhrase(): string
    {
        return $this->value;
    }

    public function isConfirmedBy(string $typed): bool
    {
        return strtolower(trim($typed)) === $this->confirmationPhrase();
    }
}
