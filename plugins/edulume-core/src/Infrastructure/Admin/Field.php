<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

/**
 * The form controls the settings screens are built from.
 *
 * One printer per kind rather than one generic printer taking a type string: each kind escapes
 * a different set of attributes, and a generic printer ends up interpolating an attribute
 * string that neither a reviewer nor the escaping audit can prove is safe.
 *
 * Every control is a real form control inside a real form. The screens post to
 * `admin-post.php`, so they work with no script at all — which matters more here than anywhere
 * else in the product, because a settings page that needs a bundle to draw is a settings page
 * that is blank when the bundle fails.
 */
final class Field
{
    /**
     * @param array<string, string> $options
     */
    public static function select(
        string $name,
        string $label,
        string $value,
        array $options,
        string $help = ''
    ): void {
        $id = self::idOf($name);

        self::open($id, $label);

        printf('<select class="edulume-field__input" id="%1$s" name="%2$s">', esc_attr($id), esc_attr($name));

        foreach ($options as $optionValue => $optionLabel) {
            printf(
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr((string) $optionValue),
                selected((string) $optionValue, $value, false),
                esc_html($optionLabel)
            );
        }

        echo '</select>';

        self::close($help);
    }

    public static function toggle(string $name, string $label, bool $checked, string $help = ''): void
    {
        $id = self::idOf($name);

        echo '<div class="edulume-field edulume-field--toggle">';

        printf(
            '<input type="checkbox" class="edulume-field__checkbox" id="%1$s" name="%2$s" value="1"%3$s />',
            esc_attr($id),
            esc_attr($name),
            checked($checked, true, false)
        );

        printf('<label class="edulume-field__label" for="%1$s">%2$s</label>', esc_attr($id), esc_html($label));

        self::close($help);
    }

    public static function range(
        string $name,
        string $label,
        float $value,
        float $min,
        float $max,
        float $step,
        string $help = ''
    ): void {
        $id = self::idOf($name);

        self::open($id, $label);

        printf(
            '<input type="range" class="edulume-field__range" id="%1$s" name="%2$s"'
            . ' value="%3$s" min="%4$s" max="%5$s" step="%6$s" data-edulume-range />'
            . '<output class="edulume-field__output" for="%1$s">%3$s</output>',
            esc_attr($id),
            esc_attr($name),
            esc_attr(self::number($value)),
            esc_attr(self::number($min)),
            esc_attr(self::number($max)),
            esc_attr(self::number($step))
        );

        self::close($help);
    }

    public static function integer(
        string $name,
        string $label,
        int $value,
        int $min,
        int $max,
        string $help = '',
        string $suffix = ''
    ): void {
        $id = self::idOf($name);

        self::open($id, $label);

        /*
         * The numbers are escaped and printed as strings rather than passed to `%d`.
         *
         * They are integers and could not be anything else — but the escaping audit reads a
         * `%d` as unescaped output, and being right for a reason the reader has to reconstruct
         * from the type signature is not being clear.
         */
        printf(
            '<input type="number" class="edulume-field__input edulume-field__input--short"'
            . ' id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="1" />',
            esc_attr($id),
            esc_attr($name),
            esc_attr((string) $value),
            esc_attr((string) $min),
            esc_attr((string) $max)
        );

        if ($suffix !== '') {
            printf('<span class="edulume-field__suffix">%s</span>', esc_html($suffix));
        }

        self::close($help);
    }

    public static function text(
        string $name,
        string $label,
        string $value,
        string $help = '',
        string $placeholder = ''
    ): void {
        $id = self::idOf($name);

        self::open($id, $label);

        printf(
            '<input type="text" class="edulume-field__input" id="%1$s" name="%2$s"'
            . ' value="%3$s" placeholder="%4$s" />',
            esc_attr($id),
            esc_attr($name),
            esc_attr($value),
            esc_attr($placeholder)
        );

        self::close($help);
    }

    /**
     * A colour, entered two ways.
     *
     * The picker and the text box carry the same value and the same name is impossible, so the
     * text box is the one that submits and the picker writes into it. With no script the text
     * box still works on its own, which is why it is the one that is authoritative.
     */
    public static function color(string $name, string $label, string $value, string $help = ''): void
    {
        $id = self::idOf($name);

        self::open($id, $label);

        printf(
            '<span class="edulume-field__color">'
            . '<input type="color" class="edulume-field__swatch" value="%1$s"'
            . ' aria-hidden="true" tabindex="-1" data-edulume-color-for="%2$s" />'
            . '<input type="text" class="edulume-field__input edulume-field__input--hex" id="%2$s"'
            . ' name="%3$s" value="%4$s" placeholder="#123a6b" pattern="#?[0-9a-fA-F]{6}" />'
            . '</span>',
            esc_attr($value === '' ? '#123a6b' : $value),
            esc_attr($id),
            esc_attr($name),
            esc_attr($value)
        );

        self::close($help);
    }

    public static function hidden(string $name, string $value): void
    {
        printf('<input type="hidden" name="%1$s" value="%2$s" />', esc_attr($name), esc_attr($value));
    }

    /**
     * A group of fields under a heading.
     */
    public static function openGroup(string $title, string $summary = ''): void
    {
        printf('<section class="edulume-group"><h2 class="edulume-group__title">%s</h2>', esc_html($title));

        if ($summary !== '') {
            printf('<p class="edulume-group__summary">%s</p>', esc_html($summary));
        }

        echo '<div class="edulume-group__fields">';
    }

    public static function closeGroup(): void
    {
        echo '</div></section>';
    }

    private static function open(string $id, string $label): void
    {
        echo '<div class="edulume-field">';

        printf('<label class="edulume-field__label" for="%1$s">%2$s</label>', esc_attr($id), esc_html($label));
    }

    private static function close(string $help): void
    {
        if ($help !== '') {
            printf('<p class="edulume-field__help">%s</p>', esc_html($help));
        }

        echo '</div>';
    }

    /**
     * `settings[layout][density]` is not a usable id, and a label whose `for` matches nothing is
     * a label that does not move focus when clicked.
     */
    private static function idOf(string $name): string
    {
        return 'edulume-' . trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-');
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
