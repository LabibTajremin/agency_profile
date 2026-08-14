<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Application\Theming\CompileStylesheet;
use Edulume\Core\Application\Theming\PublishStylesheet;
use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\PaletteGenerator;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\SectionOverrideKey;
use Edulume\Core\Domain\Theming\SectionResolver;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\TokenCompiler;
use Edulume\Core\Tests\Unit\Fake\InMemorySettingsRepository;
use Edulume\Core\Tests\Unit\Fake\InMemoryStylesheetWriter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PublishStylesheetTest extends TestCase
{
    private InMemorySettingsRepository $settingsRepository;
    private InMemoryStylesheetWriter $stylesheetWriter;
    private PublishStylesheet $publish;

    protected function setUp(): void
    {
        $this->settingsRepository = new InMemorySettingsRepository();
        $this->stylesheetWriter = new InMemoryStylesheetWriter();

        $this->publish = new PublishStylesheet(
            $this->settingsRepository,
            new CompileStylesheet(
                new TokenCompiler(new PaletteGenerator(new ContrastEngine())),
                new SectionResolver(),
            ),
            $this->stylesheetWriter,
        );
    }

    #[Test]
    public function it_publishes_the_current_settings_to_a_hashed_file(): void
    {
        $published = ($this->publish)();

        $this->assertStringEndsWith($published->stylesheet->fileName(), $published->url);
        $this->assertSame($published->stylesheet->hash, $published->version());
        $this->assertSame([$published->stylesheet->fileName()], $this->stylesheetWriter->fileNames());
    }

    #[Test]
    public function it_does_not_rewrite_an_unchanged_stylesheet(): void
    {
        ($this->publish)();
        ($this->publish)();

        $this->assertSame(1, $this->stylesheetWriter->writeCount);
    }

    #[Test]
    public function it_writes_a_new_file_when_a_setting_moves(): void
    {
        $first = ($this->publish)();

        $this->settingsRepository->save(ThemeSettings::defaults()->withCustomAccent(Srgb::fromHex('#7a2e6b')));

        $second = ($this->publish)();

        $this->assertNotSame($first->url, $second->url);
        $this->assertSame(2, $this->stylesheetWriter->writeCount);
    }

    #[Test]
    public function it_leaves_only_the_current_stylesheet_behind(): void
    {
        ($this->publish)();

        $this->settingsRepository->save(ThemeSettings::defaults()->withCustomAccent(Srgb::fromHex('#7a2e6b')));

        $second = ($this->publish)();

        $this->assertSame([$second->stylesheet->fileName()], $this->stylesheetWriter->fileNames());
    }

    #[Test]
    public function it_includes_the_stored_section_overrides(): void
    {
        $withoutOverride = ($this->publish)();

        $this->settingsRepository->saveSectionOverride(
            'hero',
            SectionOverride::inheritEverything()->with(SectionOverrideKey::AccentSlug, 'ivy-green'),
        );

        $withOverride = ($this->publish)();

        $this->assertNotSame($withoutOverride->version(), $withOverride->version());
        $this->assertStringContainsString('[data-edulume-section="hero"]', $withOverride->stylesheet->css);
    }

    #[Test]
    public function it_drops_a_section_override_that_returns_to_inheriting_everything(): void
    {
        $this->settingsRepository->saveSectionOverride(
            'hero',
            SectionOverride::inheritEverything()->with(SectionOverrideKey::AccentSlug, 'ivy-green'),
        );
        $this->settingsRepository->saveSectionOverride('hero', SectionOverride::inheritEverything());

        $this->assertSame([], $this->settingsRepository->loadSectionOverrides());
        $this->assertStringNotContainsString('data-edulume-section', ($this->publish)()->stylesheet->css);
    }

    #[Test]
    public function it_reloads_the_settings_on_every_publish(): void
    {
        $before = ($this->publish)()->stylesheet->css;

        $this->settingsRepository->save(ThemeSettings::defaults()->withCustomAccent(Srgb::fromHex('#0f766e')));

        $this->assertNotSame($before, ($this->publish)()->stylesheet->css);
    }

    #[Test]
    public function it_deletes_a_section_override_on_request(): void
    {
        $this->settingsRepository->saveSectionOverride(
            'footer',
            SectionOverride::inheritEverything()->with(SectionOverrideKey::CornerRadiusPixels, 0),
        );

        $this->settingsRepository->deleteSectionOverride('footer');

        $this->assertSame([], $this->settingsRepository->loadSectionOverrides());
    }

    #[Test]
    public function it_round_trips_a_saved_setting_through_the_repository(): void
    {
        $settings = ThemeSettings::defaults()->withCustomAccent(Srgb::fromHex('#7a2e6b'));

        $this->settingsRepository->save($settings);

        $this->assertSame($settings->toArray(), $this->settingsRepository->load()->toArray());
        $this->assertSame(1, $this->settingsRepository->saveCount);
    }
}
