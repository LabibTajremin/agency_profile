<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Tracking;

use Edulume\Core\Application\Lead\DeliverLeadToIntegrations;
use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadWebhookPayload;
use Edulume\Core\Domain\Lead\RetrySchedule;
use Edulume\Core\Domain\Tracking\ConsentCategory;
use Edulume\Core\Domain\Tracking\ConsentState;
use Edulume\Core\Domain\Tracking\TrackingSettings;
use Edulume\Core\Tests\Unit\Fake\FixedClock;
use Edulume\Core\Tests\Unit\Fake\RecordingIntegrationLog;
use Edulume\Core\Tests\Unit\Fake\StubCrmConnector;
use Edulume\Core\Tests\Unit\Fake\StubWebhookTransport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IntegrationsAndTrackingTest extends TestCase
{
    private const NOW = '2026-03-01T09:00:00+00:00';
    private const TIMESTAMP = 1772355600;
    private const SECRET = 'a-shared-secret';

    private static function lead(): Lead
    {
        return Lead::captured('Amina Rahman', 'amina@example.test', '+8801700000000', 'enquiry', [], self::NOW);
    }

    /**
     * @param list<\Edulume\Core\Application\Port\CrmConnector> $connectors
     */
    private function deliver(
        StubWebhookTransport $transport,
        RecordingIntegrationLog $log,
        array $connectors = []
    ): DeliverLeadToIntegrations {
        return new DeliverLeadToIntegrations(
            $transport,
            $log,
            new FixedClock(self::NOW, self::TIMESTAMP),
            $connectors,
        );
    }

    #[Test]
    public function it_fires_a_signed_webhook_on_a_new_lead(): void
    {
        $transport = StubWebhookTransport::alwaysAccepting();
        $log = new RecordingIntegrationLog();

        $report = ($this->deliver($transport, $log))(self::lead(), 'https://hooks.example.test/lead', self::SECRET);

        $this->assertSame(1, $report->webhookAttempts);
        $this->assertTrue($report->everythingSucceeded());
        $this->assertSame('https://hooks.example.test/lead', $transport->lastUrl);
        $this->assertArrayHasKey(LeadWebhookPayload::SIGNATURE_HEADER, $transport->lastHeaders);
    }

    #[Test]
    public function it_signs_the_exact_bytes_it_sends(): void
    {
        $payload = LeadWebhookPayload::forLead(self::lead(), self::TIMESTAMP);

        $this->assertTrue($payload->isSignedWith($payload->signatureFor(self::SECRET), self::SECRET));
        $this->assertFalse($payload->isSignedWith($payload->signatureFor('another-secret'), self::SECRET));
    }

    #[Test]
    public function it_puts_the_timestamp_inside_the_signed_material(): void
    {
        $lead = self::lead();

        $early = LeadWebhookPayload::forLead($lead, self::TIMESTAMP);
        $later = LeadWebhookPayload::forLead($lead, self::TIMESTAMP + 60);

        $this->assertNotSame($early->signatureFor(self::SECRET), $later->signatureFor(self::SECRET));
        $this->assertFalse($later->isSignedWith($early->signatureFor(self::SECRET), self::SECRET));
    }

    #[Test]
    public function it_carries_the_lead_and_the_event_in_the_body(): void
    {
        $payload = LeadWebhookPayload::forLead(self::lead(), self::TIMESTAMP);

        $this->assertStringContainsString('"event":"lead.created"', $payload->body);
        $this->assertStringContainsString('amina@example.test', $payload->body);
        $this->assertSame((string) self::TIMESTAMP, $payload->headersFor(self::SECRET)[LeadWebhookPayload::TIMESTAMP_HEADER]);
    }

    #[Test]
    public function it_retries_a_refused_webhook_and_eventually_gives_up(): void
    {
        $transport = StubWebhookTransport::alwaysRefusing();
        $log = new RecordingIntegrationLog();

        $report = ($this->deliver($transport, $log))(self::lead(), 'https://hooks.example.test/lead', self::SECRET);

        $this->assertSame(RetrySchedule::MAXIMUM_ATTEMPTS, $report->webhookAttempts);
        $this->assertSame(RetrySchedule::MAXIMUM_ATTEMPTS, $transport->attempts);
        $this->assertCount(RetrySchedule::MAXIMUM_ATTEMPTS, $log->failures);
    }

    #[Test]
    public function it_stops_retrying_as_soon_as_the_receiver_accepts(): void
    {
        $transport = StubWebhookTransport::refusingTimes(2);

        $report = ($this->deliver($transport, new RecordingIntegrationLog()))(
            self::lead(),
            'https://hooks.example.test/lead',
            self::SECRET,
        );

        $this->assertSame(3, $report->webhookAttempts);
    }

    #[Test]
    public function it_backs_off_further_on_each_attempt_and_then_stops(): void
    {
        $schedule = new RetrySchedule();

        $this->assertSame(60, $schedule->delayAfter(1));
        $this->assertSame(120, $schedule->delayAfter(2));
        $this->assertSame(240, $schedule->delayAfter(3));
        $this->assertFalse($schedule->shouldRetryAfter(RetrySchedule::MAXIMUM_ATTEMPTS));
        $this->assertSame(0, $schedule->delayAfter(RetrySchedule::MAXIMUM_ATTEMPTS));
    }

    #[Test]
    public function it_sends_nothing_when_no_webhook_is_configured(): void
    {
        $transport = StubWebhookTransport::alwaysAccepting();

        $report = ($this->deliver($transport, new RecordingIntegrationLog()))(self::lead(), '', self::SECRET);

        $this->assertSame(0, $report->webhookAttempts);
        $this->assertSame(0, $transport->attempts);
    }

    #[Test]
    public function it_keeps_going_when_one_connector_refuses(): void
    {
        $log = new RecordingIntegrationLog();

        $report = ($this->deliver(StubWebhookTransport::alwaysAccepting(), $log, [
            StubCrmConnector::configured('Mailchimp', false),
            StubCrmConnector::configured('Brevo', true),
            StubCrmConnector::configured('HubSpot', true),
        ]))(self::lead(), '', self::SECRET);

        $this->assertSame(['Brevo', 'HubSpot'], $report->delivered);
        $this->assertSame(['Mailchimp'], $report->failed);
        $this->assertFalse($report->everythingSucceeded());
        $this->assertSame('Mailchimp', $log->failures[0]['integration']);
    }

    #[Test]
    public function it_skips_a_connector_nobody_configured(): void
    {
        $report = ($this->deliver(StubWebhookTransport::alwaysAccepting(), new RecordingIntegrationLog(), [
            StubCrmConnector::notConfigured('Google Sheets'),
            StubCrmConnector::configured('Brevo', true),
        ]))(self::lead(), '', self::SECRET);

        $this->assertSame(['Brevo'], $report->delivered);
        $this->assertSame([], $report->failed);
    }

    #[Test]
    public function it_loads_no_tracker_before_the_visitor_has_decided(): void
    {
        $settings = TrackingSettings::of('G-12345', '998877', 'GTM-ABCDE');

        $this->assertSame([], $settings->scriptsToLoad(ConsentState::undecided()));
        $this->assertCount(3, $settings->configuredScripts());
    }

    #[Test]
    public function it_loads_no_tracker_when_the_visitor_rejects_everything(): void
    {
        $settings = TrackingSettings::of('G-12345', '998877', 'GTM-ABCDE');

        $this->assertSame([], $settings->scriptsToLoad(ConsentState::rejectingEverything()));
    }

    #[Test]
    public function it_loads_only_the_categories_the_visitor_agreed_to(): void
    {
        $settings = TrackingSettings::of('G-12345', '998877', 'GTM-ABCDE');

        $analyticsOnly = $settings->scriptsToLoad(ConsentState::granting([ConsentCategory::Analytics]));
        $ids = array_map(static fn ($script): string => $script->id, $analyticsOnly);

        $this->assertSame([TrackingSettings::GOOGLE_ANALYTICS, TrackingSettings::GOOGLE_TAG_MANAGER], $ids);
    }

    #[Test]
    public function it_loads_a_marketing_tracker_only_with_marketing_consent(): void
    {
        $settings = TrackingSettings::of('', '998877', '');

        $this->assertSame([], $settings->scriptsToLoad(ConsentState::granting([ConsentCategory::Analytics])));
        $this->assertCount(1, $settings->scriptsToLoad(ConsentState::granting([ConsentCategory::Marketing])));
    }

    #[Test]
    public function it_never_loads_a_tracker_that_was_never_configured(): void
    {
        $settings = TrackingSettings::none();

        $everythingGranted = ConsentState::granting(ConsentCategory::cases());

        $this->assertSame([], $settings->scriptsToLoad($everythingGranted));
        $this->assertSame([], $settings->configuredScripts());
        $this->assertSame([], $settings->categoriesInUse());
    }

    #[Test]
    public function it_always_allows_the_necessary_category(): void
    {
        $this->assertTrue(ConsentState::undecided()->allows(ConsentCategory::Necessary));
        $this->assertTrue(ConsentState::rejectingEverything()->allows(ConsentCategory::Necessary));
        $this->assertTrue(ConsentCategory::Necessary->isAlwaysAllowed());
        $this->assertFalse(ConsentCategory::Analytics->isAlwaysAllowed());
    }

    #[Test]
    public function it_reports_only_the_categories_a_site_actually_uses(): void
    {
        $this->assertSame(
            [ConsentCategory::Analytics],
            TrackingSettings::of('G-12345', '', 'GTM-ABCDE')->categoriesInUse(),
        );
    }

    /**
     * @return array<string, array{ConsentState}>
     */
    public static function consentStateProvider(): array
    {
        return [
            'undecided' => [ConsentState::undecided()],
            'rejecting' => [ConsentState::rejectingEverything()],
            'analytics only' => [ConsentState::granting([ConsentCategory::Analytics])],
            'everything' => [ConsentState::granting(ConsentCategory::cases())],
        ];
    }

    #[Test]
    #[DataProvider('consentStateProvider')]
    public function it_round_trips_a_consent_state(ConsentState $consent): void
    {
        $restored = ConsentState::fromArray($consent->toArray());

        $this->assertSame($consent->toArray(), $restored->toArray());
        $this->assertSame($consent->hasDecided, $restored->hasDecided);
    }

    #[Test]
    public function it_round_trips_the_tracking_settings(): void
    {
        $settings = TrackingSettings::of('G-12345', '998877', 'GTM-ABCDE', false);

        $this->assertSame($settings->toArray(), TrackingSettings::fromArray($settings->toArray())->toArray());
        $this->assertFalse(TrackingSettings::fromArray($settings->toArray())->showsConsentBanner);
    }
}
