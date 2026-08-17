<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Support\Guard;
use Edulume\Core\Domain\Video\VideoItem;
use Edulume\Core\Domain\Video\VideoRail;
use Edulume\Core\Domain\Video\VideoSource;
use Edulume\Core\Infrastructure\Content\SiteContent;
use Edulume\Core\Infrastructure\Content\VideoRailProvider;
use Edulume\Core\Infrastructure\Wp\Capabilities;

/**
 * Managing the videos on the front page.
 *
 * A repeater with no row limit, because the brief that produced it was "two today, more later"
 * and a fixed pair of fields is the version of this feature that gets outgrown in a month.
 *
 * URLs are validated on save, per source, and a bad one is refused with a message naming the
 * shapes that work. The alternative is a card on the front page that renders a grey box and
 * says nothing about why — and the only moment that is fixable is while the person who pasted
 * the URL is still looking at it.
 */
final class VideoScreen
{
    public const ACTION = 'edulume_save_videos';
    public const NOTICE_PARAMETER = 'edulume_video_notice';

    /** Enough rows for any real site; a bound so a crafted post cannot allocate without end. */
    private const MAXIMUM_ROWS = 60;

    public function register(): void
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handleSave']);
        add_action('admin_notices', [$this, 'renderMissingPosterNotice']);
    }

    public function handleSave(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            wp_die(esc_html__('You are not allowed to change the videos.', 'edulume'), '', ['response' => 403]);
        }

        check_admin_referer(self::ACTION);

        $rows = $this->submittedRows();
        $rejected = [];
        $items = [];

        foreach ($rows as $index => $row) {
            $source = VideoSource::tryFrom($row['source']) ?? VideoSource::Mp4;
            $item = VideoItem::of($source, $row['url'], $row['title'], (int) $row['poster_id'], $row['duration']);

            if ($row['url'] === '') {
                continue;
            }

            if (!$item->isPlayable()) {
                $rejected[] = $index + 1;

                continue;
            }

            $items[] = $item->toArray();
        }

        $rail = VideoRail::fromArray([
            'enabled' => $this->checkbox('enabled'),
            'title' => $this->text('title'),
            'subtitle' => $this->text('subtitle'),
            'aspect' => $this->text('aspect'),
            'autoplay' => $this->checkbox('autoplay'),
            'consent' => $this->checkbox('consent'),
            'items' => $items,
        ]);

        $content = Guard::toArray(get_option(SiteContent::OPTION, []));
        $content[VideoRailProvider::OPTION_KEY] = $rail->toArray();

        update_option(SiteContent::OPTION, $content, false);

        $this->redirect($rejected === []
            ? __('Videos saved.', 'edulume')
            : sprintf(
                /* translators: %s: a comma-separated list of row numbers. */
                __('Videos saved, but rows %s were not recognised and have been left out.', 'edulume'),
                implode(', ', $rejected)
            ));
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            return;
        }

        $this->renderNotice();

        $rail = $this->load();

        printf(
            '<form method="post" action="%s" class="edulume-videos">%s'
            . '<input type="hidden" name="action" value="%s" />',
            esc_url(admin_url('admin-post.php')),
            wp_nonce_field(self::ACTION, '_wpnonce', true, false),
            esc_attr(self::ACTION)
        );

        $this->renderTopFields($rail);
        $this->renderRows($rail);

        printf(
            '<p><button type="button" class="button" data-edulume-video-add>%s</button> '
            . '<button type="submit" class="button button-primary">%s</button></p></form>',
            esc_html__('Add another video', 'edulume'),
            esc_html__('Save videos', 'edulume')
        );
    }

    /**
     * Tells the owner which videos have no poster.
     *
     * On the admin, never on the front page. The visitor cannot fix it and does not need to know
     * about it; the owner can, and otherwise never finds out.
     */
    public function renderMissingPosterNotice(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            return;
        }

        $missing = $this->load()->itemsMissingPosters();

        if ($missing === []) {
            return;
        }

        printf(
            '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
            esc_html(sprintf(
                /* translators: %d: how many videos have no poster image. */
                _n(
                    '%d video on your front page has no poster image, so it shows a generated panel instead.',
                    '%d videos on your front page have no poster image, so they show generated panels instead.',
                    count($missing),
                    'edulume'
                ),
                count($missing)
            ))
        );
    }

    private function renderTopFields(VideoRail $rail): void
    {
        printf(
            '<p class="edulume-shield__field"><label><input type="checkbox" name="enabled" value="1"%s /> %s</label></p>'
            . '<p class="edulume-shield__field"><label for="edulume-video-title">%s</label>'
            . '<input type="text" id="edulume-video-title" name="title" value="%s" /></p>'
            . '<p class="edulume-shield__field"><label for="edulume-video-subtitle">%s</label>'
            . '<input type="text" id="edulume-video-subtitle" name="subtitle" value="%s" /></p>',
            $rail->enabled ? ' checked' : '',
            esc_html__('Show the video row on the front page', 'edulume'),
            esc_html__('Heading', 'edulume'),
            esc_attr($rail->title),
            esc_html__('Line under the heading', 'edulume'),
            esc_attr($rail->subtitle)
        );

        echo '<p class="edulume-shield__field"><label for="edulume-video-aspect">'
            . esc_html__('Video shape', 'edulume') . '</label><select id="edulume-video-aspect" name="aspect">';

        foreach (VideoRail::ASPECTS as $aspect) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($aspect),
                $aspect === $rail->aspect ? ' selected' : '',
                esc_html($aspect)
            );
        }

        echo '</select></p>';

        printf(
            '<p class="edulume-shield__field"><label><input type="checkbox" name="autoplay" value="1"%s /> %s</label>'
            . '<span class="description">%s</span></p>'
            . '<p class="edulume-shield__field"><label><input type="checkbox" name="consent" value="1"%s /> %s</label></p>',
            $rail->autoplay ? ' checked' : '',
            esc_html__('Start playing when the row scrolls into view', 'edulume'),
            esc_html__(
                'Always without sound — every browser blocks sound until a visitor asks for it. '
                . 'An uploaded video file starts reliably; Facebook and YouTube sometimes will '
                . 'not, and Instagram and TikTok never do. If it matters for a particular clip, '
                . 'upload the file here as well.',
                'edulume'
            ),
            $rail->consent ? ' checked' : '',
            esc_html__('Ask before loading videos from Facebook or YouTube', 'edulume')
        );
    }

    private function renderRows(VideoRail $rail): void
    {
        echo '<ol class="edulume-videos__rows" data-edulume-video-rows>';

        $items = $rail->items;

        if ($items === []) {
            $items = [VideoItem::of(VideoSource::Facebook, '')];
        }

        foreach ($items as $index => $item) {
            $this->renderRow($index, $item);
        }

        echo '</ol>';
    }

    private function renderRow(int $index, VideoItem $item): void
    {
        echo '<li class="edulume-videos__row" data-edulume-video-row>';

        printf(
            '<span class="edulume-sections__handle" aria-hidden="true" data-edulume-drag-handle></span>'
            . '<label><span class="screen-reader-text">%s</span><select name="items[%d][source]">',
            esc_html__('Where the video is hosted', 'edulume'),
            $index
        );

        foreach (VideoSource::cases() as $source) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($source->value),
                $source === $item->source ? ' selected' : '',
                esc_html($source->label())
            );
        }

        printf(
            '</select></label>'
            . '<label><span class="screen-reader-text">%1$s</span>'
            . '<input type="url" name="items[%2$d][url]" value="%3$s" placeholder="%1$s" /></label>'
            . '<label><span class="screen-reader-text">%4$s</span>'
            . '<input type="text" name="items[%2$d][title]" value="%5$s" placeholder="%4$s" /></label>'
            . '<label><span class="screen-reader-text">%6$s</span>'
            . '<input type="text" name="items[%2$d][duration]" value="%7$s" placeholder="%6$s" size="6" /></label>'
            . '<label><span class="screen-reader-text">%8$s</span>'
            . '<input type="number" name="items[%2$d][poster_id]" value="%9$d" placeholder="%8$s" size="6" /></label>'
            . '<button type="button" class="button-link" data-edulume-video-remove>%10$s</button>'
            // What to paste, beside the field rather than only in the help panel at the top:
            // the two that get pasted wrong are Instagram stories and TikTok share links, and
            // both mistakes are made while looking at this input.
            . '<span class="description edulume-videos__hint">%11$s</span></li>',
            esc_attr__('Video address', 'edulume'),
            $index,
            esc_attr($item->url->value),
            esc_attr__('Title', 'edulume'),
            esc_attr($item->title),
            esc_attr__('Length', 'edulume'),
            esc_attr($item->duration),
            esc_attr__('Poster image ID', 'edulume'),
            $item->posterId,
            esc_html__('Remove', 'edulume'),
            esc_html($item->source->urlHint())
        );
    }

    /**
     * The rail as the front page will see it: stored values over the seeded ones.
     *
     * Deliberately the merged view rather than the raw row, so the screen opens showing the
     * sample videos that are actually on the site. An editor who sees an empty repeater while
     * the front page plays four clips has been told something false about their own site.
     */
    private function load(): VideoRail
    {
        return VideoRail::fromArray(
            Guard::toArray(apply_filters(SiteContent::VALUE_FILTER, [], VideoRailProvider::OPTION_KEY))
        );
    }

    /**
     * @return list<array{source: string, url: string, title: string, duration: string, poster_id: string}>
     */
    private function submittedRows(): array
    {
        // Capability and nonce were both checked in `handleSave` before anything is read.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $raw = isset($_POST['items']) && is_array($_POST['items']) ? wp_unslash($_POST['items']) : [];
        $rows = [];

        foreach (array_slice($raw, 0, self::MAXIMUM_ROWS) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rows[] = [
                'source' => sanitize_key((string) ($row['source'] ?? '')),
                // `esc_url_raw`, not `sanitize_text_field`: this has to survive the query string
                // a Facebook watch URL carries, which a text sanitiser would leave intact but a
                // URL sanitiser also normalises.
                'url' => esc_url_raw(trim((string) ($row['url'] ?? ''))),
                'title' => sanitize_text_field((string) ($row['title'] ?? '')),
                'duration' => sanitize_text_field((string) ($row['duration'] ?? '')),
                'poster_id' => sanitize_text_field((string) ($row['poster_id'] ?? '0')),
            ];
        }

        return $rows;
    }

    private function checkbox(string $field): bool
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return isset($_POST[$field]);
    }

    private function text(string $field): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST[$field])) {
            return '';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return sanitize_text_field(wp_unslash((string) $_POST[$field]));
    }

    private function redirect(string $notice): void
    {
        wp_safe_redirect(add_query_arg(
            [
                'page' => 'edulume-videos',
                self::NOTICE_PARAMETER => rawurlencode($notice),
            ],
            admin_url('admin.php')
        ));

        exit;
    }

    private function renderNotice(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $notice = isset($_GET[self::NOTICE_PARAMETER])
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? sanitize_text_field(wp_unslash((string) $_GET[self::NOTICE_PARAMETER]))
            : '';

        if ($notice === '') {
            return;
        }

        printf('<div class="notice notice-success"><p>%s</p></div>', esc_html($notice));
    }
}
