<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Security;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Lead\LeadQuery;
use Edulume\Core\Domain\Lead\LeadScope;
use Edulume\Core\Domain\Lead\StaffRole;
use Edulume\Core\Domain\Security\SvgSanitiser;
use Edulume\Core\Domain\Seo\SchemaType;
use Edulume\Core\Domain\Seo\SeoDeferral;
use Edulume\Core\Domain\Seo\StructuredData;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RolesSeoAndSecurityTest extends TestCase
{
    /**
     * @return array<string, array{StaffRole}>
     */
    public static function roleProvider(): array
    {
        $cases = [];

        foreach (StaffRole::cases() as $role) {
            $cases[$role->value] = [$role];
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('roleProvider')]
    public function it_gives_every_role_a_label_and_at_least_read(StaffRole $role): void
    {
        $this->assertNotSame('', $role->label());
        $this->assertTrue($role->can('read'));
    }

    /**
     * The denial that matters most: a Content Editor manages content and nothing else.
     */
    #[Test]
    public function it_never_lets_a_content_editor_change_the_colour_scheme(): void
    {
        $editor = StaffRole::ContentEditor;

        $this->assertFalse($editor->can(Capabilities::MANAGE_THEME));
        $this->assertFalse($editor->can(Capabilities::MANAGE_LEADS));
        $this->assertFalse($editor->can(Capabilities::EXPORT_LEADS));
        $this->assertTrue($editor->can(Capabilities::MANAGE_CONTENT));
    }

    #[Test]
    public function it_never_lets_a_counsellor_touch_the_theme_or_export_the_inbox(): void
    {
        $counsellor = StaffRole::Counsellor;

        $this->assertTrue($counsellor->can(Capabilities::MANAGE_LEADS));
        $this->assertFalse($counsellor->can(Capabilities::EXPORT_LEADS));
        $this->assertFalse($counsellor->can(Capabilities::MANAGE_THEME));
        $this->assertFalse($counsellor->can(Capabilities::MANAGE_CONTENT));
    }

    #[Test]
    public function it_gives_the_site_manager_the_configurator_but_not_the_whole_of_wordpress(): void
    {
        $manager = StaffRole::SiteManager;

        $this->assertTrue($manager->can(Capabilities::MANAGE_THEME));
        $this->assertTrue($manager->can(Capabilities::IMPORT_DEMO_CONTENT));
        $this->assertFalse($manager->can('manage_options'));
        $this->assertFalse($manager->can('activate_plugins'));
        $this->assertFalse($manager->can('edit_users'));
    }

    #[Test]
    public function it_shows_a_counsellor_only_the_leads_assigned_to_them(): void
    {
        $scoped = StaffRole::Counsellor->leadScope()->apply(LeadQuery::all(), 44, 7);

        $this->assertSame(LeadScope::OwnAssignments, StaffRole::Counsellor->leadScope());
        $this->assertSame(44, $scoped->assignedToId);
    }

    #[Test]
    public function it_scopes_a_branch_manager_to_their_branch(): void
    {
        $scoped = StaffRole::BranchManager->leadScope()->apply(LeadQuery::of(branchId: 99), 44, 7);

        $this->assertSame(7, $scoped->branchId);
    }

    #[Test]
    public function it_shows_a_content_editor_no_leads_at_all(): void
    {
        $scope = StaffRole::ContentEditor->leadScope();

        $this->assertFalse($scope->seesAnything());
        $this->assertSame(0, $scope->apply(LeadQuery::all(), 44, 7)->assignedToId);
    }

    #[Test]
    public function it_leaves_the_site_managers_view_unscoped(): void
    {
        $query = LeadQuery::all();

        $this->assertSame($query, StaffRole::SiteManager->leadScope()->apply($query, 44, 7));
        $this->assertTrue(LeadScope::Everything->seesAnything());
    }

    #[Test]
    public function it_keeps_a_branch_managers_other_filters_while_scoping(): void
    {
        $scoped = LeadScope::OwnBranch->apply(
            LeadQuery::of(searchTerm: 'amina', page: 3, perPage: 50, sortColumn: 'name', isDescending: false),
            44,
            7,
        );

        $this->assertSame('amina', $scoped->searchTerm);
        $this->assertSame(3, $scoped->page);
        $this->assertSame('name', $scoped->sortColumn);
        $this->assertFalse($scoped->isDescending);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function maliciousSvgProvider(): array
    {
        return [
            'inline script' => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            'event handler' => ['<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect/></svg>'],
            'javascript href' => ['<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"/></svg>'],
            'foreign object' => ['<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><b>x</b></foreignObject></svg>'],
            'external image' => ['<svg xmlns="http://www.w3.org/2000/svg"><image href="https://evil.test/x.png"/></svg>'],
            'animate handler' => ['<svg xmlns="http://www.w3.org/2000/svg"><rect onmouseover="alert(1)"/></svg>'],
        ];
    }

    #[Test]
    #[DataProvider('maliciousSvgProvider')]
    public function it_neutralises_a_malicious_svg(string $markup): void
    {
        $sanitiser = new SvgSanitiser();
        $clean = $sanitiser->sanitise($markup);

        $this->assertFalse($sanitiser->isSafe($markup), 'The upload was accepted unchanged.');

        foreach (['script', 'onload', 'onmouseover', 'javascript:', 'foreignObject', 'evil.test'] as $trace) {
            $this->assertStringNotContainsString($trace, $clean);
        }
    }

    #[Test]
    public function it_keeps_a_legitimate_svg_intact(): void
    {
        $sanitiser = new SvgSanitiser();
        $markup = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">'
            . '<path d="M4 4h16v16H4Z" fill="#1a5fb4" stroke-width="2"/></svg>';

        $this->assertStringContainsString('M4 4h16v16H4Z', $sanitiser->sanitise($markup));
        $this->assertStringContainsString('#1a5fb4', $sanitiser->sanitise($markup));
    }

    #[Test]
    public function it_keeps_an_internal_reference_and_drops_an_external_one(): void
    {
        $sanitiser = new SvgSanitiser();

        $internal = '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a" d="M0 0h4v4H0Z"/></defs>'
            . '<use href="#a"/></svg>';

        $this->assertStringContainsString('href="#a"', $sanitiser->sanitise($internal));
        $this->assertStringNotContainsString(
            'evil.test',
            $sanitiser->sanitise('<svg xmlns="http://www.w3.org/2000/svg"><use href="https://evil.test/#a"/></svg>'),
        );
    }

    #[Test]
    public function it_refuses_something_that_is_not_an_svg_at_all(): void
    {
        $sanitiser = new SvgSanitiser();

        $this->assertSame('', $sanitiser->sanitise('<html><body>hello</body></html>'));
        $this->assertSame('', $sanitiser->sanitise('not markup'));
        $this->assertSame('', $sanitiser->sanitise(''));
        $this->assertSame('', $sanitiser->sanitise('<!DOCTYPE svg SYSTEM "x"><svg/>'));
        $this->assertFalse($sanitiser->isSafe(''));
    }

    #[Test]
    public function it_strips_comments_and_processing_instructions(): void
    {
        $clean = (new SvgSanitiser())->sanitise(
            '<svg xmlns="http://www.w3.org/2000/svg"><!-- sneaky --><rect width="4" height="4"/></svg>',
        );

        $this->assertStringNotContainsString('sneaky', $clean);
        $this->assertStringContainsString('<rect', $clean);
    }

    #[Test]
    public function it_maps_every_content_type_that_has_a_schema_type(): void
    {
        $this->assertSame(SchemaType::Course, SchemaType::forPostType(ContentModel::postTypeKey('course')));
        $this->assertSame(
            SchemaType::CollegeOrUniversity,
            SchemaType::forPostType(ContentModel::postTypeKey('institution')),
        );
        $this->assertSame(SchemaType::FaqPage, SchemaType::forPostType(ContentModel::postTypeKey('faq')));
        $this->assertSame(SchemaType::Event, SchemaType::forPostType(ContentModel::postTypeKey('event')));
        $this->assertSame(SchemaType::Review, SchemaType::forPostType(ContentModel::postTypeKey('testimonial')));
        $this->assertSame(SchemaType::LocalBusiness, SchemaType::forPostType(ContentModel::postTypeKey('branch')));
        $this->assertSame(SchemaType::BlogPosting, SchemaType::forPostType('post'));
        $this->assertNull(SchemaType::forPostType(ContentModel::postTypeKey('gallery-item')));
    }

    #[Test]
    public function it_emits_a_single_graph_rather_than_unrelated_documents(): void
    {
        $data = StructuredData::empty()
            ->with(SchemaType::EducationalOrganization, ['name' => 'Edulume Consultancy'])
            ->with(SchemaType::Course, ['name' => 'MSc Data Science', 'provider' => ['@type' => 'CollegeOrUniversity']]);

        $decoded = json_decode($data->toJson(), true);

        $this->assertIsArray($decoded);
        $this->assertSame(StructuredData::CONTEXT, $decoded['@context']);
        $this->assertCount(2, $decoded['@graph']);
        $this->assertSame('EducationalOrganization', $decoded['@graph'][0]['@type']);
        $this->assertSame(2, $data->count());
    }

    /**
     * A Course with `"provider": null` validates worse than one that never claimed a provider.
     */
    #[Test]
    public function it_drops_empty_properties_rather_than_emitting_null(): void
    {
        $json = StructuredData::empty()
            ->with(SchemaType::Course, ['name' => 'MSc', 'description' => '', 'offers' => [], 'url' => null])
            ->toJson();

        $this->assertStringNotContainsString('null', $json);
        $this->assertStringNotContainsString('description', $json);
        $this->assertStringContainsString('MSc', $json);
    }

    #[Test]
    public function it_emits_nothing_when_there_is_nothing_to_say(): void
    {
        $this->assertTrue(StructuredData::empty()->isEmpty());
        $this->assertSame('', StructuredData::empty()->toJson());
        $this->assertSame(0, StructuredData::empty()->count());
    }

    #[Test]
    public function it_leaves_slashes_and_unicode_readable(): void
    {
        $json = StructuredData::empty()
            ->with(SchemaType::LocalBusiness, ['url' => 'https://edulume.test/branches/dhaka', 'name' => 'ঢাকা'])
            ->toJson();

        $this->assertStringContainsString('https://edulume.test/branches/dhaka', $json);
        $this->assertStringContainsString('ঢাকা', $json);
    }

    /**
     * Emitting a second set of meta tags produces duplicates, which every SEO audit flags.
     */
    #[Test]
    public function it_stands_aside_for_a_dedicated_seo_plugin(): void
    {
        $deferral = SeoDeferral::to('Yoast SEO');

        $this->assertTrue($deferral->anSeoPluginIsActive);
        $this->assertFalse($deferral->shouldEmitTitleTag());
        $this->assertFalse($deferral->shouldEmitMetaDescription());
        $this->assertFalse($deferral->shouldEmitOpenGraph());
        $this->assertFalse($deferral->shouldEmitTwitterCard());
    }

    /**
     * Structured data stays ours: this product knows about courses and intakes in a way a
     * general SEO plugin does not.
     */
    #[Test]
    public function it_keeps_emitting_structured_data_either_way(): void
    {
        $this->assertTrue(SeoDeferral::to('RankMath')->shouldEmitStructuredData());
        $this->assertTrue(SeoDeferral::none()->shouldEmitStructuredData());
    }

    #[Test]
    public function it_emits_everything_when_no_seo_plugin_is_active(): void
    {
        $deferral = SeoDeferral::none();

        $this->assertFalse($deferral->anSeoPluginIsActive);
        $this->assertSame('', $deferral->activePluginName);
        $this->assertTrue($deferral->shouldEmitTitleTag());
        $this->assertTrue($deferral->shouldEmitOpenGraph());
        $this->assertTrue($deferral->shouldEmitTwitterCard());
        $this->assertTrue($deferral->shouldEmitMetaDescription());
    }

    #[Test]
    public function it_treats_a_blank_plugin_name_as_no_plugin(): void
    {
        $this->assertFalse(SeoDeferral::to('   ')->anSeoPluginIsActive);
    }
}
