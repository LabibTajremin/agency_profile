<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadQuery;
use Edulume\Core\Domain\Lead\LeadStatus;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * The enquiries the site has taken, and what happened to each one.
 *
 * The pipeline is the product for a consultancy: an enquiry that arrives and is never moved is
 * a student who went somewhere else. So the status control is on the row rather than behind a
 * detail view — moving twenty leads along should be twenty clicks, not sixty.
 *
 * Export writes the CSV straight to the response rather than to a file in uploads. A leads
 * export sitting at a guessable URL under `wp-content` is a data breach waiting for a crawler.
 */
final class LeadScreen
{
    public const ACTION = 'edulume_lead_action';
    public const EXPORT_ACTION = 'edulume_export_leads';
    public const NOTICE_PARAMETER = 'edulume_leads_notice';

    private const PER_PAGE = 25;

    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handleAction']);
        add_action('admin_post_' . self::EXPORT_ACTION, [$this, 'handleExport']);
    }

    public function handleAction(): void
    {
        $this->assertCapability(Capabilities::MANAGE_LEADS);
        check_admin_referer(self::ACTION);

        $leadId = isset($_POST['lead']) ? absint(wp_unslash($_POST['lead'])) : 0;
        $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : '';
        $erase = isset($_POST['erase']);

        if ($leadId === 0) {
            $this->redirect(__('That enquiry no longer exists.', 'edulume'));
        }

        if ($erase) {
            ($this->container->eraseLead())($leadId);

            $this->redirect(__('Enquiry erased, along with anything it uploaded.', 'edulume'));
        }

        $moved = LeadStatus::tryFrom($status);

        if ($moved !== null) {
            $this->container->moveLeadThroughPipeline()->moveTo($leadId, $moved, get_current_user_id());
        }

        $this->redirect(__('Enquiry updated.', 'edulume'));
    }

    /**
     * Streams the CSV as a download.
     *
     * `exit` rather than returning: anything WordPress prints after this lands inside the file.
     */
    public function handleExport(): void
    {
        $this->assertCapability(Capabilities::EXPORT_LEADS);
        check_admin_referer(self::EXPORT_ACTION);

        $csv = ($this->container->exportLeadsCsv())($this->queryFromRequest(), $this->counsellorRestriction());

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=edulume-leads-' . gmdate('Y-m-d') . '.csv');

        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV body, not markup.

        exit;
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_LEADS)) {
            return;
        }

        Notice::render(self::NOTICE_PARAMETER);

        $query = $this->queryFromRequest();
        $page = ($this->container->listLeads())($query, $this->counsellorRestriction());

        $this->renderFilters($query, $page->totalCount);

        if ($page->isEmpty()) {
            printf(
                '<p class="edulume-empty">%s</p>',
                esc_html__('No enquiries yet. Every form on the site lands here.', 'edulume')
            );

            return;
        }

        echo '<table class="widefat striped edulume-leads"><thead><tr>';

        foreach (
            [
            __('Who', 'edulume'),
            __('Contact', 'edulume'),
            __('Form', 'edulume'),
            __('Received', 'edulume'),
            __('Stage', 'edulume'),
            ] as $heading
        ) {
            printf('<th scope="col">%s</th>', esc_html($heading));
        }

        echo '</tr></thead><tbody>';

        foreach ($page->leads as $lead) {
            if ($lead instanceof Lead) {
                $this->renderRow($lead);
            }
        }

        echo '</tbody></table>';

        $this->renderPagination($page->pageCount(), $query);
    }

    private function renderRow(Lead $lead): void
    {
        printf(
            '<tr><td><strong>%1$s</strong></td><td>%2$s<br /><span class="edulume-muted">%3$s</span></td>'
            . '<td>%4$s</td><td>%5$s</td><td>',
            esc_html($lead->name === '' ? __('(no name given)', 'edulume') : $lead->name),
            esc_html($lead->email),
            esc_html($lead->phone),
            esc_html($lead->formId),
            esc_html($lead->createdAt)
        );

        printf('<form method="post" action="%s" class="edulume-leads__row-form">', esc_url(admin_url('admin-post.php')));

        wp_nonce_field(self::ACTION);
        Field::hidden('action', self::ACTION);
        Field::hidden('lead', (string) $lead->id);

        printf(
            '<label class="screen-reader-text" for="edulume-lead-status-%1$s">%2$s</label>'
            . '<select id="edulume-lead-status-%1$s" name="status">',
            esc_attr((string) $lead->id),
            esc_html__('Stage', 'edulume')
        );

        foreach (LeadStatus::cases() as $status) {
            printf(
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr($status->value),
                selected($status->value, $lead->status->value, false),
                esc_html($status->label())
            );
        }

        printf(
            '</select> <button type="submit" class="button button-small">%1$s</button>'
            . ' <button type="submit" name="erase" value="1" class="button button-small button-link-delete">%2$s</button>'
            . '</form></td></tr>',
            esc_html__('Move', 'edulume'),
            esc_html__('Erase', 'edulume')
        );
    }

    private function renderFilters(LeadQuery $query, int $total): void
    {
        printf('<form method="get" action="%s" class="edulume-leads__filters">', esc_url(admin_url('admin.php')));

        Field::hidden('page', 'edulume-leads');

        $statuses = ['' => __('Every stage', 'edulume')];

        foreach (LeadStatus::cases() as $status) {
            $statuses[$status->value] = $status->label();
        }

        Field::select('status', __('Stage', 'edulume'), $query->status?->value ?? '', $statuses);
        Field::text('search', __('Search', 'edulume'), $query->searchTerm, '', __('Name, email or phone', 'edulume'));

        printf(
            '<p class="edulume-settings__actions"><button type="submit" class="button">%s</button></p></form>',
            esc_html__('Filter', 'edulume')
        );

        printf(
            '<p class="edulume-muted">%s</p>',
            esc_html(sprintf(
                /* translators: %d: how many enquiries match the current filter. */
                _n('%d enquiry', '%d enquiries', $total, 'edulume'),
                $total
            ))
        );

        if (!current_user_can(Capabilities::EXPORT_LEADS)) {
            return;
        }

        printf('<form method="post" action="%s">', esc_url(admin_url('admin-post.php')));

        wp_nonce_field(self::EXPORT_ACTION);
        Field::hidden('action', self::EXPORT_ACTION);
        Field::hidden('status', $query->status?->value ?? '');
        Field::hidden('search', $query->searchTerm);

        printf(
            '<p><button type="submit" class="button">%s</button></p></form>',
            esc_html__('Export these as CSV', 'edulume')
        );
    }

    private function renderPagination(int $pageCount, LeadQuery $query): void
    {
        if ($pageCount < 2) {
            return;
        }

        echo '<p class="edulume-pagination">';

        for ($number = 1; $number <= $pageCount; $number++) {
            $url = add_query_arg(
                [
                    'page' => 'edulume-leads',
                    'status' => $query->status?->value ?? '',
                    'search' => $query->searchTerm,
                    'paged' => $number,
                ],
                admin_url('admin.php')
            );

            printf(
                '<a class="button button-small%1$s" href="%2$s">%3$s</a> ',
                $number === $query->page ? ' button-primary' : '',
                esc_url($url),
                esc_html((string) $number)
            );
        }

        echo '</p>';
    }

    private function queryFromRequest(): LeadQuery
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters on
        // a capability-gated screen; every value is coerced before it reaches the query.
        $status = isset($_REQUEST['status']) ? sanitize_key(wp_unslash($_REQUEST['status'])) : '';
        $search = isset($_REQUEST['search']) ? sanitize_text_field(wp_unslash($_REQUEST['search'])) : '';
        $paged = isset($_REQUEST['paged']) ? absint(wp_unslash($_REQUEST['paged'])) : 1;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        return LeadQuery::of(LeadStatus::tryFrom($status), null, null, $search, max(1, $paged), self::PER_PAGE);
    }

    /**
     * A counsellor sees their own enquiries; a manager sees everyone's.
     */
    private function counsellorRestriction(): ?int
    {
        return current_user_can(Capabilities::MANAGE_ALL_LEADS) ? null : get_current_user_id();
    }

    private function redirect(string $notice): never
    {
        Notice::redirect('edulume-leads', self::NOTICE_PARAMETER, $notice);
    }

    private function assertCapability(string $capability): void
    {
        if (!current_user_can($capability)) {
            wp_die(esc_html__('You are not allowed to manage enquiries.', 'edulume'), '', ['response' => 403]);
        }
    }
}
