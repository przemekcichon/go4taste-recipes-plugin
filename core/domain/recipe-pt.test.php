<?php
/**
 * Unit tests for registration/post-types.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Post_Types extends WP_UnitTestCase {

    private $plugin;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Go4Taste_Recipes_Plugin();
    }

    /**
     * Test recipe CPT registration in MODE_RECIPES_CPT mode.
     */
    public function test_register_recipe_cpt_in_recipes_mode() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'recipes_cpt' ) );
        $this->plugin->clear_options_cache();

        g4t_register_recipe_post_type( $this->plugin );

        $this->assertTrue( post_type_exists( G4T_RECIPES_POST_TYPE ) );
    }

    /**
     * Test recipe CPT is NOT registered in MODE_POST mode.
     */
    public function test_recipe_cpt_not_registered_in_post_mode() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        // Unregister if exists
        if ( post_type_exists( G4T_RECIPES_POST_TYPE ) ) {
            unregister_post_type( G4T_RECIPES_POST_TYPE );
        }

        g4t_register_recipe_post_type( $this->plugin );

        $this->assertFalse( post_type_exists( G4T_RECIPES_POST_TYPE ) );
    }
}
