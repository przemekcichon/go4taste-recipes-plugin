<?php
/**
 * Unit tests for registration/blocks.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Blocks extends WP_UnitTestCase {

    private $plugin;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Go4Taste_Recipes_Plugin();
    }

    /**
     * Test block names constant is defined.
     */
    public function test_block_names_constant_defined() {
        $this->assertTrue( defined( 'G4T_RECIPE_BLOCK_NAMES' ) );
        $this->assertIsArray( G4T_RECIPE_BLOCK_NAMES );
        $this->assertNotEmpty( G4T_RECIPE_BLOCK_NAMES );
    }

    /**
     * Test all expected blocks are in the constant.
     */
    public function test_all_expected_blocks_registered() {
        $expected_blocks = array(
            'go4taste-recipes/hero',
            'go4taste-recipes/ingredients',
            'go4taste-recipes/steps',
            'go4taste-recipes/tips',
            'go4taste-recipes/source',
        );

        foreach ( $expected_blocks as $block_name ) {
            $this->assertContains( $block_name, G4T_RECIPE_BLOCK_NAMES );
        }
    }

    /**
     * Test blocks are registered when Gutenberg is available.
     */
    public function test_blocks_registered() {
        if ( ! function_exists( 'register_block_type' ) ) {
            $this->markTestSkipped( 'Block editor not available in this test environment.' );
        }

        g4t_register_recipe_blocks( $this->plugin );

        $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

        foreach ( G4T_RECIPE_BLOCK_NAMES as $block_name ) {
            $this->assertArrayHasKey( $block_name, $registered_blocks );
        }
    }

    /**
     * Test editor script is registered.
     */
    public function test_editor_script_registered() {
        g4t_register_recipe_blocks( $this->plugin );

        $this->assertTrue( wp_script_is( 'go4taste-recipes-editor-blocks', 'registered' ) );
    }

    /**
     * Test editor script has correct dependencies.
     */
    public function test_editor_script_dependencies() {
        g4t_register_recipe_blocks( $this->plugin );

        $script = wp_scripts()->registered['go4taste-recipes-editor-blocks'];
        $expected_deps = array(
            'wp-blocks',
            'wp-element',
            'wp-block-editor',
            'wp-components',
            'wp-i18n',
            'wp-hooks',
            'wp-api-fetch',
        );

        foreach ( $expected_deps as $dep ) {
            $this->assertContains( $dep, $script->deps );
        }
    }
}
