<?php

/**
 * Template Name: Founders / About Us
 *
 * The co-founders' page: who they are, what they studied, what they did before, and what the
 * company is trying to become.
 *
 * Real `edulume_team-member` posts marked as founders win; the bundled two are what a new
 * install shows. Same CPT as the counsellors section — a second people post type would mean a
 * counsellor promoted to partner has to be retyped rather than reclassified.
 *
 * The layout does not assume two people. One founder, two or five all render; the alternating
 * image side comes from `:nth-child`, not from a hardcoded pair.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();

$edulume_founders = apply_filters('edulume_founders', []);

if (!is_array($edulume_founders) || $edulume_founders === []) {
    $edulume_founders = edulume_opt_list('founders.people');
}

$edulume_goals = edulume_opt_list('founders.goals');
$edulume_milestones = edulume_opt_list('founders.milestones');

$edulume_vision_title = edulume_opt('founders.visionTitle', __('Our vision', 'edulume'));
$edulume_goals_title = edulume_opt('founders.goalsTitle', __('Our goals', 'edulume'));
$edulume_milestones_title = edulume_opt('founders.milestonesTitle', __('Milestones', 'edulume'));
$edulume_cta_title = edulume_opt('founders.ctaTitle', __('Talk to us', 'edulume'));

?>
<article <?php post_class('edulume-founders'); ?>>
    <header class="edulume-section edulume-founders__hero">
        <div class="edulume-container">
            <h1 class="edulume-founders__title">
                <?php echo esc_html(edulume_opt('founders.title', get_the_title())); ?>
            </h1>
            <p class="edulume-founders__lead"><?php echo esc_html(edulume_opt('founders.lead')); ?></p>
        </div>
    </header>

    <?php if (trim(get_the_content()) !== '') : ?>
        <div class="edulume-section edulume-founders__intro">
            <div class="edulume-container"><?php the_content(); ?></div>
        </div>
    <?php endif; ?>

    <?php if ($edulume_founders !== []) : ?>
        <div class="edulume-section edulume-founders__people">
            <div class="edulume-container">
                <?php foreach ($edulume_founders as $edulume_founder) : ?>
                    <?php get_template_part('template-parts/content/founder', null, ['founder' => $edulume_founder]); ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php $edulume_vision = edulume_opt('founders.vision'); ?>
    <?php if ($edulume_vision !== '') : ?>
        <section
            class="edulume-section edulume-founders__vision"
            aria-label="<?php echo esc_attr($edulume_vision_title); ?>"
        >
            <div class="edulume-container">
                <h2><?php echo esc_html($edulume_vision_title); ?></h2>
                <?php foreach (explode("\n\n", $edulume_vision) as $edulume_paragraph) : ?>
                    <p><?php echo esc_html(trim($edulume_paragraph)); ?></p>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($edulume_goals !== []) : ?>
        <?php edulume_require_feature('counters'); ?>
        <section
            class="edulume-section edulume-founders__goals"
            aria-label="<?php echo esc_attr($edulume_goals_title); ?>"
        >
            <div class="edulume-container">
                <h2><?php echo esc_html($edulume_goals_title); ?></h2>
                <p class="edulume-founders__goals-year"><?php echo esc_html(edulume_opt('founders.goalsYear')); ?></p>

                <ul class="edulume-stats" role="list">
                    <?php foreach ($edulume_goals as $edulume_goal) : ?>
                        <?php $edulume_value = (int) edulume_row($edulume_goal, 'value', '0'); ?>
                        <li class="edulume-stat">
                            <p class="edulume-stat__value">
                                <?php
                                /*
                                 * The final number is in the markup and the script counts up to
                                 * it. An element the script fills is a blank with JavaScript off
                                 * and a zero for the first frame with it on.
                                 */
                                ?>
                                <span data-edulume-counter="<?php echo esc_attr((string) $edulume_value); ?>">
                                    <?php echo esc_html(number_format_i18n($edulume_value)); ?>
                                </span>
                            </p>
                            <p class="edulume-stat__label"><?php echo esc_html(edulume_row($edulume_goal, 'label')); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($edulume_milestones !== []) : ?>
        <section
            class="edulume-section edulume-founders__milestones"
            aria-label="<?php echo esc_attr($edulume_milestones_title); ?>"
        >
            <div class="edulume-container">
                <h2><?php echo esc_html($edulume_milestones_title); ?></h2>

                <?php
                /*
                 * The same scroll pattern as the video rail, including the same split: the
                 * focusable region is a div and the list stays an <ol>. `role="region"` on the
                 * list would replace its implicit role and orphan every <li> in it.
                 */
                ?>
                <div
                    class="edulume-timeline"
                    role="region"
                    aria-label="<?php echo esc_attr($edulume_milestones_title); ?>"
                    tabindex="0"
                >
                    <ol class="edulume-timeline__list">
                        <?php foreach ($edulume_milestones as $edulume_milestone) : ?>
                            <li class="edulume-timeline__item">
                                <p class="edulume-timeline__year">
                                    <?php echo esc_html(edulume_row($edulume_milestone, 'year')); ?>
                                </p>
                                <p class="edulume-timeline__event">
                                    <?php echo esc_html(edulume_row($edulume_milestone, 'event')); ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section
        class="edulume-section edulume-founders__cta"
        aria-label="<?php echo esc_attr($edulume_cta_title); ?>"
    >
        <div class="edulume-container">
            <h2><?php echo esc_html($edulume_cta_title); ?></h2>
            <p><?php echo esc_html(edulume_opt('founders.ctaBlurb')); ?></p>

            <?php
            get_template_part('template-parts/content/enquiry-form', null, [
                'form_id' => 'enquiry',
                'title' => __('Book a consultation', 'edulume'),
            ]);
            ?>
        </div>
    </section>
</article>
<?php

get_footer();
