<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Blocks\BlockCatalogue;
use Edulume\Core\Domain\Blocks\BlockDefinition;
use Edulume\Core\Domain\Blocks\BlockGroup;
use Edulume\Core\Domain\Blocks\BlockVariation;
use Edulume\Core\Application\Theming\CompileStylesheet;
use Edulume\Core\Domain\Chrome\AnnouncementBar;
use Edulume\Core\Domain\Chrome\ChromeSettings;
use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Theming\PaletteGenerator;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\SectionOverrideKey;
use Edulume\Core\Domain\Theming\SectionResolver;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\TokenCompiler;
use Edulume\Core\Domain\Lead\FormDefinition;
use Edulume\Core\Domain\Lead\FormField;
use Edulume\Core\Domain\Lead\FormFieldType;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The branches taken when the answer is "no".
 *
 * Each of these is the fallback arm of a loop that returns early on a match — the line reached
 * only when nothing matched. Every existing test supplied data that matched, so the suite had
 * proved the "found it" path repeatedly and the "found nothing" path never once. That is the
 * arm that returns the wrong empty value, and it is the arm a caller hits in production first,
 * because production is where the half-configured form lives.
 */
final class UntakenPathsTest extends TestCase
{
    #[Test]
    public function a_block_with_no_variation_marked_default_falls_back_to_its_first(): void
    {
        $block = new BlockDefinition(
            'hero',
            'Hero',
            'A hero.',
            BlockGroup::Layout,
            [new BlockVariation('plain', 'Plain'), new BlockVariation('split', 'Split')],
        );

        self::assertSame('plain', $block->defaultVariation()->slug);
    }

    #[Test]
    public function a_block_that_marks_a_default_returns_that_one_rather_than_the_first(): void
    {
        $block = new BlockDefinition(
            'hero',
            'Hero',
            'A hero.',
            BlockGroup::Layout,
            [new BlockVariation('plain', 'Plain'), new BlockVariation('split', 'Split', true)],
        );

        self::assertSame('split', $block->defaultVariation()->slug);
    }

    #[Test]
    public function every_shipped_block_resolves_a_default_variation(): void
    {
        foreach (BlockCatalogue::all() as $block) {
            self::assertContains(
                $block->defaultVariation(),
                $block->variations,
                $block->slug,
            );
        }
    }

    #[Test]
    public function a_form_with_no_consent_field_does_not_claim_to_require_consent(): void
    {
        $form = FormDefinition::of('enquiry', 'Enquiry', [
            FormField::of('name', 'Name', FormFieldType::Text),
            FormField::of('email', 'Email', FormFieldType::Email),
        ]);

        self::assertFalse($form->requiresConsent());
    }

    #[Test]
    public function a_form_carrying_a_consent_field_requires_consent(): void
    {
        $form = FormDefinition::of('enquiry', 'Enquiry', [
            FormField::of('name', 'Name', FormFieldType::Text),
            FormField::of('consent', 'I agree', FormFieldType::Consent),
        ]);

        self::assertTrue($form->requiresConsent());
    }

    #[Test]
    public function a_disabled_announcement_never_shows(): void
    {
        $settings = ChromeSettings::fromArray([]);

        self::assertFalse($settings->showsAnnouncementAt(new DateTimeImmutable('2026-01-01 12:00:00')));
    }

    /**
     * A section that overrides nothing must emit nothing.
     *
     * Inheritance is stored as absence, so an override that sets no key resolves identically to
     * global and its token diff is empty. Emitting `[data-edulume-section="hero"] { }` for it
     * would put one empty rule in the stylesheet per section per mode — thirty-odd rules that
     * style nothing, on every page, for the common case of a site that customised none of them.
     */
    #[Test]
    public function a_section_that_overrides_nothing_contributes_no_rule(): void
    {
        $compile = new CompileStylesheet(
            new TokenCompiler(new PaletteGenerator(new ContrastEngine())),
            new SectionResolver(),
        );

        $withoutSections = $compile(ThemeSettings::defaults())->css;
        $withInheritingSection = $compile(
            ThemeSettings::defaults(),
            ['hero' => SectionOverride::inheritEverything()],
        )->css;

        self::assertSame($withoutSections, $withInheritingSection);
        self::assertStringNotContainsString('data-edulume-section', $withInheritingSection);
    }

    /**
     * A section that overrides a value to the value it already had.
     *
     * This is not a contrived case: it is what a preset writes when it states every key
     * explicitly, and what a person produces by nudging a control and putting it back. The
     * override is genuinely set, so it survives the inherit-everything filter and reaches the
     * compiler — and only then turns out to change nothing. Emitting a rule for it would give
     * the section a specificity win over global for tokens it holds identical values for, which
     * is invisible until a later global change stops propagating into that one section.
     */
    #[Test]
    public function a_section_overriding_a_value_to_the_one_it_already_had_contributes_no_rule(): void
    {
        $settings = ThemeSettings::defaults();
        $sameAsGlobal = SectionOverride::inheritEverything()
            ->with(SectionOverrideKey::AccentSlug, $settings->accentSlug);

        self::assertFalse($sameAsGlobal->isInheritingEverything());

        $compile = new CompileStylesheet(
            new TokenCompiler(new PaletteGenerator(new ContrastEngine())),
            new SectionResolver(),
        );

        $css = $compile($settings, ['hero' => $sameAsGlobal])->css;

        self::assertStringNotContainsString('data-edulume-section', $css);
        self::assertSame($compile($settings)->css, $css);
    }

    #[Test]
    public function a_section_that_genuinely_diverges_still_gets_its_rule(): void
    {
        $settings = ThemeSettings::defaults();
        $diverging = SectionOverride::inheritEverything()
            ->with(SectionOverrideKey::AccentSlug, 'ivy-green');

        $compile = new CompileStylesheet(
            new TokenCompiler(new PaletteGenerator(new ContrastEngine())),
            new SectionResolver(),
        );

        $css = $compile($settings, ['hero' => $diverging])->css;

        self::assertStringContainsString('[data-edulume-section="hero"]', $css);
    }

    #[Test]
    public function an_announcement_shows_between_its_start_and_its_end(): void
    {
        $bar = new AnnouncementBar(
            true,
            'Applications close on Friday.',
            '',
            '',
            new DateTimeImmutable('2026-01-01 00:00:00'),
            new DateTimeImmutable('2026-01-31 23:59:59'),
        );

        self::assertTrue($bar->isVisibleAt(new DateTimeImmutable('2026-01-15 09:00:00')));
        self::assertFalse($bar->isVisibleAt(new DateTimeImmutable('2025-12-31 23:59:59')));
        self::assertFalse($bar->isVisibleAt(new DateTimeImmutable('2026-02-01 00:00:01')));
    }
}
