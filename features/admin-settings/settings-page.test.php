<?php
/**
 * Unit tests for admin/settings-page.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Settings_Page extends WP_UnitTestCase {

    private $plugin;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Go4Taste_Recipes_Plugin();
    }

    /**
     * Test settings registration.
     */
    public function test_register_settings() {
        g4t_register_settings( $this->plugin );

        global $wp_registered_settings;
        $this->assertArrayHasKey( 'go4taste_recipes_options', $wp_registered_settings );
    }

    /**
     * Test sanitize options callback.
     */
    public function test_sanitize_options() {
        $input = array(
            'source_mode'        => 'post',
            'existing_post_type' => 'page',
            'malicious_key'      => '<script>alert("xss")</script>',
        );

        $result = g4t_sanitize_options( $input, $this->plugin );

        $this->assertEquals( 'post', $result['source_mode'] );
        $this->assertEquals( 'page', $result['existing_post_type'] );
        $this->assertArrayNotHasKey( 'malicious_key', $result );
    }

    /**
     * Test sanitize options validates source_mode.
     */
    public function test_sanitize_options_validates_source_mode() {
        $input = array(
            'source_mode' => 'invalid_mode',
        );

        $result = g4t_sanitize_options( $input, $this->plugin );

        $this->assertEquals( 'post', $result['source_mode'] ); // Should fallback to default
    }

    /**
     * Test dependency notices for missing ACF.
     */
    public function test_dependency_notices_missing_acf() {
        // Simulate ACF not active
        if ( function_exists( 'get_field' ) ) {
            $this->markTestSkipped( 'ACF is active, cannot test missing ACF notice.' );
        }

        // Notices render only for users who can manage options.
        wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

        ob_start();
        g4t_render_dependency_notices( $this->plugin );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'ACF jest wymagane', $output );
    }

    /**
     * Test options cache clearing.
     */
    public function test_options_cache_clear() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );

        // First call caches
        $options1 = $this->plugin->get_options();

        // Update option
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'recipes_cpt' ) );

        // Without clearing, should return cached value
        $options2 = $this->plugin->get_options();
        $this->assertEquals( 'post', $options2['source_mode'] );

        // After clearing cache
        $this->plugin->clear_options_cache();
        $options3 = $this->plugin->get_options();
        $this->assertEquals( 'recipes_cpt', $options3['source_mode'] );
    }
}
