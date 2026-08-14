<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Finder;

/**
 * Which facets each finder offers.
 *
 * Declared here rather than assembled at the call site, so the REST endpoint, the archive
 * template and the compare view all filter on exactly the same set — a facet available in one
 * and not the other is how a shared URL stops reproducing what the sender saw.
 */
final class FinderCatalogue
{
    /**
     * @return list<FacetDefinition>
     */
    public static function facetsFor(string $postType): array
    {
        return match ($postType) {
            'edulume_course' => self::courseFacets(),
            'edulume_institution' => self::institutionFacets(),
            'edulume_scholarship' => self::scholarshipFacets(),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function postTypes(): array
    {
        return ['edulume_course', 'edulume_institution', 'edulume_scholarship'];
    }

    public static function supports(string $postType): bool
    {
        return in_array($postType, self::postTypes(), true);
    }

    /**
     * @return list<FacetDefinition>
     */
    private static function courseFacets(): array
    {
        return [
            new FacetDefinition('level', 'Study level', FacetSource::Taxonomy),
            new FacetDefinition('field', 'Field of study', FacetSource::Taxonomy),
            new FacetDefinition('intake', 'Intake', FacetSource::Taxonomy),
            new FacetDefinition('destination', 'Destination', FacetSource::Meta),
            new FacetDefinition('institution', 'Institution', FacetSource::Meta),
            new FacetDefinition('duration', 'Duration', FacetSource::Meta, false, ['6', '12', '18', '24', '36', '48']),
            new FacetDefinition('tuition', 'Tuition band', FacetSource::Meta, false, ['0-10000', '10000-25000', '25000-50000', '50000+']),
        ];
    }

    /**
     * @return list<FacetDefinition>
     */
    private static function institutionFacets(): array
    {
        return [
            new FacetDefinition('institution-type', 'Institution type', FacetSource::Taxonomy),
            new FacetDefinition('region', 'Region', FacetSource::Taxonomy),
            new FacetDefinition('destination', 'Destination', FacetSource::Meta),
            new FacetDefinition('ranking', 'World ranking', FacetSource::Meta, false, ['top-100', 'top-500', 'top-1000']),
        ];
    }

    /**
     * @return list<FacetDefinition>
     */
    private static function scholarshipFacets(): array
    {
        return [
            new FacetDefinition('scholarship-type', 'Scholarship type', FacetSource::Taxonomy),
            new FacetDefinition('level', 'Study level', FacetSource::Taxonomy),
            new FacetDefinition('destination', 'Destination', FacetSource::Meta),
            new FacetDefinition('coverage', 'Coverage', FacetSource::Meta, false, ['full', 'partial', 'tuition-only']),
        ];
    }
}
