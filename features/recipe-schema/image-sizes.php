<?php
/**
 * GO4TASTE Recipes Plugin — Recipe schema image sizes
 *
 * Registers hard-cropped 16:9, 4:3 and 1:1 sizes for `Recipe.image`
 * (Google recommends one high-resolution image per aspect ratio).
 *
 * WHY a custom crop for small originals:
 *
 * WordPress never crops when the original is smaller than the target size in
 * a dimension — a 600×600 original gets no 1200×675 file at all. For these
 * sizes the original is cropped to the target aspect ratio instead, without
 * upscaling (600×600 → 600×338). Larger originals keep WordPress' default
 * behavior.
 *
 * Existing attachments need `wp media regenerate` after activation.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Schema image sizes in `Recipe.image` order: name => [ width, height ].
 *
 * @return array<string,array{0:int,1:int}>
 */
function g4t_recipe_schema_image_sizes(): array {
    return array(
        'g4t-recipe-1x1'  => array( 1200, 1200 ),
        'g4t-recipe-4x3'  => array( 1200, 900 ),
        'g4t-recipe-16x9' => array( 1200, 675 ),
    );
}

/**
 * `after_setup_theme` callback: register the schema image sizes.
 */
function g4t_recipe_schema_register_image_sizes(): void {
    foreach ( g4t_recipe_schema_image_sizes() as $name => $size ) {
        add_image_size( $name, $size[0], $size[1], true );
    }
}

/**
 * `image_resize_dimensions` callback: crop small originals to the schema size ratio without upscaling.
 *
 * Applies only to hard crops whose target matches a schema size and is larger
 * than the original in at least one dimension; everything else is left to
 * WordPress (null). The filter gets no size name, so any other hard-crop size
 * with the same dimensions gets this behavior too. A crop position array
 * (`array( 'left', 'top' )`) is honored as in WordPress core.
 *
 * @param null|mixed $payload Earlier filter result.
 * @param int        $orig_w  Original width.
 * @param int        $orig_h  Original height.
 * @param int        $dest_w  Target width.
 * @param int        $dest_h  Target height.
 * @param bool|array $crop    Crop setting.
 * @return null|false|array{0:int,1:int,2:int,3:int,4:int,5:int,6:int,7:int}
 */
function g4t_recipe_schema_ratio_crop_dimensions( $payload, $orig_w, $orig_h, $dest_w, $dest_h, $crop ) {
    if ( null !== $payload || ! $crop ) {
        return $payload;
    }

    $orig_w = (int) $orig_w;
    $orig_h = (int) $orig_h;
    $dest_w = (int) $dest_w;
    $dest_h = (int) $dest_h;

    if ( $orig_w < 1 || $orig_h < 1 || ! in_array( array( $dest_w, $dest_h ), array_values( g4t_recipe_schema_image_sizes() ), true ) ) {
        return $payload;
    }

    if ( $orig_w >= $dest_w && $orig_h >= $dest_h ) {
        return $payload;
    }

    $ratio = $dest_w / $dest_h;

    if ( $orig_w / $orig_h > $ratio ) {
        $crop_h = $orig_h;
        $crop_w = (int) round( $orig_h * $ratio );
    } else {
        $crop_w = $orig_w;
        $crop_h = (int) round( $orig_w / $ratio );
    }

    // The original already has the target ratio: no new file (as WordPress does for same-size resizes).
    if ( $crop_w === $orig_w && $crop_h === $orig_h ) {
        return false;
    }

    // As in core: anything but a two-item position array means centered.
    $position = is_array( $crop ) && 2 === count( $crop ) ? array_values( $crop ) : array( 'center', 'center' );
    $crop_x   = (string) $position[0];
    $crop_y   = (string) $position[1];

    $src_x = 'left' === $crop_x ? 0 : ( 'right' === $crop_x ? $orig_w - $crop_w : (int) floor( ( $orig_w - $crop_w ) / 2 ) );
    $src_y = 'top' === $crop_y ? 0 : ( 'bottom' === $crop_y ? $orig_h - $crop_h : (int) floor( ( $orig_h - $crop_h ) / 2 ) );

    return array( 0, 0, $src_x, $src_y, $crop_w, $crop_h, $crop_w, $crop_h );
}

/**
 * Resolve `Recipe.image` candidates for an attachment: generated schema crops, then the original.
 *
 * Only crops that actually exist in the attachment metadata are used — never
 * WordPress' scaled fallback, whose ratio would not match the size name.
 *
 * @return array<int,array{url:string,width:int,height:int}>
 */
function g4t_recipe_schema_attachment_images( int $attachment_id ): array {
    $images = array();

    foreach ( array_keys( g4t_recipe_schema_image_sizes() ) as $name ) {
        $size = image_get_intermediate_size( $attachment_id, $name );
        if ( is_array( $size ) && ! empty( $size['url'] ) ) {
            $images[] = array(
                'url'    => (string) $size['url'],
                'width'  => (int) $size['width'],
                'height' => (int) $size['height'],
            );
        }
    }

    $full = wp_get_attachment_image_src( $attachment_id, 'full' );
    if ( is_array( $full ) && ! empty( $full[0] ) && ! in_array( (string) $full[0], array_column( $images, 'url' ), true ) ) {
        $images[] = array(
            'url'    => (string) $full[0],
            'width'  => (int) $full[1],
            'height' => (int) $full[2],
        );
    }

    return $images;
}
