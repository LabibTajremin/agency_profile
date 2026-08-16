<?php

/**
 * Home section: the counsellors.
 *
 * A face and a name against a specialisation, because "who will I actually be talking to" is
 * the question this section exists to answer.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('team.title', __('Our counsellors', 'edulume'));
$edulume_posts = edulume_section_posts('edulume_team-member', 6);

if ($edulume_posts !== []) {
    edulume_the_grid_section('team', $edulume_label, 'edulume_team-member', 6);

    return;
}

$edulume_items = edulume_opt_list('team.items');

if ($edulume_items === []) {
    return;
}

if (!edulume_home_section_open('team', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);
?>
<ul class="edulume-people" role="list">
    <?php foreach ($edulume_items as $edulume_item) : ?>
        <?php
        $edulume_name = edulume_row($edulume_item, 'name');
        $edulume_socials = is_array($edulume_item['socials'] ?? null) ? $edulume_item['socials'] : [];
        ?>
        <li class="edulume-person">
            <span class="edulume-person__portrait">
                <?php edulume_the_demo_image(edulume_row($edulume_item, 'photo'), $edulume_name, 240, 300); ?>
            </span>
            <h3 class="edulume-person__name"><?php echo esc_html($edulume_name); ?></h3>
            <p class="edulume-person__role"><?php echo esc_html(edulume_row($edulume_item, 'role')); ?></p>
            <p class="edulume-person__languages"><?php echo esc_html(edulume_row($edulume_item, 'languages')); ?></p>

            <?php if ($edulume_socials !== []) : ?>
                <ul class="edulume-person__links" role="list">
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
        </li>
    <?php endforeach; ?>
</ul>
<?php

edulume_home_section_close();
