<?php
/**
 * GO4TASTE Recipes Plugin — View Model Normalizers
 *
 * Pure data normalization helpers with no database calls or side effects.
 * These functions transform raw repeater arrays and mixed-type values into
 * clean, consistently-typed structures for the view model.
 *
 * WHY separate normalizers from the builder:
 *
 * - Normalizers are pure functions: same input always produces same output.
 * - They can be unit-tested in isolation without WordPress.
 * - The builder (builder.php) orchestrates data fetching and normalization,
 *   but delegates the actual transformation logic to these functions.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize an array of ingredient rows into a consistent shape.
 *
 * @param array<int,mixed> $items
 * @return array<int,array{ingredient_term_id:int|null,name:string,amount:float|null,unit:string}>
 */
function g4t_normalize_ingredients_list( array $items ): array {
    $normalized = array();

    foreach ( $items as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $name = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
        if ( '' === $name ) {
            continue;
        }

        $amount = null;
        if ( isset( $row['amount'] ) && '' !== trim( (string) $row['amount'] ) && is_numeric( (string) $row['amount'] ) ) {
            $amount = (float) $row['amount'];
        }

        $ingredient_term_id = null;
        if ( isset( $row['ingredient_term_id'] ) && '' !== trim( (string) $row['ingredient_term_id'] ) && is_numeric( (string) $row['ingredient_term_id'] ) ) {
            $ingredient_term_id = (int) $row['ingredient_term_id'];
        }
        if ( null === $ingredient_term_id && isset( $row['term_id'] ) && '' !== trim( (string) $row['term_id'] ) && is_numeric( (string) $row['term_id'] ) ) {
            $ingredient_term_id = (int) $row['term_id'];
        }

        $normalized[] = array(
            'ingredient_term_id' => $ingredient_term_id,
            'name'               => $name,
            'amount'             => $amount,
            'unit'               => isset( $row['unit'] ) ? trim( (string) $row['unit'] ) : '',
        );
    }

    return $normalized;
}

/**
 * Normalize an array of step rows into a consistent shape.
 *
 * @param array<int,mixed> $steps
 * @return array<int,array{title:string,description:string,tip:string,images:array<int,string>}>
 */
function g4t_normalize_steps_list( array $steps ): array {
    $normalized = array();

    foreach ( $steps as $index => $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $title = isset( $row['heading'] ) ? trim( (string) $row['heading'] ) : '';
        if ( '' === $title && isset( $row['title'] ) ) {
            $title = trim( (string) $row['title'] );
        }

        $description = isset( $row['description'] ) ? trim( (string) $row['description'] ) : '';
        if ( '' === $description && isset( $row['text'] ) ) {
            $description = trim( (string) $row['text'] );
        }
        if ( '' === $description && isset( $row['step'] ) ) {
            $description = trim( (string) $row['step'] );
        }

        $tip    = isset( $row['tip'] ) ? trim( (string) $row['tip'] ) : '';
        $images = g4t_normalize_images_value( $row['images'] ?? array() );

        // imageUrl from block editor (single image per step)
        if ( empty( $images ) && ! empty( $row['imageUrl'] ) ) {
            $url = esc_url_raw( trim( (string) $row['imageUrl'] ) );
            if ( '' !== $url ) {
                $images = array( $url );
            }
        }

        if ( '' === $description ) {
            continue;
        }

        if ( '' === $title ) {
            $title = sprintf( 'Krok %d', $index + 1 );
        }

        $normalized[] = array(
            'title'       => $title,
            'description' => $description,
            'tip'         => $tip,
            'images'      => $images,
        );
    }

    return $normalized;
}

/**
 * Normalize an images value that may be a string, a numeric ID, or an array of mixed items.
 *
 * @param mixed $value
 * @return array<int,string>
 */
function g4t_normalize_images_value( $value ): array {
    if ( is_string( $value ) ) {
        return g4t_normalize_images_input( $value );
    }

    if ( ! is_array( $value ) ) {
        return array();
    }

    $items = array();

    foreach ( $value as $item ) {
        if ( is_string( $item ) ) {
            $url = trim( $item );
            if ( '' !== $url ) {
                $items[] = $url;
            }
            continue;
        }

        if ( is_numeric( $item ) ) {
            $attachment_url = wp_get_attachment_url( (int) $item );
            if ( $attachment_url ) {
                $items[] = (string) $attachment_url;
            }
            continue;
        }

        if ( is_array( $item ) ) {
            if ( isset( $item['url'] ) && '' !== trim( (string) $item['url'] ) ) {
                $items[] = trim( (string) $item['url'] );
                continue;
            }

            if ( isset( $item['id'] ) && is_numeric( $item['id'] ) ) {
                $attachment_url = wp_get_attachment_url( (int) $item['id'] );
                if ( $attachment_url ) {
                    $items[] = (string) $attachment_url;
                }
            }
        }
    }

    $items = array_values( array_unique( $items ) );

    return array_values( array_filter( $items, static fn( string $url ): bool => '' !== $url ) );
}

/**
 * Parse a raw string of image URLs (newline or comma separated) into an array.
 *
 * @return array<int,string>
 */
function g4t_normalize_images_input( string $raw ): array {
    if ( '' === trim( $raw ) ) {
        return array();
    }

    $items = preg_split( '/\r\n|\r|\n|,/', $raw );
    if ( ! is_array( $items ) ) {
        return array();
    }

    $items = array_map( static fn( string $line ): string => trim( $line ), $items );
    $items = array_filter( $items, static fn( string $line ): bool => '' !== $line );

    return array_values( $items );
}

/**
 * Parse a raw multiline string into a list of non-empty lines.
 *
 * @return array<int,string>
 */
function g4t_normalize_multiline_to_list( string $raw ): array {
    if ( '' === trim( $raw ) ) {
        return array();
    }

    $items = preg_split( '/\r\n|\r|\n/', $raw );
    if ( ! is_array( $items ) ) {
        return array();
    }

    $items = array_map( static fn( string $line ): string => trim( $line ), $items );
    $items = array_filter( $items, static fn( string $line ): bool => '' !== $line );

    return array_values( $items );
}

/**
 * Return true when $value is considered non-empty (non-null, non-false, non-empty-string, non-empty-array).
 *
 * @param mixed $value
 */
function g4t_has_value( $value ): bool {
    if ( is_array( $value ) ) {
        return ! empty( $value );
    }

    if ( is_string( $value ) ) {
        return '' !== trim( $value );
    }

    return null !== $value && false !== $value;
}
