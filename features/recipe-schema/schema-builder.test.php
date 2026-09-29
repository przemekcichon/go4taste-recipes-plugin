<?php
/**
 * Unit tests for recipe-schema/schema-builder.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Recipe_Schema_Builder extends WP_UnitTestCase {

    /**
     * @return array<string,mixed>
     */
    private function recipe( array $overrides = array() ): array {
        return array_replace(
            array(
                'hero'                => array(
                    'title'   => 'Żurek na zakwasie',
                    'excerpt' => '<p>Klasyczny  żurek.</p>',
                    'image'   => 'https://example.com/zurek.jpg',
                ),
                'times'               => array( 'prep' => '20', 'cook' => '40 min', 'total' => '', 'soak' => '' ),
                'servings'            => array( 'value' => 4.0, 'unit' => 'osoby' ),
                'ingredient_sections' => array(
                    array(
                        'title' => null,
                        'items' => array(
                            array( 'name' => 'zakwas', 'amount' => '500', 'unit' => 'ml', 'ingredient_note' => 'domowy' ),
                            array( 'name' => 'sól', 'amount' => null, 'unit' => '', 'ingredient_note' => '' ),
                        ),
                    ),
                ),
                'steps'               => array(
                    array( 'heading' => 'Krok 1', 'description' => 'Zagotuj wodę.', 'tip' => 'x', 'imageUrl' => '' ),
                    array( 'heading' => 'Zakwas', 'description' => 'Wlej zakwas.', 'tip' => '', 'imageUrl' => 'https://example.com/s2.jpg' ),
                ),
            ),
            $overrides
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function post_data( array $overrides = array() ): array {
        return array_replace(
            array(
                'url'            => 'https://example.com/zurek/',
                'images'         => array( array( 'url' => 'https://example.com/zurek.jpg', 'width' => 1200, 'height' => 800 ) ),
                'date_published' => '2026-01-10T10:00:00+00:00',
                'date_modified'  => '2026-02-01T10:00:00+00:00',
                'language'       => 'pl-PL',
                'categories'     => array( 'Zupy', 'Obiad' ),
                'author'         => array( '@type' => 'Organization', 'name' => 'Go4Taste', 'url' => 'https://example.com/' ),
            ),
            $overrides
        );
    }

    public function test_parse_minutes_formats() {
        $this->assertSame( 30, g4t_recipe_schema_parse_minutes( '30' ) );
        $this->assertSame( 30, g4t_recipe_schema_parse_minutes( '30 min' ) );
        $this->assertSame( 30, g4t_recipe_schema_parse_minutes( ' 30min ' ) );
        $this->assertSame( 80, g4t_recipe_schema_parse_minutes( '1 h 20 min' ) );
        $this->assertSame( 90, g4t_recipe_schema_parse_minutes( '1,5 h' ) );
        $this->assertSame( 120, g4t_recipe_schema_parse_minutes( '2 godz.' ) );
        $this->assertSame( 90, g4t_recipe_schema_parse_minutes( '1h30' ) );
        $this->assertSame( 90, g4t_recipe_schema_parse_minutes( '1 h 30' ) );
        $this->assertSame( 45, g4t_recipe_schema_parse_minutes( '45 minut' ) );
        $this->assertSame( 0, g4t_recipe_schema_parse_minutes( '' ) );
        $this->assertSame( 0, g4t_recipe_schema_parse_minutes( 'chwilę' ) );
        $this->assertSame( 0, g4t_recipe_schema_parse_minutes( '0' ) );
    }

    public function test_format_duration() {
        $this->assertSame( 'PT30M', g4t_recipe_schema_format_duration( 30 ) );
        $this->assertSame( 'PT1H20M', g4t_recipe_schema_format_duration( 80 ) );
        $this->assertSame( 'PT2H', g4t_recipe_schema_format_duration( 120 ) );
        $this->assertSame( '', g4t_recipe_schema_format_duration( 0 ) );
    }

    public function test_build_full_recipe() {
        $schema = g4t_recipe_schema_build( $this->recipe(), $this->post_data() );

        $this->assertSame( 'Recipe', $schema['@type'] );
        $this->assertSame( 'https://example.com/zurek/#recipe', $schema['@id'] );
        $this->assertSame( 'Żurek na zakwasie', $schema['name'] );
        $this->assertSame( 'Klasyczny żurek.', $schema['description'] );
        $this->assertSame( array( '@type' => 'ImageObject', 'url' => 'https://example.com/zurek.jpg', 'width' => 1200, 'height' => 800 ), $schema['image'] );
        $this->assertSame( 'PT20M', $schema['prepTime'] );
        $this->assertSame( 'PT40M', $schema['cookTime'] );
        $this->assertSame( 'PT1H', $schema['totalTime'] );
        $this->assertSame( '4 osoby', $schema['recipeYield'] );
        $this->assertSame( 'Zupy, Obiad', $schema['recipeCategory'] );
        $this->assertSame( array( '500 ml zakwas', 'sól' ), $schema['recipeIngredient'] );
        $this->assertSame( 'pl-PL', $schema['inLanguage'] );
        $this->assertSame( 'Go4Taste', $schema['author']['name'] );
        $this->assertTrue( g4t_recipe_schema_is_printable( $schema ) );
        $this->assertArrayNotHasKey( '@context', $schema );
    }

    public function test_instructions_skip_auto_heading_and_tip() {
        $schema = g4t_recipe_schema_build( $this->recipe(), $this->post_data() );

        $this->assertSame( array( '@type' => 'HowToStep', 'text' => 'Zagotuj wodę.' ), $schema['recipeInstructions'][0] );
        $this->assertSame(
            array( '@type' => 'HowToStep', 'name' => 'Zakwas', 'text' => 'Wlej zakwas.', 'image' => 'https://example.com/s2.jpg' ),
            $schema['recipeInstructions'][1]
        );
    }

    public function test_step_markup_keeps_word_boundaries() {
        $recipe = $this->recipe(
            array( 'steps' => array( array( 'heading' => '', 'description' => '<p>Wymieszaj.<br>Odstaw</p><ul><li>raz</li><li>dwa &amp; trzy</li></ul>', 'tip' => '', 'imageUrl' => '' ) ) )
        );
        $schema = g4t_recipe_schema_build( $recipe, $this->post_data() );

        $this->assertSame( 'Wymieszaj. Odstaw raz dwa & trzy', $schema['recipeInstructions'][0]['text'] );
    }

    public function test_no_id_without_url() {
        $schema = g4t_recipe_schema_build( $this->recipe(), $this->post_data( array( 'url' => '' ) ) );

        $this->assertArrayNotHasKey( '@id', $schema );
    }

    public function test_prep_and_cook_only_in_pair() {
        $recipe = $this->recipe( array( 'times' => array( 'prep' => '20', 'cook' => '', 'total' => '45 min', 'soak' => '' ) ) );
        $schema = g4t_recipe_schema_build( $recipe, $this->post_data() );

        $this->assertArrayNotHasKey( 'prepTime', $schema );
        $this->assertArrayNotHasKey( 'cookTime', $schema );
        $this->assertSame( 'PT45M', $schema['totalTime'] );
    }

    public function test_explicit_total_wins_over_sum() {
        $recipe = $this->recipe( array( 'times' => array( 'prep' => '20', 'cook' => '40', 'total' => '90', 'soak' => '' ) ) );
        $schema = g4t_recipe_schema_build( $recipe, $this->post_data() );

        $this->assertSame( 'PT1H30M', $schema['totalTime'] );
    }

    public function test_ingredient_sections_are_flattened_without_titles() {
        $recipe = $this->recipe(
            array(
                'ingredient_sections' => array(
                    array( 'title' => 'Ciasto', 'items' => array( array( 'name' => 'mąka', 'amount' => '250', 'unit' => 'g' ) ) ),
                    array( 'title' => 'Krem', 'items' => array( array( 'name' => 'śmietana', 'amount' => '200', 'unit' => 'ml' ) ) ),
                    array( 'title' => 'Pusta', 'items' => array() ),
                ),
            )
        );
        $schema = g4t_recipe_schema_build( $recipe, $this->post_data() );

        $this->assertSame( array( '250 g mąka', '200 ml śmietana' ), $schema['recipeIngredient'] );
    }

    public function test_empty_fields_are_omitted() {
        $recipe = $this->recipe(
            array(
                'hero'     => array( 'title' => 'Sałatka', 'excerpt' => '', 'image' => '' ),
                'times'    => array( 'prep' => '', 'cook' => '', 'total' => '', 'soak' => '' ),
                'servings' => array( 'value' => null, 'unit' => 'osoby' ),
            )
        );
        $schema = g4t_recipe_schema_build( $recipe, $this->post_data( array( 'categories' => array(), 'language' => '' ) ) );

        foreach ( array( 'description', 'prepTime', 'cookTime', 'totalTime', 'recipeYield', 'recipeCategory', 'inLanguage' ) as $key ) {
            $this->assertArrayNotHasKey( $key, $schema, $key );
        }
    }

    public function test_build_lists_every_aspect_ratio_once() {
        $schema = g4t_recipe_schema_build(
            $this->recipe(),
            $this->post_data(
                array(
                    'images' => array(
                        array( 'url' => 'https://example.com/zurek-600x450.jpg', 'width' => 600, 'height' => 450 ),
                        array( 'url' => 'https://example.com/zurek-600x338.jpg', 'width' => 600, 'height' => 338 ),
                        array( 'url' => '', 'width' => 1, 'height' => 1 ),
                        array( 'url' => 'https://example.com/zurek-600x338.jpg', 'width' => 600, 'height' => 338 ),
                        array( 'url' => 'https://example.com/zurek.jpg', 'width' => 0, 'height' => 0 ),
                    ),
                )
            )
        );

        $this->assertSame(
            array(
                array( '@type' => 'ImageObject', 'url' => 'https://example.com/zurek-600x450.jpg', 'width' => 600, 'height' => 450 ),
                array( '@type' => 'ImageObject', 'url' => 'https://example.com/zurek-600x338.jpg', 'width' => 600, 'height' => 338 ),
                array( '@type' => 'ImageObject', 'url' => 'https://example.com/zurek.jpg' ),
            ),
            $schema['image']
        );
    }

    public function test_yield_formats_decimal_with_comma() {
        $this->assertSame( '1,5 l', g4t_recipe_schema_yield( 1.5, 'l' ) );
        $this->assertSame( '12 sztuk', g4t_recipe_schema_yield( 12.0, 'sztuk' ) );
        $this->assertSame( '', g4t_recipe_schema_yield( null, 'osoby' ) );
        $this->assertSame( '', g4t_recipe_schema_yield( 0, 'osoby' ) );
    }

    public function test_not_printable_without_image_or_content() {
        $no_image = g4t_recipe_schema_build( $this->recipe(), $this->post_data( array( 'images' => array() ) ) );
        $this->assertFalse( g4t_recipe_schema_is_printable( $no_image ) );

        $no_content = g4t_recipe_schema_build(
            $this->recipe( array( 'ingredient_sections' => array( array( 'title' => null, 'items' => array() ) ), 'steps' => array() ) ),
            $this->post_data()
        );
        $this->assertFalse( g4t_recipe_schema_is_printable( $no_content ) );
    }

    public function test_item_list_numbers_unique_urls_in_order() {
        $list = g4t_recipe_schema_build_item_list(
            array( 'https://example.com/a/', '', 'https://example.com/b/', 'https://example.com/a/' ),
            'https://example.com/page/2/'
        );

        $this->assertSame(
            array(
                '@type'           => 'ItemList',
                '@id'             => 'https://example.com/page/2/#itemlist',
                'numberOfItems'   => 2,
                'itemListElement' => array(
                    array( '@type' => 'ListItem', 'position' => 1, 'url' => 'https://example.com/a/' ),
                    array( '@type' => 'ListItem', 'position' => 2, 'url' => 'https://example.com/b/' ),
                ),
            ),
            $list
        );
    }

    public function test_item_list_without_page_url_has_no_id() {
        $this->assertArrayNotHasKey( '@id', g4t_recipe_schema_build_item_list( array( 'https://example.com/a/' ), '' ) );
    }
}
