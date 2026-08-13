<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Lead;

use Edulume\Core\Application\Port\CaptchaVerifier;
use Edulume\Core\Application\Port\Clock;
use Edulume\Core\Application\Port\LeadNotifier;
use Edulume\Core\Application\Port\LeadRepository;
use Edulume\Core\Application\Port\RateLimiter;
use Edulume\Core\Domain\Lead\FormDefinition;
use Edulume\Core\Domain\Lead\FormFieldType;
use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\SpamPolicy;
use Edulume\Core\Domain\Lead\SubmissionOutcome;

/**
 * Takes one submission from rate limit to stored lead.
 *
 * The order matters. Rate limiting comes first because it is the cheapest check. Spam is
 * discarded before validation, so a bot never learns which field it got wrong. The lead is
 * stored before anyone is notified, because a mail server having a bad afternoon must not
 * cost the consultancy an enquiry.
 */
final class RegisterLead
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly RateLimiter $rateLimiter,
        private readonly CaptchaVerifier $captchaVerifier,
        private readonly LeadNotifier $leadNotifier,
        private readonly Clock $clock,
        private readonly SpamPolicy $spamPolicy = new SpamPolicy(),
    ) {
    }

    /**
     * @param array<string, string> $answers
     */
    public function __invoke(
        FormDefinition $form,
        array $answers,
        string $ipAddress,
        int $secondsOnForm,
        string $captchaToken = '',
        int $branchId = 0
    ): SubmissionOutcome {
        if (!$this->rateLimiter->isAllowed($ipAddress)) {
            return SubmissionOutcome::rateLimited();
        }

        $this->rateLimiter->record($ipAddress);

        $spamReason = $this->spamPolicy->reasonToDiscard(
            $answers,
            $secondsOnForm,
            $this->captchaPassed($captchaToken, $ipAddress),
        );

        if ($spamReason !== null) {
            return SubmissionOutcome::discardedAsSpam($spamReason);
        }

        $errors = $form->validate($answers);

        if ($errors !== []) {
            return SubmissionOutcome::invalid($errors);
        }

        $lead = $this->captureLead($form, $answers, $ipAddress, $branchId);

        $this->leadRepository->save($lead, $this->clock->now());

        $this->leadNotifier->notifyStaff($lead, $form);
        $this->leadNotifier->autorespond($lead, $form);

        return SubmissionOutcome::accepted();
    }

    /**
     * An unconfigured captcha passes. A site owner who never signed up for one must not have
     * every visitor locked out.
     */
    private function captchaPassed(string $token, string $ipAddress): bool
    {
        if (!$this->captchaVerifier->isConfigured()) {
            return true;
        }

        return $this->captchaVerifier->verify($token, $ipAddress);
    }

    /**
     * @param array<string, string> $answers
     */
    private function captureLead(FormDefinition $form, array $answers, string $ipAddress, int $branchId): Lead
    {
        $submission = $form->visibleAnswers($answers);
        $submission['ipAddress'] = $ipAddress;

        unset($submission[SpamPolicy::HONEYPOT_FIELD]);

        return Lead::captured(
            $this->firstAnswerOfType($form, $answers, FormFieldType::Text),
            $this->firstAnswerOfType($form, $answers, FormFieldType::Email),
            $this->firstAnswerOfType($form, $answers, FormFieldType::Telephone),
            $form->id,
            $submission,
            $this->clock->now(),
            $branchId,
        );
    }

    /**
     * @param array<string, string> $answers
     */
    private function firstAnswerOfType(FormDefinition $form, array $answers, FormFieldType $type): string
    {
        foreach ($form->fields as $field) {
            if ($field->type === $type && trim($answers[$field->id] ?? '') !== '') {
                return trim($answers[$field->id]);
            }
        }

        return '';
    }
}
