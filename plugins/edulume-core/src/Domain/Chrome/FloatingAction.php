<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Chrome;

/**
 * The floating action cluster.
 *
 * Each action is a real link with a real href, not a JavaScript handler, so it works with
 * JavaScript off and can be opened in a new tab. Back-to-top is the exception and says so.
 */
enum FloatingAction: string
{
    case Whatsapp = 'whatsapp';
    case Call = 'call';
    case BookCounselling = 'book-counselling';
    case BackToTop = 'back-to-top';

    public function label(): string
    {
        return match ($this) {
            self::Whatsapp => 'WhatsApp',
            self::Call => 'Call us',
            self::BookCounselling => 'Book counselling',
            self::BackToTop => 'Back to top',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Whatsapp => 'message-circle',
            self::Call => 'phone',
            self::BookCounselling => 'calendar-check',
            self::BackToTop => 'arrow-up',
        };
    }

    /** True for the one action that has no destination and needs script to do anything. */
    public function isScriptDriven(): bool
    {
        return $this === self::BackToTop;
    }

    /**
     * Builds the href from the configured contact details.
     *
     * Returns an empty string when the detail the action needs is not configured — an empty
     * `tel:` link is worse than no button, because it looks tappable and does nothing.
     */
    public function href(ContactDetails $contact): string
    {
        return match ($this) {
            self::Whatsapp => $contact->whatsappUrl(),
            self::Call => $contact->telUrl(),
            self::BookCounselling => $contact->bookingUrl,
            self::BackToTop => '#top',
        };
    }

    public function isAvailable(ContactDetails $contact): bool
    {
        return $this->href($contact) !== '';
    }

    /** @return list<self> */
    public static function all(): array
    {
        return self::cases();
    }
}
