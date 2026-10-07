<?php
/**
 * GO4TASTE Recipes Plugin — Timber Context Filters
 *
 * Provides integration between the plugin's data layer and Timber-based themes.
 * These filter callbacks inject recipe data into the Timber context array so
 * templates can access structured recipe data without querying ACF directly.
 *
 * WHY use context filters instead of template functions:
 *
 * Timber's context pattern centralizes all data preparation in one place. Templates
 * receive a fully-hydrated $context array and only need to output values. This
 * keeps template logic simple and makes it easy to swap rendering systems later
 * (e.g. WPGraphQL, REST API, static site generation).
 *
 * Each filter is registered in the main plugin class and fires when Timber
 * prepares the context for a specific template type (single, archive, home).
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Filter the Timber context for single recipe pages.
 *
 * @param array<string,mixed> $context
 * @param array<string,mixed> $meta
 * @return array<string,mixed>
 */
function g4t_filter_single_context( array $context, array $meta = array(), Go4Taste_Recipes_Plugin $plugin ): array {
    $strategy = g4t_get_source_strategy();
    $post     = get_queried_object();

    $context['recipes']          = g4t_base_context_meta( $strategy );
    $context['recipes']['scope'] = 'single';

    if ( ! ( $post instanceof WP_Post ) ) {
        return $context;
    }

    if ( $post->post_type !== $strategy['post_type'] ) {
        return $context;
    }

    $context['is_recipe'] = true;
    $context['recipe']    = g4t_build_single_recipe_view_model( $post, $plugin );

    if ( g4t_is_recipe_debug_enabled() ) {
        $parsed_blocks       = g4t_collect_blocks_debug( $post );
        $ingredient_sections = isset( $context['recipe']['ingredient_sections'] ) && is_array( $context['recipe']['ingredient_sections'] )
            ? $context['recipe']['ingredient_sections']
            : array();
        $ingredients_count   = 0;
        foreach ( $ingredient_sections as $section ) {
            if ( is_array( $section ) && isset( $section['items'] ) && is_array( $section['items'] ) ) {
                $ingredients_count += count( $section['items'] );
            }
        }
        $panel_payload = array(
            'debug_marker'        => 'g4t-recipes-debug-2026-05-09-fallback-restored-v1',
            'strategy'            => $strategy,
            'environment'         => array(
                'home_url' => home_url(),
                'site_url' => site_url(),
            ),
            'queried_post_id'     => (int) $post->ID,
            'queried_post_type'   => (string) $post->post_type,
            'queried_post_status' => (string) $post->post_status,
            'source_debug'        => g4t_collect_recipe_source_debug( $post, $plugin ),
            'revision_debug'      => g4t_collect_recipe_revision_debug( $post ),
            'autosave_debug'      => g4t_collect_recipe_autosave_debug( $post ),
            'parsed_block_names'  => array_map(
                static function ( array $block ): string {
                    return isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
                },
                array_values(
                    array_filter(
                        $parsed_blocks,
                        static function ( $item ): bool {
                            return is_array( $item );
                        }
                    )
                )
            ),
            'recipe_model_counts' => array(
                'ingredients'         => $ingredients_count,
                'ingredient_sections' => count( $ingredient_sections ),
                'steps'               => isset( $context['recipe']['steps'] ) && is_array( $context['recipe']['steps'] ) ? count( $context['recipe']['steps'] ) : 0,
            ),
        );

        $json_options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ( defined( 'JSON_PARTIAL_OUTPUT_ON_ERROR' ) ) {
            $json_options |= JSON_PARTIAL_OUTPUT_ON_ERROR;
        }

        $debug_json = wp_json_encode( $panel_payload, $json_options );
        if ( false === $debug_json ) {
            $debug_json = wp_json_encode(
                array(
                    'debug_marker' => 'g4t-recipes-debug-json-fallback-v1',
                    'error'        => 'wp_json_encode_failed',
                ),
                $json_options
            );
        }

        $context['recipes_debug_enabled'] = true;
        $context['recipes_debug_json']    = is_string( $debug_json ) ? $debug_json : '{"error":"debug_json_not_available"}';
        error_log( 'GO4TASTE Recipes Debug: ' . $context['recipes_debug_json'] );
    }

    return $context;
}

/**
 * Resolve active filter URL params to an array of term objects for display as tags.
 *
 * @return array<int, array{name: string, url: string, taxonomy: string}> `url` is '' when the term has no page.
 */
function g4t_get_active_filter_tags(): array {
    $param_to_taxonomy = array(
        'g4t_meal_type'   => G4T_MEAL_TYPE_TAXONOMY,
        'g4t_prep_time'   => G4T_PREP_TIME_TAXONOMY,
        'g4t_feature'     => G4T_FEATURE_TAXONOMY,
        'g4t_ingredients' => G4T_INGREDIENT_TAXONOMY,
    );

    $tags = array();
    foreach ( $param_to_taxonomy as $param => $taxonomy ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $raw = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : '';
        if ( '' === $raw ) {
            continue;
        }
        $slugs = array_filter( array_map( 'sanitize_key', explode( ',', $raw ) ) );
        foreach ( $slugs as $slug ) {
            $term = get_term_by( 'slug', $slug, $taxonomy );
            if ( $term instanceof WP_Term ) {
                // Ingredient terms have no pages; a link would point at a 404.
                $term_link = is_taxonomy_viewable( $taxonomy ) ? get_term_link( $term ) : '';
                $tags[] = array(
                    'name'     => $term->name,
                    'url'      => is_wp_error( $term_link ) ? '' : $term_link,
                    'taxonomy' => $taxonomy,
                );
            }
        }
    }

    return $tags;
}

/**
 * Filter the Timber context for recipe archive pages.
 *
 * WHY inject taxonomy term hero data here:
 *
 * Archive pages can be either post type archives (e.g. /recipes/) or taxonomy
 * archives (e.g. /typ-dania/sniadanie/). For taxonomy archives, we fetch custom
 * hero settings (icon, title suffix, claim, hero image, blog posts) from ACF
 * term meta fields and inject them into the context so Timber templates can
 * render a customized hero area per term without querying ACF directly.
 *
 * @param array<string,mixed> $context
 * @param array<string,mixed> $meta
 * @return array<string,mixed>
 */
function g4t_filter_archive_context( array $context, array $meta = array(), Go4Taste_Recipes_Plugin $plugin ): array {
    $strategy = g4t_get_source_strategy();
    $queried_object = get_queried_object();
    $archive_description = '';

    if ( $queried_object instanceof WP_Term ) {
        $archive_description = trim( (string) $queried_object->description );
    }

    $context['recipes']                              = g4t_base_context_meta( $strategy );
    $context['recipes']['scope']                     = 'archive';
    $context['recipes']['use_archive_recipe_template'] = g4t_should_use_archive_recipe_template( $strategy );
    $context['recipe_archive']                       = array(
        'title'       => isset( $context['title'] ) ? (string) $context['title'] : '',
        'description' => $archive_description,
        'post_type'   => $strategy['post_type'],
    );

    if ( is_post_type_archive( $strategy['post_type'] ) ) {
        $context['is_recipe_archive'] = true;
    }

    // Inject taxonomy term hero data for all term archives (custom tax, category, tag).
    if ( is_tax() || is_category() || is_tag() ) {
        if ( $queried_object instanceof WP_Term ) {
            $context['recipe_archive']['hero'] = g4t_get_taxonomy_term_hero_data( $queried_object );
        }
    }

    $context['recipe_archive']['active_filter_tags'] = g4t_get_active_filter_tags();

    return $context;
}

/**
 * Filter the Timber context for the recipe home/listing page.
 *
 * @param array<string,mixed> $context
 * @param array<string,mixed> $meta
 * @return array<string,mixed>
 */
function g4t_filter_home_context( array $context, array $meta = array(), Go4Taste_Recipes_Plugin $plugin ): array {
    $strategy = g4t_get_source_strategy();

    $context['recipes']          = g4t_base_context_meta( $strategy );
    $context['recipes']['scope'] = 'home';
    $context['is_recipe_home']   = true;
    $context['recipe_home']      = array(
        'post_type'             => $strategy['post_type'],
        'active_filter_tags'    => g4t_get_active_filter_tags(),
        'latest_recipe_url'     => g4t_get_latest_recipe_url( $strategy['post_type'] ),
    );

    return $context;
}

/**
 * Permalink of the newest published recipe, independent of listing page and filters.
 *
 * @param string $post_type Source post type.
 * @return string Absolute URL, or "" when there are no published recipes.
 */
function g4t_get_latest_recipe_url( string $post_type ): string {
    $ids = get_posts( array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        // ID breaks ties between posts imported with the same post_date.
        'orderby'        => array( 'date' => 'DESC', 'ID' => 'DESC' ),
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    if ( empty( $ids ) ) {
        return '';
    }

    $url = get_permalink( (int) $ids[0] );

    return $url ? (string) $url : '';
}

/**
 * Filter whether to use the archive recipe template.
 *
 * @param bool  $use_template
 * @param mixed $queried_object
 */
function g4t_filter_use_archive_template( bool $use_template, $queried_object = null, Go4Taste_Recipes_Plugin $plugin ): bool {
    $strategy = g4t_get_source_strategy();

    return g4t_should_use_archive_recipe_template( $strategy );
}

/**
 * Filter the WP_Query args used for the recipe home listing.
 *
 * @param array<string,mixed> $query_args
 * @return array<string,mixed>
 */
function g4t_filter_home_query_args( array $query_args = array(), Go4Taste_Recipes_Plugin $plugin ): array {
    $strategy = g4t_get_source_strategy();

    $defaults = array(
        'post_type'      => $strategy['post_type'],
        'post_status'    => 'publish',
        'paged'          => max( 1, get_query_var( 'paged' ) ),
        'posts_per_page' => (int) get_option( 'posts_per_page' ),
    );

    return wp_parse_args( $query_args, $defaults );
}

/**
 * Filter to expose the active recipe source post type.
 *
 * @param string $post_type
 * @return string
 */
function g4t_filter_source_post_type( string $post_type = 'post', Go4Taste_Recipes_Plugin $plugin ): string {
    $strategy = g4t_get_source_strategy();

    return isset( $strategy['post_type'] ) ? (string) $strategy['post_type'] : $post_type;
}

/**
 * Build the base recipes context meta array shared across all scopes.
 *
 * @param array{mode:string,post_type:string,fallback_used:bool} $strategy
 * @return array<string,mixed>
 */
function g4t_base_context_meta( array $strategy ): array {
    return array(
        'enabled'             => true,
        'source_mode'         => $strategy['mode'],
        'source_post_type'    => $strategy['post_type'],
        'fallback_used'       => $strategy['fallback_used'],
        'acf_available'       => function_exists( 'get_field' ),
        'wpgraphql_available' => defined( 'GRAPHQL_REQUEST' ) || class_exists( 'WPGraphQL\\Router' ),
        'facetwp_available'   => function_exists( 'FWP' ) || class_exists( 'FacetWP' ),
        'facetwp_prefix'      => 'g4t_',
    );
}

/**
 * Determine whether the archive recipe template should be used for the current request.
 *
 * @param array{mode:string,post_type:string,fallback_used:bool} $strategy
 */
function g4t_should_use_archive_recipe_template( array $strategy ): bool {
    if ( is_post_type_archive( $strategy['post_type'] ) ) {
        return true;
    }

    if ( is_tax() ) {
        $queried_object = get_queried_object();
        if ( $queried_object instanceof WP_Term ) {
            $taxonomy = get_taxonomy( $queried_object->taxonomy );
            if ( $taxonomy && is_array( $taxonomy->object_type ) ) {
                return in_array( $strategy['post_type'], $taxonomy->object_type, true );
            }
        }
    }

    $recipe_taxonomies = array(
        G4T_MEAL_TYPE_TAXONOMY,
        G4T_PREP_TIME_TAXONOMY,
        G4T_FEATURE_TAXONOMY,
        G4T_INGREDIENT_TAXONOMY,
    );

    if ( Go4Taste_Recipes_Plugin::MODE_POST === $strategy['mode'] && ( is_category() || is_tag() || is_date() || is_tax( $recipe_taxonomies ) ) ) {
        return true;
    }

    return false;
}

/**
 * Return true if recipe debug mode is enabled for the current request.
 */
function g4t_is_recipe_debug_enabled(): bool {
    if ( ! isset( $_GET['g4t_recipe_debug'] ) ) {
        return false;
    }

    if ( ! function_exists( 'current_user_can' ) ) {
        return false;
    }

    return current_user_can( 'manage_options' );
}

/**
 * Collect a flat list of block debug info for the given post.
 *
 * @param WP_Post $post
 * @return array<int,array<string,mixed>>
 */
function g4t_collect_blocks_debug( WP_Post $post ): array {
    if ( ! function_exists( 'parse_blocks' ) ) {
        return array();
    }

    $blocks = parse_blocks( (string) $post->post_content );
    $blocks = g4t_flatten_debug_blocks( $blocks );

    $debug_blocks = array();
    foreach ( $blocks as $index => $block ) {
        if ( ! is_array( $block ) ) {
            continue;
        }

        $debug_blocks[] = array(
            'index'       => $index,
            'blockName'   => isset( $block['blockName'] ) ? (string) $block['blockName'] : '',
            'attrs'       => isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array(),
            'inner_count' => isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? count( $block['innerBlocks'] ) : 0,
        );
    }

    return $debug_blocks;
}

/**
 * Collect block-data source debug info for the given post.
 *
 * @param WP_Post                $post
 * @param Go4Taste_Recipes_Plugin $plugin
 * @return array<string,mixed>
 */
function g4t_collect_recipe_source_debug( WP_Post $post, Go4Taste_Recipes_Plugin $plugin ): array {
    $post_content = (string) $post->post_content;

    return array(
        'data_source'                         => 'meta_only',
        'has_recipe_blocks'                   => false,
        'current_content_recipe_marker_count' => substr_count( $post_content, '<!-- wp:go4taste-recipes/' ),
        'recipe_blocks_present'               => array(),
    );
}

/**
 * Collect revision debug info for the given post.
 *
 * @param WP_Post $post
 * @return array<string,mixed>
 */
function g4t_collect_recipe_revision_debug( WP_Post $post ): array {
    if ( ! function_exists( 'wp_get_post_revisions' ) ) {
        return array( 'available' => false );
    }

    $revisions = wp_get_post_revisions(
        (int) $post->ID,
        array(
            'posts_per_page' => 1,
            'order'          => 'DESC',
            'orderby'        => 'date',
        )
    );

    if ( empty( $revisions ) ) {
        return array(
            'available'        => true,
            'has_latest'       => false,
            'current_modified' => (string) $post->post_modified_gmt,
        );
    }

    $latest = array_values( $revisions )[0];
    if ( ! ( $latest instanceof WP_Post ) ) {
        return array(
            'available'  => true,
            'has_latest' => false,
        );
    }

    $analysis = g4t_analyze_block_content( (string) $latest->post_content );

    return array(
        'available'                => true,
        'has_latest'               => true,
        'current_modified_gmt'     => (string) $post->post_modified_gmt,
        'latest_revision_id'       => (int) $latest->ID,
        'latest_revision_date_gmt' => (string) $latest->post_date_gmt,
        'latest_revision_author'   => (int) $latest->post_author,
        'latest_revision_analysis' => $analysis,
    );
}

/**
 * Collect autosave debug info for the given post.
 *
 * @param WP_Post $post
 * @return array<string,mixed>
 */
function g4t_collect_recipe_autosave_debug( WP_Post $post ): array {
    if ( ! function_exists( 'wp_get_post_autosave' ) || ! function_exists( 'get_current_user_id' ) ) {
        return array( 'available' => false );
    }

    $autosave = wp_get_post_autosave( (int) $post->ID, get_current_user_id() );
    if ( ! ( $autosave instanceof WP_Post ) ) {
        return array(
            'available'    => true,
            'has_autosave' => false,
        );
    }

    return array(
        'available'         => true,
        'has_autosave'      => true,
        'autosave_id'       => (int) $autosave->ID,
        'autosave_date_gmt' => (string) $autosave->post_date_gmt,
        'autosave_author'   => (int) $autosave->post_author,
        'autosave_analysis' => g4t_analyze_block_content( (string) $autosave->post_content ),
    );
}

/**
 * Analyse raw block editor content and return summary statistics.
 *
 * @return array<string,mixed>
 */
function g4t_analyze_block_content( string $content ): array {
    $recipe_blocks    = array();
    $has_named_blocks = false;

    if ( function_exists( 'parse_blocks' ) ) {
        $blocks = g4t_flatten_debug_blocks( parse_blocks( $content ) );
        foreach ( $blocks as $block ) {
            if ( ! is_array( $block ) || empty( $block['blockName'] ) ) {
                continue;
            }

            $has_named_blocks = true;
            $block_name       = (string) $block['blockName'];
            if ( 0 === strpos( $block_name, 'go4taste-recipes/' ) ) {
                $recipe_blocks[] = $block_name;
            }
        }
    }

    return array(
        'content_length'      => strlen( $content ),
        'has_named_blocks'    => $has_named_blocks,
        'has_recipe_blocks'   => ! empty( $recipe_blocks ),
        'recipe_block_names'  => array_values( array_unique( $recipe_blocks ) ),
        'recipe_marker_count' => substr_count( $content, '<!-- wp:go4taste-recipes/' ),
    );
}

/**
 * Flatten parsed blocks for debug-only inspection.
 *
 * @param array<int,mixed> $blocks
 * @return array<int,mixed>
 */
function g4t_flatten_debug_blocks( array $blocks ): array {
    $flat = array();

    foreach ( $blocks as $block ) {
        if ( ! is_array( $block ) ) {
            continue;
        }

        $flat[] = $block;

        if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) ) {
            $flat = array_merge( $flat, g4t_flatten_debug_blocks( $block['innerBlocks'] ) );
        }
    }

    return $flat;
}

/**
 * Get taxonomy term hero data from ACF term meta fields.
 *
 * WHY a dedicated helper instead of querying ACF in templates:
 *
 * This function centralizes all term meta queries and applies normalization
 * (null coalescing, type casting, safe defaults) in one place. Templates receive
 * a guaranteed-shape array and don't need to check if ACF exists or if fields
 * are set. If ACF is not active, the function returns safe null values.
 *
 * WHY 'term_' . $term->term_id format:
 *
 * ACF's get_field() requires a taxonomy qualifier when fetching term meta.
 * The canonical format is 'term_' . $term_id (e.g. 'term_123') to tell ACF
 * we're querying a term, not a post or option.
 *
 * WHY description comes from $term->description:
 *
 * WordPress taxonomies have a built-in description field accessible via
 * $term->description. There is no need for a duplicate ACF field. This
 * ensures consistency with WordPress core taxonomy UI and reduces redundancy.
 *
 * @param WP_Term $term The queried taxonomy term.
 * @return array<string,mixed> Hero data with keys: icon, title_suffix, description, hero_image, short_claim, blog_posts.
 */
function g4t_get_taxonomy_term_hero_data( WP_Term $term ): array {
    $term_identifier = 'term_' . $term->term_id;

    // Safe default if ACF not available
    if ( ! function_exists( 'get_field' ) ) {
        return array(
            'icon'         => null,
            'title_suffix' => '',
            'description'  => ! empty( $term->description ) ? $term->description : '',
            'hero_image'   => null,
            'short_claim'  => '',
            'blog_posts'   => array(),
        );
    }

    $icon_field        = get_field( 'hero_icon', $term_identifier );
    $hero_image_field  = get_field( 'hero_image', $term_identifier );

    return array(
        'icon'         => is_array( $icon_field ) ? $icon_field : null,
        'title_suffix' => (string) ( get_field( 'title_suffix', $term_identifier ) ?: '' ),
        'description'  => ! empty( $term->description ) ? (string) $term->description : '',
        'hero_image'   => is_array( $hero_image_field ) ? $hero_image_field : null,
        'short_claim'  => (string) ( get_field( 'short_claim', $term_identifier ) ?: '' ),
        'blog_posts'   => g4t_normalize_term_blog_posts( (array) get_field( 'blog_posts', $term_identifier ) ),
    );
}

/**
 * Normalize blog posts repeater data from term meta.
 *
 * WHY separate normalizer:
 *
 * ACF repeater fields return arrays but the shape is not guaranteed. Empty
 * repeaters may return false, single-row repeaters may have unexpected keys.
 * This normalizer ensures we always return a flat array of {title, url} objects
 * with sanitized values, regardless of what ACF returns.
 *
 * @param array<int,mixed> $raw_posts Raw ACF repeater value.
 * @return array<int,array{title:string,url:string}> Normalized blog posts.
 */
function g4t_normalize_term_blog_posts( array $raw_posts ): array {
    $normalized = array();

    foreach ( $raw_posts as $item ) {
        if ( ! is_array( $item ) ) {
            continue;
        }

        $title = isset( $item['title'] ) ? trim( (string) $item['title'] ) : '';
        $url   = isset( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '';

        // Skip entries with empty title or invalid URL
        if ( empty( $title ) || empty( $url ) ) {
            continue;
        }

        $normalized[] = array(
            'title' => $title,
            'url'   => $url,
        );
    }

    return $normalized;
}
