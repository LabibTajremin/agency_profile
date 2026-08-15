<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Chrome;

use DateTimeImmutable;
use Edulume\Core\Domain\Support\Guard;

/**
 * Everything the header, the footer and the floating cluster need, in one aggregate that
 * round-trips losslessly through the settings blob.
 */
final class ChromeSettings
{
    /**
     * @param list<FloatingAction> $floatingActions
     */
    public function __construct(
        public readonly HeaderVariant $headerVariant = HeaderVariant::Classic,
        public readonly FooterVariant $footerVariant = FooterVariant::Columns,
        public readonly bool $isHeaderSticky = true,
        public readonly bool $hasUtilityBar = true,
        public readonly bool $hasMegaMenu = false,
        public readonly bool $hasPreloader = false,
        public readonly bool $hasNewsletter = true,
        public readonly string $footerCredit = '',
        public readonly array $floatingActions = [],
        public readonly AnnouncementBar $announcement = new AnnouncementBar(false, ''),
        public readonly ContactDetails $contact = new ContactDetails(),
        public readonly LogoSet $logos = new LogoSet(),
        /**
         * Whether the logo wall prints each partner's name under its mark.
         *
         * On by default. A wall of unlabelled logos is a recognition test the visitor did not
         * ask to sit — it works for household brands and fails for a regional accreditor, which
         * is most of what a consultancy actually lists. Turning it off is the right call once
         * the marks are genuinely recognisable, so it is a switch rather than a rule.
         */
        public readonly bool $showsPartnerNames = true,
    ) {
    }

    /**
     * The actions that will actually render: configured, in order, and each with a destination.
     *
     * @return list<FloatingAction>
     */
    public function visibleFloatingActions(): array
    {
        return array_values(array_filter(
            $this->floatingActions,
            fn (FloatingAction $action): bool => $action->isAvailable($this->contact),
        ));
    }

    public function showsAnnouncementAt(DateTimeImmutable $now): bool
    {
        return $this->announcement->isVisibleAt($now);
    }

    /**
     * Whether the utility bar has anything to say. Rendering an empty strip costs 40px of the
     * first screen and communicates nothing.
     */
    public function showsUtilityBar(): bool
    {
        return $this->hasUtilityBar && $this->contact->hasAny();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'headerVariant' => $this->headerVariant->value,
            'footerVariant' => $this->footerVariant->value,
            'isHeaderSticky' => $this->isHeaderSticky,
            'hasUtilityBar' => $this->hasUtilityBar,
            'hasMegaMenu' => $this->hasMegaMenu,
            'hasPreloader' => $this->hasPreloader,
            'hasNewsletter' => $this->hasNewsletter,
            'footerCredit' => $this->footerCredit,
            'floatingActions' => array_map(
                static fn (FloatingAction $action): string => $action->value,
                $this->floatingActions,
            ),
            'announcement' => [
                'isEnabled' => $this->announcement->isEnabled,
                'message' => $this->announcement->message,
                'linkUrl' => $this->announcement->linkUrl,
                'linkLabel' => $this->announcement->linkLabel,
                'startsAt' => $this->announcement->startsAt?->format(DATE_ATOM),
                'endsAt' => $this->announcement->endsAt?->format(DATE_ATOM),
                'isDismissible' => $this->announcement->isDismissible,
            ],
            'contact' => [
                'phone' => $this->contact->phone,
                'whatsapp' => $this->contact->whatsapp,
                'email' => $this->contact->email,
                'officeHours' => $this->contact->officeHours,
                'bookingUrl' => $this->contact->bookingUrl,
            ],
            'logos' => [
                'primary' => $this->logos->primary,
                'primaryDark' => $this->logos->primaryDark,
                'mobile' => $this->logos->mobile,
                'mobileDark' => $this->logos->mobileDark,
                'footer' => $this->logos->footer,
                'footerDark' => $this->logos->footerDark,
                'alternativeText' => $this->logos->alternativeText,
            ],
            'showsPartnerNames' => $this->showsPartnerNames,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Guard::toEnum(HeaderVariant::class, $data['headerVariant'] ?? null, HeaderVariant::Classic),
            Guard::toEnum(FooterVariant::class, $data['footerVariant'] ?? null, FooterVariant::Columns),
            Guard::toBool($data['isHeaderSticky'] ?? true),
            Guard::toBool($data['hasUtilityBar'] ?? true),
            Guard::toBool($data['hasMegaMenu'] ?? false),
            Guard::toBool($data['hasPreloader'] ?? false),
            Guard::toBool($data['hasNewsletter'] ?? true),
            Guard::toString($data['footerCredit'] ?? ''),
            self::actionsFrom($data['floatingActions'] ?? []),
            self::announcementFrom($data['announcement'] ?? []),
            self::contactFrom($data['contact'] ?? []),
            self::logosFrom($data['logos'] ?? []),
            Guard::toBool($data['showsPartnerNames'] ?? true),
        );
    }

    private static function announcementFrom(mixed $value): AnnouncementBar
    {
        $data = Guard::toArray($value);

        return new AnnouncementBar(
            Guard::toBool($data['isEnabled'] ?? false),
            Guard::toString($data['message'] ?? ''),
            Guard::toString($data['linkUrl'] ?? ''),
            Guard::toString($data['linkLabel'] ?? ''),
            self::toDate($data['startsAt'] ?? null),
            self::toDate($data['endsAt'] ?? null),
            Guard::toBool($data['isDismissible'] ?? true),
        );
    }

    private static function contactFrom(mixed $value): ContactDetails
    {
        $data = Guard::toArray($value);

        return new ContactDetails(
            Guard::toString($data['phone'] ?? ''),
            Guard::toString($data['whatsapp'] ?? ''),
            Guard::toString($data['email'] ?? ''),
            Guard::toString($data['officeHours'] ?? ''),
            Guard::toString($data['bookingUrl'] ?? ''),
        );
    }

    private static function logosFrom(mixed $value): LogoSet
    {
        $data = Guard::toArray($value);

        return new LogoSet(
            Guard::toString($data['primary'] ?? ''),
            Guard::toString($data['primaryDark'] ?? ''),
            Guard::toString($data['mobile'] ?? ''),
            Guard::toString($data['mobileDark'] ?? ''),
            Guard::toString($data['footer'] ?? ''),
            Guard::toString($data['footerDark'] ?? ''),
            Guard::toString($data['alternativeText'] ?? ''),
        );
    }

    /**
     * @return list<FloatingAction>
     */
    private static function actionsFrom(mixed $value): array
    {
        $actions = [];

        foreach (Guard::toEnumList(FloatingAction::class, $value) as $action) {
            // Deduplicated on the way in: the same action twice would render two identical
            // buttons on top of each other, and no stored order makes that meaningful.
            if (!in_array($action, $actions, true)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    private static function toDate(mixed $value): ?DateTimeImmutable
    {
        $raw = Guard::toString($value ?? '');

        if ($raw === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($raw);
        } catch (\Exception) {
            // A malformed stored date means "no boundary", never a fatal on the front end.
            return null;
        }
    }
}
