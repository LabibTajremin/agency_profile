<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Lead;

use Edulume\Core\Application\Lead\EraseLead;
use Edulume\Core\Application\Lead\RegisterLead;
use Edulume\Core\Domain\Lead\ConditionOperator;
use Edulume\Core\Domain\Lead\FieldCondition;
use Edulume\Core\Domain\Lead\FormDefinition;
use Edulume\Core\Domain\Lead\FormField;
use Edulume\Core\Domain\Lead\FormFieldType;
use Edulume\Core\Domain\Lead\FormPlacement;
use Edulume\Core\Domain\Lead\RetentionPolicy;
use Edulume\Core\Domain\Lead\SpamPolicy;
use Edulume\Core\Tests\Unit\Fake\FixedClock;
use Edulume\Core\Tests\Unit\Fake\InMemoryLeadRepository;
use Edulume\Core\Tests\Unit\Fake\InMemoryUploadedFileStore;
use Edulume\Core\Tests\Unit\Fake\RecordingLeadNotifier;
use Edulume\Core\Tests\Unit\Fake\StubCaptchaVerifier;
use Edulume\Core\Tests\Unit\Fake\WindowedRateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegisterLeadTest extends TestCase
{
    private const NOW = '2026-03-01T09:00:00+00:00';
    private const IP = '203.0.113.9';
    private const HUMAN_SECONDS = 12;

    private InMemoryLeadRepository $leads;
    private WindowedRateLimiter $rateLimiter;
    private StubCaptchaVerifier $captcha;
    private RecordingLeadNotifier $notifier;

    protected function setUp(): void
    {
        $this->leads = new InMemoryLeadRepository();
        $this->rateLimiter = new WindowedRateLimiter(3);
        $this->captcha = StubCaptchaVerifier::notConfigured();
        $this->notifier = new RecordingLeadNotifier();
    }

    private function register(): RegisterLead
    {
        return new RegisterLead(
            $this->leads,
            $this->rateLimiter,
            $this->captcha,
            $this->notifier,
            new FixedClock(self::NOW, 1772355600),
        );
    }

    private static function form(): FormDefinition
    {
        return FormDefinition::of('counselling-enquiry', 'Book a counselling session', [
            FormField::of('name', 'Full name', FormFieldType::Text, true),
            FormField::of('email', 'Email', FormFieldType::Email, true),
            FormField::of('phone', 'Phone', FormFieldType::Telephone, true),
            FormField::of('destination', 'Destination', FormFieldType::Select, true, 1, ['Canada', 'UK', 'Australia']),
            FormField::of(
                'province',
                'Province',
                FormFieldType::Select,
                true,
                1,
                ['Ontario', 'British Columbia'],
                FieldCondition::of('destination', ConditionOperator::Equals, 'Canada'),
            ),
            FormField::of('consent', 'I agree to be contacted', FormFieldType::Consent, true, 1),
        ], FormPlacement::Inline, 365, 'We will keep your details for one year.');
    }

    /**
     * @return array<string, string>
     */
    private static function goodAnswers(): array
    {
        return [
            'name' => 'Amina Rahman',
            'email' => 'amina@example.test',
            'phone' => '+8801700000000',
            'destination' => 'UK',
            'consent' => '1',
        ];
    }

    #[Test]
    public function it_stores_notifies_and_autoresponds_on_a_good_submission(): void
    {
        $outcome = ($this->register())(self::form(), self::goodAnswers(), self::IP, self::HUMAN_SECONDS);

        $this->assertTrue($outcome->isAccepted);
        $this->assertCount(1, $this->leads->all());
        $this->assertSame(1, $this->notifier->staffNotifications);
        $this->assertSame(1, $this->notifier->autoresponses);
    }

    #[Test]
    public function it_pulls_the_name_email_and_phone_off_the_submission(): void
    {
        ($this->register())(self::form(), self::goodAnswers(), self::IP, self::HUMAN_SECONDS);

        $lead = $this->leads->all()[0];

        $this->assertSame('Amina Rahman', $lead->name);
        $this->assertSame('amina@example.test', $lead->email);
        $this->assertSame('+8801700000000', $lead->phone);
        $this->assertSame('counselling-enquiry', $lead->formId);
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function invalidAnswerProvider(): array
    {
        return [
            'missing name' => [['name' => ''], 'name'],
            'malformed email' => [['email' => 'amina@'], 'email'],
            'phone with no country code' => [['phone' => '01700000000'], 'phone'],
            'option not on the list' => [['destination' => 'Atlantis'], 'destination'],
            'consent withheld' => [['consent' => ''], 'consent'],
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    #[Test]
    #[DataProvider('invalidAnswerProvider')]
    public function it_rejects_a_bad_answer_and_says_which_field(array $overrides, string $expectedField): void
    {
        $outcome = ($this->register())(
            self::form(),
            array_merge(self::goodAnswers(), $overrides),
            self::IP,
            self::HUMAN_SECONDS,
        );

        $this->assertFalse($outcome->isAccepted);
        $this->assertArrayHasKey($expectedField, $outcome->fieldErrors);
        $this->assertSame([], $this->leads->all());
    }

    #[Test]
    public function it_requires_a_conditional_field_only_when_it_is_showing(): void
    {
        $withoutCanada = ($this->register())(self::form(), self::goodAnswers(), self::IP, self::HUMAN_SECONDS);

        $this->assertTrue($withoutCanada->isAccepted);

        $withCanada = ($this->register())(
            self::form(),
            array_merge(self::goodAnswers(), ['destination' => 'Canada']),
            self::IP,
            self::HUMAN_SECONDS,
        );

        $this->assertFalse($withCanada->isAccepted);
        $this->assertArrayHasKey('province', $withCanada->fieldErrors);
    }

    #[Test]
    public function it_never_stores_an_answer_to_a_field_the_visitor_could_not_see(): void
    {
        ($this->register())(
            self::form(),
            array_merge(self::goodAnswers(), ['province' => 'Ontario']),
            self::IP,
            self::HUMAN_SECONDS,
        );

        $this->assertArrayNotHasKey('province', $this->leads->all()[0]->submission);
    }

    #[Test]
    public function it_discards_a_submission_that_filled_the_honeypot(): void
    {
        $outcome = ($this->register())(
            self::form(),
            array_merge(self::goodAnswers(), [SpamPolicy::HONEYPOT_FIELD => 'http://spam.test']),
            self::IP,
            self::HUMAN_SECONDS,
        );

        $this->assertFalse($outcome->isAccepted);
        $this->assertTrue($outcome->wasSilentlyDiscarded);
        $this->assertSame('honeypot', $outcome->rejectionReason);
        $this->assertSame([], $this->leads->all());
    }

    #[Test]
    public function it_discards_a_form_completed_faster_than_a_person_could_read_it(): void
    {
        $outcome = ($this->register())(self::form(), self::goodAnswers(), self::IP, 1);

        $this->assertSame('time-trap', $outcome->rejectionReason);
        $this->assertTrue($outcome->wasSilentlyDiscarded);
    }

    #[Test]
    public function it_discards_a_form_that_was_opened_yesterday(): void
    {
        $outcome = ($this->register())(self::form(), self::goodAnswers(), self::IP, 90000);

        $this->assertSame('stale-form', $outcome->rejectionReason);
    }

    #[Test]
    public function it_tells_a_bot_nothing_about_which_trap_it_tripped(): void
    {
        $outcome = ($this->register())(self::form(), ['name' => ''], self::IP, 1);

        $this->assertSame([], $outcome->fieldErrors);
        $this->assertTrue($outcome->wasSilentlyDiscarded);
    }

    /**
     * The check that matters most: a site owner who never configured a captcha must still be
     * protected, and must not have every visitor rejected.
     */
    #[Test]
    public function it_protects_a_site_with_no_captcha_configured(): void
    {
        $accepted = ($this->register())(self::form(), self::goodAnswers(), self::IP, self::HUMAN_SECONDS);

        $this->assertTrue($accepted->isAccepted);

        $bot = ($this->register())(
            self::form(),
            array_merge(self::goodAnswers(), [SpamPolicy::HONEYPOT_FIELD => 'x']),
            '203.0.113.10',
            self::HUMAN_SECONDS,
        );

        $this->assertFalse($bot->isAccepted);
    }

    #[Test]
    public function it_honours_a_configured_captcha_that_fails(): void
    {
        $this->captcha = StubCaptchaVerifier::configured(false);

        $outcome = ($this->register())(self::form(), self::goodAnswers(), self::IP, self::HUMAN_SECONDS, 'token');

        $this->assertSame('captcha', $outcome->rejectionReason);
    }

    #[Test]
    public function it_accepts_a_configured_captcha_that_passes(): void
    {
        $this->captcha = StubCaptchaVerifier::configured(true);

        $outcome = ($this->register())(self::form(), self::goodAnswers(), self::IP, self::HUMAN_SECONDS, 'token');

        $this->assertTrue($outcome->isAccepted);
    }

    #[Test]
    public function it_rate_limits_a_flood_from_one_address(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->assertTrue(
                ($this->register())(self::form(), self::goodAnswers(), self::IP, self::HUMAN_SECONDS)->isAccepted,
            );
        }

        $blocked = ($this->register())(self::form(), self::goodAnswers(), self::IP, self::HUMAN_SECONDS);

        $this->assertFalse($blocked->isAccepted);
        $this->assertFalse($blocked->wasSilentlyDiscarded);
        $this->assertCount(3, $this->leads->all());
    }

    #[Test]
    public function it_rate_limits_per_address_rather_than_globally(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            ($this->register())(self::form(), self::goodAnswers(), self::IP, self::HUMAN_SECONDS);
        }

        $other = ($this->register())(self::form(), self::goodAnswers(), '198.51.100.4', self::HUMAN_SECONDS);

        $this->assertTrue($other->isAccepted);
    }

    #[Test]
    public function it_reports_progress_through_a_multi_step_form(): void
    {
        $form = self::form();

        $this->assertTrue($form->isMultiStep());
        $this->assertSame(2, $form->stepCount());
        $this->assertSame(50, $form->progressAfterStep(0));
        $this->assertSame(100, $form->progressAfterStep(1));
        $this->assertSame(100, $form->progressAfterStep(9));
        $this->assertCount(3, $form->fieldsOnStep(0));
    }

    #[Test]
    public function it_knows_which_placements_interrupt_the_visitor(): void
    {
        $this->assertCount(6, FormPlacement::cases());
        $this->assertTrue(FormPlacement::Popup->interruptsTheVisitor());
        $this->assertFalse(FormPlacement::Inline->interruptsTheVisitor());
    }

    #[Test]
    public function it_clamps_a_retention_period_into_something_defensible(): void
    {
        $this->assertSame(
            FormDefinition::MINIMUM_RETENTION_DAYS,
            FormDefinition::of('f', 'F', [], FormPlacement::Inline, 1)->retentionDays,
        );
        $this->assertSame(
            FormDefinition::MAXIMUM_RETENTION_DAYS,
            FormDefinition::of('f', 'F', [], FormPlacement::Inline, 99999)->retentionDays,
        );
        $this->assertTrue(self::form()->requiresConsent());
        $this->assertNotNull(self::form()->field('email'));
        $this->assertNull(self::form()->field('nope'));
    }

    #[Test]
    public function it_expires_a_lead_when_its_retention_period_runs_out(): void
    {
        $policy = RetentionPolicy::forForm(self::form());
        $capturedAt = 1772355600;
        $oneYearLater = $capturedAt + (365 * 86400);

        $this->assertFalse($policy->hasExpired($capturedAt, $capturedAt));
        $this->assertTrue($policy->hasExpired($capturedAt, $oneYearLater));
        $this->assertSame(365, $policy->daysRemaining($capturedAt, $capturedAt));
        $this->assertSame(0, $policy->daysRemaining($capturedAt, $oneYearLater + 1));
    }

    #[Test]
    public function it_erases_a_lead_and_every_file_they_uploaded(): void
    {
        ($this->register())(self::form(), self::goodAnswers(), self::IP, self::HUMAN_SECONDS);

        $files = new InMemoryUploadedFileStore();
        $files->add(1, 'transcript.pdf');

        $erased = (new EraseLead($this->leads, $files))(1);

        $this->assertTrue($erased);
        $this->assertSame([], $this->leads->all());
        $this->assertSame([], $files->pathsFor(1));
    }

    #[Test]
    public function it_reports_nothing_erased_for_a_lead_that_does_not_exist(): void
    {
        $this->assertFalse((new EraseLead($this->leads, new InMemoryUploadedFileStore()))(404));
    }
}
