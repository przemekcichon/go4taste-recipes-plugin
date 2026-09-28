<?php
/**
 * Integration tests for recipe-schema/listing-context.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Recipe_Schema_Listing_Context extends WP_UnitTestCase {

    public function tear_down() {
        $_GET = array();
        g4t_recipe_schema_listing_state(
            array(
                'post_ids' => null,
                'node'     => null,
                'resolved' => false,
                'printed'  => false,
            )
        );
        parent::tear_down();
    }

    /**
     * @param array<int,WP_Post> $posts
     * @return array<string,mixed>
     */
    private function home_context( array $posts ): array {
        return array(
            'is_recipe_home' => true,
            'recipes'        => array( 'facetwp_prefix' => 'g4t_' ),
            'posts'          => $posts,
        );
    }

    public function test_home_listing_becomes_item_list_of_published_recipes() {
        $first    = self::factory()->post->create_and_get();
        $draft    = self::factory()->post->create_and_get( array( 'post_status' => 'draft' ) );
        $page     = self::factory()->post->create_and_get( array( 'post_type' => 'page' ) );
        $locked   = self::factory()->post->create_and_get( array( 'post_password' => 'x' ) );
        $second   = self::factory()->post->create_and_get();
        $context  = $this->home_context( array( $first, $draft, $page, $locked, $second ) );

        $this->assertSame( $context, g4t_recipe_schema_capture_listing_context( $context ) );

        $node = g4t_recipe_schema_get_item_list_node();

        $this->assertSame( 'ItemList', $node['@type'] );
        $this->assertSame(
            array( get_permalink( $first ), get_permalink( $second ) ),
            array_column( $node['itemListElement'], 'url' )
        );
        $this->assertSame( array( 1, 2 ), array_column( $node['itemListElement'], 'position' ) );
    }

    public function test_filtered_listing_gets_no_item_list() {
        $_GET = array( 'g4t_meal_type' => 'zupy' );

        g4t_recipe_schema_capture_listing_context( $this->home_context( array( self::factory()->post->create_and_get() ) ) );

        $this->assertNull( g4t_recipe_schema_get_item_list_node() );
    }

    public function test_non_recipe_listing_is_ignored() {
        $context = array(
            'recipes' => array( 'use_archive_recipe_template' => false, 'facetwp_prefix' => 'g4t_' ),
            'posts'   => array( self::factory()->post->create_and_get() ),
        );

        g4t_recipe_schema_capture_listing_context( $context );

        $this->assertNull( g4t_recipe_schema_get_item_list_node() );
    }

    public function test_recipe_archive_is_captured() {
        $post    = self::factory()->post->create_and_get();
        $context = array(
            'recipes' => array( 'use_archive_recipe_template' => true, 'facetwp_prefix' => 'g4t_' ),
            'posts'   => new ArrayObject( array( $post ) ),
        );

        g4t_recipe_schema_capture_listing_context( $context );

        $node = g4t_recipe_schema_get_item_list_node();
        $this->assertSame( array( get_permalink( $post ) ), array_column( $node['itemListElement'], 'url' ) );
    }

    public function test_empty_listing_gets_no_item_list() {
        g4t_recipe_schema_capture_listing_context( $this->home_context( array() ) );

        $this->assertNull( g4t_recipe_schema_get_item_list_node() );
    }
}
