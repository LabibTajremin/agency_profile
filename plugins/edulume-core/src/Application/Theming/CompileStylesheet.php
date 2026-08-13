<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Theming;

use Edulume\Core\Domain\Theming\ResolvedSection;
use Edulume\Core\Domain\Theming\SectionId;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\SectionResolver;
use Edulume\Core\Domain\Theming\ThemeMode;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\TokenCompiler;

/**
 * Produces the whole stylesheet: `:root` tokens, the dark-mode re-declarations, one scoped
 * block per overridden section, the pattern layer, and a reduced-motion block.
 *
 * The reduced-motion block is emitted **last**, deliberately. Specificity and source order
 * decide the winner in CSS, and a visitor who has asked their operating system to reduce
 * motion must win against every stored setting, however specific.
 */
final class CompileStylesheet
{
    public const SECTION_ATTRIBUTE = 'data-edulume-section';
    public const DARK_MODE_ATTRIBUTE = 'data-theme';

    private const INDENT = '  ';

    public function __construct(
        private readonly TokenCompiler $tokenCompiler,
        private readonly SectionResolver $sectionResolver,
    ) {
    }

    /**
     * @param array<string, SectionOverride> $sectionOverrides keyed by section id
     */
    public function __invoke(ThemeSettings $settings, array $sectionOverrides = []): CompiledStylesheet
    {
        $blocks = [
            $this->rootBlock($settings),
            $this->darkModeBlock($settings),
        ];

        foreach ($this->overriddenSections($sectionOverrides) as $sectionId => $override) {
            $blocks[] = $this->sectionBlock($sectionId, $settings, $override, ThemeMode::Light);
            $blocks[] = $this->sectionBlock($sectionId, $settings, $override, ThemeMode::Dark);
        }

        $blocks[] = $this->patternLayerBlock();
        $blocks[] = $this->reducedMotionBlock();

        return CompiledStylesheet::of(implode("\n\n", array_filter($blocks)) . "\n");
    }

    /**
     * @param array<string, SectionOverride> $sectionOverrides
     *
     * @return array<string, SectionOverride>
     */
    private function overriddenSections(array $sectionOverrides): array
    {
        $overridden = [];

        foreach (SectionId::cases() as $section) {
            $override = $sectionOverrides[$section->value] ?? null;

            if ($override !== null && !$override->isInheritingEverything()) {
                $overridden[$section->value] = $override;
            }
        }

        return $overridden;
    }

    private function rootBlock(ThemeSettings $settings): string
    {
        return $this->block(':root', $this->tokenCompiler->compile($settings, ThemeMode::Light));
    }

    private function darkModeBlock(ThemeSettings $settings): string
    {
        $light = $this->tokenCompiler->compile($settings, ThemeMode::Light);
        $dark = $this->tokenCompiler->compile($settings, ThemeMode::Dark);

        return $this->block(
            sprintf('[%s="%s"]', self::DARK_MODE_ATTRIBUTE, ThemeMode::Dark->value),
            $this->changedTokens($light, $dark),
        );
    }

    private function sectionBlock(
        string $sectionId,
        ThemeSettings $settings,
        SectionOverride $override,
        ThemeMode $mode
    ): string {
        $section = SectionId::from($sectionId);
        $resolved = $this->sectionResolver->resolve($section, $settings, $override);

        $global = $this->tokenCompiler->compile($settings, $mode);
        $scoped = $this->tokenCompiler->compile($this->asGlobalSettings($settings, $resolved), $mode);

        $selector = sprintf('[%s="%s"]', self::SECTION_ATTRIBUTE, $sectionId);

        if ($mode === ThemeMode::Dark) {
            $selector = sprintf('[%s="%s"] %s', self::DARK_MODE_ATTRIBUTE, ThemeMode::Dark->value, $selector);
        }

        return $this->block($selector, $this->changedTokens($global, $scoped));
    }

    /**
     * A resolved section is expressed as a settings object so the token compiler stays the
     * single place that knows how a setting becomes a value.
     */
    private function asGlobalSettings(ThemeSettings $settings, ResolvedSection $resolved): ThemeSettings
    {
        $stored = $settings->toArray();
        $stored['customAccent'] = $resolved->accentSeed->toHex();
        $stored['pattern'] = $resolved->pattern->toArray();
        $stored['motion'] = $resolved->motion->toArray();
        $stored['typography'] = $resolved->typography->toArray();

        $stored['layout'] = array_merge($settings->layout->toArray(), [
            'sectionSpacingPixels' => $resolved->sectionSpacingPixels,
            'cornerRadiusPixels' => $resolved->cornerRadiusPixels,
        ]);

        return ThemeSettings::fromArray($stored);
    }

    /**
     * The pattern layer is a pseudo-element rather than a background on the section itself,
     * so opacity and blend apply to the pattern alone and not to the content on top of it.
     */
    private function patternLayerBlock(): string
    {
        return <<<CSS
        [data-edulume-pattern]::before {
          content: '';
          position: absolute;
          inset: 0;
          pointer-events: none;
          background-image: var({$this->token('pattern-image')});
          background-repeat: repeat;
          background-attachment: var({$this->token('pattern-attachment')}, scroll);
          opacity: var({$this->token('pattern-opacity')});
          mix-blend-mode: var({$this->token('pattern-blend')}, normal);
          rotate: var({$this->token('pattern-rotation')}, 0deg);
          scale: var({$this->token('pattern-scale')}, 1);
        }
        CSS;
    }

    private function reducedMotionBlock(): string
    {
        $tokens = [
            $this->token('duration-fast') => '0ms',
            $this->token('duration-base') => '0ms',
            $this->token('duration-slow') => '0ms',
            $this->token('stagger-step') => '0ms',
            $this->token('motion-travel') => '0rem',
        ];

        $declarations = $this->declarations($tokens, self::INDENT . self::INDENT);

        return "@media (prefers-reduced-motion: reduce) {\n"
            . self::INDENT . ":root,\n"
            . self::INDENT . '[' . self::DARK_MODE_ATTRIBUTE . "=\"dark\"] {\n"
            . $declarations
            . self::INDENT . "}\n"
            . self::INDENT . "*,\n"
            . self::INDENT . "*::before,\n"
            . self::INDENT . "*::after {\n"
            . self::INDENT . self::INDENT . "animation-duration: 0.01ms !important;\n"
            . self::INDENT . self::INDENT . "animation-iteration-count: 1 !important;\n"
            . self::INDENT . self::INDENT . "transition-duration: 0.01ms !important;\n"
            . self::INDENT . self::INDENT . "scroll-behavior: auto !important;\n"
            . self::INDENT . "}\n"
            . '}';
    }

    /**
     * @param array<string, string> $current
     * @param array<string, string> $candidate
     *
     * @return array<string, string>
     */
    private function changedTokens(array $current, array $candidate): array
    {
        $changed = [];

        foreach ($candidate as $name => $value) {
            if (($current[$name] ?? null) !== $value) {
                $changed[$name] = $value;
            }
        }

        return $changed;
    }

    /**
     * @param array<string, string> $tokens
     */
    private function block(string $selector, array $tokens): string
    {
        if ($tokens === []) {
            return '';
        }

        return $selector . " {\n" . $this->declarations($tokens, self::INDENT) . '}';
    }

    /**
     * @param array<string, string> $tokens
     */
    private function declarations(array $tokens, string $indent): string
    {
        $lines = '';

        foreach ($tokens as $name => $value) {
            $lines .= sprintf("%s%s: %s;\n", $indent, $name, $value);
        }

        return $lines;
    }

    private function token(string $name): string
    {
        return TokenCompiler::PREFIX . $name;
    }
}
