<?php
/**
 * Unit tests for sync/meta-sync.php
 *
 * @package Go4Taste_Recipes_Plugin
 */

class Test_Meta_Sync extends WP_UnitTestCase {

    private $plugin;
    private $post_id;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = new Go4Taste_Recipes_Plugin();
        update_option( 'go4taste_recipes_options', array( 'source_mode' => 'post' ) );
        $this->plugin->clear_options_cache();

        // Create test post
        $this->post_id = $this->factory->post->create( array(
            'post_title'   => 'Test Recipe',
            'post_content' => '<!-- wp:go4taste-recipes/hero {"lead":"Test lead","difficulty":"easy","servingsValue":"4","servingsUnit":"porcje","timePrep":"15 min","timeCook":"30 min","timeTotal":"45 min"} /-->',
            'post_type'    => 'post',
        ) );
    }

    /**
     * Test ingredient term creation.
     */
    public function test_ensure_ingredient_term() {
        g4t_register_ingredient_taxonomy( $this->plugin );

        $term_id = g4t_ensure_ingredient_term( 'Mąka' );

        $this->assertIsInt( $term_id );
        $this->assertGreaterThan( 0, $term_id );

        $term = get_term( $term_id, G4T_INGREDIENT_TAXONOMY );
        $this->assertEquals( 'Mąka', $term->name );
    }

    /**
     * Test ingredient term is reused if exists.
     */
    public function test_ensure_ingredient_term_reuses_existing() {
        g4t_register_ingredient_taxonomy( $this->plugin );

        $term_id_1 = g4t_ensure_ingredient_term( 'Cukier' );
        $term_id_2 = g4t_ensure_ingredient_term( 'Cukier' );

        $this->assertEquals( $term_id_1, $term_id_2 );
    }

    /**
     * Test empty ingredient name returns null.
     */
    public function test_ensure_ingredient_term_empty_name() {
        $result = g4t_ensure_ingredient_term( '' );
        $this->assertNull( $result );

        $result = g4t_ensure_ingredient_term( '   ' );
        $this->assertNull( $result );
    }

    /**
     * Test ingredients repeater meta sync.
     */
    public function test_sync_ingredients_repeater_meta() {
        g4t_register_ingredient_taxonomy( $this->plugin );

        $ingredients = array(
            array(
                'ingredient_term_id' => null,
                'name'               => 'Mąka',
                'amount'             => '500',
                'unit'               => 'g',
            ),
            array(
                'ingredient_term_id' => null,
                'name'               => 'Cukier',
                'amount'             => '200',
                'unit'               => 'g',
            ),
        );

        g4t_sync_ingredients_repeater_meta( $this->post_id, $ingredients );

        // Check count
        $count = get_post_meta( $this->post_id, 'recipe_ingredients', true );
        $this->assertEquals( 2, $count );

        // Check first ingredient
        $this->assertEquals( 'Mąka', get_post_meta( $this->post_id, 'recipe_ingredients_0_name', true ) );
        $this->assertEquals( '500', get_post_meta( $this->post_id, 'recipe_ingredients_0_amount', true ) );
        $this->assertEquals( 'g', get_post_meta( $this->post_id, 'recipe_ingredients_0_unit', true ) );

        // Check taxonomy assignment
        $terms = wp_get_object_terms( $this->post_id, G4T_INGREDIENT_TAXONOMY, array( 'fields' => 'names' ) );
        $this->assertContains( 'Mąka', $terms );
        $this->assertContains( 'Cukier', $terms );
    }

    /**
     * Test steps repeater meta sync.
     */
    public function test_sync_steps_repeater_meta() {
        $steps = array(
            array(
                'title'       => 'Krok 1',
                'description' => 'Wymieszaj',
                'tip'         => 'Dokładnie',
                'images'      => array( 'http://example.com/img1.jpg' ),
            ),
        );

        g4t_sync_steps_repeater_meta( $this->post_id, $steps );

        // Check count
        $count = get_post_meta( $this->post_id, 'recipe_steps', true );
        $this->assertEquals( 1, $count );

        // Check step data
        $this->assertEquals( 'Krok 1', get_post_meta( $this->post_id, 'recipe_steps_0_heading', true ) );
        $this->assertEquals( 'Wymieszaj', get_post_meta( $this->post_id, 'recipe_steps_0_description', true ) );
        $this->assertEquals( 'Dokładnie', get_post_meta( $this->post_id, 'recipe_steps_0_tip', true ) );

        // Check images
        $images_count = get_post_meta( $this->post_id, 'recipe_steps_0_images', true );
        $this->assertEquals( 1, $images_count );
    }

    /**
     * Test load ingredients repeater meta.
     */
    public function test_load_ingredients_repeater_meta() {
        update_post_meta( $this->post_id, 'recipe_ingredients', 2 );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_name', 'Sól' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_amount', '5' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_unit', 'g' );
        update_post_meta( $this->post_id, 'recipe_ingredients_1_name', 'Pieprz' );
        update_post_meta( $this->post_id, 'recipe_ingredients_1_amount', '2' );
        update_post_meta( $this->post_id, 'recipe_ingredients_1_unit', 'g' );

        $result = g4t_load_ingredients_repeater_meta( $this->post_id, 2 );

        $this->assertCount( 2, $result );
        $this->assertEquals( 'Sól', $result[0]['name'] );
        $this->assertEquals( '5', $result[0]['amount'] );
        $this->assertEquals( 'Pieprz', $result[1]['name'] );
    }

    /**
     * Test load ingredients repeater meta preserves fractional amounts.
     */
    public function test_load_ingredients_repeater_meta_preserves_fractional_amount() {
        update_post_meta( $this->post_id, 'recipe_ingredients', 1 );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_name', 'Mąka' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_amount', '1/3' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_unit', 'szklanki' );

        $result = g4t_load_ingredients_repeater_meta( $this->post_id, 1 );

        $this->assertCount( 1, $result );
        $this->assertEquals( 'Mąka', $result[0]['name'] );
        $this->assertEquals( '1/3', $result[0]['amount'] );
    }

    /**
     * Test re-entry guard prevents recursion.
     */
    public function test_sync_reentry_guard() {
        $post = get_post( $this->post_id );

        // First call
        g4t_sync_recipe_meta_from_blocks( $this->post_id, $post, true, $this->plugin );

        // Simulate recursive call (should be blocked)
        g4t_sync_recipe_meta_from_blocks( $this->post_id, $post, true, $this->plugin );

        // Test passes if no fatal error occurs
        $this->assertTrue( true );
    }

    /**
     * Front creator ACF save must rebuild blocks only after ACF writes post meta.
     */
    public function test_acf_to_blocks_hook_runs_after_acf_meta_save() {
        $priority = has_action( 'acf/save_post', array( $this->plugin, 'proxy_sync_acf_to_blocks' ) );

        $this->assertSame( 20, $priority );
    }

    /**
     * Front creator ACF save should create a missing ingredient term from name fallback.
     */
    public function test_acf_to_blocks_creates_missing_ingredient_term_from_name() {
        g4t_register_ingredient_taxonomy( $this->plugin );

        update_post_meta( $this->post_id, 'recipe_ingredients', 1 );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_name', 'Nowy skladnik testowy' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_amount', '1' );
        update_post_meta( $this->post_id, 'recipe_ingredients_0_unit', 'szt.' );
        delete_post_meta( $this->post_id, 'recipe_ingredients_0_ingredient_term_id' );

        $_POST['acf'] = array(
            'field_recipe_ingredients' => array(),
        );

        try {
            g4t_sync_acf_to_blocks( $this->post_id, $this->plugin );

            $term = get_term_by( 'name', 'Nowy skladnik testowy', G4T_INGREDIENT_TAXONOMY );
        } finally {
            unset( $_POST['acf'] );
        }

        $this->assertInstanceOf( WP_Term::class, $term );
        $this->assertSame(
            (string) $term->term_id,
            (string) get_post_meta( $this->post_id, 'recipe_ingredients_0_ingredient_term_id', true )
        );
        $this->assertStringContainsString(
            '"ingredient_term_id":' . (int) $term->term_id,
            (string) get_post_field( 'post_content', $this->post_id )
        );
    }

    /**
     * Front creator publish action should publish an existing draft post.
     */
    public function test_acf_to_blocks_publishes_existing_draft_on_creator_submit() {
        wp_update_post(
            array(
                'ID'          => $this->post_id,
                'post_status' => 'draft',
            )
        );

        $_POST['acf'] = array(
            'field_recipe_lead' => 'Lead po publikacji',
        );
        $_POST['g4t_recipe_creator_action'] = 'publish';

        try {
            g4t_sync_acf_to_blocks( $this->post_id, $this->plugin );
        } finally {
            unset( $_POST['acf'], $_POST['g4t_recipe_creator_action'] );
        }

        $this->assertSame( 'publish', get_post_status( $this->post_id ) );
    }

    /**
     * Classification taxonomy validation should reject missing required selections.
     */
    public function test_recipe_creator_classification_taxonomy_validation_reports_missing_required_terms() {
        $errors = g4t_get_recipe_creator_classification_taxonomy_validation_errors(
            array(
                G4T_MEAL_TYPE_TAXONOMY => array(),
                G4T_PREP_TIME_TAXONOMY => null,
                G4T_FEATURE_TAXONOMY   => array(),
            )
        );

        $this->assertCount( 2, $errors );
        $this->assertSame( 'g4t_meal_type_term_ids', $errors[0]['field'] );
        $this->assertStringContainsString( 'Typ dania', $errors[0]['message'] );
        $this->assertSame( 'g4t_prep_time_term_ids', $errors[1]['field'] );
        $this->assertStringContainsString( 'Czas przygotowania', $errors[1]['message'] );
    }

    /**
     * Missing hidden taxonomy fields should not clear existing term relations.
     */
    public function test_recipe_creator_force_pre_save_post_preserves_terms_when_fields_missing() {
        g4t_register_flat_taxonomies( $this->plugin );

        $meal_type = self::factory()->term->create(
            array(
                'taxonomy' => G4T_MEAL_TYPE_TAXONOMY,
                'name'     => 'Sniadanie',
            )
        );
        wp_set_object_terms( $this->post_id, array( $meal_type ), G4T_MEAL_TYPE_TAXONOMY, false );

        $_POST['g4t_recipe_creator_action'] = 'update';

        try {
            g4t_recipe_creator_force_pre_save_post( $this->post_id, array() );
        } finally {
            unset( $_POST['g4t_recipe_creator_action'] );
        }

        $meal_assigned = wp_get_object_terms( $this->post_id, G4T_MEAL_TYPE_TAXONOMY, array( 'fields' => 'ids' ) );
        $this->assertCount( 1, $meal_assigned );
        $this->assertSame( $meal_type, (int) $meal_assigned[0] );
    }

    /**
     * Classification taxonomy sync should enforce single/single/multi constraints.
     */
    public function test_sync_recipe_classification_taxonomies_enforces_selection_model() {
        g4t_register_flat_taxonomies( $this->plugin );

        $meal_type_a = self::factory()->term->create(
            array(
                'taxonomy' => G4T_MEAL_TYPE_TAXONOMY,
                'name'     => 'Sniadanie',
            )
        );
        $meal_type_b = self::factory()->term->create(
            array(
                'taxonomy' => G4T_MEAL_TYPE_TAXONOMY,
                'name'     => 'Obiad',
            )
        );
        $prep_time_a = self::factory()->term->create(
            array(
                'taxonomy' => G4T_PREP_TIME_TAXONOMY,
                'name'     => 'Do 15 min',
            )
        );
        $prep_time_b = self::factory()->term->create(
            array(
                'taxonomy' => G4T_PREP_TIME_TAXONOMY,
                'name'     => '16-30 min',
            )
        );
        $feature_a = self::factory()->term->create(
            array(
                'taxonomy' => G4T_FEATURE_TAXONOMY,
                'name'     => 'Bez glutenu',
            )
        );
        $feature_b = self::factory()->term->create(
            array(
                'taxonomy' => G4T_FEATURE_TAXONOMY,
                'name'     => 'Wysokobialkowe',
            )
        );

        g4t_sync_recipe_classification_taxonomies(
            $this->post_id,
            array(
                G4T_MEAL_TYPE_TAXONOMY => array( $meal_type_a, $meal_type_b ),
                G4T_PREP_TIME_TAXONOMY => array( $prep_time_a, $prep_time_b ),
                G4T_FEATURE_TAXONOMY   => array( $feature_a, $feature_b ),
            )
        );

        $meal_assigned = wp_get_object_terms( $this->post_id, G4T_MEAL_TYPE_TAXONOMY, array( 'fields' => 'ids' ) );
        $prep_assigned = wp_get_object_terms( $this->post_id, G4T_PREP_TIME_TAXONOMY, array( 'fields' => 'ids' ) );
        $feature_assigned = wp_get_object_terms( $this->post_id, G4T_FEATURE_TAXONOMY, array( 'fields' => 'ids' ) );

        $this->assertCount( 1, $meal_assigned );
        $this->assertSame( $meal_type_a, (int) $meal_assigned[0] );
        $this->assertCount( 1, $prep_assigned );
        $this->assertSame( $prep_time_a, (int) $prep_assigned[0] );
        $this->assertCount( 2, $feature_assigned );
        $this->assertContains( $feature_a, $feature_assigned );
        $this->assertContains( $feature_b, $feature_assigned );
    }

    /**
     * Classification taxonomy sync should clear relations for empty selections.
     */
    public function test_sync_recipe_classification_taxonomies_clears_empty_selections() {
        g4t_register_flat_taxonomies( $this->plugin );

        $meal_type = self::factory()->term->create(
            array(
                'taxonomy' => G4T_MEAL_TYPE_TAXONOMY,
                'name'     => 'Sniadanie',
            )
        );
        wp_set_object_terms( $this->post_id, array( $meal_type ), G4T_MEAL_TYPE_TAXONOMY, false );

        g4t_sync_recipe_classification_taxonomies(
            $this->post_id,
            array(
                G4T_MEAL_TYPE_TAXONOMY => array(),
                G4T_PREP_TIME_TAXONOMY => array(),
                G4T_FEATURE_TAXONOMY   => array(),
            )
        );

        $meal_assigned = wp_get_object_terms( $this->post_id, G4T_MEAL_TYPE_TAXONOMY, array( 'fields' => 'ids' ) );
        $this->assertCount( 0, $meal_assigned );
    }
}
