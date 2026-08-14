<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Lead;

use Edulume\Core\Application\Port\LeadRepository;
use Edulume\Core\Application\Port\UploadedFileStore;

/**
 * Removes every trace of a lead, on request or when its retention period expires.
 *
 * "Every trace" includes the files they uploaded. A GDPR erasure that leaves a transcript PDF
 * sitting in the uploads directory is not an erasure, and is exactly the kind of thing an
 * audit finds.
 */
final class EraseLead
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly UploadedFileStore $uploadedFileStore,
    ) {
    }

    public function __invoke(int $leadId): bool
    {
        $lead = $this->leadRepository->find($leadId);

        if ($lead === null) {
            return false;
        }

        $this->uploadedFileStore->deleteAllFor($leadId);
        $this->leadRepository->delete($leadId);

        return true;
    }
}
