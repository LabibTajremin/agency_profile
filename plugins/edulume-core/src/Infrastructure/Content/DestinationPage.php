<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Support\Guard;
use Edulume\Core\Domain\Video\IntakeVideo;
use Edulume\Core\Domain\Video\IntakeVideoTier;
use Edulume\Core\Domain\Video\VideoRail;

/**
 * Everything a destination page needs, assembled where the data lives.
 *
 * Universities are found through the relationship meta the content model already registers —
 * `_edulume_rel_institution_destination` — rather than through a second taxonomy invented for
 * this page. That relation is fine up to a few hundred institutions per country; past that the
 * meta query is the wrong index and it should become a taxonomy. Noted here and in the readme
 * rather than discovered on a slow page.
 *
 * The intake video resolves through three tiers so the block is never empty on any country:
 * the destination's own setting, the site-wide one, then the first video from the front page's
 * rail. That last tier is why a site that has done nothing but add two promo videos still has a
 * video on every country page.
 */
final class DestinationPage
{
    public const DATA_FILTER = 'edulume_destination_page';
    public const DEMO_VIDEO_FILTER = 'edulume_demo_intake_video';

    public const INTAKE_VIDEO_META = '_edulume_intake_video';
    public const FLAG_META = '_edulume_flag_id';
    public const STATS_META = '_edulume_stats';
    public const GLOBAL_VIDEO_OPTION = 'edulume_intake_video_global';

    /** The point past which a meta query stops being the right tool. */
    private const UNIVERSITY_CEILING = 150;

    public function __construct(private readonly VideoRailProvider $rail = new VideoRailProvider())
    {
    }

    public function register(): void
    {
        add_action('init', [$this, 'registerMeta'], 16);
        add_filter(self::DATA_FILTER, [$this, 'data'], 10, 2);
        add_filter('manage_edulume_destination_posts_columns', [$this, 'addVideoColumn']);
        add_action('manage_edulume_destination_posts_custom_column', [$this, 'renderVideoColumn'], 10, 2);
    }

    public function registerMeta(): void
    {
        register_post_meta(ContentModel::postTypeKey('destination'), self::FLAG_META, [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
            'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
        ]);

        /*
         * The video and the stats are objects, so they are registered without `show_in_rest`.
         * Exposing a free-form array through REST without a schema is how a plugin ends up with
         * a write endpoint that accepts anything.
         */
        foreach ([self::INTAKE_VIDEO_META, self::STATS_META] as $key) {
            register_post_meta(ContentModel::postTypeKey('destination'), $key, [
                'type' => 'object',
                'single' => true,
                'show_in_rest' => false,
                'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $fallback
     *
     * @return array<string, mixed>
     */
    public function data(array $fallback, int $destinationId): array
    {
        unset($fallback);

        $universities = $this->universities($destinationId);
        $video = $this->intakeVideo($destinationId);

        return [
            'universities' => $universities,
            'filterClientSide' => count($universities) <= self::UNIVERSITY_CEILING,
            'stats' => Guard::toArray(get_post_meta($destinationId, self::STATS_META, true)),
            'flagId' => (int) get_post_meta($destinationId, self::FLAG_META, true),
            'videoTier' => $video->tier->value,
            'video' => $this->rail->prepare(
                VideoRail::fromArray($video->asRail()),
                'destination'
            ),
        ];
    }

    public function intakeVideo(int $destinationId): IntakeVideo
    {
        return IntakeVideo::resolve(
            Guard::toArray(get_post_meta($destinationId, self::INTAKE_VIDEO_META, true)),
            Guard::toArray(get_option(self::GLOBAL_VIDEO_OPTION, [])),
            $this->demoVideo()
        );
    }

    /**
     * @param array<string, string> $columns
     *
     * @return array<string, string>
     */
    public function addVideoColumn(array $columns): array
    {
        $columns['edulume_intake_video'] = __('Intake video', 'edulume');

        return $columns;
    }

    public function renderVideoColumn(string $column, int $postId): void
    {
        if ($column !== 'edulume_intake_video') {
            return;
        }

        $tier = $this->intakeVideo($postId)->tier;

        printf(
            '<span class="edulume-tier edulume-tier--%s">%s</span>',
            esc_attr($tier->value),
            esc_html($tier === IntakeVideoTier::None ? __('None', 'edulume') : $tier->label())
        );
    }

    /**
     * The last-resort video.
     *
     * The first item from the front page's rail, because a site that has added two promo videos
     * has already given us something better than nothing, and bundling a video file in a theme
     * costs megabytes every install pays for and almost none use. Filterable so a distributor
     * who does want to ship one can.
     *
     * @return array<string, mixed>
     */
    private function demoVideo(): array
    {
        $rail = VideoRail::fromArray(
            Guard::toArray(
                Guard::toArray(get_option(SiteContent::OPTION, []))[VideoRailProvider::OPTION_KEY] ?? null
            )
        );

        $items = $rail->playableItems();
        $first = $items === [] ? [] : $items[0]->toArray();

        return Guard::toArray(apply_filters(self::DEMO_VIDEO_FILTER, $first));
    }

    /**
     * The institutions attached to a destination.
     *
     * Bounded, ordered by title, and with `no_found_rows` — a country page is not paginated, so
     * counting the total is work nothing spends.
     *
     * @return list<\WP_Post>
     */
    private function universities(int $destinationId): array
    {
        $posts = get_posts([
            'post_type' => ContentModel::postTypeKey('institution'),
            'post_status' => 'publish',
            'posts_per_page' => 300,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
            'meta_key' => '_edulume_rel_institution_destination',
            'meta_value' => (string) $destinationId,
        ]);

        return is_array($posts)
            ? array_values(array_filter($posts, static fn ($post): bool => $post instanceof \WP_Post))
            : [];
    }
}
