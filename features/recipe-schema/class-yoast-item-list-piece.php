<?php
/**
 * GO4TASTE Recipes Plugin — Yoast SEO schema graph piece for ItemList
 *
 * Loaded only from g4t_recipe_schema_add_yoast_piece(), i.e. when Yoast SEO
 * is active and its Abstract_Schema_Piece class exists.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Adds the recipe listing `ItemList` node to Yoast's `@graph`, linked to Yoast's CollectionPage.
 */
class G4T_Yoast_Item_List_Piece extends Abstract_Schema_Piece {

    /**
     * Identifier used by Yoast filters (`wpseo_schema_needs_g4t_item_list`, `wpseo_schema_g4t_item_list`).
     *
     * @var string
     */
    public $identifier = 'g4t_item_list';

    /**
     * @return bool
     */
    public function is_needed() {
        return null !== g4t_recipe_schema_get_item_list_node();
    }

    /**
     * @return array<string,mixed>|false
     */
    public function generate() {
        $node = g4t_recipe_schema_get_item_list_node();
        if ( null === $node ) {
            return false;
        }

        // Yoast's canonical is the page's own URL (paged URLs included), shared with its WebPage node.
        if ( ! empty( $this->context->canonical ) ) {
            $node['@id'] = $this->context->canonical . '#itemlist';
        }

        if ( ! empty( $this->context->main_schema_id ) ) {
            $node['mainEntityOfPage'] = array( '@id' => $this->context->main_schema_id );
        }

        g4t_recipe_schema_mark_item_list_printed();

        return $node;
    }
}
