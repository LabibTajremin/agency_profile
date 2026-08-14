<?php

/**
 * The faceted finder.
 *
 * Rendered as a real GET form. Filtering without JavaScript submits the form and reloads with
 * the same URL the AJAX path would have produced — so the shareable-URL requirement and the
 * no-JavaScript path are the same mechanism rather than two that can drift apart.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use Edulume\Core\Domain\Finder\FinderCatalogue;
use Edulume\Core\Domain\Finder\FinderSort;

$edulume_type = (string) ($args['post_type'] ?? '');

if (!edulume_core_is_active() || !FinderCatalogue::supports($edulume_type)) {
    return;
}

$edulume_facets = FinderCatalogue::facetsFor($edulume_type);
$edulume_selected = apply_filters('edulume_finder_selections', [], $edulume_type);
$edulume_terms = apply_filters('edulume_finder_options', [], $edulume_type);

?>
<form class="edulume-finder"
      method="get"
      data-edulume-finder="<?php echo esc_attr($edulume_type); ?>"
      aria-label="<?php esc_attr_e('Filter results', 'edulume'); ?>">
    <div class="edulume-finder__search">
        <label class="screen-reader-text" for="edulume-finder-q"><?php esc_html_e('Search', 'edulume'); ?></label>
        <input type="search"
               id="edulume-finder-q"
               name="q"
               value="<?php echo esc_attr((string) ($edulume_selected['q'] ?? '')); ?>"
               placeholder="<?php esc_attr_e('Search by name or keyword', 'edulume'); ?>" />
        <button type="submit" class="edulume-finder__submit"><?php esc_html_e('Filter', 'edulume'); ?></button>
    </div>

    <div class="edulume-finder__facets">
        <?php foreach ($edulume_facets as $edulume_facet) : ?>
            <fieldset class="edulume-finder__facet">
                <legend class="edulume-finder__legend"><?php echo esc_html($edulume_facet->label); ?></legend>

                <?php foreach ((array) ($edulume_terms[$edulume_facet->key] ?? []) as $edulume_value => $edulume_label) : ?>
                    <?php
                    $edulume_id = 'facet-' . $edulume_facet->key . '-' . sanitize_key((string) $edulume_value);
                    $edulume_chosen = in_array(
                        (string) $edulume_value,
                        (array) ($edulume_selected[$edulume_facet->key] ?? []),
                        true
                    );
                    ?>
                    <div class="edulume-finder__option">
                        <input type="<?php echo $edulume_facet->isMultiple ? 'checkbox' : 'radio'; ?>"
                               id="<?php echo esc_attr($edulume_id); ?>"
                               name="<?php echo esc_attr($edulume_facet->key); ?><?php echo $edulume_facet->isMultiple ? '[]' : ''; ?>"
                               value="<?php echo esc_attr((string) $edulume_value); ?>"
                               <?php checked($edulume_chosen); ?> />
                        <label for="<?php echo esc_attr($edulume_id); ?>"><?php echo esc_html((string) $edulume_label); ?></label>
                    </div>
                <?php endforeach; ?>
            </fieldset>
        <?php endforeach; ?>
    </div>

    <div class="edulume-finder__controls">
        <label for="edulume-finder-sort"><?php esc_html_e('Sort by', 'edulume'); ?></label>
        <select id="edulume-finder-sort" name="sort">
            <?php foreach (FinderSort::forPostType($edulume_type) as $edulume_sort) : ?>
                <option value="<?php echo esc_attr($edulume_sort->value); ?>"
                    <?php selected(($edulume_selected['sort'] ?? '') === $edulume_sort->value); ?>>
                    <?php echo esc_html($edulume_sort->label()); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php
        // The archive link rather than the current URL with its query stripped: it is the same
        // destination, and it does not require reading a superglobal to work it out.
        $edulume_clear_url = get_post_type_archive_link($edulume_type);
        ?>
        <a class="edulume-finder__clear" href="<?php echo esc_url($edulume_clear_url ?: home_url('/')); ?>">
            <?php esc_html_e('Clear filters', 'edulume'); ?>
        </a>
    </div>

    <p class="edulume-finder__status" role="status" aria-live="polite"></p>
</form>
