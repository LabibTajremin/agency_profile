<?php

/**
 * A single Destination: the country page.
 *
 * The whole list renders on the server. The filter bar narrows it client-side while the count
 * is small enough for that to be instant, and the page still works with JavaScript off — the
 * unfiltered list is the page, and filtering is an addition to it. A country page that renders
 * an empty div until a script fetches its own content is a page search engines and a third of
 * mobile visitors on a bad connection never see.
 *
 * The intake video card is sticky on desktop and moves above the grid on a phone. Source order
 * puts it first and `order` moves it on wide screens, so there is one copy of the markup: two
 * copies with one hidden is two players in the DOM, which is exactly what the iframe budget
 * exists to prevent.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();

    $edulume_destination_id = get_the_ID();
    $edulume_page = apply_filters('edulume_destination_page', [], (int) $edulume_destination_id);

    $edulume_universities = is_array($edulume_page['universities'] ?? null) ? $edulume_page['universities'] : [];
    $edulume_stats = is_array($edulume_page['stats'] ?? null) ? $edulume_page['stats'] : [];
    $edulume_video = is_array($edulume_page['video'] ?? null) ? $edulume_page['video'] : [];
    $edulume_client_side = (bool) ($edulume_page['filterClientSide'] ?? true);
    ?>

    <article <?php post_class('edulume-destination'); ?>>
        <header class="edulume-section edulume-destination__hero">
            <div class="edulume-container">
                <h1 class="edulume-destination__title"><?php the_title(); ?></h1>

                <?php if ($edulume_stats !== []) : ?>
                    <ul class="edulume-destination__stats" role="list">
                        <?php foreach ($edulume_stats as $edulume_key => $edulume_value) : ?>
                            <?php if (is_scalar($edulume_value)) : ?>
                                <li class="edulume-destination__stat">
                                    <span class="edulume-destination__stat-value">
                                        <?php echo esc_html((string) $edulume_value); ?>
                                    </span>
                                    <span class="edulume-destination__stat-label">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', (string) $edulume_key))); ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </header>

        <div class="edulume-section edulume-destination__body">
            <div class="edulume-container">
                <?php if (trim(get_the_content()) !== '') : ?>
                    <div class="edulume-destination__intro"><?php the_content(); ?></div>
                <?php endif; ?>

                <div class="edulume-destination__columns">
                    <aside class="edulume-destination__aside">
                        <?php if (($edulume_video['enabled'] ?? false) === true) : ?>
                            <div class="edulume-destination__video">
                                <h2 class="edulume-destination__video-title">
                                    <?php esc_html_e('This intake', 'edulume'); ?>
                                </h2>
                                <?php
                                get_template_part('template-parts/video/rail', null, [
                                    'rail' => $edulume_video,
                                    'single' => true,
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        get_template_part('template-parts/content/enquiry-form', null, [
                            'form_id' => 'assessment',
                            'title' => __('Free assessment', 'edulume'),
                        ]);
                        ?>
                    </aside>

                    <div class="edulume-destination__main">
                        <?php if ($edulume_universities === []) : ?>
                            <p class="edulume-destination__empty">
                                <?php esc_html_e('University listings for this country are on their way.', 'edulume'); ?>
                            </p>
                        <?php else : ?>
                            <?php edulume_require_feature('university-filter'); ?>

                            <form
                                class="edulume-university-filter"
                                data-edulume-university-filter
                                data-edulume-client-side="<?php echo $edulume_client_side ? 'true' : 'false'; ?>"
                                method="get"
                            >
                                <p class="edulume-form__field">
                                    <label class="edulume-form__label" for="edulume-university-search">
                                        <?php esc_html_e('Search universities', 'edulume'); ?>
                                    </label>
                                    <input
                                        class="edulume-form__input"
                                        type="search"
                                        id="edulume-university-search"
                                        name="q"
                                        data-edulume-filter-search
                                    />
                                </p>

                                <p class="edulume-form__field">
                                    <label class="edulume-form__label" for="edulume-university-intake">
                                        <?php esc_html_e('Intake month', 'edulume'); ?>
                                    </label>
                                    <select
                                        class="edulume-form__input"
                                        id="edulume-university-intake"
                                        name="intake"
                                        data-edulume-filter-intake
                                    >
                                        <option value=""><?php esc_html_e('Any', 'edulume'); ?></option>
                                        <?php foreach (edulume_destination_intakes($edulume_universities) as $edulume_intake) : ?>
                                            <option value="<?php echo esc_attr($edulume_intake); ?>">
                                                <?php echo esc_html($edulume_intake); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>

                                <noscript>
                                    <button class="edulume-button" type="submit">
                                        <?php esc_html_e('Filter', 'edulume'); ?>
                                    </button>
                                </noscript>
                            </form>

                            <p class="edulume-university-grid__count" data-edulume-filter-count role="status" aria-live="polite">
                                <?php
                                printf(
                                    /* translators: %s: how many universities are listed. */
                                    esc_html(_n(
                                        '%s university',
                                        '%s universities',
                                        count($edulume_universities),
                                        'edulume'
                                    )),
                                    esc_html(number_format_i18n(count($edulume_universities)))
                                );
                                ?>
                            </p>

                            <ul class="edulume-university-grid" role="list">
                                <?php foreach ($edulume_universities as $edulume_university) : ?>
                                    <?php
                                    get_template_part('template-parts/content/university', null, [
                                        'post' => $edulume_university,
                                    ]);
                                    ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <?php
endwhile;

get_footer();
