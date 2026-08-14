<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Lead;

use Edulume\Core\Application\Lead\RegisterLead;
use Edulume\Core\Domain\Lead\FormDefinition;
use Edulume\Core\Domain\Lead\FormField;
use Edulume\Core\Domain\Lead\FormFieldType;
use Edulume\Core\Domain\Security\SvgSanitiser;
use Edulume\Core\Tests\Unit\Fake\FixedClock;
use Edulume\Core\Tests\Unit\Fake\InMemoryLeadRepository;
use Edulume\Core\Tests\Unit\Fake\RecordingLeadNotifier;
use Edulume\Core\Tests\Unit\Fake\StubCaptchaVerifier;
use Edulume\Core\Tests\Unit\Fake\WindowedRateLimiter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A form that does not ask for everything the lead record can hold.
 *
 * Every other test submits the full counselling form, which carries a name, an email and a
 * phone number. A real site's newsletter box asks for one field. The lookup that fills the
 * absent columns had never returned its empty fallback, so nothing proved a two-field form
 * produces a valid lead rather than a fatal on a missing key.
 */
final class SparseSubmissionTest extends TestCase
{
    private const NOW = '2026-03-01T09:00:00+00:00';
    private const IP = '203.0.113.9';

    private InMemoryLeadRepository $leads;

    protected function setUp(): void
    {
        $this->leads = new InMemoryLeadRepository();
    }

    private function register(): RegisterLead
    {
        return new RegisterLead(
            $this->leads,
            new WindowedRateLimiter(3),
            StubCaptchaVerifier::notConfigured(),
            new RecordingLeadNotifier(),
            new FixedClock(self::NOW, 1772355600),
        );
    }

    #[Test]
    public function a_form_with_no_telephone_field_stores_an_empty_phone_rather_than_failing(): void
    {
        $form = FormDefinition::of('newsletter', 'Newsletter', [
            FormField::of('name', 'Full name', FormFieldType::Text, true),
            FormField::of('email', 'Email', FormFieldType::Email, true),
        ]);

        $outcome = ($this->register())(
            $form,
            ['name' => 'Nadia Rahman', 'email' => 'nadia@example.test'],
            self::IP,
            12,
        );

        self::assertTrue($outcome->isAccepted);

        $lead = $this->leads->all()[0];

        self::assertSame('Nadia Rahman', $lead->name);
        self::assertSame('nadia@example.test', $lead->email);
        self::assertSame('', $lead->phone);
    }

    /**
     * A field present but left blank is the same absence as a field that was never asked for —
     * storing a whitespace string would put " " in an exported CSV column.
     */
    #[Test]
    public function a_telephone_field_left_blank_stores_an_empty_phone(): void
    {
        $form = FormDefinition::of('enquiry', 'Enquiry', [
            FormField::of('name', 'Full name', FormFieldType::Text, true),
            FormField::of('email', 'Email', FormFieldType::Email, true),
            FormField::of('phone', 'Phone', FormFieldType::Telephone),
        ]);

        $outcome = ($this->register())(
            $form,
            ['name' => 'Nadia Rahman', 'email' => 'nadia@example.test', 'phone' => '   '],
            self::IP,
            12,
        );

        self::assertTrue($outcome->isAccepted);
        self::assertSame('', $this->leads->all()[0]->phone);
    }

    /**
     * An allowed attribute holding a disallowed value: `fill` is on the allowlist, and
     * `url(javascript:…)` inside it is the whole attack. Allowlisting the attribute name alone
     * is what makes an SVG sanitiser look thorough and let a payload through.
     */
    #[Test]
    public function an_allowed_attribute_carrying_a_script_url_is_stripped(): void
    {
        $sanitiser = new SvgSanitiser();
        $markup = '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect fill="url(javascript:alert(1))"/></svg>';

        $sanitised = $sanitiser->sanitise($markup);

        self::assertStringNotContainsString('javascript:', $sanitised);
        self::assertFalse($sanitiser->isSafe($markup));
    }

    #[Test]
    public function the_other_script_bearing_schemes_are_stripped_from_an_allowed_attribute(): void
    {
        $sanitiser = new SvgSanitiser();

        foreach (['vbscript:msgbox(1)', 'data:text/html,<script>', 'JaVaScRiPt:alert(1)'] as $payload) {
            $markup = sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="%s"/></svg>',
                $payload,
            );

            self::assertFalse($sanitiser->isSafe($markup), $payload);
        }
    }

    #[Test]
    public function an_allowed_attribute_with_an_ordinary_value_survives(): void
    {
        $sanitiser = new SvgSanitiser();
        $markup = '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#365e92"/></svg>';

        self::assertStringContainsString('#365e92', $sanitiser->sanitise($markup));
    }
}
