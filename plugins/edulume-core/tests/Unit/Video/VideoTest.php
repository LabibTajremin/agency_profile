<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Video;

use Edulume\Core\Domain\Video\VideoItem;
use Edulume\Core\Domain\Video\VideoRail;
use Edulume\Core\Domain\Video\VideoSource;
use Edulume\Core\Domain\Video\VideoUrl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The URL parsers, and the promises the rail makes to the template.
 *
 * Every accepted shape here is one somebody will paste; every rejected one is a card that would
 * otherwise render as a grey rectangle on a live front page with nothing anywhere saying why.
 */
#[CoversClass(VideoSource::class)]
#[CoversClass(VideoUrl::class)]
#[CoversClass(VideoItem::class)]
#[CoversClass(VideoRail::class)]
final class VideoTest extends TestCase
{
    #[DataProvider('validFacebookUrls')]
    public function testAFacebookUrlIsRecognised(string $url): void
    {
        self::assertTrue(VideoUrl::parse(VideoSource::Facebook, $url)->isValid, $url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validFacebookUrls(): array
    {
        return [
            'page video' => ['https://www.facebook.com/sparkpath/videos/1234567890/'],
            'no www' => ['https://facebook.com/sparkpath/videos/open-day-2026/'],
            'watch' => ['https://www.facebook.com/watch/?v=1234567890'],
            'reel' => ['https://www.facebook.com/reel/1234567890'],
            'video.php' => ['https://www.facebook.com/video.php?v=1234567890'],
            'fb.watch' => ['https://fb.watch/aB3dE-f9x/'],
        ];
    }

    #[DataProvider('invalidFacebookUrls')]
    public function testANonFacebookUrlIsRefused(string $url): void
    {
        self::assertFalse(VideoUrl::parse(VideoSource::Facebook, $url)->isValid, $url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidFacebookUrls(): array
    {
        return [
            'plain http' => ['http://www.facebook.com/sparkpath/videos/1234567890/'],
            'a profile, not a video' => ['https://www.facebook.com/sparkpath/'],
            'somebody else entirely' => ['https://vimeo.com/1234567890'],
            'empty' => [''],
        ];
    }

    #[DataProvider('youTubeShapes')]
    public function testEveryYouTubeShapeReducesToTheSameId(string $url): void
    {
        $parsed = VideoUrl::parse(VideoSource::YouTube, $url);

        self::assertTrue($parsed->isValid, $url);
        self::assertSame('dQw4w9WgXcQ', $parsed->value, $url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function youTubeShapes(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'watch with other parameters first' => ['https://www.youtube.com/watch?list=PL1&v=dQw4w9WgXcQ'],
            'short link' => ['https://youtu.be/dQw4w9WgXcQ'],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ'],
            'live' => ['https://www.youtube.com/live/dQw4w9WgXcQ'],
            'nocookie' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'a bare id' => ['dQw4w9WgXcQ'],
        ];
    }

    #[DataProvider('badYouTubeUrls')]
    public function testARubbishYouTubeUrlIsRefused(string $url): void
    {
        self::assertFalse(VideoUrl::parse(VideoSource::YouTube, $url)->isValid, $url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function badYouTubeUrls(): array
    {
        return [
            'a channel' => ['https://www.youtube.com/@sparkpath'],
            'an id of the wrong length' => ['https://youtu.be/tooshort'],
            'not youtube' => ['https://example.com/watch?v=dQw4w9WgXcQ'],
            'empty' => [''],
        ];
    }

    #[DataProvider('instagramShapes')]
    public function testAnInstagramUrlKeepsItsPathSegment(string $url, string $expected): void
    {
        $parsed = VideoUrl::parse(VideoSource::Instagram, $url);

        self::assertTrue($parsed->isValid, $url);
        // Instagram serves a different embed per segment; guessing wrong renders an empty frame.
        self::assertSame($expected, $parsed->value, $url);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function instagramShapes(): array
    {
        return [
            'post' => ['https://www.instagram.com/p/CyA1b2C3d4e/', 'p/CyA1b2C3d4e'],
            'post without www' => ['https://instagram.com/p/CyA1b2C3d4e/', 'p/CyA1b2C3d4e'],
            'reel' => ['https://www.instagram.com/reel/CyA1b2C3d4e/', 'reel/CyA1b2C3d4e'],
            // The app links to the plural; only the singular answers on the embed endpoint.
            'reels, as the app writes it' => [
                'https://www.instagram.com/reels/CyA1b2C3d4e/',
                'reel/CyA1b2C3d4e',
            ],
            'igtv' => ['https://www.instagram.com/tv/CyA1b2C3d4e/', 'tv/CyA1b2C3d4e'],
            'with tracking parameters' => [
                'https://www.instagram.com/reel/CyA1b2C3d4e/?igsh=abc123&utm_source=ig_web',
                'reel/CyA1b2C3d4e',
            ],
        ];
    }

    #[DataProvider('badInstagramUrls')]
    public function testARubbishInstagramUrlIsRefused(string $url): void
    {
        self::assertFalse(VideoUrl::parse(VideoSource::Instagram, $url)->isValid, $url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function badInstagramUrls(): array
    {
        return [
            'a profile, not a post' => ['https://www.instagram.com/sparkpath/'],
            'plain http' => ['http://www.instagram.com/p/CyA1b2C3d4e/'],
            'stories, which have no embed' => ['https://www.instagram.com/stories/sparkpath/123/'],
            'not instagram' => ['https://example.com/p/CyA1b2C3d4e/'],
            'empty' => [''],
        ];
    }

    #[DataProvider('tikTokShapes')]
    public function testATikTokUrlReducesToItsVideoId(string $url): void
    {
        $parsed = VideoUrl::parse(VideoSource::TikTok, $url);

        self::assertTrue($parsed->isValid, $url);
        self::assertSame('7234567890123456789', $parsed->value, $url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function tikTokShapes(): array
    {
        return [
            'full url' => ['https://www.tiktok.com/@sparkpath/video/7234567890123456789'],
            'no www' => ['https://tiktok.com/@sparkpath/video/7234567890123456789'],
            'with parameters' => [
                'https://www.tiktok.com/@sparkpath/video/7234567890123456789?is_from_webapp=1',
            ],
            'an embed url pasted back in' => [
                'https://www.tiktok.com/embed/v2/7234567890123456789',
            ],
            'a player url' => ['https://www.tiktok.com/player/v1/7234567890123456789'],
            'a bare id' => ['7234567890123456789'],
        ];
    }

    #[DataProvider('badTikTokUrls')]
    public function testATikTokLinkWithNoIdInItIsRefused(string $url): void
    {
        self::assertFalse(VideoUrl::parse(VideoSource::TikTok, $url)->isValid, $url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function badTikTokUrls(): array
    {
        return [
            // Resolving a share link means following a redirect, which is a network call this
            // refuses to make while somebody is waiting for a save to complete.
            'vm share link' => ['https://vm.tiktok.com/ZMabc123/'],
            't share link' => ['https://www.tiktok.com/t/ZTabc123/'],
            'a profile' => ['https://www.tiktok.com/@sparkpath'],
            'plain http' => ['http://www.tiktok.com/@sparkpath/video/7234567890123456789'],
            'empty' => [''],
        ];
    }

    public function testTheNewHostsBuildTheEmbedUrlsTheirPlayersAnswerTo(): void
    {
        $instagram = VideoUrl::parse(VideoSource::Instagram, 'https://www.instagram.com/reel/CyA1b2C3d4e/');
        $tiktok = VideoUrl::parse(VideoSource::TikTok, 'https://www.tiktok.com/@sparkpath/video/7234567890123456789');

        self::assertSame('https://www.instagram.com/reel/CyA1b2C3d4e/embed/', $instagram->embedUrl());
        self::assertSame('https://www.tiktok.com/embed/v2/7234567890123456789', $tiktok->embedUrl());
    }

    public function testNeitherNewHostHasAnAutoplayParameterToInvent(): void
    {
        $instagram = VideoUrl::parse(VideoSource::Instagram, 'https://www.instagram.com/p/CyA1b2C3d4e/');

        // The flag is accepted and ignored rather than turned into a query parameter Instagram
        // has never documented — a made-up parameter breaks the day they start validating.
        self::assertSame($instagram->embedUrl(true), $instagram->embedUrl(false));
        self::assertStringNotContainsString('autoplay', $instagram->embedUrl());

        self::assertFalse(VideoSource::Instagram->acceptsAutoplayRequest());
        self::assertFalse(VideoSource::TikTok->acceptsAutoplayRequest());
        self::assertTrue(VideoSource::Facebook->acceptsAutoplayRequest());
        self::assertTrue(VideoSource::Mp4->acceptsAutoplayRequest());
    }

    public function testTheVerticalHostsCarryTheirOwnShape(): void
    {
        // A landscape frame around a TikTok gives two black pillars and a third of the width.
        self::assertSame('9:16', VideoSource::Instagram->nativeAspect());
        self::assertSame('9:16', VideoSource::TikTok->nativeAspect());
        self::assertSame('', VideoSource::YouTube->nativeAspect());
        self::assertSame('', VideoSource::Mp4->nativeAspect());
    }

    public function testEverySourceNamesItselfAndSaysWhatToPaste(): void
    {
        foreach (VideoSource::cases() as $source) {
            self::assertNotSame('', $source->label(), $source->value);
            self::assertNotSame('', $source->urlHint(), $source->value);
        }

        // Spelled the way the companies spell them; `ucfirst()` gets both of these wrong.
        self::assertSame('TikTok', VideoSource::TikTok->label());
        self::assertSame('YouTube', VideoSource::YouTube->label());
    }

    #[DataProvider('mp4Cases')]
    public function testAFileIsAcceptedOnlyWhenItLooksLikeOne(string $url, bool $expected): void
    {
        self::assertSame($expected, VideoUrl::parse(VideoSource::Mp4, $url)->isValid, $url);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function mp4Cases(): array
    {
        return [
            'https mp4' => ['https://example.com/wp-content/uploads/2026/08/promo.mp4', true],
            'relative upload' => ['/wp-content/uploads/2026/08/promo.mp4', true],
            'webm' => ['https://example.com/promo.webm', true],
            'query string after the extension' => ['https://example.com/promo.mp4?v=2', true],
            'plain http' => ['http://example.com/promo.mp4', false],
            'a page, not a file' => ['https://example.com/promo', false],
            'the wrong kind of file' => ['https://example.com/promo.pdf', false],
            'empty' => ['', false],
        ];
    }

    public function testTheEmbedUrlAlwaysMutes(): void
    {
        $facebook = VideoUrl::parse(VideoSource::Facebook, 'https://www.facebook.com/watch/?v=1234567890');
        $youtube = VideoUrl::parse(VideoSource::YouTube, 'https://youtu.be/dQw4w9WgXcQ');

        self::assertStringContainsString('mute=1', $facebook->embedUrl());
        self::assertStringContainsString('mute=1', $youtube->embedUrl());
    }

    public function testAutoplayCanBeTurnedOffInTheEmbedUrl(): void
    {
        $youtube = VideoUrl::parse(VideoSource::YouTube, 'https://youtu.be/dQw4w9WgXcQ');

        self::assertStringContainsString('autoplay=0', $youtube->embedUrl(false));
        self::assertStringContainsString('autoplay=1', $youtube->embedUrl(true));
    }

    public function testTheFacebookEmbedCarriesTheWholeUrlEncoded(): void
    {
        $url = 'https://www.facebook.com/sparkpath/videos/1234567890/';
        $embed = VideoUrl::parse(VideoSource::Facebook, $url)->embedUrl();

        self::assertStringContainsString('plugins/video.php?href=' . rawurlencode($url), $embed);
    }

    public function testAYouTubeEmbedUsesTheNoCookieHost(): void
    {
        $embed = VideoUrl::parse(VideoSource::YouTube, 'https://youtu.be/dQw4w9WgXcQ')->embedUrl();

        self::assertStringStartsWith('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $embed);
    }

    public function testAnInvalidUrlHasNoEmbed(): void
    {
        self::assertSame('', VideoUrl::parse(VideoSource::YouTube, 'nonsense')->embedUrl());
    }

    public function testAnMp4EmbedIsJustTheFile(): void
    {
        $url = 'https://example.com/promo.mp4';

        self::assertSame($url, VideoUrl::parse(VideoSource::Mp4, $url)->embedUrl());
    }

    public function testOnlyTheUploadedFileIsPromisedReliableAutoplay(): void
    {
        self::assertTrue(VideoSource::Mp4->autoplayIsReliable());
    }

    public function testOnlyTheThirdPartySourcesNeedConsent(): void
    {
        foreach ([VideoSource::Facebook, VideoSource::YouTube, VideoSource::Instagram, VideoSource::TikTok] as $source) {
            self::assertTrue($source->isThirdParty(), $source->value);
            self::assertFalse($source->autoplayIsReliable(), $source->value);
        }

        self::assertFalse(VideoSource::Mp4->isThirdParty());
    }

    public function testAnItemRoundTripsThroughStorage(): void
    {
        $item = VideoItem::of(
            VideoSource::YouTube,
            'https://youtu.be/dQw4w9WgXcQ',
            '  Open day  ',
            42,
            '  1:20  '
        );

        self::assertSame('Open day', $item->title);
        self::assertSame('1:20', $item->duration);
        self::assertSame(42, $item->posterId);
        self::assertTrue($item->isPlayable());
        self::assertTrue($item->needsConsent());

        $restored = VideoItem::fromArray($item->toArray());

        self::assertSame($item->toArray(), $restored->toArray());
    }

    public function testAnItemNeedsNothingButASourceAndAUrl(): void
    {
        $item = VideoItem::of(VideoSource::Mp4, 'https://example.com/promo.mp4');

        self::assertSame('', $item->title);
        self::assertSame('', $item->duration);
        self::assertSame(0, $item->posterId);
        self::assertTrue($item->isPlayable());
        self::assertFalse($item->needsConsent());
        self::assertSame('https://example.com/promo.mp4', $item->embedUrl());
    }

    public function testAnItemStoredWithNonsenseStillLoads(): void
    {
        $item = VideoItem::fromArray(['source' => 'myspace', 'poster_id' => -4]);

        self::assertSame(VideoSource::Mp4, $item->source);
        self::assertSame(0, $item->posterId);
        self::assertFalse($item->isPlayable());
    }

    public function testUnplayableItemsNeverReachTheTemplate(): void
    {
        $rail = VideoRail::fromArray([
            'items' => [
                ['source' => 'youtube', 'url' => 'https://youtu.be/dQw4w9WgXcQ'],
                ['source' => 'youtube', 'url' => 'https://www.youtube.com/@sparkpath'],
                'not even an array',
            ],
        ]);

        // The row that is not an array never becomes an item at all; the one whose URL does not
        // parse becomes an item and is dropped on the way to the template.
        self::assertCount(2, $rail->items);
        self::assertCount(1, $rail->playableItems());
        self::assertTrue($rail->hasAnythingToShow());
    }

    public function testARailWithNothingPlayableShowsNothing(): void
    {
        $rail = VideoRail::fromArray(['items' => [['source' => 'youtube', 'url' => 'rubbish']]]);

        self::assertFalse($rail->hasAnythingToShow());
    }

    public function testASwitchedOffRailShowsNothingHoweverManyVideosItHas(): void
    {
        $rail = VideoRail::fromArray([
            'enabled' => false,
            'items' => [['source' => 'youtube', 'url' => 'https://youtu.be/dQw4w9WgXcQ']],
        ]);

        self::assertFalse($rail->hasAnythingToShow());
    }

    public function testARailDefaultsToOnWithAPortraitShape(): void
    {
        $rail = VideoRail::fromArray([]);

        self::assertTrue($rail->enabled);
        self::assertTrue($rail->autoplay);
        self::assertFalse($rail->consent);
        self::assertSame('9:16', $rail->aspect);
        self::assertSame('9 / 16', $rail->aspectRatio());
    }

    public function testAnUnknownShapeFallsBackRatherThanReachingTheStylesheet(): void
    {
        self::assertSame('9:16', VideoRail::fromArray(['aspect' => 'banana'])->aspect);
        self::assertSame('16 / 9', VideoRail::fromArray(['aspect' => '16:9'])->aspectRatio());
    }

    public function testTheOwnerIsToldWhichVideosHaveNoPoster(): void
    {
        $rail = VideoRail::fromArray([
            'items' => [
                ['source' => 'youtube', 'url' => 'https://youtu.be/dQw4w9WgXcQ', 'poster_id' => 12],
                ['source' => 'youtube', 'url' => 'https://youtu.be/aQw4w9WgXcQ'],
                ['source' => 'youtube', 'url' => 'rubbish'],
            ],
        ]);

        self::assertCount(1, $rail->itemsMissingPosters());
    }

    public function testARailRoundTripsThroughStorage(): void
    {
        $rail = VideoRail::fromArray([
            'enabled' => true,
            'title' => 'Student stories',
            'subtitle' => 'Two minutes each',
            'aspect' => '1:1',
            'autoplay' => false,
            'consent' => true,
            'items' => [['source' => 'mp4', 'url' => 'https://example.com/promo.mp4', 'title' => 'Promo']],
        ]);

        self::assertSame($rail->toArray(), VideoRail::fromArray($rail->toArray())->toArray());
    }
}
