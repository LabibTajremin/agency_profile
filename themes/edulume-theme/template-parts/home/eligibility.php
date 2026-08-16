<?php

/**
 * Home section: the eligibility check and the cost estimate.
 *
 * The highest-converting element on the page, and it rendered nothing at all unless somebody
 * hooked a filter — so on every real install it was absent.
 *
 * Both panels work from the destinations the site already has. The thresholds and the costs are
 * per-destination meta rendered into data attributes, so the figures a visitor sees are the
 * figures the site owner configured; a rate written into a script is a rate nobody can update
 * without a deployment.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Check your eligibility', 'edulume');
$edulume_authored = apply_filters('edulume_home_section_content', '', 'eligibility');

if (is_string($edulume_authored) && $edulume_authored !== '') {
    echo '<section class="edulume-section edulume-home-section edulume-home-eligibility" aria-label="'
        . esc_attr($edulume_label) . '"><div class="edulume-container">'
        . wp_kses_post($edulume_authored) . '</div></section>';

    return;
}

$edulume_destinations = edulume_section_posts('edulume_destination', 12);

if ($edulume_destinations === []) {
    return;
}

edulume_require_feature('eligibility');
edulume_require_feature('calculator');

/**
 * A destination's configured number, or a sensible default when it has none.
 */
$edulume_meta = static function (WP_Post $post, string $key, string $fallback): string {
    $value = get_post_meta($post->ID, $key, true);

    return is_scalar($value) && (string) $value !== '' ? (string) $value : $fallback;
};

?>
<section
    class="edulume-section edulume-home-section edulume-home-eligibility"
    aria-label="<?php echo esc_attr($edulume_label); ?>"
>
    <div class="edulume-container">
        <div class="edulume-eligibility-grid">
            <div class="edulume-eligibility" data-edulume-eligibility>
                <h2 class="edulume-eligibility__title"><?php echo esc_html($edulume_label); ?></h2>
                <p class="edulume-eligibility__lead">
                    <?php esc_html_e('Four questions. An honest answer, not a sales pitch.', 'edulume'); ?>
                </p>

                <p class="edulume-form__field">
                    <label class="edulume-form__label" for="edulume-eligibility-destination">
                        <?php esc_html_e('Destination', 'edulume'); ?>
                    </label>
                    <select
                        class="edulume-form__input"
                        id="edulume-eligibility-destination"
                        data-edulume-eligibility-destination
                    >
                        <?php foreach ($edulume_destinations as $edulume_destination) : ?>
                            <?php
                            $edulume_grade = $edulume_meta($edulume_destination, '_edulume_minimum_grade', '3.0');
                            $edulume_english = $edulume_meta($edulume_destination, '_edulume_minimum_english', '6.5');
                            ?>
                            <option
                                value="<?php echo esc_attr((string) $edulume_destination->ID); ?>"
                                data-minimum-grade="<?php echo esc_attr($edulume_grade); ?>"
                                data-minimum-english="<?php echo esc_attr($edulume_english); ?>"
                            >
                                <?php echo esc_html(get_the_title($edulume_destination)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p class="edulume-form__field">
                    <label class="edulume-form__label" for="edulume-eligibility-grade">
                        <?php esc_html_e('Your CGPA, out of 4', 'edulume'); ?>
                    </label>
                    <input
                        class="edulume-form__input"
                        type="number"
                        id="edulume-eligibility-grade"
                        min="0"
                        max="4"
                        step="0.01"
                        value="3.0"
                        data-edulume-eligibility-grade
                    />
                </p>

                <p class="edulume-form__field">
                    <label class="edulume-form__label" for="edulume-eligibility-english">
                        <?php esc_html_e('IELTS overall, if you have sat it', 'edulume'); ?>
                    </label>
                    <input
                        class="edulume-form__input"
                        type="number"
                        id="edulume-eligibility-english"
                        min="0"
                        max="9"
                        step="0.5"
                        value="6.5"
                        data-edulume-eligibility-english
                    />
                </p>

                <p class="edulume-eligibility__verdict" data-edulume-eligibility-verdict role="status" aria-live="polite"></p>
                <p class="edulume-eligibility__advice" data-edulume-eligibility-advice></p>
            </div>

            <div
                class="edulume-calculator"
                data-edulume-calculator
                data-edulume-locale="<?php echo esc_attr(str_replace('_', '-', get_locale())); ?>"
            >
                <h2 class="edulume-calculator__title"><?php esc_html_e('What it will cost', 'edulume'); ?></h2>
                <p class="edulume-calculator__lead">
                    <?php esc_html_e('Tuition, living, visa and flights for the whole course.', 'edulume'); ?>
                </p>

                <p class="edulume-form__field">
                    <label class="edulume-form__label" for="edulume-calculator-destination">
                        <?php esc_html_e('Destination', 'edulume'); ?>
                    </label>
                    <select
                        class="edulume-form__input"
                        id="edulume-calculator-destination"
                        data-edulume-calculator-destination
                    >
                        <?php foreach ($edulume_destinations as $edulume_destination) : ?>
                            <?php
                            $edulume_currency = $edulume_meta($edulume_destination, '_edulume_currency', 'USD');
                            $edulume_tuition = $edulume_meta($edulume_destination, '_edulume_tuition_per_year', '20000');
                            $edulume_living = $edulume_meta($edulume_destination, '_edulume_living_per_year', '10000');
                            $edulume_visa = $edulume_meta($edulume_destination, '_edulume_visa_cost', '800');
                            $edulume_flights = $edulume_meta($edulume_destination, '_edulume_flight_cost', '700');
                            ?>
                            <option
                                value="<?php echo esc_attr((string) $edulume_destination->ID); ?>"
                                data-currency="<?php echo esc_attr($edulume_currency); ?>"
                                data-tuition="<?php echo esc_attr($edulume_tuition); ?>"
                                data-living="<?php echo esc_attr($edulume_living); ?>"
                                data-visa="<?php echo esc_attr($edulume_visa); ?>"
                                data-flights="<?php echo esc_attr($edulume_flights); ?>"
                            >
                                <?php echo esc_html(get_the_title($edulume_destination)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p class="edulume-form__field">
                    <label class="edulume-form__label" for="edulume-calculator-years">
                        <?php esc_html_e('Course length, in years', 'edulume'); ?>
                    </label>
                    <input
                        class="edulume-form__input"
                        type="number"
                        id="edulume-calculator-years"
                        min="1"
                        max="10"
                        step="1"
                        value="1"
                        data-edulume-calculator-years
                    />
                </p>

                <p class="edulume-calculator__total" data-edulume-calculator-total role="status" aria-live="polite"></p>
                <div class="edulume-calculator__breakdown" data-edulume-calculator-breakdown></div>

                <p class="edulume-calculator__note">
                    <?php esc_html_e('An estimate to plan with, not a quotation.', 'edulume'); ?>
                </p>
            </div>
        </div>
    </div>
</section>
