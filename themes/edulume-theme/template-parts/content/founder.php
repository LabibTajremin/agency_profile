<?php

/**
 * One founder: portrait, biography, and two timelines.
 *
 * Accepts either an array of demo copy or a `WP_Post` from the team CPT, normalised here so the
 * markup below has one shape to render. Doing it the other way — two templates, one per source
 * — is how the post-backed version quietly loses a field the demo version has.
 *
 * The portrait is 4/5 with `object-position: top`, because a centred crop on a portrait cuts
 * faces at the chin.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_founder = $args['founder'] ?? null;

if (!is_array($edulume_founder)) {
    return;
}

$edulume_name = edulume_row($edulume_founder, 'name');

if ($edulume_name === '') {
    return;
}

$edulume_education = is_array($edulume_founder['education'] ?? null) ? $edulume_founder['education'] : [];
$edulume_experience = is_array($edulume_founder['experience'] ?? null) ? $edulume_founder['experience'] : [];
$edulume_socials = is_array($edulume_founder['socials'] ?? null) ? $edulume_founder['socials'] : [];
$edulume_photo = edulume_row($edulume_founder, 'photo');
$edulume_photo_url = edulume_row($edulume_founder, 'photoUrl');

?>
<div class="edulume-founder">
    <div class="edulume-founder__portrait">
        <?php if ($edulume_photo_url !== '') : ?>
            <img
                src="<?php echo esc_url($edulume_photo_url); ?>"
                alt="<?php echo esc_attr($edulume_name); ?>"
                width="800"
                height="1000"
                loading="lazy"
                decoding="async"
            />
        <?php elseif ($edulume_photo !== '') : ?>
            <?php edulume_the_demo_image($edulume_photo, $edulume_name, 800, 1000); ?>
        <?php else : ?>
            <?php
            /*
             * Initials, not a broken image icon. A founders page is the one page somebody sends
             * to a partner university, and a missing-image glyph on it is worse than plain.
             */
            ?>
            <span class="edulume-founder__initials" aria-hidden="true">
                <?php echo esc_html(edulume_initials($edulume_name)); ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="edulume-founder__body">
        <h2 class="edulume-founder__name"><?php echo esc_html($edulume_name); ?></h2>

        <?php $edulume_designation = edulume_row($edulume_founder, 'designation'); ?>
        <?php if ($edulume_designation !== '') : ?>
            <p class="edulume-founder__designation"><?php echo esc_html($edulume_designation); ?></p>
        <?php endif; ?>

        <?php $edulume_tagline = edulume_row($edulume_founder, 'tagline'); ?>
        <?php if ($edulume_tagline !== '') : ?>
            <p class="edulume-founder__tagline"><?php echo esc_html($edulume_tagline); ?></p>
        <?php endif; ?>

        <?php $edulume_bio = edulume_row($edulume_founder, 'bio'); ?>
        <?php if ($edulume_bio !== '') : ?>
            <div class="edulume-founder__bio"><p><?php echo esc_html($edulume_bio); ?></p></div>
        <?php endif; ?>

        <dl class="edulume-founder__facts">
            <?php $edulume_expertise = edulume_row($edulume_founder, 'expertise'); ?>
            <?php if ($edulume_expertise !== '') : ?>
                <dt><?php esc_html_e('Specialises in', 'edulume'); ?></dt>
                <dd><?php echo esc_html($edulume_expertise); ?></dd>
            <?php endif; ?>

            <?php $edulume_languages = edulume_row($edulume_founder, 'languages'); ?>
            <?php if ($edulume_languages !== '') : ?>
                <dt><?php esc_html_e('Speaks', 'edulume'); ?></dt>
                <dd><?php echo esc_html($edulume_languages); ?></dd>
            <?php endif; ?>
        </dl>

        <?php if ($edulume_education !== []) : ?>
            <h3 class="edulume-founder__heading"><?php esc_html_e('Education', 'edulume'); ?></h3>
            <ol class="edulume-track">
                <?php foreach ($edulume_education as $edulume_entry) : ?>
                    <?php if (is_array($edulume_entry)) : ?>
                        <li class="edulume-track__item">
                            <p class="edulume-track__when"><?php echo esc_html(edulume_row($edulume_entry, 'year')); ?></p>
                            <p class="edulume-track__what"><?php echo esc_html(edulume_row($edulume_entry, 'degree')); ?></p>
                            <p class="edulume-track__where">
                                <?php
                                $edulume_country = edulume_row($edulume_entry, 'country');
                                echo esc_html($edulume_country === ''
                                    ? edulume_row($edulume_entry, 'institution')
                                    : edulume_row($edulume_entry, 'institution') . ', ' . $edulume_country);
                                ?>
                            </p>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php if ($edulume_experience !== []) : ?>
            <h3 class="edulume-founder__heading"><?php esc_html_e('Experience', 'edulume'); ?></h3>
            <ol class="edulume-track">
                <?php foreach ($edulume_experience as $edulume_entry) : ?>
                    <?php if (is_array($edulume_entry)) : ?>
                        <li class="edulume-track__item">
                            <p class="edulume-track__when"><?php echo esc_html(edulume_row($edulume_entry, 'years')); ?></p>
                            <p class="edulume-track__what"><?php echo esc_html(edulume_row($edulume_entry, 'role')); ?></p>
                            <p class="edulume-track__where"><?php echo esc_html(edulume_row($edulume_entry, 'org')); ?></p>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php if ($edulume_socials !== []) : ?>
            <ul class="edulume-founder__links" role="list">
                <?php foreach ($edulume_socials as $edulume_network => $edulume_url) : ?>
                    <?php if (is_string($edulume_url) && $edulume_url !== '') : ?>
                        <li>
                            <a href="<?php echo esc_url($edulume_url); ?>">
                                <?php
                                printf(
                                    /* translators: 1: a social network or contact method, 2: the person's name. */
                                    esc_html__('%1$s — %2$s', 'edulume'),
                                    esc_html(ucfirst((string) $edulume_network)),
                                    esc_html($edulume_name)
                                );
                                ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
