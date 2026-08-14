<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * The whole content model in one readable place: sixteen post types, their taxonomies, and
 * the relationships between them.
 *
 * Registered by the plugin rather than the theme, so a site that switches theme keeps every
 * course, institution and testimonial it ever entered.
 */
final class ContentModel
{
    public const POST_TYPE_COUNT = 16;

    /**
     * @return list<PostTypeDefinition>
     */
    public static function postTypes(): array
    {
        return [
            PostTypeDefinition::of('destination', 'Destination', 'Destinations', 'destinations', 'dashicons-admin-site-alt3', [
                self::taxonomyKey('region'),
            ]),
            PostTypeDefinition::of('institution', 'Institution', 'Institutions', 'institutions', 'dashicons-building', [
                self::taxonomyKey('institution-type'),
                self::taxonomyKey('region'),
            ]),
            PostTypeDefinition::of('course', 'Course', 'Courses', 'courses', 'dashicons-welcome-learn-more', [
                self::taxonomyKey('study-level'),
                self::taxonomyKey('field-of-study'),
                self::taxonomyKey('intake'),
            ]),
            PostTypeDefinition::of('service', 'Service', 'Services', 'services', 'dashicons-clipboard', [
                self::taxonomyKey('service-category'),
            ]),
            PostTypeDefinition::of('scholarship', 'Scholarship', 'Scholarships', 'scholarships', 'dashicons-awards', [
                self::taxonomyKey('scholarship-type'),
                self::taxonomyKey('study-level'),
            ]),
            PostTypeDefinition::of('test-prep', 'Test Prep Course', 'Test Prep', 'test-prep', 'dashicons-edit-page', [
                self::taxonomyKey('language-test'),
            ]),
            PostTypeDefinition::of('event', 'Event', 'Events', 'events', 'dashicons-calendar-alt', [
                self::taxonomyKey('event-type'),
            ]),
            PostTypeDefinition::of('team-member', 'Team Member', 'Team', 'team', 'dashicons-groups', [
                self::taxonomyKey('department'),
            ]),
            PostTypeDefinition::of('testimonial', 'Testimonial', 'Testimonials', 'testimonials', 'dashicons-format-quote'),
            PostTypeDefinition::of('story', 'Success Story', 'Success Stories', 'success-stories', 'dashicons-star-filled', [
                self::taxonomyKey('study-level'),
            ]),
            PostTypeDefinition::of('gallery-item', 'Gallery Item', 'Gallery', 'gallery', 'dashicons-format-gallery'),
            PostTypeDefinition::of('branch', 'Branch', 'Branches', 'branches', 'dashicons-location-alt'),
            PostTypeDefinition::of('job-opening', 'Job Opening', 'Careers', 'careers', 'dashicons-businessperson', [
                self::taxonomyKey('department'),
            ]),
            PostTypeDefinition::of('faq', 'FAQ', 'FAQs', 'faqs', 'dashicons-editor-help', [
                self::taxonomyKey('service-category'),
            ]),
            PostTypeDefinition::of('resource', 'Resource', 'Resources', 'resources', 'dashicons-media-document', [
                self::taxonomyKey('resource-type'),
            ]),
            PostTypeDefinition::of('partner', 'Partner', 'Partners', 'partners', 'dashicons-networking'),
        ];
    }

    /**
     * @return list<TaxonomyDefinition>
     */
    public static function taxonomies(): array
    {
        return [
            TaxonomyDefinition::of('region', 'Region', 'Regions', 'region'),
            TaxonomyDefinition::of('institution-type', 'Institution Type', 'Institution Types', 'institution-type'),
            TaxonomyDefinition::of('study-level', 'Study Level', 'Study Levels', 'study-level'),
            TaxonomyDefinition::of('field-of-study', 'Field of Study', 'Fields of Study', 'field-of-study'),
            TaxonomyDefinition::of('intake', 'Intake', 'Intakes', 'intake', false),
            TaxonomyDefinition::of('service-category', 'Service Category', 'Service Categories', 'service-category'),
            TaxonomyDefinition::of('scholarship-type', 'Scholarship Type', 'Scholarship Types', 'scholarship-type'),
            TaxonomyDefinition::of('language-test', 'Language Test', 'Language Tests', 'language-test'),
            TaxonomyDefinition::of('event-type', 'Event Type', 'Event Types', 'event-type'),
            TaxonomyDefinition::of('department', 'Department', 'Departments', 'department'),
            TaxonomyDefinition::of('resource-type', 'Resource Type', 'Resource Types', 'resource-type'),
        ];
    }

    /**
     * @return list<RelationshipDefinition>
     */
    public static function relationships(): array
    {
        return [
            RelationshipDefinition::of(
                'course_institution',
                self::postTypeKey('course'),
                self::postTypeKey('institution'),
                'Institution',
                'Courses',
            ),
            RelationshipDefinition::of(
                'institution_destination',
                self::postTypeKey('institution'),
                self::postTypeKey('destination'),
                'Destination',
                'Institutions',
            ),
            RelationshipDefinition::of(
                'scholarship_destination',
                self::postTypeKey('scholarship'),
                self::postTypeKey('destination'),
                'Destinations',
                'Scholarships',
                true,
            ),
            RelationshipDefinition::of(
                'scholarship_institution',
                self::postTypeKey('scholarship'),
                self::postTypeKey('institution'),
                'Institutions',
                'Scholarships',
                true,
            ),
            RelationshipDefinition::of(
                'event_branch',
                self::postTypeKey('event'),
                self::postTypeKey('branch'),
                'Branch',
                'Events',
            ),
            RelationshipDefinition::of(
                'team_member_branch',
                self::postTypeKey('team-member'),
                self::postTypeKey('branch'),
                'Branch',
                'Team',
            ),
            RelationshipDefinition::of(
                'testimonial_course',
                self::postTypeKey('testimonial'),
                self::postTypeKey('course'),
                'Course',
                'Testimonials',
            ),
            RelationshipDefinition::of(
                'success_story_destination',
                self::postTypeKey('story'),
                self::postTypeKey('destination'),
                'Destination',
                'Success Stories',
            ),
        ];
    }

    public static function postTypeKey(string $slug): string
    {
        return PostTypeDefinition::KEY_PREFIX . $slug;
    }

    public static function taxonomyKey(string $slug): string
    {
        return TaxonomyDefinition::KEY_PREFIX . $slug;
    }

    /**
     * @return list<RelationshipDefinition>
     */
    public static function relationshipsFor(string $postTypeKey): array
    {
        $relationships = [];

        foreach (self::relationships() as $relationship) {
            if ($relationship->connects($postTypeKey)) {
                $relationships[] = $relationship;
            }
        }

        return $relationships;
    }

    /**
     * @throws UnknownPostTypeException when the key is not part of the model
     */
    public static function postType(string $postTypeKey): PostTypeDefinition
    {
        foreach (self::postTypes() as $postType) {
            if ($postType->key === $postTypeKey) {
                return $postType;
            }
        }

        throw UnknownPostTypeException::forKey($postTypeKey);
    }
}
