<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Chrome;

use DateTimeImmutable;
use Edulume\Core\Domain\Chrome\AnnouncementBar;
use Edulume\Core\Domain\Chrome\ChromeSettings;
use Edulume\Core\Domain\Chrome\ContactDetails;
use Edulume\Core\Domain\Chrome\FaviconSet;
use Edulume\Core\Domain\Chrome\FloatingAction;
use Edulume\Core\Domain\Chrome\FooterVariant;
use Edulume\Core\Domain\Chrome\HeaderVariant;
use Edulume\Core\Domain\Chrome\LogoSet;
use Edulume\Core\Domain\Chrome\LogoSlot;
use Edulume\Core\Domain\Theming\ThemeMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ChromeTest extends TestCase
{
    #[Test]
    public function it_ships_six_header_variants_each_with_its_own_template_part(): void
    {
        $variants = HeaderVariant::all();
        $parts = array_map(static fn (HeaderVariant $v): string => $v->templatePart(), $variants);

        self::assertCount(6, $variants);
        self::assertSame($parts, array_unique($parts));
    }

    #[Test]
    public function it_ships_four_footer_variants_with_sensible_column_counts(): void
    {
        self::assertCount(4, FooterVariant::all());
        self::assertSame(4, FooterVariant::Columns->columnCount());
        self::assertSame(1, FooterVariant::Centred->columnCount());
        self::assertSame(2, FooterVariant::Compact->columnCount());
        self::assertSame(3, FooterVariant::ContactFirst->columnCount());
    }

    #[Test]
    public function only_the_transparent_variant_starts_transparent(): void
    {
        foreach (HeaderVariant::all() as $variant) {
            self::assertSame(
                $variant === HeaderVariant::TransparentHero,
                $variant->isTransparent(),
                $variant->value,
            );
        }
    }

    #[Test]
    public function the_two_row_variants_are_the_ones_that_stack_the_logo(): void
    {
        self::assertTrue(HeaderVariant::Centred->isTwoRow());
        self::assertTrue(HeaderVariant::Stacked->isTwoRow());
        self::assertFalse(HeaderVariant::Classic->isTwoRow());
    }

    #[Test]
    public function every_header_and_footer_variant_has_a_label(): void
    {
        foreach (HeaderVariant::all() as $variant) {
            self::assertNotSame('', $variant->label());
        }

        foreach (FooterVariant::all() as $variant) {
            self::assertNotSame('', $variant->label());
            self::assertStringContainsString($variant->value, $variant->templatePart());
        }
    }

    #[Test]
    public function an_announcement_runs_between_its_dates(): void
    {
        $bar = new AnnouncementBar(
            true,
            'September intake closes soon',
            'https://example.test/apply',
            'Apply',
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-01-31T23:59:59+00:00'),
        );

        self::assertFalse($bar->isVisibleAt(new DateTimeImmutable('2025-12-31T23:59:59+00:00')));
        self::assertTrue($bar->isVisibleAt(new DateTimeImmutable('2026-01-15T12:00:00+00:00')));
        self::assertFalse($bar->isVisibleAt(new DateTimeImmutable('2026-02-01T00:00:00+00:00')));
        self::assertTrue($bar->hasLink());
    }

    #[Test]
    public function an_announcement_with_no_dates_runs_until_it_is_switched_off(): void
    {
        $bar = new AnnouncementBar(true, 'Open days every Saturday');

        self::assertTrue($bar->isVisibleAt(new DateTimeImmutable('2030-06-01T00:00:00+00:00')));
        self::assertFalse($bar->hasLink());
    }

    #[Test]
    public function an_empty_or_disabled_announcement_never_renders(): void
    {
        self::assertFalse(AnnouncementBar::disabled()->isVisibleAt(new DateTimeImmutable('now')));
        self::assertFalse((new AnnouncementBar(true, '   '))->isVisibleAt(new DateTimeImmutable('now')));
    }

    #[Test]
    public function rewriting_the_message_invalidates_a_previous_dismissal(): void
    {
        $first = new AnnouncementBar(true, 'January intake');
        $second = new AnnouncementBar(true, 'February intake');

        self::assertNotSame($first->dismissalKey(), $second->dismissalKey());
        self::assertSame($first->dismissalKey(), (new AnnouncementBar(true, 'January intake'))->dismissalKey());
    }

    #[Test]
    public function contact_details_build_dialable_and_whatsapp_urls(): void
    {
        $contact = new ContactDetails('+971 4 555 0100', '+971 50 555 0100', 'hello@example.test');

        self::assertSame('tel:+97145550100', $contact->telUrl());
        self::assertSame('https://wa.me/971505550100', $contact->whatsappUrl());
        self::assertSame('mailto:hello@example.test', $contact->mailtoUrl());
        self::assertTrue($contact->hasAny());
    }

    #[Test]
    public function a_local_number_keeps_no_plus_and_an_empty_one_yields_nothing(): void
    {
        self::assertSame('tel:02055501', (new ContactDetails('(020) 5550-1'))->telUrl());
        self::assertSame('', (new ContactDetails('   '))->telUrl());
        self::assertSame('', (new ContactDetails())->whatsappUrl());
        self::assertSame('', (new ContactDetails())->mailtoUrl());
        self::assertFalse((new ContactDetails())->hasAny());
        self::assertTrue((new ContactDetails(officeHours: 'Sun-Thu 9-6'))->hasAny());
    }

    #[Test]
    public function a_floating_action_with_no_configured_destination_is_not_offered(): void
    {
        $contact = new ContactDetails(phone: '+97145550100');

        self::assertTrue(FloatingAction::Call->isAvailable($contact));
        self::assertFalse(FloatingAction::Whatsapp->isAvailable($contact));
        self::assertFalse(FloatingAction::BookCounselling->isAvailable($contact));
        self::assertTrue(FloatingAction::BackToTop->isAvailable($contact));
    }

    #[Test]
    public function only_back_to_top_needs_script(): void
    {
        foreach (FloatingAction::all() as $action) {
            self::assertSame($action === FloatingAction::BackToTop, $action->isScriptDriven());
            self::assertNotSame('', $action->label());
            self::assertNotSame('', $action->icon());
        }
    }

    #[Test]
    public function the_settings_hide_a_utility_bar_with_nothing_to_say(): void
    {
        self::assertFalse((new ChromeSettings(hasUtilityBar: true))->showsUtilityBar());
        self::assertTrue(
            (new ChromeSettings(hasUtilityBar: true, contact: new ContactDetails(phone: '+97145550100')))
                ->showsUtilityBar(),
        );
        self::assertFalse(
            (new ChromeSettings(hasUtilityBar: false, contact: new ContactDetails(phone: '+97145550100')))
                ->showsUtilityBar(),
        );
    }

    #[Test]
    public function the_settings_drop_floating_actions_that_have_nowhere_to_go(): void
    {
        $settings = new ChromeSettings(
            floatingActions: [FloatingAction::Whatsapp, FloatingAction::Call, FloatingAction::BackToTop],
            contact: new ContactDetails(whatsapp: '+971505550100'),
        );

        self::assertSame(
            [FloatingAction::Whatsapp, FloatingAction::BackToTop],
            $settings->visibleFloatingActions(),
        );
    }

    #[Test]
    public function the_settings_round_trip_losslessly(): void
    {
        $settings = new ChromeSettings(
            HeaderVariant::Split,
            FooterVariant::ContactFirst,
            false,
            true,
            true,
            true,
            false,
            'Built by Example Consultancy',
            [FloatingAction::Whatsapp, FloatingAction::BackToTop],
            new AnnouncementBar(
                true,
                'Open day on Saturday',
                'https://example.test/open-day',
                'Reserve a seat',
                new DateTimeImmutable('2026-03-01T09:00:00+00:00'),
                new DateTimeImmutable('2026-03-08T18:00:00+00:00'),
                false,
            ),
            new ContactDetails('+97145550100', '+971505550100', 'hi@example.test', 'Sun-Thu', 'https://cal.test'),
            new LogoSet('a.svg', 'a-dark.svg', 'm.svg', 'm-dark.svg', 'f.svg', 'f-dark.svg', 'Example'),
        );

        self::assertEquals($settings, ChromeSettings::fromArray($settings->toArray()));
    }

    #[Test]
    public function unreadable_stored_chrome_settings_fall_back_rather_than_fataling(): void
    {
        $settings = ChromeSettings::fromArray([
            'headerVariant' => 'does-not-exist',
            'footerVariant' => 42,
            'floatingActions' => ['whatsapp', 'whatsapp', 'nonsense', 'call'],
            'announcement' => ['isEnabled' => true, 'message' => 'Hi', 'startsAt' => 'not a date'],
            'contact' => 'not an array',
        ]);

        self::assertSame(HeaderVariant::Classic, $settings->headerVariant);
        self::assertSame(FooterVariant::Columns, $settings->footerVariant);
        self::assertSame([FloatingAction::Whatsapp, FloatingAction::Call], $settings->floatingActions);
        self::assertNull($settings->announcement->startsAt);
        self::assertSame('', $settings->contact->phone);
    }

    #[Test]
    public function a_dark_mode_logo_falls_back_along_a_chain_that_always_ends_somewhere(): void
    {
        $full = new LogoSet('light.svg', 'dark.svg', 'mark.svg', 'mark-dark.svg');

        self::assertSame('dark.svg', $full->resolve(LogoSlot::Header, ThemeMode::Dark));
        self::assertSame('mark-dark.svg', $full->resolve(LogoSlot::Mobile, ThemeMode::Dark));
        self::assertSame('light.svg', $full->resolve(LogoSlot::Header, ThemeMode::Light));
    }

    #[Test]
    public function one_uploaded_logo_still_fills_every_slot_in_both_modes(): void
    {
        $single = new LogoSet('only.svg');

        foreach ([LogoSlot::Header, LogoSlot::Mobile, LogoSlot::Footer] as $slot) {
            self::assertSame('only.svg', $single->resolve($slot, ThemeMode::Light), $slot->value);
            self::assertSame('only.svg', $single->resolve($slot, ThemeMode::Dark), $slot->value);
        }

        self::assertTrue($single->hasAny());
        self::assertFalse((new LogoSet())->hasAny());
        self::assertSame('', (new LogoSet())->resolve(LogoSlot::Header, ThemeMode::Dark));
    }

    #[Test]
    public function the_favicon_set_generates_every_size_a_platform_asks_for(): void
    {
        $set = new FaviconSet('/uploads/mark.png', '#1b7f79', '#ffffff', 'Example Consultancy', 'Example');

        self::assertSame([16, 32, 48, 96, 180, 192, 512], array_keys($set->files()));
        self::assertSame(array_keys($set->files()), array_keys($set->purposes()));
        self::assertSame('edulume-icon-180x180.png', $set->files()[180]);
    }

    #[Test]
    public function the_favicon_set_emits_link_tags_and_a_manifest(): void
    {
        $set = new FaviconSet('/uploads/mark.png', '#1b7f79', '#ffffff', 'Example Consultancy');
        $tags = $set->linkTags('https://example.test/icons/');
        $manifest = $set->manifest('https://example.test/icons/');

        self::assertSame(
            ['icon', 'icon', 'apple-touch-icon', 'manifest'],
            array_map(static fn (array $tag): string => $tag['rel'], $tags),
        );
        self::assertSame('https://example.test/icons/site.webmanifest', $tags[3]['href']);
        self::assertSame('Example Consultancy', $manifest['short_name']);
        self::assertSame('browser', $manifest['display']);
        self::assertCount(2, $manifest['icons']);
        self::assertStringContainsString('"theme_color": "#1b7f79"', $set->manifestJson('https://example.test/icons/'));
    }

    /**
     * @return list<array{int, int, bool}>
     */
    public static function faviconUploads(): array
    {
        return [
            [512, 512, true],
            [1024, 1024, true],
            [511, 511, false],
            [512, 256, false],
        ];
    }

    #[Test]
    #[DataProvider('faviconUploads')]
    public function it_refuses_a_favicon_upload_it_would_have_to_scale_up(int $width, int $height, bool $expected): void
    {
        self::assertSame($expected, FaviconSet::accepts($width, $height));
    }
}
