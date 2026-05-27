<?php
/**
 * Unit tests for registration/taxonomies.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Taxonomies extends WP_UnitTestCase {

    private $plugin;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Go4Taste_Recipes_Plugin();
    }

    /**
     * Test ingredient taxonomy registration.
     */
    public function test_register_ingredient_taxonomy() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        g4t_register_ingredient_taxonomy( $this->plugin );

        $this->assertTrue( taxonomy_exists( G4T_INGREDIENT_TAXONOMY ) );
        $taxonomy = get_taxonomy( G4T_INGREDIENT_TAXONOMY );
        $this->assertFalse( $taxonomy->hierarchical );
        $this->assertTrue( $taxonomy->show_in_rest );
    }

    /**
     * Test flat taxonomies registration.
     */
    public function test_register_flat_taxonomies() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        g4t_register_flat_taxonomies( $this->plugin );

        $this->assertTrue( taxonomy_exists( G4T_MEAL_TYPE_TAXONOMY ) );
        $this->assertTrue( taxonomy_exists( G4T_PREP_TIME_TAXONOMY ) );
        $this->assertTrue( taxonomy_exists( G4T_FEATURE_TAXONOMY ) );

        // Check they are all flat
        $meal_type = get_taxonomy( G4T_MEAL_TYPE_TAXONOMY );
        $this->assertFalse( $meal_type->hierarchical );
        $this->assertTrue( $meal_type->show_admin_column );
    }

    /**
     * Test taxonomy constants are defined.
     */
    public function test_taxonomy_constants_defined() {
        $this->assertTrue( defined( 'G4T_INGREDIENT_TAXONOMY' ) );
        $this->assertTrue( defined( 'G4T_MEAL_TYPE_TAXONOMY' ) );
        $this->assertTrue( defined( 'G4T_PREP_TIME_TAXONOMY' ) );
        $this->assertTrue( defined( 'G4T_FEATURE_TAXONOMY' ) );

        $this->assertEquals( 'recipe_ingredient', G4T_INGREDIENT_TAXONOMY );
        $this->assertEquals( 'recipe_meal_type', G4T_MEAL_TYPE_TAXONOMY );
        $this->assertEquals( 'recipe_prep_time_range', G4T_PREP_TIME_TAXONOMY );
        $this->assertEquals( 'recipe_feature', G4T_FEATURE_TAXONOMY );
    }

    /**
     * Test taxonomies are attached to correct post type.
     */
    public function test_taxonomies_attached_to_correct_post_type() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        g4t_register_ingredient_taxonomy( $this->plugin );
        g4t_register_flat_taxonomies( $this->plugin );

        $ingredient_tax = get_taxonomy( G4T_INGREDIENT_TAXONOMY );
        $this->assertContains( 'post', $ingredient_tax->object_type );

        $meal_type_tax = get_taxonomy( G4T_MEAL_TYPE_TAXONOMY );
        $this->assertContains( 'post', $meal_type_tax->object_type );
    }
}
