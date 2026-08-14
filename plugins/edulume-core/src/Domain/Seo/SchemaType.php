<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Seo;

use Edulume\Core\Domain\Content\ContentModel;

/**
 * The Schema.org types this product emits, and which post type each belongs to.
 */
enum SchemaType: string
{
    case EducationalOrganization = 'EducationalOrganization';
    case Course = 'Course';
    case CollegeOrUniversity = 'CollegeOrUniversity';
    case FaqPage = 'FAQPage';
    case Event = 'Event';
    case BlogPosting = 'BlogPosting';
    case BreadcrumbList = 'BreadcrumbList';
    case Review = 'Review';
    case LocalBusiness = 'LocalBusiness';

    public static function forPostType(string $postTypeKey): ?self
    {
        return match ($postTypeKey) {
            ContentModel::postTypeKey('course'), ContentModel::postTypeKey('test-prep') => self::Course,
            ContentModel::postTypeKey('institution') => self::CollegeOrUniversity,
            ContentModel::postTypeKey('faq') => self::FaqPage,
            ContentModel::postTypeKey('event') => self::Event,
            ContentModel::postTypeKey('testimonial'), ContentModel::postTypeKey('story') => self::Review,
            ContentModel::postTypeKey('branch') => self::LocalBusiness,
            'post' => self::BlogPosting,
            default => null,
        };
    }
}
