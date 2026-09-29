<?php
/**
 * Unit tests for recipe-schema/image-sizes.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Recipe_Schema_Image_Sizes extends WP_UnitTestCase {

    public function test_sizes_are_registered_as_hard_crops() {
        $sizes = wp_get_registered_image_subsizes();

        $this->assertSame( array( 'width' => 1200, 'height' => 1200, 'crop' => true ), $sizes['g4t-recipe-1x1'] );
        $this->assertSame( array( 'width' => 1200, 'height' => 900, 'crop' => true ), $sizes['g4t-recipe-4x3'] );
        $this->assertSame( array( 'width' => 1200, 'height' => 675, 'crop' => true ), $sizes['g4t-recipe-16x9'] );
    }

    public function test_small_square_original_is_cropped_to_ratio_without_upscaling() {
        // dst_x, dst_y, src_x, src_y, dst_w, dst_h, src_w, src_h
        $this->assertSame( array( 0, 0, 0, 131, 600, 338, 600, 338 ), image_resize_dimensions( 600, 600, 1200, 675, true ) );
        $this->assertSame( array( 0, 0, 0, 75, 600, 450, 600, 450 ), image_resize_dimensions( 600, 600, 1200, 900, true ) );
    }

    public function test_small_original_with_target_ratio_gets_no_new_file() {
        $this->assertFalse( image_resize_dimensions( 600, 600, 1200, 1200, true ) );
    }

    public function test_small_wide_original_is_cropped_horizontally() {
        $this->assertSame( array( 0, 0, 300, 0, 600, 600, 600, 600 ), image_resize_dimensions( 1200, 600, 1200, 1200, true ) );
    }

    public function test_original_larger_in_one_dimension_is_cropped_without_upscaling() {
        $this->assertSame( array( 0, 0, 266, 0, 1067, 800, 1067, 800 ), image_resize_dimensions( 1600, 800, 1200, 900, true ) );
    }

    public function test_crop_position_array_is_honored() {
        $this->assertSame( array( 0, 0, 0, 0, 600, 338, 600, 338 ), g4t_recipe_schema_ratio_crop_dimensions( null, 600, 600, 1200, 675, array( 'left', 'top' ) ) );
        $this->assertSame( array( 0, 0, 0, 262, 600, 338, 600, 338 ), g4t_recipe_schema_ratio_crop_dimensions( null, 600, 600, 1200, 675, array( 'left', 'bottom' ) ) );
        $this->assertSame( array( 0, 0, 0, 131, 600, 338, 600, 338 ), g4t_recipe_schema_ratio_crop_dimensions( null, 600, 600, 1200, 675, array( 'left' ) ) );
    }

    public function test_large_original_keeps_wordpress_default_crop() {
        $this->assertSame( array( 0, 0, 0, 187, 1200, 675, 2000, 1125 ), image_resize_dimensions( 2000, 1500, 1200, 675, true ) );
    }

    public function test_other_sizes_are_not_affected() {
        $this->assertNull( g4t_recipe_schema_ratio_crop_dimensions( null, 600, 600, 800, 450, true ) );
        $this->assertNull( g4t_recipe_schema_ratio_crop_dimensions( null, 600, 600, 1200, 675, false ) );
    }
}
