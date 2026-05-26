<?php
/**
 * GO4TASTE Recipes Plugin — Recipe Meta Utilities
 *
 * Responsibilities:
 *  1. Normalize and write ingredient/steps repeater data to post_meta.
 *  2. Keep ingredient taxonomy terms in sync with repeater rows.
 *  3. Provide helper loaders for repeater rows from post_meta.
 *  4. Normalize difficulty value for canonical ACF/meta storage.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize difficulty value for ACF select field storage.
 *
 * ACF uses canonical keys: easy|medium|hard. Editor block currently allows
 * free text, so we map common Polish labels to canonical keys.
 */
function g4t_normalize_difficulty_for_meta( string $difficulty ): string {
    $value = strtolower( trim( sanitize_text_field( $difficulty ) ) );
    if ( '' === $value ) {
        return '';
    }

    $normalized = g4t_normalize_difficulty_key( $value );
    return '' !== $normalized ? $normalized : $value;
}


/**
 * Sync ingredient taxonomy from existing ACF/post-meta when no recipe blocks are present.
 */
function g4t_sync_ingredient_taxonomy_from_existing_meta( int $post_id ): void {
    $count = (int) get_post_meta( $post_id, 'recipe_ingredients', true );

    if ( $count <= 0 ) {
        if ( taxonomy_exists( G4T_INGREDIENT_TAXONOMY ) ) {
            wp_set_object_terms( $post_id, array(), G4T_INGREDIENT_TAXONOMY, false );
        }
        return;
    }

    $rows = g4t_load_ingredients_repeater_meta( $post_id, $count );
    g4t_sync_ingredients_repeater_meta( $post_id, $rows );
}

/**
 * Write ingredient rows to post meta (ACF repeater format) and sync taxonomy terms.
 *
 * WHY delete all rows before writing:
 *
 * ACF repeater fields store data in individual meta keys (e.g. recipe_ingredients_0_name,
 * recipe_ingredients_1_name). If an ingredient is removed in the editor, its old meta
 * keys would remain unless explicitly deleted. The loop deletes rows up to max(old, new)
 * to clean up orphaned entries.
 *
 * WHY taxonomy sync happens here:
 *
 * Each ingredient row can specify an ingredient_term_id (explicit term assignment) or
 * just a name (auto-create/resolve term). This function resolves missing terms via
 * g4t_ensure_ingredient_term() and assigns all ingredient terms to the post via
 * wp_set_object_terms(). This keeps the taxonomy in sync with the repeater data.
 *
 * @param int $post_id Post ID.
 * @param array<int,array<string,mixed>> $ingredients Array of ingredient rows.
 */
function g4t_sync_ingredients_repeater_meta( int $post_id, array $ingredients ): void {
    $old_count = (int) get_post_meta( $post_id, 'recipe_ingredients', true );
    $new_count = count( $ingredients );
    $term_ids  = array();

    for ( $i = 0; $i < max( $old_count, $new_count ); $i++ ) {
        delete_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_name', $i ) );
        delete_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_amount', $i ) );
        delete_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_unit', $i ) );
        delete_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_ingredient_term_id', $i ) );
        delete_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_ingredient_note', $i ) );
    }

    foreach ( $ingredients as $index => $row ) {
        $name            = sanitize_text_field( (string) ( $row['name'] ?? '' ) );
        $amount          = sanitize_text_field( trim( (string) ( $row['amount'] ?? '' ) ) );
        $unit            = sanitize_text_field( (string) ( $row['unit'] ?? '' ) );
        $ingredient_note = sanitize_text_field( (string) ( $row['ingredient_note'] ?? '' ) );

        update_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_name', $index ), $name );
        update_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_amount', $index ), $amount );
        update_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_unit', $index ), $unit );
        update_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_ingredient_note', $index ), $ingredient_note );

        $term_id = null;
        if ( isset( $row['ingredient_term_id'] ) && is_numeric( (string) $row['ingredient_term_id'] ) ) {
            $term_id = (int) $row['ingredient_term_id'];
        }

        if ( null === $term_id ) {
            $term_id = g4t_ensure_ingredient_term( $name );
        }

        if ( null !== $term_id ) {
            update_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_ingredient_term_id', $index ), $term_id );
            $term_ids[] = $term_id;
        }
    }

    update_post_meta( $post_id, 'recipe_ingredients', $new_count );

    if ( taxonomy_exists( G4T_INGREDIENT_TAXONOMY ) ) {
        wp_set_object_terms( $post_id, array_values( array_unique( $term_ids ) ), G4T_INGREDIENT_TAXONOMY, false );
    }
}

/**
 * Find or create a taxonomy term for an ingredient name.
 */
/**
 * Ensure an ingredient term exists in the taxonomy, creating it if necessary.
 *
 * WHY term resolution happens by name then slug:
 *
 * 1. get_term_by('name') checks for exact name match (case-sensitive).
 * 2. term_exists(slug) checks if a term with that slug exists (handles name changes).
 * 3. wp_insert_term() creates the term if neither check finds it.
 *
 * This pattern handles:
 *  - Imported data with just a name string (no term_id).
 *  - Renamed terms (slug remains stable).
 *  - Duplicate prevention (won't create "Mąka" twice).
 *
 * @param string $ingredient_name The ingredient name to resolve.
 * @return int|null Term ID if found/created, null if name is empty or creation failed.
 */
function g4t_ensure_ingredient_term( string $ingredient_name ): ?int {
    $ingredient_name = trim( wp_strip_all_tags( $ingredient_name ) );
    if ( '' === $ingredient_name ) {
        return null;
    }

    $term = get_term_by( 'name', $ingredient_name, G4T_INGREDIENT_TAXONOMY );
    if ( $term instanceof WP_Term ) {
        return (int) $term->term_id;
    }

    $slug        = sanitize_title( $ingredient_name );
    $term_exists = term_exists( $slug, G4T_INGREDIENT_TAXONOMY );
    if ( is_array( $term_exists ) && isset( $term_exists['term_id'] ) ) {
        return (int) $term_exists['term_id'];
    }
    if ( is_int( $term_exists ) ) {
        return $term_exists;
    }

    $created = wp_insert_term(
        $ingredient_name,
        G4T_INGREDIENT_TAXONOMY,
        array( 'slug' => $slug )
    );

    if ( is_wp_error( $created ) || ! isset( $created['term_id'] ) ) {
        return null;
    }

    return (int) $created['term_id'];
}

/**
 * Write step rows to post meta (ACF repeater format).
 *
 * @param int $post_id Post ID.
 * @param array<int,array{heading:string,description:string,tip:string,imageUrl:string}> $steps Step objects.
 */
function g4t_sync_steps_repeater_meta( int $post_id, array $steps ): void {
    $old_count = (int) get_post_meta( $post_id, 'recipe_steps', true );
    $new_count = count( $steps );

    for ( $i = 0; $i < max( $old_count, $new_count ); $i++ ) {
        delete_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_header', $i ) );
        delete_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_description', $i ) );
        delete_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_tip', $i ) );
        delete_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_image_url', $i ) );
    }

    foreach ( $steps as $index => $row ) {
        update_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_header', $index ), (string) ( $row['heading'] ?? '' ) );
        update_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_description', $index ), (string) ( $row['description'] ?? '' ) );
        update_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_tip', $index ), (string) ( $row['tip'] ?? '' ) );
        update_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_image_url', $index ), (string) ( $row['imageUrl'] ?? '' ) );
    }

    update_post_meta( $post_id, 'recipe_steps', $new_count );
}

/**
 * Load ingredient rows from post meta ACF repeater format.
 *
 * @return array<int,array{ingredient_term_id:int|null,name:string,amount:string|null,unit:string}>
 */
function g4t_load_ingredients_repeater_meta( int $post_id, int $count ): array {
    $rows = array();

    for ( $i = 0; $i < $count; $i++ ) {
        $name = trim( (string) get_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_name', $i ), true ) );
        if ( '' === $name ) {
            continue;
        }

        $amount_raw = get_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_amount', $i ), true );
        $amount     = trim( (string) $amount_raw );
        if ( '' === $amount ) {
            $amount = null;
        }
        $term_raw   = get_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_ingredient_term_id', $i ), true );
        $term_id    = ( '' !== (string) $term_raw && is_numeric( (string) $term_raw ) ) ? (int) $term_raw : null;

        $rows[] = array(
            'ingredient_term_id' => $term_id,
            'name'               => $name,
            'amount'             => $amount,
            'unit'               => trim( (string) get_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_unit', $i ), true ) ),
            'ingredient_note'    => trim( (string) get_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_ingredient_note', $i ), true ) ),
        );
    }

    return $rows;
}

/**
 * Load step rows from post meta ACF repeater format.
 *
 * @return array<int,array{title:string,description:string,tip:string,images:array<int,string>}>
 */
function g4t_load_steps_repeater_meta( int $post_id, int $count ): array {
    $rows = array();

    for ( $i = 0; $i < $count; $i++ ) {
        $heading     = trim( (string) get_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_header', $i ), true ) );
        $description = trim( (string) get_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_description', $i ), true ) );
        $tip         = trim( (string) get_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_tip', $i ), true ) );
        $image_url   = trim( (string) get_post_meta( $post_id, sprintf( 'recipe_steps_%d_step_image_url', $i ), true ) );

        if ( '' === $description ) {
            continue;
        }

        if ( '' === $heading ) {
            $heading = sprintf( 'Krok %d', $i + 1 );
        }

        $rows[] = array(
            'heading'     => $heading,
            'description' => $description,
            'tip'         => $tip,
            'imageUrl'    => $image_url,
        );
    }

    return $rows;
}

