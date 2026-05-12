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

    /**
     * Test block template is applied to the correct post type.
     */
    public function test_apply_recipe_block_template() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        $args = array(
            'labels' => array( 'name' => 'Posts' ),
        );

        $result = g4t_apply_recipe_block_template( $args, 'post', $this->plugin );

        $this->assertArrayHasKey( 'template', $result );
        $this->assertIsArray( $result['template'] );
        $this->assertNotEmpty( $result['template'] );

        // Check first block is hero
        $this->assertEquals( 'go4taste-recipes/hero', $result['template'][0][0] );
    }

    /**
     * Test block template is NOT applied to wrong post type.
     */
    public function test_block_template_not_applied_to_wrong_post_type() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        $args = array(
            'labels' => array( 'name' => 'Pages' ),
        );

        $result = g4t_apply_recipe_block_template( $args, 'page', $this->plugin );

        $this->assertArrayNotHasKey( 'template', $result );
    }

    /**
     * Test template lock is set to 'all'.
     */
    public function test_template_lock_is_all() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        $args = array();
        $result = g4t_apply_recipe_block_template( $args, 'post', $this->plugin );

        $this->assertEquals( 'all', $result['template_lock'] );
    }
}
