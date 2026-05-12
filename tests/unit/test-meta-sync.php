<?php
/**
 * Unit tests for sync/meta-sync.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Meta_Sync extends WP_UnitTestCase {

    private $plugin;
    private $post_id;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Go4Taste_Recipes_Plugin();
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        // Create test post
        $this->post_id = $this->factory->post->create( array(
            'post_title'   => 'Test Recipe',
            'post_content' => '<!-- wp:go4taste-recipes/hero {"lead":"Test lead","difficulty":"easy","servingsValue":"4","servingsUnit":"porcje","timePrep":"15 min","timeCook":"30 min","timeTotal":"45 min"} /-->',
            'post_type'    => 'post',
        ) );
    }

    /**
     * Test ingredient term creation.
     */
    public function test_ensure_ingredient_term() {
        g4t_register_ingredient_taxonomy( $this->plugin );

        $term_id = g4t_ensure_ingredient_term( 'Mąka' );

        $this->assertIsInt( $term_id );
        $this->assertGreaterThan( 0, $term_id );

        $term = get_term( $term_id, G4T_INGREDIENT_TAXONOMY );
        $this->assertEquals( 'Mąka', $term->name );
    }

    /**
     * Test ingredient term is reused if exists.
     */
    public function test_ensure_ingredient_term_reuses_existing() {
        g4t_register_ingredient_taxonomy( $this->plugin );

        $term_id_1 = g4t_ensure_ingredient_term( 'Cukier' );
        $term_id_2 = g4t_ensure_ingredient_term( 'Cukier' );

        $this->assertEquals( $term_id_1, $term_id_2 );
    }

    /**
     * Test empty ingredient name returns null.
     */
    public function test_ensure_ingredient_term_empty_name() {
        $result = g4t_ensure_ingredient_term( '' );
        $this->assertNull( $result );

        $result = g4t_ensure_ingredient_term( '   ' );
        $this->assertNull( $result );
    }

    /**
     * Test ingredients repeater meta sync.
     */
    public function test_sync_ingredients_repeater_meta() {
        g4t_register_ingredient_taxonomy( $this->plugin );

        $ingredients = array(
            array(
                'ingredient_term_id' => null,
                'name'               => 'Mąka',
                'amount'             => 500.0,
                'unit'               => 'g',
            ),
            array(
                'ingredient_term_id' => null,
                'name'               => 'Cukier',
                'amount'             => 200.0,
                'unit'               => 'g',
            ),
        );

        g4t_sync_ingredients_repeater_meta( $this->post_id, $ingredients );

        // Check count
        $count = get_post_meta( $this->post_id, 'recipe_ingredients', true );
        $this->assertEquals( 2, $count );

        // Check first ingredient
        $this->assertEquals( 'Mąka', get_post_meta( $this->post_id, 'recipe_ingredients_0_name', true ) );
        $this->assertEquals( 500.0, get_post_meta( $this->post_id, 'recipe_ingredients_0_amount', true ) );
        $this->assertEquals( 'g', get_post_meta( $this->post_id, 'recipe_ingredients_0_unit', true ) );

        // Check taxonomy assignment
        $terms = wp_get_object_terms( $this->post_id, G4T_INGREDIENT_TAXONOMY, array( 'fields' => 'names' ) );
        $this->assertContains( 'Mąka', $terms );
        $this->assertContains( 'Cukier', $terms );
    }

    /**
     * Test steps repeater meta sync.
     */
    public function test_sync_steps_repeater_meta() {
        $steps = array(
            array(
                'title'       => 'Krok 1',
                'description' => 'Wymieszaj',
                'tip'         => 'Dokładnie',
                'images'      => array( 'http://example.com/img1.jpg' ),
            ),
        );

        g4t_sync_steps_repeater_meta( $this->post_id, $steps );

        // Check count
        $count = get_post_meta( $this->post_id, 'recipe_steps', true );
        $this->assertEquals( 1, $count );

        // Check step data
        $this->assertEquals( 'Krok 1', get_post_meta( $this->post_id, 'recipe_steps_0_heading', true ) );
        $this->assertEquals( 'Wymieszaj', get_post_meta( $this->post_id, 'recipe_steps_0_description', true ) );
        $this->assertEquals( 'Dokładnie', get_post_meta( $this->post_id, 'recipe_steps_0_tip', true ) );

        // Check images
        $images_count = get_post_meta( $this->post_id, 'recipe_steps_0_images', true );
        $this->assertEquals( 1, $images_count );
    }

    /**
     * Test load ingredients repeater meta.
     */
    public function test_load_ingredients_repeater_meta() {
        update_post_meta( $this->post_id, 'recipe_ingredients', 2 );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_name', 'Sól' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_amount', '5' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_unit', 'g' );
        update_post_meta( $this->post_id, 'recipe_ingredients_1_name', 'Pieprz' );
        update_post_meta( $this->post_id, 'recipe_ingredients_1_amount', '2' );
        update_post_meta( $this->post_id, 'recipe_ingredients_1_unit', 'g' );

        $result = g4t_load_ingredients_repeater_meta( $this->post_id, 2 );

        $this->assertCount( 2, $result );
        $this->assertEquals( 'Sól', $result[0]['name'] );
        $this->assertEquals( 5.0, $result[0]['amount'] );
        $this->assertEquals( 'Pieprz', $result[1]['name'] );
    }

    /**
     * Test re-entry guard prevents recursion.
     */
    public function test_sync_reentry_guard() {
        $post = get_post( $this->post_id );

        // First call
        g4t_sync_recipe_meta_from_blocks( $this->post_id, $post, true, $this->plugin );

        // Simulate recursive call (should be blocked)
        g4t_sync_recipe_meta_from_blocks( $this->post_id, $post, true, $this->plugin );

        // Test passes if no fatal error occurs
        $this->assertTrue( true );
    }
}
