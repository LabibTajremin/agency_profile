<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Licence;

use DateTimeImmutable;
use Edulume\Core\Application\Licence\ManageLicence;
use Edulume\Core\Domain\Licence\Licence;
use Edulume\Core\Domain\Licence\LicenceResponse;
use Edulume\Core\Domain\Licence\LicenceStatus;
use Edulume\Core\Tests\Unit\Fake\FixedClock;
use Edulume\Core\Tests\Unit\Fake\StubLicenceServer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LicenceTest extends TestCase
{
    private const SITE = 'https://example.test';

    private function at(string $moment): FixedClock
    {
        $when = new DateTimeImmutable($moment);

        return new FixedClock($when->format('Y-m-d H:i:s'), $when->getTimestamp());
    }

    private function expiring(string $moment): Licence
    {
        return new Licence('EDU-1234-5678-ABCD', new DateTimeImmutable($moment), 3, 1, false, true);
    }

    #[Test]
    public function no_licence_state_ever_stops_the_product_working(): void
    {
        foreach (LicenceStatus::cases() as $status) {
            self::assertTrue($status->allowsUse(), $status->value);
            self::assertNotSame('', $status->label(), $status->value);
        }
    }

    #[Test]
    public function only_an_active_or_grace_licence_receives_updates_and_support(): void
    {
        foreach (LicenceStatus::cases() as $status) {
            $expected = $status === LicenceStatus::Active || $status === LicenceStatus::Grace;

            self::assertSame($expected, $status->allowsUpdates(), $status->value);
            self::assertSame($expected, $status->allowsSupport(), $status->value);
        }
    }

    #[Test]
    public function only_an_active_licence_needs_no_attention_and_urgency_is_graded(): void
    {
        self::assertFalse(LicenceStatus::Active->needsAttention());
        self::assertTrue(LicenceStatus::Expired->needsAttention());
        self::assertSame('none', LicenceStatus::Active->noticeUrgency());
        self::assertSame('info', LicenceStatus::Unlicensed->noticeUrgency());
        self::assertSame('info', LicenceStatus::Grace->noticeUrgency());
        self::assertSame('warning', LicenceStatus::Expired->noticeUrgency());
        self::assertSame('warning', LicenceStatus::SiteLimitReached->noticeUrgency());
        self::assertSame('error', LicenceStatus::Revoked->noticeUrgency());
    }

    #[Test]
    public function a_site_with_no_key_is_unlicensed_rather_than_broken(): void
    {
        $status = Licence::none()->statusAt(new DateTimeImmutable('2026-06-01'));

        self::assertSame(LicenceStatus::Unlicensed, $status);
        self::assertTrue($status->allowsUse());
        self::assertFalse(Licence::none()->hasKey());
        self::assertFalse((new Licence('   '))->hasKey());
    }

    #[Test]
    public function a_licence_moves_through_active_grace_and_expired(): void
    {
        $licence = $this->expiring('2026-06-01T00:00:00+00:00');

        self::assertSame(LicenceStatus::Active, $licence->statusAt(new DateTimeImmutable('2026-05-31T23:00:00+00:00')));
        self::assertSame(LicenceStatus::Active, $licence->statusAt(new DateTimeImmutable('2026-06-01T00:00:00+00:00')));
        self::assertSame(LicenceStatus::Grace, $licence->statusAt(new DateTimeImmutable('2026-06-10T00:00:00+00:00')));
        self::assertSame(LicenceStatus::Grace, $licence->statusAt(new DateTimeImmutable('2026-06-15T00:00:00+00:00')));
        self::assertSame(LicenceStatus::Expired, $licence->statusAt(new DateTimeImmutable('2026-06-16T00:00:00+00:00')));
    }

    #[Test]
    public function a_lifetime_licence_never_expires(): void
    {
        $licence = new Licence('EDU-LIFETIME', null, 1, 1, false, true);

        self::assertSame(LicenceStatus::Active, $licence->statusAt(new DateTimeImmutable('2099-01-01')));
    }

    #[Test]
    public function a_revoked_licence_outranks_every_other_state(): void
    {
        $licence = new Licence('EDU-1', new DateTimeImmutable('2020-01-01'), 1, 9, true, false);

        self::assertSame(LicenceStatus::Revoked, $licence->statusAt(new DateTimeImmutable('2026-06-01')));
    }

    #[Test]
    public function a_site_beyond_the_limit_is_told_the_thing_it_can_fix(): void
    {
        $licence = new Licence('EDU-1', new DateTimeImmutable('2020-01-01'), 2, 2, false, false);

        self::assertSame(LicenceStatus::SiteLimitReached, $licence->statusAt(new DateTimeImmutable('2026-06-01')));
        self::assertFalse($licence->hasSeatAvailable());
        self::assertSame(0, $licence->seatsRemaining());
    }

    #[Test]
    public function an_already_activated_site_is_not_counted_against_the_limit_again(): void
    {
        $licence = new Licence('EDU-1', null, 2, 2, false, true);

        self::assertTrue($licence->hasSeatAvailable());
        self::assertSame(LicenceStatus::Active, $licence->statusAt(new DateTimeImmutable('2026-06-01')));
    }

    #[Test]
    public function activating_and_deactivating_move_the_seat_count_once_each(): void
    {
        $now = new DateTimeImmutable('2026-06-01');
        $licence = new Licence('EDU-1', null, 3, 1);

        $activated = $licence->activatedHere($now);

        self::assertSame(2, $activated->activationCount);
        self::assertTrue($activated->isActivatedHere);
        self::assertSame(2, $activated->activatedHere($now)->activationCount);

        $deactivated = $activated->deactivatedHere($now);

        self::assertSame(1, $deactivated->activationCount);
        self::assertFalse($deactivated->isActivatedHere);
        self::assertSame(1, $deactivated->deactivatedHere($now)->activationCount);
        self::assertSame(2, $licence->seatsRemaining());
        self::assertSame(1, $activated->seatsRemaining());
    }

    #[Test]
    public function a_licence_is_rechecked_daily_and_not_more_often(): void
    {
        $licence = (new Licence('EDU-1'))->checkedAt(new DateTimeImmutable('2026-06-01T09:00:00+00:00'));

        self::assertFalse($licence->isDueForCheck(new DateTimeImmutable('2026-06-01T20:00:00+00:00')));
        self::assertTrue($licence->isDueForCheck(new DateTimeImmutable('2026-06-02T09:00:00+00:00')));
        self::assertTrue((new Licence('EDU-1'))->isDueForCheck(new DateTimeImmutable('2026-06-01')));
    }

    #[Test]
    public function the_key_is_masked_for_display(): void
    {
        self::assertSame('••••••••••••••ABCD', (new Licence('EDU-1234-5678-ABCD'))->maskedKey());
        self::assertSame('••••', (new Licence('ABCD'))->maskedKey());
        self::assertSame('', Licence::none()->maskedKey());
    }

    #[Test]
    public function activating_stores_what_the_server_returned(): void
    {
        $server = new StubLicenceServer(new LicenceResponse(
            true,
            true,
            new DateTimeImmutable('2027-01-01T00:00:00+00:00'),
            5,
            2,
        ));

        $licence = (new ManageLicence($server, $this->at('2026-06-01T00:00:00+00:00')))
            ->activate(' EDU-1234 ', self::SITE);

        self::assertSame('EDU-1234', $licence->key);
        self::assertSame(5, $licence->siteLimit);
        self::assertSame(2, $licence->activationCount);
        self::assertSame(['activate'], $server->methodsCalled());
    }

    #[Test]
    public function an_empty_key_is_not_sent_to_the_server_at_all(): void
    {
        $server = new StubLicenceServer();
        $licence = (new ManageLicence($server, $this->at('2026-06-01')))->activate('   ', self::SITE);

        self::assertFalse($licence->hasKey());
        self::assertSame([], $server->methodsCalled());
    }

    #[Test]
    public function a_vendor_outage_during_activation_still_stores_the_key(): void
    {
        $server = new StubLicenceServer(LicenceResponse::unreachable());
        $licence = (new ManageLicence($server, $this->at('2026-06-01')))->activate('EDU-1234', self::SITE);

        self::assertSame('EDU-1234', $licence->key);
        self::assertNull($licence->lastCheckedAt);
        self::assertTrue($licence->isDueForCheck(new DateTimeImmutable('2026-06-01')));
    }

    #[Test]
    public function a_rejected_key_is_stored_but_not_marked_activated_here(): void
    {
        $server = new StubLicenceServer(LicenceResponse::invalid('That key does not exist.'));
        $licence = (new ManageLicence($server, $this->at('2026-06-01')))->activate('EDU-WRONG', self::SITE);

        self::assertFalse($licence->isActivatedHere);
    }

    #[Test]
    public function deactivating_releases_the_key_locally_even_if_the_server_is_unreachable(): void
    {
        $server = new StubLicenceServer(LicenceResponse::unreachable());
        $licence = (new ManageLicence($server, $this->at('2026-06-01')))
            ->deactivate($this->expiring('2027-01-01'), self::SITE);

        self::assertFalse($licence->hasKey());
        self::assertSame(['deactivate'], $server->methodsCalled());
    }

    #[Test]
    public function deactivating_a_site_with_no_key_asks_the_server_nothing(): void
    {
        $server = new StubLicenceServer();

        (new ManageLicence($server, $this->at('2026-06-01')))->deactivate(Licence::none(), self::SITE);

        self::assertSame([], $server->methodsCalled());
    }

    #[Test]
    public function the_daily_check_does_not_run_early(): void
    {
        $server = new StubLicenceServer();
        $licence = (new Licence('EDU-1'))->checkedAt(new DateTimeImmutable('2026-06-01T09:00:00+00:00'));

        $refreshed = (new ManageLicence($server, $this->at('2026-06-01T12:00:00+00:00')))
            ->refresh($licence, self::SITE);

        self::assertSame($licence, $refreshed);
        self::assertSame([], $server->methodsCalled());
    }

    #[Test]
    public function the_daily_check_updates_what_the_server_now_says(): void
    {
        $server = new StubLicenceServer(new LicenceResponse(true, true, null, 5, 3, true));
        $licence = (new Licence('EDU-1', isActivatedHere: true))
            ->checkedAt(new DateTimeImmutable('2026-06-01T09:00:00+00:00'));

        $refreshed = (new ManageLicence($server, $this->at('2026-06-03T09:00:00+00:00')))
            ->refresh($licence, self::SITE);

        self::assertTrue($refreshed->isRevoked);
        self::assertSame(3, $refreshed->activationCount);
        self::assertSame(['check'], $server->methodsCalled());
    }

    #[Test]
    public function an_unreachable_check_leaves_everything_alone_and_retries_next_request(): void
    {
        $server = new StubLicenceServer(LicenceResponse::unreachable());
        $licence = (new Licence('EDU-1', isActivatedHere: true))
            ->checkedAt(new DateTimeImmutable('2026-06-01T09:00:00+00:00'));

        $refreshed = (new ManageLicence($server, $this->at('2026-06-03T09:00:00+00:00')))
            ->refresh($licence, self::SITE);

        self::assertSame($licence, $refreshed);
        self::assertTrue($refreshed->isDueForCheck(new DateTimeImmutable('2026-06-03T09:00:00+00:00')));
    }

    #[Test]
    public function a_site_with_no_key_is_never_checked(): void
    {
        $server = new StubLicenceServer();

        (new ManageLicence($server, $this->at('2026-06-01')))->refresh(Licence::none(), self::SITE);

        self::assertSame([], $server->methodsCalled());
    }

    #[Test]
    public function an_update_is_offered_to_an_active_licence_when_a_newer_version_exists(): void
    {
        $server = new StubLicenceServer();
        $server->release = ['version' => '1.4.0', 'package' => 'https://example.test/x.zip', 'changelog' => 'Fixes'];

        $update = (new ManageLicence($server, $this->at('2026-06-01')))
            ->availableUpdate($this->expiring('2027-01-01'), '1.3.0', self::SITE);

        self::assertSame('1.4.0', $update['version'] ?? null);
    }

    #[Test]
    public function an_update_already_installed_is_not_offered_again(): void
    {
        $server = new StubLicenceServer();
        $server->release = ['version' => '1.3.0', 'package' => 'https://example.test/x.zip', 'changelog' => ''];

        $manager = new ManageLicence($server, $this->at('2026-06-01'));

        self::assertNull($manager->availableUpdate($this->expiring('2027-01-01'), '1.3.0', self::SITE));
        self::assertNull($manager->availableUpdate($this->expiring('2027-01-01'), '1.4.0', self::SITE));
    }

    #[Test]
    public function a_server_with_nothing_to_say_offers_no_update(): void
    {
        $server = new StubLicenceServer();

        self::assertNull(
            (new ManageLicence($server, $this->at('2026-06-01')))
                ->availableUpdate($this->expiring('2027-01-01'), '1.3.0', self::SITE),
        );
    }

    #[Test]
    public function an_expired_licence_blocks_the_update_and_nothing_else(): void
    {
        $server = new StubLicenceServer();
        $server->release = ['version' => '9.9.9', 'package' => 'https://example.test/x.zip', 'changelog' => ''];

        $manager = new ManageLicence($server, $this->at('2026-06-01T00:00:00+00:00'));
        $expired = $this->expiring('2026-01-01T00:00:00+00:00');

        self::assertSame(LicenceStatus::Expired, $manager->statusOf($expired));
        self::assertNull($manager->availableUpdate($expired, '1.0.0', self::SITE));
        self::assertTrue($manager->statusOf($expired)->allowsUse());
        // The server is never even asked, so an expired licence costs no outbound request.
        self::assertSame([], $server->methodsCalled());
    }

    #[Test]
    public function a_licence_inside_the_grace_period_still_receives_updates(): void
    {
        $server = new StubLicenceServer();
        $server->release = ['version' => '2.0.0', 'package' => 'https://example.test/x.zip', 'changelog' => ''];

        $manager = new ManageLicence($server, $this->at('2026-06-05T00:00:00+00:00'));
        $licence = $this->expiring('2026-06-01T00:00:00+00:00');

        self::assertSame(LicenceStatus::Grace, $manager->statusOf($licence));
        self::assertSame('2.0.0', $manager->availableUpdate($licence, '1.0.0', self::SITE)['version'] ?? null);
    }

    #[Test]
    public function an_unreachable_response_never_overwrites_what_the_site_believes(): void
    {
        self::assertFalse(LicenceResponse::unreachable()->shouldReplaceStoredLicence());
        self::assertTrue(LicenceResponse::invalid('no')->shouldReplaceStoredLicence());
        self::assertSame('Could not reach the licence server.', LicenceResponse::unreachable()->message);
        self::assertSame('no', LicenceResponse::invalid('no')->message);
    }
}
