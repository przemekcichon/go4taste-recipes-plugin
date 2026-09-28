<?php
/**
 * Unit tests for breadcrumbs/breadcrumb-trail.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Breadcrumb_Trail extends WP_UnitTestCase {

    public function test_normalize_keeps_order_and_unlinks_current_page() {
        $trail = g4t_breadcrumbs_normalize(
            array(
                array( 'url' => 'https://example.com/', 'text' => 'Przepisy', 'id' => 1 ),
                array( 'url' => 'https://example.com/typ-dania/zupy', 'text' => 'Zupy', 'term_id' => 5 ),
                array( 'url' => 'https://example.com/zurek.html', 'text' => 'Żurek &amp; <b>chleb</b>', 'id' => 9 ),
            )
        );

        $this->assertSame(
            array(
                array( 'text' => 'Przepisy', 'url' => 'https://example.com/' ),
                array( 'text' => 'Zupy', 'url' => 'https://example.com/typ-dania/zupy' ),
                array( 'text' => 'Żurek & chleb', 'url' => '' ),
            ),
            $trail
        );
    }

    public function test_normalize_drops_crumbs_without_text() {
        $trail = g4t_breadcrumbs_normalize(
            array(
                array( 'url' => 'https://example.com/', 'text' => 'Przepisy' ),
                array( 'url' => 'https://example.com/x', 'text' => '  ' ),
                'not-a-crumb',
            )
        );

        $this->assertSame( array( array( 'text' => 'Przepisy', 'url' => '' ) ), $trail );
        $this->assertSame( array(), g4t_breadcrumbs_normalize( array() ) );
    }

    public function test_fallback_links_recipe_home() {
        $this->assertSame(
            array(
                array( 'text' => 'Przepisy', 'url' => home_url( '/' ) ),
                array( 'text' => 'Żurek', 'url' => '' ),
            ),
            g4t_breadcrumbs_fallback( 'Żurek' )
        );
    }

    public function test_context_gets_trail_for_single_post() {
        $post_id = self::factory()->post->create( array( 'post_title' => 'Zupa dyniowa' ) );
        $this->go_to( get_permalink( $post_id ) );

        $context = apply_filters( 'go4taste/recipes/context/single', array(), array() );

        $this->assertArrayHasKey( 'breadcrumbs', $context );
        $trail = $context['breadcrumbs'];
        $this->assertSame( array( 'text' => 'Zupa dyniowa', 'url' => '' ), end( $trail ) );
        $this->assertNotSame( '', $trail[0]['url'] );
    }

    public function test_current_title_for_term_archive() {
        $term_id = self::factory()->term->create( array( 'taxonomy' => G4T_MEAL_TYPE_TAXONOMY, 'name' => 'Zupy' ) );
        self::factory()->post->create( array( 'tax_input' => array( G4T_MEAL_TYPE_TAXONOMY => array( $term_id ) ) ) );
        $this->go_to( get_term_link( $term_id ) );

        $this->assertSame( 'Zupy', g4t_breadcrumbs_current_title( array() ) );
    }

    public function test_current_title_falls_back_to_context_title() {
        $this->go_to( home_url( '/' ) );

        $this->assertSame( 'Archiwum', g4t_breadcrumbs_current_title( array( 'title' => 'Archiwum' ) ) );
    }
}
