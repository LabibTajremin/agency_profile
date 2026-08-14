# Child theme guide

Use the child theme for anything you want to survive an update. **Edulume Child** ships with the
product; upload and activate it instead of the parent.

## What to override

Copy the file you want to change from `edulume-theme/` into `edulume-child/`, keeping the same
path. WordPress picks up the child's copy.

The parts most worth overriding:

| Path                              | What it is                     |
| --------------------------------- | ------------------------------ |
| `template-parts/header/*.php`     | One header arrangement         |
| `template-parts/footer/*.php`     | One footer arrangement         |
| `template-parts/home/*.php`       | One home section               |
| `template-parts/content/card.php` | The card used by every archive |
| `single-edulume_course.php`       | The course page                |

## What not to override

**Do not copy `assets/css/base.css`.** Every value in it is a custom property the plugin
compiles; a copied stylesheet stops receiving your configurator changes and turns a global
change into a search across two files. Add a small `style.css` in the child that sets or uses
the same custom properties instead.

## Filters worth knowing

```php
// Reorder or remove home sections.
add_filter( 'edulume_home_sections', function ( array $sections ): array {
    return array_values( array_diff( $sections, [ 'events' ] ) );
} );

// Change an archive base.
add_filter( 'edulume_post_type_rewrite_base', function ( string $base, string $type ): string {
    return $type === 'edulume_course' ? 'programmes' : $base;
}, 10, 2 );

// Add a row to the meta strip on a single item.
add_filter( 'edulume_single_meta', function ( array $rows, string $type, int $id ): array {
    if ( $type === 'edulume_course' ) {
        $rows[] = [ 'label' => __( 'Campus', 'your-domain' ), 'value' => get_post_meta( $id, 'campus', true ) ];
    }

    return $rows;
}, 10, 3 );
```

Use your own text domain in a child theme, not `edulume` — the parent's translations do not
cover strings you added.

## Adding a block style

Block styles are registered by the plugin, so a child theme adds to them rather than replacing
them:

```php
add_action( 'init', function (): void {
    register_block_style( 'edulume/hero', [
        'name'  => 'compact',
        'label' => __( 'Compact', 'your-domain' ),
    ] );
}, 20 );
```

Then style `.is-style-compact` in your child stylesheet, using the same custom properties the
rest of the theme uses so it follows the accent.
