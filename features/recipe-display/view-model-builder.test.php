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
     * Test the view model matches the full shape in SCHEMA_CONTRACT_V3.md:
     * exact key sets and value types for a populated recipe.
     */
    public function test_build_single_recipe_view_model_matches_contract_v3() {
        update_post_meta( $this->post_id, 'recipe_lead', 'Lead przepisu' );
        update_post_meta( $this->post_id, 'recipe_time_prep', '15' );
        update_post_meta( $this->post_id, 'recipe_time_cook', '30 min' );
        update_post_meta( $this->post_id, 'recipe_time_total', '45' );
        update_post_meta( $this->post_id, 'recipe_time_soak', '' );
        update_post_meta( $this->post_id, 'recipe_difficulty', 'łatwy' );
        update_post_meta( $this->post_id, 'recipe_servings_value', '4' );
        update_post_meta( $this->post_id, 'recipe_servings_unit', 'porcje' );
        update_post_meta( $this->post_id, 'recipe_ingredients_photo_url', 'https://example.com/ingredients.jpg' );
        update_post_meta( $this->post_id, 'recipe_ingredients', 1 );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_name', 'Mąka' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_amount', '1/3' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_unit', 'szkl.' );
        update_post_meta( $this->post_id, 'recipe_steps', 2 );
        update_post_meta( $this->post_id, 'recipe_steps_0_step_description', 'Wymieszaj.' );
        update_post_meta( $this->post_id, 'recipe_steps_1_step_header', 'Pieczenie' );
        update_post_meta( $this->post_id, 'recipe_steps_1_step_description', 'Piecz 30 minut.' );
        update_post_meta( $this->post_id, 'recipe_steps_1_step_tip', 'Nie otwieraj piekarnika.' );
        update_post_meta( $this->post_id, 'recipe_tips', 'Podawaj na ciepło.' );
        update_post_meta( $this->post_id, 'recipe_source_label', 'Babcia' );
        update_post_meta( $this->post_id, 'recipe_source_url', 'https://example.com/zrodlo' );

        $vm = g4t_build_single_recipe_view_model( get_post( $this->post_id ), $this->plugin );

        $this->assertSame(
            array( 'id', 'hero', 'times', 'difficulty', 'difficulty_key', 'servings', 'date', 'ingredient_sections', 'ingredients_photo_url', 'steps', 'tips', 'source', 'ads' ),
            array_keys( $vm )
        );

        $this->assertSame( $this->post_id, $vm['id'] );

        $this->assertSame(
            array(
                'title'        => 'Test Recipe',
                'excerpt'      => 'Lead przepisu',
                'image'        => '',
                'image_id'     => 0,
                'image_width'  => 0,
                'image_height' => 0,
                'image_srcset' => '',
            ),
            $vm['hero']
        );

        $this->assertSame(
            array( 'prep' => '15', 'cook' => '30 min', 'total' => '45', 'soak' => '' ),
            $vm['times']
        );

        $this->assertSame( 'Łatwy', $vm['difficulty'] );
        $this->assertSame( 'easy', $vm['difficulty_key'] );

        $this->assertSame( array( 'value' => 4.0, 'unit' => 'porcje' ), $vm['servings'] );

        $this->assertSame( array( 'label', 'value' ), array_keys( $vm['date'] ) );
        $this->assertSame( 'Data dodania', $vm['date']['label'] );
        $this->assertIsString( $vm['date']['value'] );
        $this->assertNotSame( '', $vm['date']['value'] );

        $this->assertSame(
            array(
                array(
                    'title' => null,
                    'items' => array(
                        array(
                            'ingredient_term_id' => null,
                            'name'               => 'Mąka',
                            'amount'             => '1/3',
                            'unit'               => 'szkl.',
                            'ingredient_note'    => '',
                        ),
                    ),
                ),
            ),
            $vm['ingredient_sections']
        );
        $this->assertSame( 'https://example.com/ingredients.jpg', $vm['ingredients_photo_url'] );

        $this->assertSame(
            array(
                array(
                    'heading'     => 'Krok 1',
                    'description' => 'Wymieszaj.',
                    'tip'         => '',
                    'imageUrl'    => '',
                ),
                array(
                    'heading'     => 'Pieczenie',
                    'description' => 'Piecz 30 minut.',
                    'tip'         => 'Nie otwieraj piekarnika.',
                    'imageUrl'    => '',
                ),
            ),
            $vm['steps']
        );

        $this->assertSame( 'Podawaj na ciepło.', $vm['tips'] );
        $this->assertSame( array( 'label' => 'Babcia', 'url' => 'https://example.com/zrodlo' ), $vm['source'] );

        // go4taste-ads is not loaded in the test suite: no products, strip disabled.
        $this->assertSame(
            array( 'bottom_product_ids' => array(), 'products' => array(), 'enabled' => false ),
            $vm['ads']
        );
    }

    /**
     * Test empty values of an unpopulated recipe match SCHEMA_CONTRACT_V3.md.
     */
    public function test_build_single_recipe_view_model_empty_values() {
        $vm = g4t_build_single_recipe_view_model( get_post( $this->post_id ), $this->plugin );

        $this->assertSame( '', $vm['hero']['excerpt'] );
        $this->assertSame( '', $vm['hero']['image'] );
        $this->assertSame( 0, $vm['hero']['image_id'] );
        $this->assertSame( 0, $vm['hero']['image_width'] );
        $this->assertSame( 0, $vm['hero']['image_height'] );
        $this->assertSame( '', $vm['hero']['image_srcset'] );
        $this->assertSame( array( 'prep' => '', 'cook' => '', 'total' => '', 'soak' => '' ), $vm['times'] );
        $this->assertSame( '', $vm['difficulty'] );
        $this->assertSame( '', $vm['difficulty_key'] );
        $this->assertSame( array( 'value' => null, 'unit' => '' ), $vm['servings'] );
        $this->assertSame( '', $vm['ingredients_photo_url'] );
        $this->assertSame( array(), $vm['steps'] );
        $this->assertSame( '', $vm['tips'] );
        $this->assertSame( array( 'label' => '', 'url' => '' ), $vm['source'] );
    }

    /**
     * Test the hero image fields come from the featured image's 'full' size.
     */
    public function test_build_single_recipe_view_model_hero_image_fields() {
        $attachment_id = $this->factory->attachment->create_object(
            array(
                'file'           => '2026/05/hero.jpg',
                'post_mime_type' => 'image/jpeg',
            ),
            $this->post_id
        );
        wp_update_attachment_metadata(
            $attachment_id,
            array(
                'width'  => 600,
                'height' => 450,
                'file'   => '2026/05/hero.jpg',
                'sizes'  => array(
                    'medium'         => array( 'file' => 'hero-300x225.jpg', 'width' => 300, 'height' => 225, 'mime-type' => 'image/jpeg' ),
                    'g4t-recipe-400' => array( 'file' => 'hero-400x300.jpg', 'width' => 400, 'height' => 300, 'mime-type' => 'image/jpeg' ),
                ),
            )
        );
        set_post_thumbnail( $this->post_id, $attachment_id );

        $hero = g4t_build_single_recipe_view_model( get_post( $this->post_id ), $this->plugin )['hero'];

        $this->assertStringEndsWith( '/2026/05/hero.jpg', $hero['image'] );
        $this->assertSame( $attachment_id, $hero['image_id'] );
        $this->assertSame( 600, $hero['image_width'] );
        $this->assertSame( 450, $hero['image_height'] );
        $this->assertStringContainsString( 'hero-300x225.jpg 300w', $hero['image_srcset'] );
        $this->assertStringContainsString( 'hero-400x300.jpg 400w', $hero['image_srcset'] );
        $this->assertStringContainsString( 'hero.jpg 600w', $hero['image_srcset'] );
    }

    /**
     * Test the date switches to the modified date when the post was updated later.
     */
    public function test_build_single_recipe_view_model_date_uses_modified_date() {
        global $wpdb;

        $wpdb->update(
            $wpdb->posts,
            array(
                'post_date'         => '2026-01-10 10:00:00',
                'post_date_gmt'     => '2026-01-10 10:00:00',
                'post_modified'     => '2026-02-20 10:00:00',
                'post_modified_gmt' => '2026-02-20 10:00:00',
            ),
            array( 'ID' => $this->post_id )
        );
        clean_post_cache( $this->post_id );

        $vm = g4t_build_single_recipe_view_model( get_post( $this->post_id ), $this->plugin );

        $this->assertSame( 'Data aktualizacji', $vm['date']['label'] );
        $this->assertSame( '20 February 2026', $vm['date']['value'] );
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
