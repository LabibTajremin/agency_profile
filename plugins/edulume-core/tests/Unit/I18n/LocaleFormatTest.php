<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\I18n;

use DateTimeImmutable;
use Edulume\Core\Domain\I18n\LocaleFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LocaleFormatTest extends TestCase
{
    /** The formatter with `intl` deliberately unavailable — what shared hosting gets. */
    private function withoutIntl(string $locale): LocaleFormat
    {
        return new LocaleFormat($locale, false);
    }

    private function withIntl(string $locale): LocaleFormat
    {
        return new LocaleFormat($locale, true);
    }

    #[Test]
    public function it_ships_english_arabic_and_bengali_translated(): void
    {
        self::assertSame(['en_US', 'ar', 'bn_BD'], LocaleFormat::shippedLocales());
    }

    /**
     * @return list<array{string, bool, string}>
     */
    public static function directions(): array
    {
        return [
            ['en_US', false, 'ltr'],
            ['ar', true, 'rtl'],
            ['ar_AE', true, 'rtl'],
            ['bn_BD', false, 'ltr'],
            ['ur_PK', true, 'rtl'],
            ['he-IL', true, 'rtl'],
        ];
    }

    #[Test]
    #[DataProvider('directions')]
    public function it_resolves_direction_from_the_language_subtag(
        string $locale,
        bool $isRtl,
        string $direction
    ): void {
        $format = new LocaleFormat($locale);

        self::assertSame($isRtl, $format->isRtl());
        self::assertSame($direction, $format->direction());
    }

    #[Test]
    public function it_reads_the_language_out_of_either_separator(): void
    {
        self::assertSame('en', (new LocaleFormat('en_US'))->language());
        self::assertSame('pt', (new LocaleFormat('pt-BR'))->language());
        self::assertSame('ar', (new LocaleFormat('ar'))->language());
    }

    #[Test]
    public function a_south_asian_locale_groups_digits_in_lakhs_without_intl(): void
    {
        self::assertSame('12,00,000', $this->withoutIntl('bn_BD')->number(1200000));
        self::assertSame('1,00,000', $this->withoutIntl('hi_IN')->number(100000));
        self::assertSame('999', $this->withoutIntl('bn_BD')->number(999));
        self::assertSame('-12,00,000', $this->withoutIntl('bn_BD')->number(-1200000));
        self::assertSame('1,00,000.50', $this->withoutIntl('bn_BD')->number(100000.5, 2));
    }

    #[Test]
    public function a_western_locale_groups_in_thousands_without_intl(): void
    {
        self::assertSame('1,200,000', $this->withoutIntl('en_US')->number(1200000));
        self::assertSame('1,200.75', $this->withoutIntl('en_GB')->number(1200.75, 2));
    }

    #[Test]
    public function it_formats_a_number_in_every_shipped_locale_through_intl(): void
    {
        if (!extension_loaded('intl')) {
            self::markTestSkipped('This asserts the intl path, which needs the extension present.');
        }

        self::assertSame('1,200,000', $this->withIntl('en_US')->number(1200000));

        foreach (LocaleFormat::shippedLocales() as $locale) {
            self::assertNotSame('', $this->withIntl($locale)->number(1200000), $locale);
        }
    }

    #[Test]
    public function the_currency_comes_from_the_amount_not_from_the_reader(): void
    {
        // Without intl the code is printed verbatim, which is the whole point: a Dhaka
        // consultancy quoting Australian tuition must not have it relabelled as BDT.
        self::assertSame('AUD 42,000', $this->withoutIntl('bn_BD')->currency(42000, 'aud'));
        self::assertSame('GBP 9,500', $this->withoutIntl('en_GB')->currency(9500, 'GBP'));
    }

    #[Test]
    public function the_currency_carries_its_own_symbol_through_intl(): void
    {
        if (!extension_loaded('intl')) {
            self::markTestSkipped('This asserts the intl path, which needs the extension present.');
        }

        self::assertStringContainsString('42,000', $this->withIntl('en_US')->currency(42000, 'aud'));
    }

    #[Test]
    public function a_date_without_intl_falls_back_to_iso_rather_than_to_a_guess(): void
    {
        $moment = new DateTimeImmutable('2026-04-03T00:00:00+00:00');

        self::assertSame('2026-04-03', $this->withoutIntl('en_GB')->date($moment));
        self::assertSame('2026-04-03', $this->withoutIntl('en_US')->date($moment));
    }

    #[Test]
    public function a_date_through_intl_is_never_left_ambiguous(): void
    {
        if (!extension_loaded('intl')) {
            self::markTestSkipped('This asserts the intl path, which needs the extension present.');
        }

        $formatted = $this->withIntl('en_GB')->date(new DateTimeImmutable('2026-04-03T00:00:00+00:00'));

        self::assertStringNotContainsString('03/04', $formatted);
        self::assertStringNotContainsString('04/03', $formatted);
        self::assertStringContainsString('2026', $formatted);
    }

    #[Test]
    public function detection_is_what_production_gets_when_nothing_is_passed(): void
    {
        // Whichever way the extension falls on this machine, the constructor must not throw and
        // must produce something.
        self::assertNotSame('', (new LocaleFormat('en_US'))->number(1000));
    }
}
