<?php
/**
 * GO4TASTE Recipes Plugin — Yoast SEO schema graph piece for Recipe
 *
 * Loaded only from g4t_recipe_schema_add_yoast_piece(), i.e. when Yoast SEO
 * is active and its Abstract_Schema_Piece class exists.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Adds the `Recipe` node to Yoast's `@graph`, linked to Yoast's WebPage and Organization.
 */
class G4T_Yoast_Recipe_Piece extends Abstract_Schema_Piece {

    /**
     * Identifier used by Yoast filters (`wpseo_schema_needs_g4t_recipe`, `wpseo_schema_g4t_recipe`).
     *
     * @var string
     */
    public $identifier = 'g4t_recipe';

    /**
     * @return bool
     */
    public function is_needed() {
        return null !== g4t_recipe_schema_get_node();
    }

    /**
     * @return array<string,mixed>|false
     */
    public function generate() {
        $node = g4t_recipe_schema_get_node();
        if ( null === $node ) {
            return false;
        }

        if ( ! empty( $this->context->main_schema_id ) ) {
            $node['mainEntityOfPage'] = array( '@id' => $this->context->main_schema_id );
        }

        // Reuse Yoast's Organization node when it is part of the graph; keep the inline author otherwise.
        if ( 'company' === $this->context->site_represents ) {
            $node['author'] = array( '@id' => $this->context->site_url . Schema_IDs::ORGANIZATION_HASH );
        }

        g4t_recipe_schema_mark_printed();

        return $node;
    }
}
