<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Seo;

/**
 * What to emit when a dedicated SEO plugin is already doing part of the job.
 *
 * Yoast, RankMath and SEOPress all write their own title, description, Open Graph and Twitter
 * tags. Emitting a second set does not improve anything — it produces duplicate tags, which
 * every SEO audit flags and which some crawlers resolve unpredictably.
 *
 * Structured data is different: this product knows about courses, scholarships and intakes in
 * a way a general SEO plugin does not, so the JSON-LD graph stays ours either way.
 */
final class SeoDeferral
{
    private function __construct(
        public readonly bool $anSeoPluginIsActive,
        public readonly string $activePluginName,
    ) {
    }

    public static function none(): self
    {
        return new self(false, '');
    }

    public static function to(string $pluginName): self
    {
        return new self(trim($pluginName) !== '', trim($pluginName));
    }

    public function shouldEmitTitleTag(): bool
    {
        return !$this->anSeoPluginIsActive;
    }

    public function shouldEmitMetaDescription(): bool
    {
        return !$this->anSeoPluginIsActive;
    }

    public function shouldEmitOpenGraph(): bool
    {
        return !$this->anSeoPluginIsActive;
    }

    public function shouldEmitTwitterCard(): bool
    {
        return !$this->anSeoPluginIsActive;
    }

    public function shouldEmitStructuredData(): bool
    {
        return true;
    }
}
