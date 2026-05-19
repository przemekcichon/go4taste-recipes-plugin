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
 * @return array<int,array{ingredient_term_id:int|null,name:string,amount:string|null,unit:string,ingredient_note:string}>
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
        if ( isset( $row['amount'] ) ) {
            $amount_raw = trim( (string) $row['amount'] );
            if ( '' !== $amount_raw ) {
                $amount = $amount_raw;
            }
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
            'ingredient_note'    => isset( $row['ingredient_note'] ) ? trim( (string) $row['ingredient_note'] ) : '',
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

        $heading = isset( $row['heading'] ) ? trim( (string) $row['heading'] ) : '';
        if ( '' === $heading && isset( $row['title'] ) ) {
            $heading = trim( (string) $row['title'] );
        }

        $description = isset( $row['description'] ) ? trim( (string) $row['description'] ) : '';
        if ( '' === $description && isset( $row['text'] ) ) {
            $description = trim( (string) $row['text'] );
        }
        if ( '' === $description && isset( $row['step'] ) ) {
            $description = trim( (string) $row['step'] );
        }

        $tip = isset( $row['tip'] ) ? trim( (string) $row['tip'] ) : '';

        // imageUrl from block editor (single image per step)
        $image_url = '';
        if ( ! empty( $row['imageUrl'] ) ) {
            $image_url = esc_url_raw( trim( (string) $row['imageUrl'] ) );
        }
        // Fallback: legacy 'images' array (first item)
        if ( '' === $image_url && isset( $row['images'] ) && is_array( $row['images'] ) && ! empty( $row['images'] ) ) {
            $first_image = reset( $row['images'] );
            if ( is_string( $first_image ) ) {
                $image_url = esc_url_raw( trim( $first_image ) );
            }
        }

        if ( '' === $description ) {
            continue;
        }

        if ( '' === $heading ) {
            $heading = sprintf( 'Krok %d', $index + 1 );
        }

        $normalized[] = array(
            'heading'     => $heading,
            'description' => $description,
            'tip'         => $tip,
            'imageUrl'    => $image_url,
        );
    }

    return $normalized;
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
