<?php
/**
 * Unit tests for view-model/normalizers.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Normalizers extends WP_UnitTestCase {

    /**
     * Test g4t_normalize_ingredients_list with valid data.
     */
    public function test_normalize_ingredients_list_with_valid_data() {
        $input = array(
            array(
                'ingredient_term_id' => '123',
                'name'               => 'Mąka',
                'amount'             => '500',
                'unit'               => 'g',
            ),
            array(
                'ingredient_term_id' => '',
                'name'               => 'Cukier',
                'amount'             => '200',
                'unit'               => 'g',
            ),
        );

        $result = g4t_normalize_ingredients_list( $input );

        $this->assertIsArray( $result );
        $this->assertCount( 2, $result );

        // First ingredient
        $this->assertEquals( 123, $result[0]['ingredient_term_id'] );
        $this->assertEquals( 'Mąka', $result[0]['name'] );
        $this->assertEquals( '500', $result[0]['amount'] );
        $this->assertEquals( 'g', $result[0]['unit'] );

        // Second ingredient (no term_id)
        $this->assertNull( $result[1]['ingredient_term_id'] );
        $this->assertEquals( 'Cukier', $result[1]['name'] );
        $this->assertEquals( '200', $result[1]['amount'] );
        $this->assertEquals( 'g', $result[1]['unit'] );
    }

    /**
     * Test g4t_normalize_ingredients_list filters empty names.
     */
    public function test_normalize_ingredients_list_filters_empty_names() {
        $input = array(
            array( 'name' => 'Mąka', 'amount' => '500', 'unit' => 'g' ),
            array( 'name' => '', 'amount' => '200', 'unit' => 'g' ),
            array( 'name' => '   ', 'amount' => '100', 'unit' => 'ml' ),
        );

        $result = g4t_normalize_ingredients_list( $input );

        $this->assertCount( 1, $result );
        $this->assertEquals( 'Mąka', $result[0]['name'] );
    }

    /**
     * Test g4t_normalize_ingredients_list keeps non-empty amounts as text.
     */
    public function test_normalize_ingredients_list_keeps_textual_amounts() {
        $input = array(
            array( 'name' => 'Sól', 'amount' => 'szczypta', 'unit' => '' ),
            array( 'name' => 'Mąka', 'amount' => '1/3', 'unit' => 'szklanki' ),
            array( 'name' => 'Cukier', 'amount' => '', 'unit' => 'g' ),
        );

        $result = g4t_normalize_ingredients_list( $input );

        $this->assertEquals( 'szczypta', $result[0]['amount'] );
        $this->assertEquals( '1/3', $result[1]['amount'] );
        $this->assertNull( $result[2]['amount'] );
    }

    /**
     * Test g4t_normalize_steps_list with valid data.
     */
    public function test_normalize_steps_list_with_valid_data() {
        $input = array(
            array(
                'title'       => 'Krok 1',
                'description' => 'Wymieszaj składniki',
                'tip'         => 'Uważaj na grudki',
                'images'      => array( 'http://example.com/img1.jpg' ),
            ),
            array(
                'title'       => '',
                'description' => 'Piecz przez 30 minut',
                'tip'         => '',
                'images'      => '',
            ),
        );

        $result = g4t_normalize_steps_list( $input );

        $this->assertIsArray( $result );
        $this->assertCount( 2, $result );

        // First step: legacy 'title' / 'images' input maps to 'heading' / 'imageUrl'.
        $this->assertEquals( 'Krok 1', $result[0]['heading'] );
        $this->assertEquals( 'Wymieszaj składniki', $result[0]['description'] );
        $this->assertEquals( 'Uważaj na grudki', $result[0]['tip'] );
        $this->assertEquals( 'http://example.com/img1.jpg', $result[0]['imageUrl'] );

        // Second step (auto-generated heading)
        $this->assertEquals( 'Krok 2', $result[1]['heading'] );
        $this->assertEquals( 'Piecz przez 30 minut', $result[1]['description'] );
        $this->assertEquals( '', $result[1]['tip'] );
        $this->assertEquals( '', $result[1]['imageUrl'] );
    }

    /**
     * Test g4t_normalize_steps_list filters empty descriptions.
     */
    public function test_normalize_steps_list_filters_empty_descriptions() {
        $input = array(
            array( 'title' => 'Krok 1', 'description' => 'Wymieszaj', 'tip' => '' ),
            array( 'title' => 'Krok 2', 'description' => '', 'tip' => '' ),
            array( 'title' => 'Krok 3', 'description' => '   ', 'tip' => '' ),
        );

        $result = g4t_normalize_steps_list( $input );

        $this->assertCount( 1, $result );
        $this->assertEquals( 'Krok 1', $result[0]['heading'] );
    }

    /**
     * Regression: normalize ACF repeater step keys used by recipe creator/metabox.
     */
    public function test_normalize_steps_list_supports_acf_step_keys() {
        $input = array(
            array(
                'step_header'      => 'Przygotuj składniki',
                'step_description' => 'Pokrój warzywa i odmierz przyprawy.',
                'step_tip'         => 'Nożem szefa kuchni idzie szybciej.',
                'step_image_url'   => 'https://example.com/step-1.jpg',
            ),
        );

        $result = g4t_normalize_steps_list( $input );

        $this->assertCount( 1, $result );
        $this->assertEquals( 'Przygotuj składniki', $result[0]['heading'] );
        $this->assertEquals( 'Pokrój warzywa i odmierz przyprawy.', $result[0]['description'] );
        $this->assertEquals( 'Nożem szefa kuchni idzie szybciej.', $result[0]['tip'] );
        $this->assertEquals( 'https://example.com/step-1.jpg', $result[0]['imageUrl'] );
    }

    /**
     * Test g4t_has_value helper function.
     */
    public function test_has_value() {
        $this->assertTrue( g4t_has_value( 'text' ) );
        $this->assertTrue( g4t_has_value( 123 ) );
        $this->assertTrue( g4t_has_value( 0 ) );
        $this->assertTrue( g4t_has_value( array( 'item' ) ) );

        $this->assertFalse( g4t_has_value( '' ) );
        $this->assertFalse( g4t_has_value( '   ' ) );
        $this->assertFalse( g4t_has_value( null ) );
        $this->assertFalse( g4t_has_value( array() ) );
    }
}
