<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * The field types a form can be built from.
 *
 * Telephone is its own type rather than a text field with a pattern: this audience is
 * international, and a number typed without a country code is a lead nobody can call back.
 */
enum FormFieldType: string
{
    case Text = 'text';
    case Email = 'email';
    case Telephone = 'telephone';
    case Textarea = 'textarea';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Consent = 'consent';
    case Date = 'date';
    case Number = 'number';
    case File = 'file';
    case Hidden = 'hidden';

    public function needsOptions(): bool
    {
        return $this === self::Select || $this === self::Radio || $this === self::Checkbox;
    }

    public function isAlwaysRequired(): bool
    {
        return $this === self::Consent;
    }

    public function showsCountryFlag(): bool
    {
        return $this === self::Telephone;
    }
}
