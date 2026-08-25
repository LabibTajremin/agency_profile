<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Video;

use Edulume\Core\Domain\Video\IntakeVideo;
use Edulume\Core\Domain\Video\IntakeVideoTier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * "Every country must have a video."
 *
 * That promise is only kept if a broken setting falls through instead of winning. Each test
 * here is one way a country page ends up with an empty block on a live site.
 */
#[CoversClass(IntakeVideo::class)]
#[CoversClass(IntakeVideoTier::class)]
final class IntakeVideoTest extends TestCase
{
    private const OWN = ['source' => 'youtube', 'url' => 'https://youtu.be/dQw4w9WgXcQ'];
    private const GLOBAL = ['source' => 'youtube', 'url' => 'https://youtu.be/aQw4w9WgXcQ'];
    private const DEMO = ['source' => 'mp4', 'url' => 'https://example.com/sample.mp4'];

    public function testTheCountrysOwnVideoWins(): void
    {
        $resolved = IntakeVideo::resolve(self::OWN, self::GLOBAL, self::DEMO);

        self::assertSame(IntakeVideoTier::Own, $resolved->tier);
        self::assertSame('dQw4w9WgXcQ', $resolved->item?->url->value);
    }

    public function testWithNoOwnVideoTheSiteWideOneIsUsed(): void
    {
        $resolved = IntakeVideo::resolve([], self::GLOBAL, self::DEMO);

        self::assertSame(IntakeVideoTier::Global, $resolved->tier);
        self::assertSame('aQw4w9WgXcQ', $resolved->item?->url->value);
    }

    public function testWithNeitherTheSampleIsUsed(): void
    {
        $resolved = IntakeVideo::resolve([], [], self::DEMO);

        self::assertSame(IntakeVideoTier::Demo, $resolved->tier);
        self::assertTrue($resolved->hasVideo());
    }

    public function testASwitchedOffOwnVideoFallsThroughRatherThanWinning(): void
    {
        $own = self::OWN + ['enabled' => false];

        self::assertSame(IntakeVideoTier::Global, IntakeVideo::resolve($own, self::GLOBAL, self::DEMO)->tier);
    }

    public function testABrokenUrlFallsThroughRatherThanRenderingNothing(): void
    {
        $own = ['source' => 'youtube', 'url' => 'https://www.youtube.com/@sparkpath'];

        self::assertSame(IntakeVideoTier::Global, IntakeVideo::resolve($own, self::GLOBAL, self::DEMO)->tier);
    }

    public function testABrokenSiteWideVideoFallsThroughToo(): void
    {
        $global = ['source' => 'mp4', 'url' => 'http://example.com/insecure.mp4'];

        self::assertSame(IntakeVideoTier::Demo, IntakeVideo::resolve([], $global, self::DEMO)->tier);
    }

    public function testWithNothingAnywhereThereIsNoVideoAndNothingPretendsOtherwise(): void
    {
        $resolved = IntakeVideo::resolve([], [], []);

        self::assertSame(IntakeVideoTier::None, $resolved->tier);
        self::assertFalse($resolved->hasVideo());
        self::assertNull($resolved->item);
        self::assertSame(['enabled' => false, 'items' => []], $resolved->asRail());
    }

    public function testTheResolvedVideoIsHandedToTheRailComponent(): void
    {
        $rail = IntakeVideo::resolve(self::OWN, [], [])->asRail('This intake');

        self::assertTrue($rail['enabled']);
        self::assertSame('This intake', $rail['title']);
        self::assertSame('16:9', $rail['aspect']);
        self::assertCount(1, $rail['items']);
    }

    public function testEveryTierNamesItselfAndKnowsWhetherItIsAFallback(): void
    {
        foreach (IntakeVideoTier::cases() as $tier) {
            self::assertNotSame('', $tier->label());
        }

        self::assertFalse(IntakeVideoTier::Own->isFallback());
        self::assertFalse(IntakeVideoTier::None->isFallback());
        self::assertTrue(IntakeVideoTier::Global->isFallback());
        self::assertTrue(IntakeVideoTier::Demo->isFallback());
    }
}
