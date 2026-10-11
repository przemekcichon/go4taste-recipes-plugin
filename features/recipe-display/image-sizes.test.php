<?php
/**
 * Unit tests for recipe-display/image-sizes.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Recipe_Display_Image_Sizes extends WP_UnitTestCase {

    public function test_intermediate_size_keeps_original_ratio() {
        $sizes = wp_get_registered_image_subsizes();

        $this->assertSame( array( 'width' => 400, 'height' => 0, 'crop' => false ), $sizes['g4t-recipe-400'] );
    }
}
