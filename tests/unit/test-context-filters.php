<?php
/**
 * Unit tests for timber/context-filters.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Context_Filters extends WP_UnitTestCase {

    private $plugin;
    private $post_id;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Go4Taste_Recipes_Plugin();
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        $this->post_id = $this->factory->post->create( array(
            'post_title' => 'Test Recipe',
            'post_type'  => 'post',
        ) );
    }

    /**
     * Test single context filter adds recipe data.
     */
    public function test_filter_single_context_adds_recipe() {
        global $post;
        $post = get_post( $this->post_id );
        setup_postdata( $post );

        $context = array();
        $meta = array();

        $result = g4t_filter_single_context( $context, $meta, $this->plugin );

        $this->assertArrayHasKey( 'recipe', $result );
        $this->assertIsArray( $result['recipe'] );
        $this->assertEquals( $this->post_id, $result['recipe']['id'] );

        wp_reset_postdata();
    }

    /**
     * Test use archive template filter for recipe taxonomies.
     */
    public function test_filter_use_archive_template_for_recipe_taxonomy() {
        g4t_register_ingredient_taxonomy( $this->plugin );

        $term = $this->factory->term->create( array(
            'taxonomy' => G4T_INGREDIENT_TAXONOMY,
            'name'     => 'Mąka',
        ) );

        $queried_object = get_term( $term, G4T_INGREDIENT_TAXONOMY );

        $result = g4t_filter_use_archive_template( false, $queried_object, $this->plugin );

        $this->assertTrue( $result );
    }

    /**
     * Test use archive template filter returns false for non-recipe taxonomy.
     */
    public function test_filter_use_archive_template_for_non_recipe_taxonomy() {
        $term = $this->factory->term->create( array(
            'taxonomy' => 'category',
            'name'     => 'General',
        ) );

        $queried_object = get_term( $term, 'category' );

        $result = g4t_filter_use_archive_template( false, $queried_object, $this->plugin );

        $this->assertFalse( $result );
    }

    /**
     * Test archive context filter structure.
     */
    public function test_filter_archive_context_structure() {
        $context = array();
        $meta = array();

        $result = g4t_filter_archive_context( $context, $meta, $this->plugin );

        $this->assertArrayHasKey( 'archive_title', $result );
        $this->assertArrayHasKey( 'archive_description', $result );
        $this->assertArrayHasKey( 'recipes', $result );
    }

    /**
     * Test home query args filter.
     */
    public function test_filter_home_query_args() {
        $query_args = array( 'posts_per_page' => 10 );

        $result = g4t_filter_home_query_args( $query_args, $this->plugin );

        $this->assertArrayHasKey( 'post_type', $result );
        $this->assertEquals( 'post', $result['post_type'] );
    }

    /**
     * Test source post type filter.
     */
    public function test_filter_source_post_type() {
        $result = g4t_filter_source_post_type( 'post', $this->plugin );

        $this->assertEquals( 'post', $result );
    }

    /**
     * Test source post type filter in recipes_cpt mode.
     */
    public function test_filter_source_post_type_recipes_mode() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'recipes_cpt' ) );
        $this->plugin->clear_options_cache();

        $result = g4t_filter_source_post_type( 'post', $this->plugin );

        $this->assertEquals( G4T_RECIPES_POST_TYPE, $result );
    }
}
