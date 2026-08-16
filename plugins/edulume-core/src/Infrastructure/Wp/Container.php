<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Wp;

use Edulume\Core\Application\Content\ImportContentCsv;
use Edulume\Core\Application\Demo\ImportDemo;
use Edulume\Core\Application\Lead\EraseLead;
use Edulume\Core\Application\Lead\ExportLeadsCsv;
use Edulume\Core\Application\Lead\ListLeads;
use Edulume\Core\Application\Lead\MoveLeadThroughPipeline;
use Edulume\Core\Application\Lead\RegisterLead;
use Edulume\Core\Application\Licence\ManageLicence;
use Edulume\Core\Application\Port\CaptchaVerifier;
use Edulume\Core\Application\Port\Clock;
use Edulume\Core\Application\Port\FinderRepository;
use Edulume\Core\Application\Port\ContentWriter;
use Edulume\Core\Application\Port\DemoStore;
use Edulume\Core\Application\Port\ExecutionBudget;
use Edulume\Core\Application\Port\FormRepository;
use Edulume\Core\Application\Port\LeadNotifier;
use Edulume\Core\Application\Port\LeadRepository;
use Edulume\Core\Application\Port\LicenceServer;
use Edulume\Core\Application\Port\RateLimiter;
use Edulume\Core\Application\Port\UploadedFileStore;
use Edulume\Core\Application\Port\WebhookTransport;
use Edulume\Core\Application\Port\SettingsRepository;
use Edulume\Core\Application\Port\StylesheetWriter;
use Edulume\Core\Application\Theming\ApplyStylePreset;
use Edulume\Core\Application\Theming\CompileStylesheet;
use Edulume\Core\Application\Theming\PublishStylesheet;
use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Theming\AccentReviewer;
use Edulume\Core\Domain\Theming\AdminTheme;
use Edulume\Core\Domain\Theming\PaletteGenerator;
use Edulume\Core\Domain\Theming\SectionResolver;
use Edulume\Core\Domain\Theming\SettingsMigrator;
use Edulume\Core\Domain\Theming\TokenCompiler;
use Edulume\Core\Infrastructure\Settings\OptionSettingsRepository;
use Edulume\Core\Infrastructure\Theming\UploadsStylesheetWriter;

/**
 * The wiring, in one readable place.
 *
 * Deliberately not a reflection-driven auto-wiring container: with this few services, a list
 * of explicit factories is shorter to read, impossible to misconfigure at runtime, and shows
 * a newcomer the whole dependency graph on one screen. Services are built once and reused.
 */
final class Container
{
    /** @var array<string, object> */
    private array $services = [];

    public function contrastEngine(): ContrastEngine
    {
        return $this->service(ContrastEngine::class, static fn (): ContrastEngine => new ContrastEngine());
    }

    public function paletteGenerator(): PaletteGenerator
    {
        return $this->service(
            PaletteGenerator::class,
            fn (): PaletteGenerator => new PaletteGenerator($this->contrastEngine()),
        );
    }

    /**
     * The admin's own palette, so wp-admin can wear the site's accent.
     */
    public function adminTheme(): AdminTheme
    {
        return $this->service(
            AdminTheme::class,
            fn (): AdminTheme => new AdminTheme($this->paletteGenerator(), $this->contrastEngine()),
        );
    }

    public function accentReviewer(): AccentReviewer
    {
        return $this->service(
            AccentReviewer::class,
            fn (): AccentReviewer => new AccentReviewer($this->contrastEngine()),
        );
    }

    public function tokenCompiler(): TokenCompiler
    {
        return $this->service(
            TokenCompiler::class,
            fn (): TokenCompiler => new TokenCompiler($this->paletteGenerator()),
        );
    }

    public function sectionResolver(): SectionResolver
    {
        return $this->service(SectionResolver::class, static fn (): SectionResolver => new SectionResolver());
    }

    public function settingsMigrator(): SettingsMigrator
    {
        return $this->service(SettingsMigrator::class, static fn (): SettingsMigrator => new SettingsMigrator());
    }

    public function settingsRepository(): SettingsRepository
    {
        return $this->service(
            SettingsRepository::class,
            fn (): SettingsRepository => new OptionSettingsRepository($this->settingsMigrator()),
        );
    }

    public function stylesheetWriter(): StylesheetWriter
    {
        return $this->service(
            StylesheetWriter::class,
            static fn (): StylesheetWriter => new UploadsStylesheetWriter(),
        );
    }

    public function compileStylesheet(): CompileStylesheet
    {
        return $this->service(
            CompileStylesheet::class,
            fn (): CompileStylesheet => new CompileStylesheet($this->tokenCompiler(), $this->sectionResolver()),
        );
    }

    public function publishStylesheet(): PublishStylesheet
    {
        return $this->service(
            PublishStylesheet::class,
            fn (): PublishStylesheet => new PublishStylesheet(
                $this->settingsRepository(),
                $this->compileStylesheet(),
                $this->stylesheetWriter(),
            ),
        );
    }

    public function applyStylePreset(): ApplyStylePreset
    {
        return $this->service(ApplyStylePreset::class, static fn (): ApplyStylePreset => new ApplyStylePreset());
    }

    public function clock(): Clock
    {
        return $this->service(Clock::class, static fn (): Clock => new \Edulume\Core\Infrastructure\Wp\WpClock());
    }

    public function leadRepository(): LeadRepository
    {
        return $this->service(
            LeadRepository::class,
            static fn (): LeadRepository => new \Edulume\Core\Infrastructure\Lead\WpdbLeadRepository(),
        );
    }

    public function formRepository(): FormRepository
    {
        return $this->service(
            FormRepository::class,
            static fn (): FormRepository => new \Edulume\Core\Infrastructure\Lead\OptionFormRepository(),
        );
    }

    public function uploadedFileStore(): UploadedFileStore
    {
        return $this->service(
            UploadedFileStore::class,
            static fn (): UploadedFileStore => new \Edulume\Core\Infrastructure\Lead\UploadsFileStore(),
        );
    }

    public function rateLimiter(): RateLimiter
    {
        return $this->service(
            RateLimiter::class,
            static fn (): RateLimiter => new \Edulume\Core\Infrastructure\Lead\TransientRateLimiter(),
        );
    }

    /**
     * The captcha verifier, configured from stored settings.
     *
     * Built from a filter rather than read directly, so a site that uses none gets a verifier
     * that reports itself unconfigured — and the spam defences that need no keys still apply.
     */
    public function captchaVerifier(): CaptchaVerifier
    {
        return $this->service(
            CaptchaVerifier::class,
            static function (): CaptchaVerifier {
                $configuration = apply_filters('edulume_captcha_configuration', ['provider' => '', 'secret' => '']);
                $provider = is_array($configuration) ? (string) ($configuration['provider'] ?? '') : '';
                $secret = is_array($configuration) ? (string) ($configuration['secret'] ?? '') : '';

                return new \Edulume\Core\Infrastructure\Lead\HttpCaptchaVerifier($provider, $secret);
            },
        );
    }

    public function leadNotifier(): LeadNotifier
    {
        return $this->service(
            LeadNotifier::class,
            fn (): LeadNotifier => new \Edulume\Core\Infrastructure\Lead\WpMailLeadNotifier(
                \Edulume\Core\Domain\Lead\NotificationRecipients::of(
                    \Edulume\Core\Domain\Support\Guard::toStringList(
                        apply_filters('edulume_notification_recipients', [get_option('admin_email', '')])
                    ),
                ),
                new \Edulume\Core\Domain\Lead\AutoresponderTemplate(
                    $this->paletteGenerator()->generate($this->settingsRepository()->load()->accentSeed()),
                    (string) get_option('blogname', ''),
                ),
                (string) apply_filters('edulume_autoresponder_body', ''),
            ),
        );
    }

    public function webhookTransport(): WebhookTransport
    {
        return $this->service(
            WebhookTransport::class,
            static fn (): WebhookTransport => new \Edulume\Core\Infrastructure\Lead\WpRemoteWebhookTransport(),
        );
    }

    public function contentWriter(): ContentWriter
    {
        return $this->service(
            ContentWriter::class,
            static fn (): ContentWriter => new \Edulume\Core\Infrastructure\Content\WpContentWriter(),
        );
    }

    public function finderRepository(): FinderRepository
    {
        return $this->service(
            FinderRepository::class,
            static fn (): FinderRepository => new \Edulume\Core\Infrastructure\Content\WpFinderRepository(),
        );
    }

    public function executionBudget(): ExecutionBudget
    {
        return $this->service(
            ExecutionBudget::class,
            static fn (): ExecutionBudget => new \Edulume\Core\Infrastructure\Content\WpExecutionBudget(),
        );
    }

    public function demoStore(): DemoStore
    {
        return $this->service(
            DemoStore::class,
            static fn (): DemoStore => new \Edulume\Core\Infrastructure\Demo\WpDemoStore(),
        );
    }

    public function licenceServer(): LicenceServer
    {
        return $this->service(
            LicenceServer::class,
            static fn (): LicenceServer => new \Edulume\Core\Infrastructure\Licence\HttpLicenceServer(
                (string) apply_filters('edulume_licence_endpoint', 'https://licences.edulume.test/v1'),
            ),
        );
    }

    public function listLeads(): ListLeads
    {
        return $this->service(ListLeads::class, fn (): ListLeads => new ListLeads($this->leadRepository()));
    }

    public function moveLeadThroughPipeline(): MoveLeadThroughPipeline
    {
        return $this->service(
            MoveLeadThroughPipeline::class,
            fn (): MoveLeadThroughPipeline => new MoveLeadThroughPipeline($this->leadRepository(), $this->clock()),
        );
    }

    public function eraseLead(): EraseLead
    {
        return $this->service(
            EraseLead::class,
            fn (): EraseLead => new EraseLead($this->leadRepository(), $this->uploadedFileStore()),
        );
    }

    public function exportLeadsCsv(): ExportLeadsCsv
    {
        return $this->service(
            ExportLeadsCsv::class,
            fn (): ExportLeadsCsv => new ExportLeadsCsv($this->leadRepository()),
        );
    }

    public function registerLead(): RegisterLead
    {
        return $this->service(
            RegisterLead::class,
            fn (): RegisterLead => new RegisterLead(
                $this->leadRepository(),
                $this->rateLimiter(),
                $this->captchaVerifier(),
                $this->leadNotifier(),
                $this->clock(),
            ),
        );
    }

    public function importContentCsv(): ImportContentCsv
    {
        return $this->service(
            ImportContentCsv::class,
            fn (): ImportContentCsv => new ImportContentCsv($this->contentWriter(), $this->executionBudget()),
        );
    }

    public function importDemo(): ImportDemo
    {
        return $this->service(ImportDemo::class, fn (): ImportDemo => new ImportDemo($this->demoStore()));
    }

    public function manageLicence(): ManageLicence
    {
        return $this->service(
            ManageLicence::class,
            fn (): ManageLicence => new ManageLicence($this->licenceServer(), $this->clock()),
        );
    }

    /**
     * @template TService of object
     *
     * @param class-string<TService> $id
     * @param callable(): TService $factory
     *
     * @return TService
     */
    private function service(string $id, callable $factory): object
    {
        if (!array_key_exists($id, $this->services)) {
            $this->services[$id] = $factory();
        }

        /** @var TService $service */
        $service = $this->services[$id];

        return $service;
    }
}
