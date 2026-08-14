<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Lead;

use Edulume\Core\Application\Port\LeadNotifier;
use Edulume\Core\Domain\Lead\AutoresponderTemplate;
use Edulume\Core\Domain\Lead\FormDefinition;
use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\NotificationRecipients;

/**
 * Sends the staff notification and the visitor autoresponder through `wp_mail`.
 *
 * A send failure is announced on a hook and otherwise swallowed. The lead is already stored by
 * the time this runs, so throwing here would show the visitor an error for a submission that
 * actually succeeded — which loses the enquiry twice: once because they believe it failed, and
 * again because they do not try a second time.
 */
final class WpMailLeadNotifier implements LeadNotifier
{
    public function __construct(
        private readonly NotificationRecipients $recipients,
        private readonly ?AutoresponderTemplate $autoresponder = null,
        private readonly string $autoresponderBody = '',
    ) {
    }

    public function notifyStaff(Lead $lead, FormDefinition $form): void
    {
        $addresses = $this->recipients->for($form->id, $lead->branchId);

        if ($addresses === []) {
            return;
        }

        $subject = sprintf(
            /* translators: 1: the form title, 2: the enquirer's name. */
            __('New enquiry from %2$s (%1$s)', 'edulume'),
            $form->title,
            $lead->name
        );

        $this->send($addresses, $subject, $this->staffBody($lead, $form));
    }

    public function autorespond(Lead $lead, FormDefinition $form): void
    {
        if ($this->autoresponder === null || trim($lead->email) === '') {
            return;
        }

        $subject = sprintf(
            /* translators: %s: the form title. */
            __('We have your enquiry (%s)', 'edulume'),
            $form->title
        );

        $this->send([$lead->email], $subject, $this->autoresponder->render($lead, $this->autoresponderBody));
    }

    /**
     * @param list<string> $addresses
     */
    private function send(array $addresses, string $subject, string $body): void
    {
        $sent = wp_mail($addresses, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);

        if (!$sent) {
            // A hook rather than a log line, so a site can surface this in whatever it already
            // uses for monitoring instead of in a file nobody opens.
            do_action('edulume_notification_failed', $addresses, $subject);
        }
    }

    private function staffBody(Lead $lead, FormDefinition $form): string
    {
        $rows = '';

        foreach ($lead->submission as $label => $value) {
            $rows .= sprintf(
                '<tr><th align="left">%s</th><td>%s</td></tr>',
                esc_html((string) $label),
                esc_html(is_scalar($value) ? (string) $value : '')
            );
        }

        return sprintf(
            '<p>%s</p><table>%s</table>',
            esc_html(sprintf(
                /* translators: %s: the form title. */
                __('A new enquiry arrived through %s.', 'edulume'),
                $form->title
            )),
            $rows
        );
    }
}
