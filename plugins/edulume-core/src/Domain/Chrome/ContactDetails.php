<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Chrome;

/**
 * The contact details the utility bar, the floating actions and the footer all read.
 *
 * One source, because a phone number stored three times is a phone number that is wrong in two
 * places after the office moves.
 */
final class ContactDetails
{
    public function __construct(
        public readonly string $phone = '',
        public readonly string $whatsapp = '',
        public readonly string $email = '',
        public readonly string $officeHours = '',
        public readonly string $bookingUrl = '',
    ) {
    }

    /**
     * `tel:` needs the number stripped of everything a human reads it with.
     *
     * A leading `+` survives because dropping it breaks international dialling, which is most
     * of the point for a study-abroad consultancy.
     */
    public function telUrl(): string
    {
        $digits = $this->dialable($this->phone);

        return $digits === '' ? '' : 'tel:' . $digits;
    }

    /**
     * WhatsApp takes digits only — no `+`, no spaces — and silently fails on anything else.
     */
    public function whatsappUrl(): string
    {
        $number = preg_replace('/\D+/', '', $this->whatsapp) ?? '';

        return $number === '' ? '' : 'https://wa.me/' . $number;
    }

    public function mailtoUrl(): string
    {
        return trim($this->email) === '' ? '' : 'mailto:' . trim($this->email);
    }

    public function hasAny(): bool
    {
        return $this->telUrl() !== ''
            || $this->whatsappUrl() !== ''
            || $this->mailtoUrl() !== ''
            || trim($this->officeHours) !== '';
    }

    private function dialable(string $number): string
    {
        $trimmed = trim($number);
        $isInternational = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if ($digits === '') {
            return '';
        }

        return $isInternational ? '+' . $digits : $digits;
    }
}
