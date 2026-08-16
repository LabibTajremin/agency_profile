<?php

/**
 * The enquiry form.
 *
 * A real `<form>` with a real `action`, so it submits and creates a lead with the script
 * blocked, on an old browser, or while the module is still downloading. `forms.js` upgrades it
 * to inline validation and a submission without a page reload; nothing here depends on that
 * happening.
 *
 * Every field carries a label — not a placeholder standing in for one. A placeholder disappears
 * the moment somebody types, which is exactly when they need to check what the field was for,
 * and it is invisible to most voice control.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

edulume_require_feature('form');

$edulume_form_id = isset($args['form_id']) && is_string($args['form_id'])
    ? sanitize_key($args['form_id'])
    : 'enquiry';
$edulume_title = isset($args['title']) && is_string($args['title'])
    ? $args['title']
    : __('Ask us a question', 'edulume');
$edulume_endpoint = rest_url('edulume/v1/submit/' . $edulume_form_id);

?>
<form
    class="edulume-form"
    method="post"
    action="<?php echo esc_url($edulume_endpoint); ?>"
    data-edulume-form
    data-edulume-endpoint="<?php echo esc_url($edulume_endpoint); ?>"
    data-edulume-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"
>
    <h2 class="edulume-form__title"><?php echo esc_html($edulume_title); ?></h2>

    <p class="edulume-form__field">
        <label class="edulume-form__label" for="edulume-name"><?php esc_html_e('Full name', 'edulume'); ?></label>
        <input class="edulume-form__input" type="text" id="edulume-name" name="name" required autocomplete="name" />
        <span class="edulume-form__error" data-edulume-error="name" role="alert"></span>
    </p>

    <p class="edulume-form__field">
        <label class="edulume-form__label" for="edulume-email"><?php esc_html_e('Email', 'edulume'); ?></label>
        <input class="edulume-form__input" type="email" id="edulume-email" name="email" required autocomplete="email" />
        <span class="edulume-form__error" data-edulume-error="email" role="alert"></span>
    </p>

    <p class="edulume-form__field">
        <label class="edulume-form__label" for="edulume-phone"><?php esc_html_e('Phone', 'edulume'); ?></label>
        <input class="edulume-form__input" type="tel" id="edulume-phone" name="phone" autocomplete="tel" />
        <span class="edulume-form__error" data-edulume-error="phone" role="alert"></span>
    </p>

    <p class="edulume-form__field">
        <label class="edulume-form__label" for="edulume-destination">
            <?php esc_html_e('Where do you want to study?', 'edulume'); ?>
        </label>
        <select class="edulume-form__input" id="edulume-destination" name="destination">
            <option value=""><?php esc_html_e('Not sure yet', 'edulume'); ?></option>
            <?php foreach (edulume_section_posts('edulume_destination', 20) as $edulume_destination) : ?>
                <option value="<?php echo esc_attr(get_the_title($edulume_destination)); ?>">
                    <?php echo esc_html(get_the_title($edulume_destination)); ?>
                </option>
            <?php endforeach; ?>
            <option value="other"><?php esc_html_e('Somewhere else', 'edulume'); ?></option>
        </select>
    </p>

    <?php /* Shown only when the answer above is "Somewhere else", by forms.js. */ ?>
    <p class="edulume-form__field" data-edulume-when="destination" data-edulume-equals="other" hidden>
        <label class="edulume-form__label" for="edulume-destination-other">
            <?php esc_html_e('Which country?', 'edulume'); ?>
        </label>
        <input class="edulume-form__input" type="text" id="edulume-destination-other" name="destinationOther" />
    </p>

    <p class="edulume-form__field">
        <label class="edulume-form__label" for="edulume-message"><?php esc_html_e('Your question', 'edulume'); ?></label>
        <textarea class="edulume-form__input" id="edulume-message" name="message" rows="4"></textarea>
    </p>

    <?php
    /*
     * The honeypot. Named to look worth filling in, hidden from people but not from a bot that
     * reads the markup, and checked server-side by SpamPolicy. `aria-hidden` plus `tabindex`
     * keeps it away from screen readers and the keyboard, which a `display: none` set by CSS
     * that fails to load would not.
     */
    ?>
    <p class="edulume-form__honeypot" aria-hidden="true">
        <label for="edulume-website"><?php esc_html_e('Website', 'edulume'); ?></label>
        <input type="text" id="edulume-website" name="website" tabindex="-1" autocomplete="off" />
    </p>

    <p class="edulume-form__field edulume-form__field--consent">
        <label class="edulume-form__consent" for="edulume-consent">
            <input type="checkbox" id="edulume-consent" name="consent" value="1" required />
            <span><?php esc_html_e('I agree to be contacted about my enquiry.', 'edulume'); ?></span>
        </label>
        <span class="edulume-form__error" data-edulume-error="consent" role="alert"></span>
    </p>

    <button class="edulume-button edulume-form__submit" type="submit">
        <?php esc_html_e('Send enquiry', 'edulume'); ?>
    </button>

    <?php /* Announced politely, so a success message is read without interrupting. */ ?>
    <p class="edulume-form__status" data-edulume-form-status role="status" aria-live="polite"></p>
</form>
