<?php
/**
 * GO4TASTE Recipes Plugin — Recipe schema builder
 *
 * Maps the single recipe view model (as exposed in the Timber context by the
 * recipe-display slice) to a schema.org `Recipe` node for JSON-LD output.
 *
 * WHY pure functions:
 *
 * Everything WordPress-specific (permalink, featured image size, dates,
 * taxonomy terms, author) is resolved by the caller and passed in as
 * `$post_data`. The mapping itself has no database calls or side effects,
 * so it can be tested in isolation and reused by both output paths
 * (standalone `<script>` and the Yoast schema graph piece).
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Parse a stored recipe time value into minutes.
 *
 * Handles the formats found in data: creator stores bare minutes ("30"),
 * migrated posts store "30 min". Hours ("1 h 20 min", "1,5 h", "2 godz.")
 * are supported for manual edits.
 *
 * @param string $raw Raw meta value.
 * @return int Minutes, or 0 when the value is empty or not recognized.
 */
function g4t_recipe_schema_parse_minutes( string $raw ): int {
    $value = strtolower( trim( $raw ) );
    if ( '' === $value ) {
        return 0;
    }

    if ( preg_match( '/^\d+$/', $value ) ) {
        return (int) $value;
    }

    $minutes = 0.0;
    $matched = false;

    if ( preg_match( '/(\d+(?:[.,]\d+)?)\s*(?:h|godz)/u', $value, $hours_match ) ) {
        $minutes += (float) str_replace( ',', '.', $hours_match[1] ) * 60;
        $matched  = true;
    }

    if ( preg_match( '/(\d+)\s*min/u', $value, $minutes_match ) ) {
        $minutes += (int) $minutes_match[1];
        $matched  = true;
    } elseif ( $matched && preg_match( '/(?:h|godz\w*)\.?\s*(\d+)\s*$/u', $value, $trailing_match ) ) {
        // "1h30", "1 h 30": a bare number after hours means minutes.
        $minutes += (int) $trailing_match[1];
    }

    return $matched ? (int) round( $minutes ) : 0;
}

/**
 * Format minutes as an ISO 8601 duration (e.g. PT30M, PT1H20M).
 *
 * @param int $minutes Minutes (> 0).
 * @return string Duration, or empty string when $minutes <= 0.
 */
function g4t_recipe_schema_format_duration( int $minutes ): string {
    if ( $minutes <= 0 ) {
        return '';
    }

    $hours     = intdiv( $minutes, 60 );
    $remainder = $minutes % 60;

    return 'PT' . ( $hours > 0 ? $hours . 'H' : '' ) . ( $remainder > 0 ? $remainder . 'M' : '' );
}

/**
 * Collapse markup and whitespace into a single plain-text line.
 */
function g4t_recipe_schema_plain_text( string $value ): string {
    // Line breaks and block ends become spaces, so "a.<br>b" does not glue into "a.b".
    $value = (string) preg_replace( '/<(?:br|\/p|\/li|\/div|\/h[1-6])\b[^>]*>/i', ' ', $value );
    $text  = html_entity_decode( strip_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

    return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * Flatten ingredient sections into `recipeIngredient` strings.
 *
 * Section titles and ingredient notes are skipped (Google: only the text
 * needed to make the recipe).
 *
 * @param array<int,mixed> $sections View model `ingredient_sections`.
 * @return array<int,string>
 */
function g4t_recipe_schema_ingredients( array $sections ): array {
    $ingredients = array();

    foreach ( $sections as $section ) {
        if ( ! is_array( $section ) || empty( $section['items'] ) || ! is_array( $section['items'] ) ) {
            continue;
        }

        foreach ( $section['items'] as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $line = g4t_recipe_schema_plain_text(
                implode(
                    ' ',
                    array(
                        (string) ( $item['amount'] ?? '' ),
                        (string) ( $item['unit'] ?? '' ),
                        (string) ( $item['name'] ?? '' ),
                    )
                )
            );

            if ( '' !== $line ) {
                $ingredients[] = $line;
            }
        }
    }

    return $ingredients;
}

/**
 * Map view model steps to `HowToStep` nodes.
 *
 * Auto-generated headings ("Krok 3", "Etap 2") carry no information and are
 * not emitted as `name`. Step tips are skipped.
 *
 * @param array<int,mixed> $steps View model `steps`.
 * @return array<int,array<string,string>>
 */
function g4t_recipe_schema_instructions( array $steps ): array {
    $instructions = array();

    foreach ( $steps as $step ) {
        if ( ! is_array( $step ) ) {
            continue;
        }

        $text = g4t_recipe_schema_plain_text( (string) ( $step['description'] ?? '' ) );
        if ( '' === $text ) {
            continue;
        }

        $node    = array( '@type' => 'HowToStep' );
        $heading = g4t_recipe_schema_plain_text( (string) ( $step['heading'] ?? '' ) );
        if ( '' !== $heading && ! preg_match( '/^(krok|etap)\s*\d+\.?$/iu', $heading ) ) {
            $node['name'] = $heading;
        }
        $node['text'] = $text;

        $image = trim( (string) ( $step['imageUrl'] ?? '' ) );
        if ( '' !== $image ) {
            $node['image'] = $image;
        }

        $instructions[] = $node;
    }

    return $instructions;
}

/**
 * Format servings as `recipeYield` text (e.g. "4 osoby", "1,5 l").
 *
 * @param mixed  $value Servings value (float|null in the view model).
 * @param string $unit  Free-text unit.
 */
function g4t_recipe_schema_yield( $value, string $unit ): string {
    if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
        return '';
    }

    $number = (float) $value;
    if ( $number <= 0 ) {
        return '';
    }

    $formatted = floor( $number ) === $number
        ? (string) (int) $number
        : str_replace( '.', ',', rtrim( rtrim( number_format( $number, 2, '.', '' ), '0' ), '.' ) );

    return trim( $formatted . ' ' . g4t_recipe_schema_plain_text( $unit ) );
}

/**
 * Map image candidates to `ImageObject` nodes, skipping entries without a URL and duplicate URLs.
 *
 * @param array<int,mixed> $images Items of array{url:string,width:int,height:int}.
 * @return array<int,array<string,mixed>>
 */
function g4t_recipe_schema_image_nodes( array $images ): array {
    $nodes = array();
    $seen  = array();

    foreach ( $images as $image ) {
        if ( ! is_array( $image ) || empty( $image['url'] ) ) {
            continue;
        }

        $url = (string) $image['url'];
        if ( isset( $seen[ $url ] ) ) {
            continue;
        }
        $seen[ $url ] = true;

        $node = array(
            '@type' => 'ImageObject',
            'url'   => $url,
        );
        if ( ! empty( $image['width'] ) && ! empty( $image['height'] ) ) {
            $node['width']  = (int) $image['width'];
            $node['height'] = (int) $image['height'];
        }

        $nodes[] = $node;
    }

    return $nodes;
}

/**
 * Build the schema.org `Recipe` node (without `@context`).
 *
 * @param array<string,mixed> $recipe    Single recipe view model.
 * @param array<string,mixed> $post_data Resolved post data: url (string), images (array<int,array{url:string,width:int,height:int}>),
 *                                       date_published, date_modified, language (string), categories (array<int,string>),
 *                                       author (array<string,mixed>).
 * @return array<string,mixed>
 */
function g4t_recipe_schema_build( array $recipe, array $post_data ): array {
    $url  = (string) ( $post_data['url'] ?? '' );
    $hero = isset( $recipe['hero'] ) && is_array( $recipe['hero'] ) ? $recipe['hero'] : array();

    $schema = array(
        '@type' => 'Recipe',
    );

    if ( '' !== $url ) {
        $schema['@id'] = $url . '#recipe';
    }

    $schema['name'] = g4t_recipe_schema_plain_text( (string) ( $hero['title'] ?? '' ) );

    $images = g4t_recipe_schema_image_nodes( (array) ( $post_data['images'] ?? array() ) );
    if ( ! empty( $images ) ) {
        // A single image stays an object, as before multiple aspect ratios existed.
        $schema['image'] = 1 === count( $images ) ? $images[0] : $images;
    }

    $description = g4t_recipe_schema_plain_text( (string) ( $hero['excerpt'] ?? '' ) );
    if ( '' !== $description ) {
        $schema['description'] = $description;
    }

    if ( ! empty( $post_data['author'] ) && is_array( $post_data['author'] ) ) {
        $schema['author'] = $post_data['author'];
    }

    foreach ( array( 'datePublished' => 'date_published', 'dateModified' => 'date_modified' ) as $property => $key ) {
        $date = (string) ( $post_data[ $key ] ?? '' );
        if ( '' !== $date ) {
            $schema[ $property ] = $date;
        }
    }

    $times = isset( $recipe['times'] ) && is_array( $recipe['times'] ) ? $recipe['times'] : array();
    $prep  = g4t_recipe_schema_parse_minutes( (string) ( $times['prep'] ?? '' ) );
    $cook  = g4t_recipe_schema_parse_minutes( (string) ( $times['cook'] ?? '' ) );
    $total = g4t_recipe_schema_parse_minutes( (string) ( $times['total'] ?? '' ) );

    // Google: prepTime and cookTime must be used together.
    if ( $prep > 0 && $cook > 0 ) {
        $schema['prepTime'] = g4t_recipe_schema_format_duration( $prep );
        $schema['cookTime'] = g4t_recipe_schema_format_duration( $cook );
        if ( $total <= 0 ) {
            $total = $prep + $cook;
        }
    }

    if ( $total > 0 ) {
        $schema['totalTime'] = g4t_recipe_schema_format_duration( $total );
    }

    $servings = isset( $recipe['servings'] ) && is_array( $recipe['servings'] ) ? $recipe['servings'] : array();
    $yield    = g4t_recipe_schema_yield( $servings['value'] ?? null, (string) ( $servings['unit'] ?? '' ) );
    if ( '' !== $yield ) {
        $schema['recipeYield'] = $yield;
    }

    $categories = array_values(
        array_filter(
            array_map( 'g4t_recipe_schema_plain_text', array_map( 'strval', (array) ( $post_data['categories'] ?? array() ) ) ),
            static fn( string $name ): bool => '' !== $name
        )
    );
    if ( ! empty( $categories ) ) {
        $schema['recipeCategory'] = implode( ', ', $categories );
    }

    $ingredients = g4t_recipe_schema_ingredients( isset( $recipe['ingredient_sections'] ) && is_array( $recipe['ingredient_sections'] ) ? $recipe['ingredient_sections'] : array() );
    if ( ! empty( $ingredients ) ) {
        $schema['recipeIngredient'] = $ingredients;
    }

    $instructions = g4t_recipe_schema_instructions( isset( $recipe['steps'] ) && is_array( $recipe['steps'] ) ? $recipe['steps'] : array() );
    if ( ! empty( $instructions ) ) {
        $schema['recipeInstructions'] = $instructions;
    }

    $language = (string) ( $post_data['language'] ?? '' );
    if ( '' !== $language ) {
        $schema['inLanguage'] = $language;
    }

    return $schema;
}

/**
 * Whether a built `Recipe` node is worth printing.
 *
 * Google requires `name` and `image`; without at least one ingredient or
 * step the node carries no recipe content.
 *
 * @param array<string,mixed> $schema Output of g4t_recipe_schema_build().
 */
function g4t_recipe_schema_is_printable( array $schema ): bool {
    if ( empty( $schema['name'] ) || empty( $schema['image'] ) ) {
        return false;
    }

    return ! empty( $schema['recipeIngredient'] ) || ! empty( $schema['recipeInstructions'] );
}

/**
 * Whether the listing request carries active FacetWP filters.
 *
 * Filter params are the facet names, i.e. the FacetWP prefix plus a key
 * (`g4t_meal_type`); FacetWP's own URL syntax adds a leading `_`. Empty
 * values do not filter. The debug switch shares the prefix and is ignored.
 *
 * @param array<string,mixed> $query  Request query params (`$_GET`).
 * @param string              $prefix Facet name prefix (context `recipes.facetwp_prefix`).
 */
function g4t_recipe_schema_listing_is_filtered( array $query, string $prefix ): bool {
    if ( '' === $prefix ) {
        return false;
    }

    foreach ( $query as $name => $value ) {
        $name = (string) $name;
        if ( 'g4t_recipe_debug' === $name || '' === $value || null === $value || array() === $value ) {
            continue;
        }

        if ( str_starts_with( $name, $prefix ) || str_starts_with( $name, '_' . $prefix ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Build the schema.org `ItemList` node (without `@context`) for a recipe listing page.
 *
 * Summary-page list (Google host carousel): every item only points to a
 * recipe URL that carries its own `Recipe` markup.
 *
 * @param array<int,mixed> $urls     Recipe permalinks in display order.
 * @param string           $page_url URL of the listing page (for `@id`).
 * @return array<string,mixed>
 */
function g4t_recipe_schema_build_item_list( array $urls, string $page_url ): array {
    $items = array();
    $seen  = array();

    foreach ( $urls as $url ) {
        $url = trim( (string) $url );
        if ( '' === $url || isset( $seen[ $url ] ) ) {
            continue;
        }
        $seen[ $url ] = true;

        $items[] = array(
            '@type'    => 'ListItem',
            'position' => count( $items ) + 1,
            'url'      => $url,
        );
    }

    $node = array( '@type' => 'ItemList' );
    if ( '' !== $page_url ) {
        $node['@id'] = $page_url . '#itemlist';
    }
    $node['numberOfItems']   = count( $items );
    $node['itemListElement'] = $items;

    return $node;
}
