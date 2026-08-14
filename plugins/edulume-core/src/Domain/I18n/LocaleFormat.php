<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\I18n;

use DateTimeImmutable;

/**
 * Locale-aware dates, numbers and currency.
 *
 * The reason this exists rather than `number_format()` at each call site: an audience split
 * across the Gulf, South Asia and Europe reads the same figure three different ways, and a
 * tuition fee printed as `1,200,000` to someone who expects `12,00,000` looks like a typo in
 * the one number the whole page is about.
 *
 * Uses `intl` when it is available and falls back to explicit rules when it is not, because
 * shared hosting frequently ships without the extension and a fatal there is not acceptable.
 */
final class LocaleFormat
{
    private readonly bool $hasIntl;

    /**
     * `$hasIntl` is a parameter rather than an `extension_loaded()` call inside each method.
     *
     * Shared hosting frequently ships without `intl`, so the fallback path is production code on
     * real sites — and a fallback that only runs where no test can reach it is a fallback nobody
     * has ever seen work. Passing null keeps the production behaviour of detecting it.
     */
    public function __construct(public readonly string $locale = 'en_US', ?bool $hasIntl = null)
    {
        $this->hasIntl = $hasIntl ?? extension_loaded('intl');
    }

    /**
     * The locales the product ships translated. Everything else is translation-ready.
     *
     * @return list<string>
     */
    public static function shippedLocales(): array
    {
        return ['en_US', 'ar', 'bn_BD'];
    }

    /**
     * The right-to-left locales, by language subtag.
     *
     * @return list<string>
     */
    public static function rtlLanguages(): array
    {
        return ['ar', 'he', 'fa', 'ur', 'ps', 'dv', 'ku', 'sd', 'yi'];
    }

    public function isRtl(): bool
    {
        return in_array($this->language(), self::rtlLanguages(), true);
    }

    /** The `dir` attribute value for `<html>`. */
    public function direction(): string
    {
        return $this->isRtl() ? 'rtl' : 'ltr';
    }

    public function language(): string
    {
        $separator = strpbrk($this->locale, '_-');

        return strtolower($separator === false ? $this->locale : substr($this->locale, 0, -strlen($separator)));
    }

    /**
     * Formats an integer or decimal for reading.
     *
     * South Asian locales group the leading digits in pairs after the first three — the lakh and
     * crore system — which no amount of thousands-separator configuration produces.
     */
    public function number(float $value, int $decimals = 0): string
    {
        if ($this->hasIntl) {
            $formatter = new \NumberFormatter($this->locale, \NumberFormatter::DECIMAL);
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);

            return (string) $formatter->format($value);
        }

        return $this->usesIndianGrouping()
            ? $this->indianGrouped($value, $decimals)
            : number_format($value, $decimals, '.', ',');
    }

    /**
     * Formats an amount with its currency.
     *
     * The code is passed rather than derived from the locale: a Dhaka consultancy quotes
     * Australian tuition in AUD, and deriving the currency from the reader's locale would
     * silently relabel it as BDT.
     */
    public function currency(float $amount, string $currencyCode, int $decimals = 0): string
    {
        if ($this->hasIntl) {
            $formatter = new \NumberFormatter($this->locale, \NumberFormatter::CURRENCY);
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);

            return (string) $formatter->formatCurrency($amount, strtoupper($currencyCode));
        }

        return sprintf('%s %s', strtoupper($currencyCode), $this->number($amount, $decimals));
    }

    /**
     * A date in the reader's conventional order.
     *
     * Without `intl` this falls back to ISO 8601 rather than to a guess. `03/04/2026` is the
     * third of April to most of the world and the fourth of March in the United States, and a
     * course deadline is exactly the wrong thing to be ambiguous about.
     */
    public function date(DateTimeImmutable $moment): string
    {
        if ($this->hasIntl) {
            $formatter = new \IntlDateFormatter(
                $this->locale,
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::NONE,
            );

            return (string) $formatter->format($moment);
        }

        return $moment->format('Y-m-d');
    }

    private function usesIndianGrouping(): bool
    {
        return in_array($this->language(), ['bn', 'hi', 'ne', 'ta', 'te', 'ur', 'pa', 'gu', 'mr'], true);
    }

    private function indianGrouped(float $value, int $decimals): string
    {
        $formatted = number_format(abs($value), $decimals, '.', '');
        [$whole, $fraction] = array_pad(explode('.', $formatted, 2), 2, '');

        if (strlen($whole) <= 3) {
            $grouped = $whole;
        } else {
            $last = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $grouped = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . $last;
        }

        $sign = $value < 0 ? '-' : '';

        return $sign . $grouped . ($fraction === '' ? '' : '.' . $fraction);
    }
}
