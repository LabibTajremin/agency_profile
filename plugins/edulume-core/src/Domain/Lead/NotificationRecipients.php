<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * Who hears about a lead: the site-wide addresses, plus anything the form or the branch adds.
 *
 * Branch-aware by design. A Dhaka enquiry landing only in the head office inbox is how a
 * multi-branch consultancy loses the leads it worked hardest for.
 */
final class NotificationRecipients
{
    /**
     * @param list<string> $siteWide
     * @param array<string, list<string>> $perForm
     * @param array<int, list<string>> $perBranch
     */
    private function __construct(
        private readonly array $siteWide,
        private readonly array $perForm,
        private readonly array $perBranch,
    ) {
    }

    /**
     * @param list<string> $siteWide
     * @param array<string, list<string>> $perForm
     * @param array<int, list<string>> $perBranch
     */
    public static function of(array $siteWide, array $perForm = [], array $perBranch = []): self
    {
        return new self($siteWide, $perForm, $perBranch);
    }

    /**
     * @return list<string>
     */
    public function for(string $formId, int $branchId): array
    {
        $addresses = array_merge(
            $this->siteWide,
            $this->perForm[$formId] ?? [],
            $this->perBranch[$branchId] ?? [],
        );

        $unique = [];

        foreach ($addresses as $address) {
            $normalised = strtolower(trim($address));

            if ($normalised !== '' && filter_var($normalised, FILTER_VALIDATE_EMAIL) !== false) {
                $unique[$normalised] = $normalised;
            }
        }

        return array_values($unique);
    }
}
