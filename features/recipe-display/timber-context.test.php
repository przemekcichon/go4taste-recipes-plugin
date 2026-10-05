<?php
/**
 * Unit tests for features/recipe-display/timber-context.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Timber_Context extends WP_UnitTestCase {

    private $plugin;
    private $post_id;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Go4Taste_Recipes_Plugin();
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        $this->post_id = $this->factory->post->create( array(
            'post_title' => 'Test Recipe',
            'post_type'  => 'post',
        ) );
    }

    /**
     * Test single context filter adds recipe data.
     */
    public function test_filter_single_context_adds_recipe() {
        // The filter reads get_queried_object(), so simulate a real single request.
        $this->go_to( get_permalink( $this->post_id ) );

        $context = array();
        $meta = array();

        $result = g4t_filter_single_context( $context, $meta, $this->plugin );

        $this->assertArrayHasKey( 'recipe', $result );
        $this->assertIsArray( $result['recipe'] );
        $this->assertEquals( $this->post_id, $result['recipe']['id'] );
        $this->assertTrue( $result['is_recipe'] );
    }

    /**
     * Test use archive template filter for recipe taxonomies.
     */
    public function test_filter_use_archive_template_for_recipe_taxonomy() {
        g4t_register_flat_taxonomies( $this->plugin );

        $term = $this->factory->term->create( array(
            'taxonomy' => G4T_MEAL_TYPE_TAXONOMY,
            'name'     => 'Zupy',
        ) );

        // The decision comes from the main query, not from the $queried_object argument.
        $this->go_to( get_term_link( $term, G4T_MEAL_TYPE_TAXONOMY ) );

        $result = g4t_filter_use_archive_template( false, get_queried_object(), $this->plugin );

        $this->assertTrue( $result );
    }

    /**
     * Ingredient terms have no pages, so their filter tag carries no link.
     */
    public function test_active_filter_tags_link_only_viewable_taxonomies() {
        g4t_register_ingredient_taxonomy( $this->plugin );
        g4t_register_flat_taxonomies( $this->plugin );
        $this->factory->term->create( array( 'taxonomy' => G4T_INGREDIENT_TAXONOMY, 'name' => 'Anyż', 'slug' => 'anyz' ) );
        $this->factory->term->create( array( 'taxonomy' => G4T_MEAL_TYPE_TAXONOMY, 'name' => 'Zupy', 'slug' => 'zupy' ) );

        $_GET['g4t_ingredients'] = 'anyz';
        $_GET['g4t_meal_type']   = 'zupy';
        $tags = g4t_get_active_filter_tags();
        unset( $_GET['g4t_ingredients'], $_GET['g4t_meal_type'] );

        $by_taxonomy = array_column( $tags, 'url', 'taxonomy' );
        $this->assertSame( '', $by_taxonomy[ G4T_INGREDIENT_TAXONOMY ] );
        $this->assertNotSame( '', $by_taxonomy[ G4T_MEAL_TYPE_TAXONOMY ] );
    }

    /**
     * Test use archive template filter returns false for a taxonomy not attached to the source post type.
     */
    public function test_filter_use_archive_template_for_non_recipe_taxonomy() {
        register_taxonomy( 'g4t_test_page_tax', array( 'page' ), array( 'public' => true, 'query_var' => true ) );

        $term = $this->factory->term->create( array(
            'taxonomy' => 'g4t_test_page_tax',
            'name'     => 'General',
        ) );

        try {
            $this->go_to( get_term_link( $term, 'g4t_test_page_tax' ) );

            $result = g4t_filter_use_archive_template( false, get_queried_object(), $this->plugin );

            $this->assertFalse( $result );
        } finally {
            unregister_taxonomy( 'g4t_test_page_tax' );
        }
    }

    /**
     * Test archive context filter structure.
     */
    public function test_filter_archive_context_structure() {
        $context = array();
        $meta = array();

        $result = g4t_filter_archive_context( $context, $meta, $this->plugin );

        $this->assertArrayHasKey( 'recipes', $result );
        $this->assertEquals( 'archive', $result['recipes']['scope'] );
        $this->assertArrayHasKey( 'recipe_archive', $result );
        $this->assertArrayHasKey( 'title', $result['recipe_archive'] );
        $this->assertArrayHasKey( 'description', $result['recipe_archive'] );
        $this->assertArrayHasKey( 'post_type', $result['recipe_archive'] );
        $this->assertArrayHasKey( 'active_filter_tags', $result['recipe_archive'] );
    }

    /**
     * Test home query args filter.
     */
    public function test_filter_home_query_args() {
        $query_args = array( 'posts_per_page' => 10 );

        $result = g4t_filter_home_query_args( $query_args, $this->plugin );

        $this->assertArrayHasKey( 'post_type', $result );
        $this->assertEquals( 'post', $result['post_type'] );
    }

    /**
     * Test source post type filter.
     */
    public function test_filter_source_post_type() {
        $result = g4t_filter_source_post_type( 'post', $this->plugin );

        $this->assertEquals( 'post', $result );
    }

    /**
     * Test source post type filter in recipes_cpt mode.
     */
    public function test_filter_source_post_type_recipes_mode() {
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'recipes_cpt' ) );
        $this->plugin->clear_options_cache();

        $result = g4t_filter_source_post_type( 'post', $this->plugin );

        $this->assertEquals( G4T_RECIPES_POST_TYPE, $result );
    }

    /**
     * Test taxonomy term hero data returns correct structure.
     */
    public function test_get_taxonomy_term_hero_data_structure() {
        g4t_register_flat_taxonomies( $this->plugin );

        $term_id = $this->factory->term->create( array(
            'taxonomy'    => G4T_MEAL_TYPE_TAXONOMY,
            'name'        => 'Śniadanie',
            'description' => 'Opis testowy',
        ) );

        $term = get_term( $term_id, G4T_MEAL_TYPE_TAXONOMY );

        $result = g4t_get_taxonomy_term_hero_data( $term );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'icon', $result );
        $this->assertArrayHasKey( 'title_suffix', $result );
        $this->assertArrayHasKey( 'description', $result );
        $this->assertArrayHasKey( 'hero_image', $result );
        $this->assertArrayHasKey( 'short_claim', $result );
        $this->assertArrayHasKey( 'blog_posts', $result );

        // Safe defaults when ACF not active or fields empty
        $this->assertNull( $result['icon'] );
        $this->assertEquals( '', $result['title_suffix'] );
        $this->assertEquals( 'Opis testowy', $result['description'] ); // from WP term, not ACF
        $this->assertNull( $result['hero_image'] );
        $this->assertEquals( '', $result['short_claim'] );
        $this->assertIsArray( $result['blog_posts'] );
    }

    /**
     * Test normalize term blog posts filters invalid entries.
     */
    public function test_normalize_term_blog_posts_filters_invalid() {
        $raw_posts = array(
            array( 'title' => 'Valid Post', 'url' => 'https://example.com/post-1' ),
            array( 'title' => '', 'url' => 'https://example.com/post-2' ), // empty title
            array( 'title' => 'No URL', 'url' => '' ), // empty url
            array( 'title' => 'Another Valid', 'url' => 'https://example.com/post-3' ),
        );

        $result = g4t_normalize_term_blog_posts( $raw_posts );

        $this->assertCount( 2, $result );
        $this->assertEquals( 'Valid Post', $result[0]['title'] );
        $this->assertEquals( 'https://example.com/post-1', $result[0]['url'] );
        $this->assertEquals( 'Another Valid', $result[1]['title'] );
        $this->assertEquals( 'https://example.com/post-3', $result[1]['url'] );
    }

    /**
     * Test normalize term blog posts handles empty array.
     */
    public function test_normalize_term_blog_posts_empty_array() {
        $result = g4t_normalize_term_blog_posts( array() );

        $this->assertIsArray( $result );
        $this->assertCount( 0, $result );
    }

    /**
     * Test normalize term blog posts sanitizes URLs.
     */
    public function test_normalize_term_blog_posts_sanitizes_urls() {
        $raw_posts = array(
            array( 'title' => 'Test', 'url' => 'javascript:alert(1)' ), // malicious
            array( 'title' => 'Test 2', 'url' => 'https://example.com/post?param=value' ), // valid
        );

        $result = g4t_normalize_term_blog_posts( $raw_posts );

        // javascript: URL should be filtered out (esc_url_raw returns empty for invalid/unsafe URLs)
        $this->assertCount( 1, $result );
        $this->assertEquals( 'Test 2', $result[0]['title'] );
    }

    /**
     * Test archive context includes hero data for taxonomy archives.
     */
    public function test_filter_archive_context_includes_hero_for_taxonomy() {
        g4t_register_flat_taxonomies( $this->plugin );

        $term_id = $this->factory->term->create( array(
            'taxonomy' => G4T_MEAL_TYPE_TAXONOMY,
            'name'     => 'Śniadanie',
        ) );

        // Simulate is_tax() by setting global $wp_query
        global $wp_query;
        $original_query = $wp_query;
        $wp_query = new WP_Query( array(
            'taxonomy' => G4T_MEAL_TYPE_TAXONOMY,
            'term'     => $term_id,
        ) );
        $wp_query->is_tax = true;
        $wp_query->queried_object = get_term( $term_id, G4T_MEAL_TYPE_TAXONOMY );
        $wp_query->queried_object_id = $term_id;

        $context = array();
        $result = g4t_filter_archive_context( $context, array(), $this->plugin );

        $this->assertArrayHasKey( 'recipe_archive', $result );
        $this->assertArrayHasKey( 'hero', $result['recipe_archive'] );
        $this->assertIsArray( $result['recipe_archive']['hero'] );

        // Restore original query
        $wp_query = $original_query;
    }
}

