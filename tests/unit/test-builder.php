<?php
/**
 * Unit tests for view-model/builder.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Builder extends WP_UnitTestCase {

    private $plugin;
    private $post_id;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Go4Taste_Recipes_Plugin();
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        // Create test post
        $this->post_id = $this->factory->post->create( array(
            'post_title' => 'Test Recipe',
            'post_type'  => 'post',
        ) );
    }

    /**
     * Test view model builder returns correct structure.
     */
    public function test_build_single_recipe_view_model_structure() {
        $post = get_post( $this->post_id );
        $view_model = g4t_build_single_recipe_view_model( $post, $this->plugin );

        // Top-level keys
        $this->assertArrayHasKey( 'id', $view_model );
        $this->assertArrayHasKey( 'hero', $view_model );
        $this->assertArrayHasKey( 'times', $view_model );
        $this->assertArrayHasKey( 'difficulty', $view_model );
        $this->assertArrayHasKey( 'servings', $view_model );
        $this->assertArrayHasKey( 'date', $view_model );
        $this->assertArrayHasKey( 'ingredients', $view_model );
        $this->assertArrayHasKey( 'steps', $view_model );
        $this->assertArrayHasKey( 'tips', $view_model );
        $this->assertArrayHasKey( 'source', $view_model );
        $this->assertArrayHasKey( 'ads', $view_model );

        // Nested structures
        $this->assertArrayHasKey( 'title', $view_model['hero'] );
        $this->assertArrayHasKey( 'excerpt', $view_model['hero'] );
        $this->assertArrayHasKey( 'image', $view_model['hero'] );

        $this->assertArrayHasKey( 'prep', $view_model['times'] );
        $this->assertArrayHasKey( 'cook', $view_model['times'] );
        $this->assertArrayHasKey( 'total', $view_model['times'] );

        $this->assertArrayHasKey( 'value', $view_model['servings'] );
        $this->assertArrayHasKey( 'unit', $view_model['servings'] );
    }

    /**
     * Test merge recipe data prefers primary over fallback.
     */
    public function test_merge_recipe_data_prefers_primary() {
        $primary = array(
            'lead'       => 'Primary lead',
            'difficulty' => 'easy',
            'tips'       => '',
        );

        $fallback = array(
            'lead'       => 'Fallback lead',
            'difficulty' => 'hard',
            'tips'       => 'Fallback tips',
        );

        $result = g4t_merge_recipe_data( $primary, $fallback );

        $this->assertEquals( 'Primary lead', $result['lead'] );
        $this->assertEquals( 'easy', $result['difficulty'] );
        $this->assertEquals( 'Fallback tips', $result['tips'] ); // Empty string uses fallback
    }

    /**
     * Test merge recipe data uses fallback for empty arrays.
     */
    public function test_merge_recipe_data_empty_arrays() {
        $primary = array(
            'ingredients' => array(),
            'steps'       => array(),
        );

        $fallback = array(
            'ingredients' => array( array( 'name' => 'Mąka' ) ),
            'steps'       => array( array( 'description' => 'Piecz' ) ),
        );

        $result = g4t_merge_recipe_data( $primary, $fallback );

        $this->assertCount( 1, $result['ingredients'] );
        $this->assertCount( 1, $result['steps'] );
    }

    /**
     * Test g4t_acf_field fallback to post meta.
     */
    public function test_acf_field_fallback() {
        update_post_meta( $this->post_id, 'recipe_difficulty', 'medium' );

        $result = g4t_acf_field( 'recipe_difficulty', $this->post_id, 'default' );

        // Should return post meta value
        $this->assertEquals( 'medium', $result );
    }

    /**
     * Test g4t_acf_field returns default when no data.
     */
    public function test_acf_field_returns_default() {
        $result = g4t_acf_field( 'nonexistent_field', $this->post_id, 'my_default' );

        $this->assertEquals( 'my_default', $result );
    }

    /**
     * Test ACF data extraction.
     */
    public function test_get_recipe_acf_data() {
        update_post_meta( $this->post_id, 'recipe_lead', 'Test excerpt' );
        update_post_meta( $this->post_id, 'recipe_difficulty', 'easy' );
        update_post_meta( $this->post_id, 'recipe_servings_value', 4 );
        update_post_meta( $this->post_id, 'recipe_servings_unit', 'porcje' );

        $result = g4t_get_recipe_acf_data( $this->post_id );

        $this->assertEquals( 'Test excerpt', $result['lead'] );
        $this->assertEquals( 'easy', $result['difficulty'] );
        $this->assertEquals( 4, $result['servings_value'] );
        $this->assertEquals( 'porcje', $result['servings_unit'] );
    }
}
