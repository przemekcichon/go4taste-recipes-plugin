<?php
/**
 * GO4TASTE Recipes Plugin — Meta Synchronization
 *
 * Responsibilities:
 *  1. Parse recipe block data from post_content on save_post.
 *  2. Write parsed data to ACF-compatible post_meta keys.
 *  3. Sync ingredient repeater rows to taxonomy terms (create missing terms).
 *  4. Sync steps repeater rows with nested image arrays.
 *  5. Load repeater data from post_meta when ACF returns numeric count.
 *
 * WHY block data is synced to post_meta:
 *
 * Block attributes in post_content are the source of truth for the editor, but:
 *  - ACF fields may be queried directly by legacy code or importers.
 *  - WPGraphQL and REST API consumers expect normalized meta keys, not block parsing.
 *  - Timber templates can fall back to ACF when block data is absent (e.g. imported posts).
 *
 * This sync keeps both representations consistent. The view model builder (see
 * view-model/builder.php) prefers block data when present but uses post_meta as
 * a fallback, so imported or migrated recipes render correctly even if they were
 * never opened in the block editor.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Keep structured recipe fields in ACF/meta keys without changing block editor UI.
 *
 * WHY the static $running guard:
 *
 * WordPress save_post hook can fire recursively when wp_update_post() is called
 * inside a save_post callback. Since this function calls update_post_meta()
 * (which does not trigger recursion) the guard is defensive: it prevents infinite
 * loops if future code calls wp_update_post() or if another plugin does so.
 *
 * The guard is reset at the end of the function (not in a finally block) so that
 * subsequent legitimate saves work correctly.
 *
 * @param int $post_id Post ID being saved.
 * @param WP_Post $post Post object.
 * @param bool $update Whether this is an update (true) or insert (false).
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance for context.
 */
function g4t_sync_recipe_meta_from_blocks( int $post_id, WP_Post $post, bool $update, Go4Taste_Recipes_Plugin $plugin ): void {
    static $running = false;
    if ( $running ) {
        return;
    }
    $running = true;

    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        $running = false;
        return;
    }

    // Performance optimization: Skip if ACF just updated blocks
    // (ACF → blocks sync already has the meta, no need to re-parse)
    if ( get_transient( "g4t_acf_save_{$post_id}" ) ) {
        delete_transient( "g4t_acf_save_{$post_id}" );
        $running = false;
        return;
    }

    $strategy = $plugin->resolve_source_strategy();
    if ( $post->post_type !== $strategy['post_type'] ) {
        $running = false;
        return;
    }

    $block_data = g4t_get_recipe_blocks_data( $post );
    if ( empty( $block_data['_has_recipe_blocks'] ) ) {
        g4t_sync_ingredient_taxonomy_from_existing_meta( $post_id );
        $running = false;
        return;
    }

    $payload = array(
        'recipe_time_prep'             => sanitize_text_field( (string) ( $block_data['time_prep'] ?? '' ) ),
        'recipe_time_cook'             => sanitize_text_field( (string) ( $block_data['time_cook'] ?? '' ) ),
        'recipe_time_total'            => sanitize_text_field( (string) ( $block_data['time_total'] ?? '' ) ),
        'recipe_time_soak'             => sanitize_text_field( (string) ( $block_data['time_soak'] ?? '' ) ),
        'recipe_difficulty'            => sanitize_text_field( (string) ( $block_data['difficulty'] ?? '' ) ),
        'recipe_servings_value'        => $block_data['servings_value'] ?? null,
        'recipe_servings_unit'         => sanitize_text_field( (string) ( $block_data['servings_unit'] ?? '' ) ),
        'recipe_ingredients_photo_url' => esc_url_raw( (string) ( $block_data['ingredients_photo_url'] ?? '' ) ),
        'recipe_tips'                  => wp_kses_post( (string) ( $block_data['tips'] ?? '' ) ),
        'recipe_source_label'          => sanitize_text_field( (string) ( $block_data['source_label'] ?? '' ) ),
        'recipe_source_url'            => esc_url_raw( (string) ( $block_data['source_url'] ?? '' ) ),
    );

    foreach ( $payload as $meta_key => $value ) {
        update_post_meta( $post_id, $meta_key, $value );
    }

    $ingredients = ( ! empty( $block_data['ingredients'] ) && is_array( $block_data['ingredients'] ) ) ? $block_data['ingredients'] : array();
    $steps       = ( ! empty( $block_data['steps'] ) && is_array( $block_data['steps'] ) ) ? $block_data['steps'] : array();

    g4t_sync_ingredients_repeater_meta( $post_id, $ingredients );
    g4t_sync_steps_repeater_meta( $post_id, $steps );

    // Track that this edit came from blocks
    g4t_track_edit_source( $post_id, 'blocks' );

    $running = false;
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
        $name = (string) ( $row['name'] ?? '' );
        update_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_name', $index ), $name );
        update_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_amount', $index ), $row['amount'] ?? '' );
        update_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_unit', $index ), (string) ( $row['unit'] ?? '' ) );
        update_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_ingredient_note', $index ), (string) ( $row['ingredient_note'] ?? '' ) );

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
 * @return array<int,array{ingredient_term_id:int|null,name:string,amount:float|null,unit:string}>
 */
function g4t_load_ingredients_repeater_meta( int $post_id, int $count ): array {
    $rows = array();

    for ( $i = 0; $i < $count; $i++ ) {
        $name = trim( (string) get_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_name', $i ), true ) );
        if ( '' === $name ) {
            continue;
        }

        $amount_raw = get_post_meta( $post_id, sprintf( 'recipe_ingredients_%d_amount', $i ), true );
        $amount     = ( '' !== (string) $amount_raw && is_numeric( (string) $amount_raw ) ) ? (float) $amount_raw : null;
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

/**
 * Parse block editor content and extract structured recipe data.
 *
 * @param WP_Post $post
 * @return array<string,mixed>
 */
function g4t_get_recipe_blocks_data( WP_Post $post ): array {
    if ( ! function_exists( 'parse_blocks' ) ) {
        return array();
    }

    $result = array(
        '_has_recipe_blocks'     => false,
        '_has_named_blocks'      => false,
        '_has_hero_block'        => false,
        '_has_ingredients_block' => false,
        '_has_steps_block'       => false,
        'ads_product_ids'        => array(),
        'lead'                   => '',
        'difficulty'             => '',
        'servings_value'         => null,
        'servings_unit'          => '',
        'time_prep'              => '',
        'time_cook'              => '',
        'time_total'             => '',
        'time_soak'              => '',
        'ingredients'            => array(),
        'ingredients_photo_url'  => '',
        'steps'                  => array(),
        'tips'                   => '',
        'source_label'           => '',
        'source_url'             => '',
    );

    $blocks = parse_blocks( (string) $post->post_content );
    $blocks = g4t_flatten_recipe_blocks( $blocks );

    foreach ( $blocks as $block ) {
        if ( ! is_array( $block ) || empty( $block['blockName'] ) ) {
            continue;
        }

        $result['_has_named_blocks'] = true;

        $attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

        if ( 'go4taste-recipes/hero' === $block['blockName'] ) {
            $result['_has_recipe_blocks'] = true;
            $result['_has_hero_block']    = true;
            $result['lead']           = isset( $attrs['lead'] ) ? (string) $attrs['lead'] : $result['lead'];
            $result['difficulty']     = isset( $attrs['difficulty'] ) ? (string) $attrs['difficulty'] : $result['difficulty'];
            $result['servings_value'] = isset( $attrs['servingsValue'] ) ? $attrs['servingsValue'] : $result['servings_value'];
            $result['servings_unit']  = isset( $attrs['servingsUnit'] ) ? (string) $attrs['servingsUnit'] : $result['servings_unit'];
            $result['time_prep']      = isset( $attrs['timePrep'] ) ? (string) $attrs['timePrep'] : $result['time_prep'];
            $result['time_cook']      = isset( $attrs['timeCook'] ) ? (string) $attrs['timeCook'] : $result['time_cook'];
            $result['time_total']     = isset( $attrs['timeTotal'] ) ? (string) $attrs['timeTotal'] : $result['time_total'];
            $result['time_soak']      = isset( $attrs['timeSoak'] ) ? (string) $attrs['timeSoak'] : $result['time_soak'];
        }

        if ( 'go4taste-recipes/ingredients' === $block['blockName'] ) {
            $result['_has_recipe_blocks']    = true;
            $result['_has_ingredients_block'] = true;
            if ( isset( $attrs['itemsList'] ) && is_array( $attrs['itemsList'] ) ) {
                $result['ingredients'] = g4t_normalize_ingredients_list( $attrs['itemsList'] );
            } else {
                $lines = g4t_normalize_multiline_to_list( isset( $attrs['items'] ) ? (string) $attrs['items'] : '' );
                $result['ingredients'] = array_map(
                    static function ( string $line ): array {
                        return array(
                            'name'   => $line,
                            'amount' => null,
                            'unit'   => '',
                        );
                    },
                    $lines
                );
            }
            $result['ingredients_photo_url'] = isset( $attrs['photoUrl'] ) ? (string) $attrs['photoUrl'] : '';
        }

        if ( 'go4taste-recipes/steps' === $block['blockName'] ) {
            $result['_has_recipe_blocks'] = true;
            $result['_has_steps_block']   = true;
            if ( isset( $attrs['stepsList'] ) && is_array( $attrs['stepsList'] ) ) {
                $result['steps'] = g4t_normalize_steps_list( $attrs['stepsList'] );
            } else {
                $result['steps'] = g4t_normalize_multiline_to_list( isset( $attrs['steps'] ) ? (string) $attrs['steps'] : '' );
            }
        }

        if ( 'go4taste-ads/inline-banner' === $block['blockName'] ) {
            $products = isset( $attrs['products'] ) && is_array( $attrs['products'] ) ? $attrs['products'] : array();
            foreach ( $products as $product_entry ) {
                $normalized_id = g4t_extract_ads_product_id( $product_entry );
                if ( '' !== $normalized_id ) {
                    $result['ads_product_ids'][] = $normalized_id;
                }
            }
            if ( ! empty( $result['ads_product_ids'] ) ) {
                $result['ads_product_ids'] = array_values( array_unique( $result['ads_product_ids'] ) );
            }
        }

        if ( 'go4taste-recipes/notes' === $block['blockName'] ) {
            $result['_has_recipe_blocks'] = true;
            $result['tips']         = isset( $attrs['tips'] ) ? (string) $attrs['tips'] : $result['tips'];
            $result['source_label'] = isset( $attrs['sourceLabel'] ) ? (string) $attrs['sourceLabel'] : $result['source_label'];
            $result['source_url']   = isset( $attrs['sourceUrl'] ) ? (string) $attrs['sourceUrl'] : $result['source_url'];
        }

        if ( 'go4taste-recipes/tips' === $block['blockName'] ) {
            $result['_has_recipe_blocks'] = true;
            $result['tips'] = isset( $attrs['tips'] ) ? (string) $attrs['tips'] : $result['tips'];
        }

        if ( 'go4taste-recipes/source' === $block['blockName'] ) {
            $result['_has_recipe_blocks'] = true;
            $result['source_label'] = isset( $attrs['sourceLabel'] ) ? (string) $attrs['sourceLabel'] : $result['source_label'];
            $result['source_url']   = isset( $attrs['sourceUrl'] ) ? (string) $attrs['sourceUrl'] : $result['source_url'];
        }
    }

    return $result;
}

/**
 * Extract a product ID from inline-banner products attribute entry.
 *
 * Supported entry shapes:
 * - scalar id: "123" / 123
 * - object-like arrays: ['id' => '123'] or ['product_id' => '123']
 *
 * @param mixed $product_entry Product entry from block attrs.
 * @return string
 */
function g4t_extract_ads_product_id( $product_entry ): string {
    if ( is_scalar( $product_entry ) ) {
        return trim( (string) $product_entry );
    }

    if ( is_array( $product_entry ) ) {
        if ( isset( $product_entry['id'] ) && is_scalar( $product_entry['id'] ) ) {
            return trim( (string) $product_entry['id'] );
        }

        if ( isset( $product_entry['product_id'] ) && is_scalar( $product_entry['product_id'] ) ) {
            return trim( (string) $product_entry['product_id'] );
        }
    }

    return '';
}

/**
 * Recursively flatten nested block arrays into a single-level list.
 *
 * @param array<int,mixed> $blocks
 * @return array<int,mixed>
 */
function g4t_flatten_recipe_blocks( array $blocks ): array {
    $flat = array();

    foreach ( $blocks as $block ) {
        if ( ! is_array( $block ) ) {
            continue;
        }

        $flat[] = $block;

        if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) ) {
            $flat = array_merge( $flat, g4t_flatten_recipe_blocks( $block['innerBlocks'] ) );
        }
    }

    return $flat;
}
