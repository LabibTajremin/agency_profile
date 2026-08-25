<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Application\Content\ExportContentCsv;
use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Content\CsvColumnMapping;
use Edulume\Core\Domain\Content\CsvImportCursor;
use Edulume\Core\Domain\Content\CsvRowMapper;
use Edulume\Core\Domain\Content\CsvTarget;
use Edulume\Core\Domain\Content\PostTypeDefinition;
use Edulume\Core\Infrastructure\Content\FileCsvSource;
use Edulume\Core\Infrastructure\Content\WpContentReader;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * Getting content in and out as a spreadsheet.
 *
 * A consultancy's course list arrives as a spreadsheet from a partner university, and it changes
 * every intake. Retyping four hundred courses is how a site goes stale, so the CSV round trip is
 * the difference between a catalogue that is current and one that is a year old.
 *
 * Export and import use the same column set, generated from the content model, so a file that
 * came out of here goes back in without editing. That is the whole point: exporting a different
 * shape from the one the importer accepts is a feature nobody can actually use.
 */
final class ContentScreen
{
    public const EXPORT_ACTION = 'edulume_export_content';
    public const IMPORT_ACTION = 'edulume_import_content';
    public const NOTICE_PARAMETER = 'edulume_content_notice';

    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        add_action('admin_post_' . self::EXPORT_ACTION, [$this, 'handleExport']);
        add_action('admin_post_' . self::IMPORT_ACTION, [$this, 'handleImport']);
    }

    public function handleExport(): void
    {
        $this->assertCapability();
        check_admin_referer(self::EXPORT_ACTION);

        $type = isset($_POST['type']) ? sanitize_key(wp_unslash($_POST['type'])) : '';
        $definition = $this->definitionFor($type);

        if ($definition === null) {
            $this->redirect(__('That is not a content type this plugin knows about.', 'edulume'));
        }

        $csv = (new ExportContentCsv(new WpContentReader()))($definition->key, $this->mappingsFor($definition));

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $definition->key . '-' . gmdate('Y-m-d') . '.csv');

        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV body, not markup.

        exit;
    }

    /**
     * One pass per request, resumed from the cursor the last pass returned.
     *
     * The upload is moved out of PHP's temporary directory before the loop, because that file is
     * removed the moment the request that received it ends — and this import spans several.
     */
    public function handleImport(): void
    {
        $this->assertCapability();
        check_admin_referer(self::IMPORT_ACTION);

        $type = isset($_POST['type']) ? sanitize_key(wp_unslash($_POST['type'])) : '';
        $definition = $this->definitionFor($type);

        if ($definition === null) {
            $this->redirect(__('That is not a content type this plugin knows about.', 'edulume'));
        }

        $path = $this->storeUpload();

        if ($path === '') {
            $this->redirect(__('Choose a .csv file to import.', 'edulume'));
        }

        $report = ($this->container->importContentCsv())(
            $definition->key,
            new FileCsvSource($path),
            new CsvRowMapper($this->mappingsFor($definition)),
            CsvImportCursor::start()
        );

        wp_delete_file($path);

        $notice = sprintf(
            /* translators: 1: rows imported, 2: rows that could not be read. */
            __('Imported %1$d row(s). %2$d could not be read.', 'edulume'),
            $report->cursor->importedCount,
            $report->cursor->failedCount
        );

        if (!$report->cursor->isComplete) {
            // Said plainly rather than reported as a success. The import stops when the request
            // budget runs out, and a partial import that claims to be a whole one is how a
            // course catalogue ends up half missing without anybody noticing.
            $notice .= ' ' . __('The file was too big to finish in one go — upload it again to continue.', 'edulume');
        }

        $this->redirect($notice);
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_CONTENT)) {
            return;
        }

        Notice::render(self::NOTICE_PARAMETER);

        $types = [];

        foreach (ContentModel::postTypes() as $definition) {
            $types[$definition->key] = $definition->pluralLabel;
        }

        $this->renderExport($types);
        $this->renderImport($types);
        $this->renderColumns();
    }

    /**
     * @param array<string, string> $types
     */
    private function renderExport(array $types): void
    {
        Field::openGroup(
            __('Export', 'edulume'),
            __('Everything of one type as a spreadsheet, drafts included.', 'edulume')
        );

        printf('<form method="post" action="%s">', esc_url(admin_url('admin-post.php')));

        wp_nonce_field(self::EXPORT_ACTION);
        Field::hidden('action', self::EXPORT_ACTION);
        Field::select('type', __('Content type', 'edulume'), 'edulume_course', $types);

        printf(
            '<p><button type="submit" class="button button-primary">%s</button></p></form>',
            esc_html__('Download CSV', 'edulume')
        );

        Field::closeGroup();
    }

    /**
     * @param array<string, string> $types
     */
    private function renderImport(array $types): void
    {
        Field::openGroup(
            __('Import', 'edulume'),
            __('A row whose title already exists is updated rather than duplicated.', 'edulume')
        );

        printf(
            '<form method="post" action="%s" enctype="multipart/form-data">',
            esc_url(admin_url('admin-post.php'))
        );

        wp_nonce_field(self::IMPORT_ACTION);
        Field::hidden('action', self::IMPORT_ACTION);
        Field::select('type', __('Content type', 'edulume'), 'edulume_course', $types);

        printf(
            '<div class="edulume-field"><label class="edulume-field__label" for="edulume-import-file">%1$s</label>'
            . '<input type="file" id="edulume-import-file" name="csv" accept=".csv,text/csv" required />'
            . '<p class="edulume-field__help">%2$s</p></div>'
            . '<p><button type="submit" class="button button-primary">%3$s</button></p></form>',
            esc_html__('CSV file', 'edulume'),
            esc_html__('Export first to get a file with the right columns in it.', 'edulume'),
            esc_html__('Import', 'edulume')
        );

        Field::closeGroup();
    }

    private function renderColumns(): void
    {
        printf(
            '<section class="edulume-group"><h2 class="edulume-group__title">%1$s</h2>'
            . '<p class="edulume-group__summary">%2$s</p><table class="widefat striped"><thead><tr>'
            . '<th scope="col">%3$s</th><th scope="col">%4$s</th></tr></thead><tbody>',
            esc_html__('Columns', 'edulume'),
            esc_html__('Several values in one cell are separated with a vertical bar.', 'edulume'),
            esc_html__('Content type', 'edulume'),
            esc_html__('Headers', 'edulume')
        );

        foreach (ContentModel::postTypes() as $definition) {
            printf(
                '<tr><th scope="row">%1$s</th><td><code>%2$s</code></td></tr>',
                esc_html($definition->pluralLabel),
                esc_html(implode(', ', array_map(
                    static fn (CsvColumnMapping $mapping): string => $mapping->header,
                    $this->mappingsFor($definition)
                )))
            );
        }

        echo '</tbody></table></section>';
    }

    /**
     * The columns for a type, derived rather than listed.
     *
     * Taxonomies and relationships come from the content model, so a new one is in the export
     * the day it is registered instead of the day somebody remembers to add it here.
     *
     * @return list<CsvColumnMapping>
     */
    private function mappingsFor(PostTypeDefinition $definition): array
    {
        $mappings = [
            CsvColumnMapping::of('title', CsvTarget::Title, 'title', true),
            CsvColumnMapping::of('slug', CsvTarget::Slug, 'slug'),
            CsvColumnMapping::of('status', CsvTarget::Status, 'status'),
            CsvColumnMapping::of('excerpt', CsvTarget::Excerpt, 'excerpt'),
            CsvColumnMapping::of('content', CsvTarget::Content, 'content'),
        ];

        foreach ($definition->taxonomyKeys as $taxonomy) {
            $mappings[] = CsvColumnMapping::of($taxonomy, CsvTarget::Taxonomy, $taxonomy);
        }

        foreach (ContentModel::relationships() as $relationship) {
            if ($relationship->fromPostTypeKey === $definition->key) {
                $mappings[] = CsvColumnMapping::of($relationship->key, CsvTarget::Relationship, $relationship->key);
            }
        }

        return $mappings;
    }

    private function definitionFor(string $key): ?PostTypeDefinition
    {
        foreach (ContentModel::postTypes() as $definition) {
            if ($definition->key === $key) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * Moves the upload somewhere it will still be there on the next request.
     */
    /**
     * Moves the upload somewhere it will still be there after this request.
     *
     * `$_FILES` is handed to `wp_handle_upload` as it arrives, because that function is what
     * validates an upload — it checks the type against the allowlist below, rejects anything
     * PHP flagged, and gives the file a name of its own choosing. Sanitising the array first
     * would corrupt the temporary path it needs and prove nothing.
     */
    private function storeUpload(): string
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- `handleImport()` calls
        // `check_admin_referer()` before this runs; the sniff cannot see across the call.
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (!isset($_FILES['csv']) || !is_array($_FILES['csv'])) {
            return '';
        }

        $handled = wp_handle_upload($_FILES['csv'], ['test_form' => false, 'mimes' => ['csv' => 'text/csv']]);
        // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        return is_array($handled) && is_string($handled['file'] ?? null) ? $handled['file'] : '';
    }

    private function redirect(string $notice): never
    {
        Notice::redirect('edulume-content', self::NOTICE_PARAMETER, $notice);
    }

    private function assertCapability(): void
    {
        if (!current_user_can(Capabilities::MANAGE_CONTENT)) {
            wp_die(esc_html__('You are not allowed to import or export content.', 'edulume'), '', ['response' => 403]);
        }
    }
}
