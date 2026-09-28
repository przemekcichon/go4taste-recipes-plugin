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
        $this->assertArrayHasKey( 'ingredient_sections', $view_model );
        $this->assertArrayHasKey( 'ingredients_photo_url', $view_model );
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

        // V3: no mode stored -> single mode -> exactly one unnamed section.
        $this->assertArrayNotHasKey( 'ingredients', $view_model );
        $this->assertCount( 1, $view_model['ingredient_sections'] );
        $this->assertNull( $view_model['ingredient_sections'][0]['title'] );
        $this->assertSame( array(), $view_model['ingredient_sections'][0]['items'] );
    }

    /**
     * Test ACF/meta data loader returns repeater rows from post meta.
     */
    public function test_get_recipe_acf_data_loads_repeaters_from_meta() {
        update_post_meta( $this->post_id, 'recipe_ingredients', 1 );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_name', 'Maka' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_amount', '250' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_unit', 'g' );

        update_post_meta( $this->post_id, 'recipe_steps', 1 );
        update_post_meta( $this->post_id, 'recipe_steps_0_step_header', 'Krok 1' );
        update_post_meta( $this->post_id, 'recipe_steps_0_step_description', 'Wymieszaj skladniki.' );
        update_post_meta( $this->post_id, 'recipe_steps_0_step_image_url', 'https://example.com/step.jpg' );

        $result = g4t_get_recipe_acf_data( $this->post_id );

        $this->assertCount( 1, $result['ingredient_sections'] );
        $this->assertNull( $result['ingredient_sections'][0]['title'] );
        $this->assertCount( 1, $result['ingredient_sections'][0]['items'] );
        $this->assertEquals( 'Maka', $result['ingredient_sections'][0]['items'][0]['name'] );
        $this->assertCount( 1, $result['steps'] );
        $this->assertEquals( 'Krok 1', $result['steps'][0]['heading'] );
        $this->assertEquals( 'https://example.com/step.jpg', $result['steps'][0]['imageUrl'] );
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
