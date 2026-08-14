<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Color\ColorSpace;
use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Color\ContrastRequirement;
use Edulume\Core\Domain\Color\NeutralScale;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\AccentLibrary;
use Edulume\Core\Domain\Theming\AccentPalette;
use Edulume\Core\Domain\Theming\PaletteGenerator;
use Edulume\Core\Domain\Theming\ThemeMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaletteGeneratorTest extends TestCase
{
    private const SAMPLE_COUNT = 400;
    private const SAMPLE_SEED = 20240612;

    private PaletteGenerator $generator;
    private ContrastEngine $contrastEngine;

    protected function setUp(): void
    {
        $this->contrastEngine = new ContrastEngine();
        $this->generator = new PaletteGenerator($this->contrastEngine);
    }

    /**
     * A fixed seed keeps the sample identical on every machine and every run, so a failure
     * is always reproducible.
     *
     * @return list<Srgb>
     */
    private static function sampledSeeds(): array
    {
        mt_srand(self::SAMPLE_SEED);

        $seeds = [];

        for ($sample = 0; $sample < self::SAMPLE_COUNT; $sample++) {
            $seeds[] = Srgb::fromChannels(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        }

        return $seeds;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function curatedAccentProvider(): array
    {
        $cases = [];

        foreach (AccentLibrary::all() as $accent) {
            $cases[$accent->slug] = [$accent->slug];
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('curatedAccentProvider')]
    public function it_generates_a_full_palette_for_every_curated_accent(string $slug): void
    {
        $palette = $this->generator->generate(AccentLibrary::get($slug)->seed);

        $this->assertCount(AccentPalette::STEP_COUNT, $palette->steps());
    }

    #[Test]
    #[DataProvider('curatedAccentProvider')]
    public function it_gives_every_step_a_foreground_that_meets_aa(string $slug): void
    {
        $palette = $this->generator->generate(AccentLibrary::get($slug)->seed);

        for ($index = 0; $index < AccentPalette::STEP_COUNT; $index++) {
            $this->assertTrue(
                $palette->contrastOn($index)->meets(ContrastRequirement::NormalTextAa),
                sprintf(
                    'Step %d of %s renders %s on %s at only %.2f:1.',
                    $index,
                    $slug,
                    $palette->foregroundOn($index)->toHex(),
                    $palette->step($index)->toHex(),
                    $palette->contrastOn($index)->ratio,
                ),
            );
        }
    }

    /**
     * The property that matters most: whatever hex a client types, every step of the palette
     * it generates is legible, and so is the accent text in both modes. Three hand-picked
     * hexes would not prove that; four hundred sampled across the whole cube do.
     */
    #[Test]
    public function it_stays_legible_across_hundreds_of_random_seeds(): void
    {
        foreach (self::sampledSeeds() as $seed) {
            $palette = $this->generator->generate($seed);
            $neutrals = NeutralScale::fromHue(ColorSpace::srgbToOklch($seed)->hue);

            for ($index = 0; $index < AccentPalette::STEP_COUNT; $index++) {
                $this->assertTrue(
                    $palette->contrastOn($index)->meets(ContrastRequirement::NormalTextAa),
                    sprintf('Seed %s failed AA at step %d.', $seed->toHex(), $index),
                );
            }

            $this->assertTrue(
                $this->contrastEngine->meets(
                    $neutrals->lightestInk(),
                    $palette->text(ThemeMode::Light),
                    ContrastRequirement::NormalTextAa,
                ),
                sprintf('Seed %s produced illegible light-mode accent text.', $seed->toHex()),
            );

            $this->assertTrue(
                $this->contrastEngine->meets(
                    $neutrals->darkestInk(),
                    $palette->text(ThemeMode::Dark),
                    ContrastRequirement::NormalTextAa,
                ),
                sprintf('Seed %s produced illegible dark-mode accent text.', $seed->toHex()),
            );
        }
    }

    #[Test]
    public function it_ramps_monotonically_from_light_to_dark(): void
    {
        $steps = $this->generator->generate(Srgb::fromHex('#1a5fb4'))->steps();

        for ($index = 1; $index < count($steps); $index++) {
            $this->assertLessThan(
                $steps[$index - 1]->relativeLuminance(),
                $steps[$index]->relativeLuminance(),
                sprintf('Step %d is not darker than step %d.', $index, $index - 1),
            );
        }
    }

    #[Test]
    public function it_holds_the_seed_hue_across_every_step(): void
    {
        $seedHue = ColorSpace::srgbToOklch(Srgb::fromHex('#1a5fb4'))->hue;

        foreach ($this->generator->generate(Srgb::fromHex('#1a5fb4'))->steps() as $index => $step) {
            $this->assertEqualsWithDelta(
                $seedHue,
                ColorSpace::srgbToOklch($step)->hue,
                12.0,
                sprintf('Step %d drifted away from the seed hue.', $index),
            );
        }
    }

    #[Test]
    public function it_peaks_the_chroma_in_the_middle_of_the_ramp(): void
    {
        $steps = $this->generator->generate(Srgb::fromHex('#1a5fb4'))->steps();

        $middleChroma = ColorSpace::srgbToOklch($steps[6])->chroma;

        $this->assertGreaterThan(ColorSpace::srgbToOklch($steps[0])->chroma, $middleChroma);
        $this->assertGreaterThan(ColorSpace::srgbToOklch($steps[10])->chroma, $middleChroma);
    }

    #[Test]
    public function it_uses_a_brighter_fill_step_in_dark_mode_than_in_light_mode(): void
    {
        $palette = $this->generator->generate(Srgb::fromHex('#1a5fb4'));

        $this->assertLessThan($palette->fillStep(ThemeMode::Light), $palette->fillStep(ThemeMode::Dark));
        $this->assertGreaterThan(
            $palette->fill(ThemeMode::Light)->relativeLuminance(),
            $palette->fill(ThemeMode::Dark)->relativeLuminance(),
        );
    }

    /**
     * The point of carrying two step choices per mode. In dark mode the fill and the accent
     * text are never the same step, and in light mode they diverge for every accent whose
     * hue is light enough that a brighter step is already readable. Collapsing the two would
     * either dull the links or ship an illegible button.
     */
    #[Test]
    public function it_carries_a_separate_fill_and_text_choice_in_each_mode(): void
    {
        $lightModeDivergences = 0;

        foreach (AccentLibrary::all() as $accent) {
            $palette = $this->generator->generate($accent->seed);
            $neutrals = NeutralScale::fromHue(ColorSpace::srgbToOklch($accent->seed)->hue);

            $this->assertNotSame(
                $palette->fill(ThemeMode::Dark)->toHex(),
                $palette->text(ThemeMode::Dark)->toHex(),
                sprintf('%s reused its dark-mode fill as accent text.', $accent->slug),
            );

            if ($palette->fill(ThemeMode::Light)->toHex() !== $palette->text(ThemeMode::Light)->toHex()) {
                $lightModeDivergences++;
            }

            $this->assertTrue($this->contrastEngine->meets(
                $neutrals->lightestInk(),
                $palette->text(ThemeMode::Light),
                ContrastRequirement::NormalTextAa,
            ));

            $this->assertTrue($this->contrastEngine->meets(
                $neutrals->darkestInk(),
                $palette->text(ThemeMode::Dark),
                ContrastRequirement::NormalTextAa,
            ));
        }

        $this->assertGreaterThanOrEqual(8, $lightModeDivergences);
    }

    #[Test]
    public function it_produces_a_grey_ramp_for_a_grey_seed(): void
    {
        foreach ($this->generator->generate(Srgb::fromHex('#808080'))->steps() as $step) {
            $this->assertSame($step->redChannel(), $step->greenChannel());
            $this->assertSame($step->greenChannel(), $step->blueChannel());
        }
    }
}
