<?php

/**
 * The site footer.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_chrome = edulume_chrome();

?>
    </main>

    <footer class="edulume-footer edulume-footer--<?php echo esc_attr($edulume_chrome->footerVariant->value); ?>">
        <?php edulume_render_variant($edulume_chrome->footerVariant->templatePart(), 'template-parts/footer/columns'); ?>

        <div class="edulume-footer__baseline">
            <?php if (has_nav_menu('footer')) : ?>
                <nav aria-label="<?php esc_attr_e('Footer', 'edulume'); ?>">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'footer',
                        'container' => false,
                        'menu_class' => 'edulume-footer__menu',
                        'depth' => 1,
                    ]);
                    ?>
                </nav>
            <?php endif; ?>

            <p class="edulume-footer__credit">
                <?php
                echo esc_html(
                    $edulume_chrome->footerCredit !== ''
                        ? $edulume_chrome->footerCredit
                        : sprintf(
                            /* translators: %s: the site name. */
                            __('© %s. All rights reserved.', 'edulume'),
                            get_bloginfo('name')
                        )
                );
                ?>
            </p>
        </div>
    </footer>

    <?php edulume_the_floating_actions(); ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
