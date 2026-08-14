<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\InvalidPatternException;
use Edulume\Core\Domain\Theming\Pattern;
use Edulume\Core\Domain\Theming\PatternGroup;
use Edulume\Core\Domain\Theming\PatternLibrary;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PatternLibraryTest extends TestCase
{
    private const TINT = '#1a5fb4';

    /**
     * @return array<string, array{string}>
     */
    public static function patternProvider(): array
    {
        $cases = [];

        foreach (PatternLibrary::slugs() as $slug) {
            $cases[$slug] = [$slug];
        }

        return $cases;
    }

    #[Test]
    public function it_ships_twenty_eight_patterns(): void
    {
        $this->assertCount(PatternLibrary::PATTERN_COUNT, PatternLibrary::all());
        $this->assertCount(PatternLibrary::PATTERN_COUNT, PatternLibrary::slugs());
    }

    #[Test]
    #[DataProvider('patternProvider')]
    public function it_emits_well_formed_svg_for_every_pattern(string $slug): void
    {
        $markup = PatternLibrary::get($slug)->markupTintedWith(Srgb::fromHex(self::TINT));

        $previous = libxml_use_internal_errors(true);
        $parsed = simplexml_load_string($markup);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($parsed, sprintf('Pattern "%s" does not parse as XML.', $slug));
        $this->assertSame('svg', $parsed->getName());
    }

    #[Test]
    #[DataProvider('patternProvider')]
    public function it_substitutes_the_tint_into_every_pattern(string $slug): void
    {
        $markup = PatternLibrary::get($slug)->markupTintedWith(Srgb::fromHex(self::TINT));

        $this->assertStringContainsString(self::TINT, $markup);
        $this->assertStringNotContainsString(Pattern::TINT_PLACEHOLDER, $markup);
    }

    #[Test]
    #[DataProvider('patternProvider')]
    public function it_emits_a_data_uri_that_survives_css_url(string $slug): void
    {
        $dataUri = PatternLibrary::get($slug)->toDataUri(Srgb::fromHex(self::TINT));

        $this->assertStringStartsWith('data:image/svg+xml,', $dataUri);

        $payload = substr($dataUri, strlen('data:image/svg+xml,'));

        foreach (['<', '>', '#', '"', "'", ' ', "\n"] as $breakingCharacter) {
            $this->assertStringNotContainsString(
                $breakingCharacter,
                $payload,
                sprintf('Pattern "%s" leaves %s unescaped in its data URI.', $slug, var_export($breakingCharacter, true)),
            );
        }
    }

    #[Test]
    #[DataProvider('patternProvider')]
    public function it_round_trips_a_data_uri_back_to_the_original_markup(string $slug): void
    {
        $pattern = PatternLibrary::get($slug);
        $tint = Srgb::fromHex(self::TINT);
        $payload = substr($pattern->toDataUri($tint), strlen('data:image/svg+xml,'));

        $this->assertSame($pattern->markupTintedWith($tint), rawurldecode($payload));
    }

    #[Test]
    public function it_ships_the_four_patterns_this_niche_asks_for(): void
    {
        foreach (['passport-stamps', 'globe-meridians', 'graduation-caps', 'compass-rose'] as $slug) {
            $this->assertTrue(PatternLibrary::has($slug), sprintf('Pattern "%s" is missing.', $slug));
            $this->assertSame(PatternGroup::Travel, PatternLibrary::get($slug)->group);
        }
    }

    #[Test]
    public function it_groups_every_pattern(): void
    {
        $grouped = 0;

        foreach (PatternGroup::cases() as $group) {
            $inGroup = PatternLibrary::inGroup($group);

            $this->assertNotEmpty($inGroup, sprintf('Group "%s" has no patterns.', $group->label()));

            $grouped += count($inGroup);
        }

        $this->assertSame(PatternLibrary::PATTERN_COUNT, $grouped);
    }

    #[Test]
    public function it_gives_every_pattern_a_unique_slug_and_name(): void
    {
        $names = array_map(static fn (Pattern $pattern): string => $pattern->name, PatternLibrary::all());

        $this->assertSame($names, array_values(array_unique($names)));
        $this->assertSame(PatternLibrary::slugs(), array_values(array_unique(PatternLibrary::slugs())));
    }

    #[Test]
    public function it_reports_whether_a_slug_is_known(): void
    {
        $this->assertTrue(PatternLibrary::has('dot-grid'));
        $this->assertFalse(PatternLibrary::has('plaid-of-doom'));
    }

    #[Test]
    public function it_rejects_a_slug_it_does_not_know(): void
    {
        $this->expectException(InvalidPatternException::class);

        PatternLibrary::get('plaid-of-doom');
    }
}
