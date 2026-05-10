<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Go4Taste_Recipes_Plugin {
    private const OPTION_KEY = 'go4taste_recipes_options';

    private const MODE_POST         = 'post';
    private const MODE_EXISTING_CPT = 'existing_cpt';
    private const MODE_RECIPES_CPT  = 'recipes_cpt';

    private const RECIPES_POST_TYPE = 'recipe';
    private const RECIPE_BLOCK_NAMES = array(
        'go4taste-recipes/hero',
        'go4taste-recipes/ingredients',
        'go4taste-recipes/steps',
        'go4taste-recipes/tips',
        'go4taste-recipes/source',
    );

    /**
     * @var array<string,mixed>|null
     */
    private ?array $cached_options = null;

    public function __construct() {
        add_action( 'init', array( $this, 'register_recipe_post_type_if_needed' ) );
        add_action( 'init', array( $this, 'register_recipe_blocks' ) );
        add_action( 'acf/init', array( $this, 'register_acf_fields' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'register_options_page' ) );
        add_action( 'admin_notices', array( $this, 'render_dependency_notices' ) );

        add_filter( 'go4taste/recipes/context/single', array( $this, 'filter_single_context' ), 10, 2 );
        add_filter( 'go4taste/recipes/context/archive', array( $this, 'filter_archive_context' ), 10, 2 );
        add_filter( 'go4taste/recipes/context/home', array( $this, 'filter_home_context' ), 10, 2 );
        add_filter( 'go4taste/recipes/use_archive_template', array( $this, 'filter_use_archive_template' ), 10, 2 );
        add_filter( 'go4taste/recipes/home_query_args', array( $this, 'filter_home_query_args' ), 10, 1 );
        add_filter( 'go4taste/recipes/source_post_type', array( $this, 'filter_source_post_type' ), 10, 1 );
        add_filter( 'register_post_type_args', array( $this, 'filter_register_post_type_args' ), 10, 2 );
    }

    /**
     * @return array<string,mixed>
     */
    private function get_options(): array {
        if ( null !== $this->cached_options ) {
            return $this->cached_options;
        }

        $raw = get_option( self::OPTION_KEY, array() );
        $raw = is_array( $raw ) ? $raw : array();

        $defaults = array(
            'source_mode'        => self::MODE_POST,
            'existing_post_type' => 'post',
        );

        $this->cached_options = wp_parse_args( $raw, $defaults );

        return $this->cached_options;
    }

    /**
     * @return array{mode:string,post_type:string,fallback_used:bool}
     */
    public function resolve_source_strategy(): array {
        $options = $this->get_options();
        $mode    = isset( $options['source_mode'] ) ? sanitize_key( (string) $options['source_mode'] ) : self::MODE_POST;

        if ( ! in_array( $mode, array( self::MODE_POST, self::MODE_EXISTING_CPT, self::MODE_RECIPES_CPT ), true ) ) {
            $mode = self::MODE_POST;
        }

        $fallback_used = false;
        $post_type     = 'post';

        if ( self::MODE_RECIPES_CPT === $mode ) {
            $post_type = self::RECIPES_POST_TYPE;
        } elseif ( self::MODE_EXISTING_CPT === $mode ) {
            $candidate = isset( $options['existing_post_type'] ) ? sanitize_key( (string) $options['existing_post_type'] ) : '';
            if ( '' === $candidate || ! post_type_exists( $candidate ) ) {
                $post_type     = 'post';
                $fallback_used = true;
            } else {
                $post_type = $candidate;
            }
        }

        return array(
            'mode'          => $mode,
            'post_type'     => $post_type,
            'fallback_used' => $fallback_used,
        );
    }

    public function register_recipe_post_type_if_needed(): void {
        $strategy = $this->resolve_source_strategy();

        if ( self::MODE_RECIPES_CPT !== $strategy['mode'] ) {
            return;
        }

        if ( post_type_exists( self::RECIPES_POST_TYPE ) ) {
            return;
        }

        register_post_type(
            self::RECIPES_POST_TYPE,
            array(
                'labels' => array(
                    'name'          => __( 'Recipes', 'go4taste-recipes-plugin' ),
                    'singular_name' => __( 'Recipe', 'go4taste-recipes-plugin' ),
                ),
                'public'       => true,
                'show_in_rest' => true,
                'has_archive'  => true,
                'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
                'rewrite'      => array( 'slug' => 'recipes' ),
            )
        );
    }

    public function register_recipe_blocks(): void {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $this->register_editor_blocks_script();

        foreach ( self::RECIPE_BLOCK_NAMES as $block_name ) {
            register_block_type(
                $block_name,
                array(
                    'api_version'     => 2,
                    'editor_script'   => 'go4taste-recipes-editor-blocks',
                    'render_callback' => array( $this, 'render_recipe_block_placeholder' ),
                    'show_in_rest'    => true,
                )
            );
        }
    }

    private function register_editor_blocks_script(): void {
        $script_path = GO4TASTE_RECIPES_PLUGIN_PATH . 'assets/js/editor-blocks-new.js';
        $css_path = GO4TASTE_RECIPES_PLUGIN_PATH . 'assets/css/editor-blocks.css';
        if ( ! file_exists( $script_path ) ) {
            return;
        }

        wp_register_script(
            'go4taste-recipes-editor-blocks',
            GO4TASTE_RECIPES_PLUGIN_URL . 'assets/js/editor-blocks-new.js',
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-hooks' ),
            (string) filemtime( $script_path ),
            true
        );

        if ( file_exists( $css_path ) ) {
            wp_register_style(
                'go4taste-recipes-editor-blocks',
                GO4TASTE_RECIPES_PLUGIN_URL . 'assets/css/editor-blocks.css',
                array(),
                (string) filemtime( $css_path )
            );
            wp_enqueue_style( 'go4taste-recipes-editor-blocks' );
        }
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function render_recipe_block_placeholder( array $attributes = array() ): string {
        return '';
    }

    public function register_acf_fields(): void {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        $strategy = $this->resolve_source_strategy();

        acf_add_local_field_group(
            array(
                'key'    => 'group_go4taste_recipe_data',
                'title'  => 'GO4TASTE Recipe Data',
                'fields' => array(
                    array(
                        'key'   => 'field_recipe_time_prep',
                        'label' => 'Time: Preparation',
                        'name'  => 'recipe_time_prep',
                        'type'  => 'text',
                    ),
                    array(
                        'key'   => 'field_recipe_time_cook',
                        'label' => 'Time: Cooking',
                        'name'  => 'recipe_time_cook',
                        'type'  => 'text',
                    ),
                    array(
                        'key'   => 'field_recipe_time_total',
                        'label' => 'Time: Total',
                        'name'  => 'recipe_time_total',
                        'type'  => 'text',
                    ),
                    array(
                        'key'   => 'field_recipe_difficulty',
                        'label' => 'Difficulty',
                        'name'  => 'recipe_difficulty',
                        'type'  => 'text',
                    ),
                    array(
                        'key'   => 'field_recipe_servings_value',
                        'label' => 'Servings Value',
                        'name'  => 'recipe_servings_value',
                        'type'  => 'number',
                        'step'  => '0.1',
                    ),
                    array(
                        'key'   => 'field_recipe_servings_unit',
                        'label' => 'Servings Unit',
                        'name'  => 'recipe_servings_unit',
                        'type'  => 'text',
                    ),
                    array(
                        'key'          => 'field_recipe_ingredients',
                        'label'        => 'Ingredients',
                        'name'         => 'recipe_ingredients',
                        'type'         => 'repeater',
                        'layout'       => 'table',
                        'button_label' => 'Add ingredient',
                        'sub_fields'   => array(
                            array(
                                'key'   => 'field_recipe_ingredients_name',
                                'label' => 'Name',
                                'name'  => 'name',
                                'type'  => 'text',
                            ),
                            array(
                                'key'   => 'field_recipe_ingredients_amount',
                                'label' => 'Amount',
                                'name'  => 'amount',
                                'type'  => 'number',
                                'step'  => '0.1',
                            ),
                            array(
                                'key'   => 'field_recipe_ingredients_unit',
                                'label' => 'Unit',
                                'name'  => 'unit',
                                'type'  => 'text',
                            ),
                        ),
                    ),
                    array(
                        'key'   => 'field_recipe_ingredients_photo_url',
                        'label' => 'Ingredients Photo URL',
                        'name'  => 'recipe_ingredients_photo_url',
                        'type'  => 'url',
                    ),
                    array(
                        'key'          => 'field_recipe_steps',
                        'label'        => 'Steps',
                        'name'         => 'recipe_steps',
                        'type'         => 'repeater',
                        'layout'       => 'block',
                        'button_label' => 'Add step',
                        'sub_fields'   => array(
                            array(
                                'key'   => 'field_recipe_steps_heading',
                                'label' => 'Heading',
                                'name'  => 'heading',
                                'type'  => 'text',
                            ),
                            array(
                                'key'   => 'field_recipe_steps_description',
                                'label' => 'Description',
                                'name'  => 'description',
                                'type'  => 'textarea',
                                'rows'  => 4,
                            ),
                            array(
                                'key'   => 'field_recipe_steps_tip',
                                'label' => 'Tip',
                                'name'  => 'tip',
                                'type'  => 'textarea',
                                'rows'  => 2,
                            ),
                            array(
                                'key'          => 'field_recipe_steps_images',
                                'label'        => 'Images',
                                'name'         => 'images',
                                'type'         => 'repeater',
                                'layout'       => 'table',
                                'button_label' => 'Add image',
                                'sub_fields'   => array(
                                    array(
                                        'key'   => 'field_recipe_steps_image_url',
                                        'label' => 'Image URL',
                                        'name'  => 'url',
                                        'type'  => 'url',
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key'   => 'field_recipe_tips',
                        'label' => 'Tips',
                        'name'  => 'recipe_tips',
                        'type'  => 'textarea',
                        'rows'  => 4,
                    ),
                    array(
                        'key'   => 'field_recipe_source_label',
                        'label' => 'Source Label',
                        'name'  => 'recipe_source_label',
                        'type'  => 'text',
                    ),
                    array(
                        'key'   => 'field_recipe_source_url',
                        'label' => 'Source URL',
                        'name'  => 'recipe_source_url',
                        'type'  => 'url',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param'    => 'post_type',
                            'operator' => '==',
                            'value'    => $strategy['post_type'],
                        ),
                    ),
                ),
                'position' => 'normal',
                'style'    => 'default',
            )
        );

        acf_add_local_field_group(
            array(
                'key'    => 'group_go4taste_recipe_ads',
                'title'  => 'GO4TASTE Recipe Ads',
                'fields' => array(
                    array(
                        'key'          => 'field_recipe_ads_products',
                        'label'        => 'Ads Products (Bottom Single Banner)',
                        'name'         => 'recipe_ads_products',
                        'type'         => 'repeater',
                        'layout'       => 'table',
                        'button_label' => 'Add product',
                        'sub_fields'   => array(
                            array(
                                'key'   => 'field_recipe_ads_product_id',
                                'label' => 'Product ID',
                                'name'  => 'product_id',
                                'type'  => 'text',
                            ),
                        ),
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param'    => 'post_type',
                            'operator' => '==',
                            'value'    => $strategy['post_type'],
                        ),
                    ),
                ),
                'position' => 'normal',
                'style'    => 'default',
            )
        );
    }

    public function register_settings(): void {
        register_setting(
            'go4taste_recipes_options_group',
            self::OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_options' ),
                'default'           => array(
                    'source_mode'        => self::MODE_POST,
                    'existing_post_type' => 'post',
                ),
            )
        );

        add_settings_section(
            'go4taste_recipes_section',
            __( 'GO4TASTE Recipes Source', 'go4taste-recipes-plugin' ),
            array( $this, 'render_settings_section' ),
            'go4taste-recipes-settings'
        );

        add_settings_field(
            'go4taste_recipes_source_mode',
            __( 'Recipes Source Mode', 'go4taste-recipes-plugin' ),
            array( $this, 'render_source_mode_field' ),
            'go4taste-recipes-settings',
            'go4taste_recipes_section'
        );

        add_settings_field(
            'go4taste_recipes_existing_post_type',
            __( 'Existing Post Type Key', 'go4taste-recipes-plugin' ),
            array( $this, 'render_existing_post_type_field' ),
            'go4taste-recipes-settings',
            'go4taste_recipes_section'
        );
    }

    public function register_options_page(): void {
        add_menu_page(
            __( 'GO4TASTE Recipes', 'go4taste-recipes-plugin' ),
            __( 'GO4TASTE Recipes', 'go4taste-recipes-plugin' ),
            'manage_options',
            'go4taste-recipes-settings',
            array( $this, 'render_options_page' ),
            'dashicons-carrot',
            58
        );
    }

    public function render_options_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'GO4TASTE Recipes Settings', 'go4taste-recipes-plugin' ) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'go4taste_recipes_options_group' );
        do_settings_sections( 'go4taste-recipes-settings' );
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * @param mixed $input
     * @return array<string,string>
     */
    public function sanitize_options( $input ): array {
        $input = is_array( $input ) ? $input : array();

        $mode = isset( $input['source_mode'] ) ? sanitize_key( (string) $input['source_mode'] ) : self::MODE_POST;
        if ( ! in_array( $mode, array( self::MODE_POST, self::MODE_EXISTING_CPT, self::MODE_RECIPES_CPT ), true ) ) {
            $mode = self::MODE_POST;
        }

        $existing_post_type = isset( $input['existing_post_type'] ) ? sanitize_key( (string) $input['existing_post_type'] ) : 'post';
        if ( '' === $existing_post_type ) {
            $existing_post_type = 'post';
        }

        $this->cached_options = null;

        return array(
            'source_mode'        => $mode,
            'existing_post_type' => $existing_post_type,
        );
    }

    public function render_settings_section(): void {
        echo '<p>Wybierz zrodlo danych recipe: post, istniejacy CPT albo dedykowany CPT recipe.</p>';
    }

    public function render_source_mode_field(): void {
        $options = $this->get_options();
        $mode    = isset( $options['source_mode'] ) ? (string) $options['source_mode'] : self::MODE_POST;
        ?>
        <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[source_mode]">
            <option value="post" <?php selected( $mode, self::MODE_POST ); ?>>Standardowe post</option>
            <option value="existing_cpt" <?php selected( $mode, self::MODE_EXISTING_CPT ); ?>>Istniejacy CPT</option>
            <option value="recipes_cpt" <?php selected( $mode, self::MODE_RECIPES_CPT ); ?>>Dedykowany CPT recipe</option>
        </select>
        <?php
    }

    public function render_existing_post_type_field(): void {
        $options = $this->get_options();
        $value   = isset( $options['existing_post_type'] ) ? (string) $options['existing_post_type'] : 'post';
        ?>
        <input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[existing_post_type]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description">Uzywane tylko dla trybu "Istniejacy CPT". Podaj klucz post type, np. recipe lub przepisy.</p>
        <?php
    }

    public function render_dependency_notices(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $strategy = $this->resolve_source_strategy();
        if ( $strategy['fallback_used'] ) {
            echo '<div class="notice notice-warning"><p><strong>GO4TASTE Recipes:</strong> Wybrany existing CPT nie istnieje. Zastosowano fallback do post.</p></div>';
        }

        if ( ! function_exists( 'get_field' ) ) {
            echo '<div class="notice notice-error"><p><strong>GO4TASTE Recipes:</strong> ACF jest wymagane do pelnego dzialania pluginu.</p></div>';
        }

        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if ( function_exists( 'is_plugin_active' ) ) {
            if ( ! is_plugin_active( 'wp-graphql/wp-graphql.php' ) ) {
                echo '<div class="notice notice-warning"><p><strong>GO4TASTE Recipes:</strong> WPGraphQL nie jest aktywny (opcjonalne na tym etapie).</p></div>';
            }

            if ( ! is_plugin_active( 'wpgraphql-acf/wpgraphql-acf.php' ) ) {
                echo '<div class="notice notice-warning"><p><strong>GO4TASTE Recipes:</strong> WPGraphQL for ACF nie jest aktywny (opcjonalne na tym etapie).</p></div>';
            }
        }
    }

    /**
     * @param array<string,mixed> $context
     * @param array<string,mixed> $meta
     * @return array<string,mixed>
     */
    public function filter_single_context( array $context, array $meta = array() ): array {
        $strategy = $this->resolve_source_strategy();
        $post     = get_queried_object();

        $context['recipes'] = $this->base_context_meta( $strategy );
        $context['recipes']['scope'] = 'single';

        if ( ! ( $post instanceof WP_Post ) ) {
            return $context;
        }

        if ( $post->post_type !== $strategy['post_type'] ) {
            return $context;
        }

        $context['is_recipe'] = true;
        $context['recipe']    = $this->build_single_recipe_view_model( $post );

        if ( $this->is_recipe_debug_enabled() ) {
            $parsed_blocks = $this->collect_blocks_debug( $post );
            $panel_payload = array(
                'debug_marker'        => 'g4t-recipes-debug-2026-05-09-fallback-restored-v1',
                'strategy'            => $strategy,
                'environment'         => array(
                    'home_url' => home_url(),
                    'site_url' => site_url(),
                ),
                'queried_post_id'     => (int) $post->ID,
                'queried_post_type'   => (string) $post->post_type,
                'queried_post_status' => (string) $post->post_status,
                'source_debug'        => $this->collect_recipe_source_debug( $post ),
                'revision_debug'      => $this->collect_recipe_revision_debug( $post ),
                'autosave_debug'      => $this->collect_recipe_autosave_debug( $post ),
                'parsed_block_names'  => array_map(
                    static function ( array $block ): string {
                        return isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
                    },
                    array_values(
                        array_filter(
                            $parsed_blocks,
                            static function ( $item ): bool {
                                return is_array( $item );
                            }
                        )
                    )
                ),
                'recipe_model_counts' => array(
                    'ingredients' => isset( $context['recipe']['ingredients'] ) && is_array( $context['recipe']['ingredients'] ) ? count( $context['recipe']['ingredients'] ) : 0,
                    'steps'       => isset( $context['recipe']['steps'] ) && is_array( $context['recipe']['steps'] ) ? count( $context['recipe']['steps'] ) : 0,
                ),
            );

            $json_options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
            if ( defined( 'JSON_PARTIAL_OUTPUT_ON_ERROR' ) ) {
                $json_options |= JSON_PARTIAL_OUTPUT_ON_ERROR;
            }

            $debug_json = wp_json_encode( $panel_payload, $json_options );
            if ( false === $debug_json ) {
                $debug_json = wp_json_encode(
                    array(
                        'debug_marker' => 'g4t-recipes-debug-json-fallback-v1',
                        'error'        => 'wp_json_encode_failed',
                    ),
                    $json_options
                );
            }

            $context['recipes_debug_enabled'] = true;
            $context['recipes_debug_json']    = is_string( $debug_json ) ? $debug_json : '{"error":"debug_json_not_available"}';
            error_log( 'GO4TASTE Recipes Debug: ' . $context['recipes_debug_json'] );
        }

        return $context;
    }

    /**
     * @param array<string,mixed> $context
     * @param array<string,mixed> $meta
     * @return array<string,mixed>
     */
    public function filter_archive_context( array $context, array $meta = array() ): array {
        $strategy = $this->resolve_source_strategy();

        $context['recipes'] = $this->base_context_meta( $strategy );
        $context['recipes']['scope'] = 'archive';
        $context['recipes']['use_archive_recipe_template'] = $this->should_use_archive_recipe_template( $strategy );
        $context['recipe_archive'] = array(
            'title'       => isset( $context['title'] ) ? (string) $context['title'] : '',
            'description' => trim( (string) term_description() ),
            'post_type'   => $strategy['post_type'],
        );

        if ( is_post_type_archive( $strategy['post_type'] ) ) {
            $context['is_recipe_archive'] = true;
        }

        return $context;
    }

    /**
     * @param array<string,mixed> $context
     * @param array<string,mixed> $meta
     * @return array<string,mixed>
     */
    public function filter_home_context( array $context, array $meta = array() ): array {
        $strategy = $this->resolve_source_strategy();

        $context['recipes'] = $this->base_context_meta( $strategy );
        $context['recipes']['scope'] = 'home';
        $context['is_recipe_home'] = true;
        $context['recipe_home'] = array(
            'post_type' => $strategy['post_type'],
        );

        return $context;
    }

    /**
     * @param bool $use_template
     * @param mixed $queried_object
     */
    public function filter_use_archive_template( bool $use_template, $queried_object = null ): bool {
        $strategy = $this->resolve_source_strategy();

        return $this->should_use_archive_recipe_template( $strategy );
    }

    /**
     * @param array<string,mixed> $query_args
     * @return array<string,mixed>
     */
    public function filter_home_query_args( array $query_args = array() ): array {
        $strategy = $this->resolve_source_strategy();

        $defaults = array(
            'post_type'      => $strategy['post_type'],
            'post_status'    => 'publish',
            'paged'          => max( 1, get_query_var( 'paged' ) ),
            'posts_per_page' => (int) get_option( 'posts_per_page' ),
        );

        return wp_parse_args( $query_args, $defaults );
    }

    public function filter_source_post_type( string $post_type = 'post' ): string {
        $strategy = $this->resolve_source_strategy();

        return isset( $strategy['post_type'] ) ? (string) $strategy['post_type'] : $post_type;
    }

    /**
     * Apply default block template for configured recipe post type.
     *
     * @param array<string,mixed> $args
     * @param string              $post_type
     * @return array<string,mixed>
     */
    public function filter_register_post_type_args( array $args, string $post_type ): array {
        $strategy = $this->resolve_source_strategy();

        if ( $post_type !== $strategy['post_type'] ) {
            return $args;
        }

        $args['template'] = array_map(
            static fn( string $block_name ): array => array( $block_name ),
            self::RECIPE_BLOCK_NAMES
        );
        $args['template'][] = array( 'go4taste-ads/inline-banner' );
        $args['template_lock'] = 'contentOnly';

        return $args;
    }

    /**
     * @param array{mode:string,post_type:string,fallback_used:bool} $strategy
     * @return array<string,mixed>
     */
    private function base_context_meta( array $strategy ): array {
        return array(
            'enabled'            => true,
            'source_mode'        => $strategy['mode'],
            'source_post_type'   => $strategy['post_type'],
            'fallback_used'      => $strategy['fallback_used'],
            'acf_available'      => function_exists( 'get_field' ),
            'wpgraphql_available' => defined( 'GRAPHQL_REQUEST' ) || class_exists( 'WPGraphQL\\Router' ),
        );
    }

    /**
     * @param array{mode:string,post_type:string,fallback_used:bool} $strategy
     */
    private function should_use_archive_recipe_template( array $strategy ): bool {
        if ( is_post_type_archive( $strategy['post_type'] ) ) {
            return true;
        }

        if ( is_tax() ) {
            $queried_object = get_queried_object();
            if ( $queried_object instanceof WP_Term ) {
                $taxonomy = get_taxonomy( $queried_object->taxonomy );
                if ( $taxonomy && is_array( $taxonomy->object_type ) ) {
                    return in_array( $strategy['post_type'], $taxonomy->object_type, true );
                }
            }
        }

        if ( self::MODE_POST === $strategy['mode'] && ( is_category() || is_tag() || is_date() ) ) {
            return true;
        }

        return false;
    }

    /**
     * @param WP_Post $post
     * @return array<string,mixed>
     */
    private function build_single_recipe_view_model( WP_Post $post ): array {
        $post_id = (int) $post->ID;
        $block_data = $this->get_recipe_blocks_data( $post );
        $acf_data   = $this->get_recipe_acf_data( $post_id );
        $data       = $this->merge_recipe_data( $block_data, $acf_data );

        $created_timestamp  = (int) get_post_time( 'U', false, $post_id );
        $modified_timestamp = (int) get_post_modified_time( 'U', false, $post_id );

        $date_label = 'Data dodania';
        $date_value = $created_timestamp > 0 ? wp_date( 'j F Y', $created_timestamp ) : '';

        if ( $modified_timestamp > 0 && $created_timestamp > 0 && $modified_timestamp > $created_timestamp ) {
            $date_label = 'Data aktualizacji';
            $date_value = wp_date( 'j F Y', $modified_timestamp );
        }

        $servings_value = $data['servings_value'] ?? null;
        if ( null !== $servings_value && '' !== $servings_value ) {
            $servings_value = is_numeric( (string) $servings_value ) ? (float) $servings_value : null;
        }

        $normalized_ingredients = ( ! empty( $data['ingredients'] ) && is_array( $data['ingredients'] ) ) ? $data['ingredients'] : array();

        $ads_products_raw = $this->acf_field( 'recipe_ads_products', $post_id, array() );
        $ads_product_ids  = array();
        if ( is_array( $ads_products_raw ) ) {
            foreach ( $ads_products_raw as $row ) {
                if ( ! is_array( $row ) || ! isset( $row['product_id'] ) ) {
                    continue;
                }

                $product_id = trim( (string) $row['product_id'] );
                if ( '' !== $product_id ) {
                    $ads_product_ids[] = $product_id;
                }
            }
        }

        return array(
            'id'    => $post_id,
            'hero'  => array(
                'title'   => get_the_title( $post_id ),
                'excerpt' => (string) ( $data['lead'] ?? '' ),
                'image'   => get_the_post_thumbnail_url( $post_id, 'full' ),
            ),
            'times' => array(
                'prep'  => (string) ( $data['time_prep'] ?? '' ),
                'cook'  => (string) ( $data['time_cook'] ?? '' ),
                'total' => (string) ( $data['time_total'] ?? '' ),
            ),
            'difficulty' => (string) ( $data['difficulty'] ?? '' ),
            'servings'   => array(
                'value' => $servings_value,
                'unit'  => (string) ( $data['servings_unit'] ?? '' ),
            ),
            'date' => array(
                'label' => $date_label,
                'value' => $date_value,
            ),
            'ingredients' => $normalized_ingredients,
            'ingredients_photo_url' => (string) ( $data['ingredients_photo_url'] ?? '' ),
            'steps'       => ( ! empty( $data['steps'] ) && is_array( $data['steps'] ) ) ? $data['steps'] : array(),
            'tips'        => (string) ( $data['tips'] ?? '' ),
            'source'      => array(
                'label' => (string) ( $data['source_label'] ?? '' ),
                'url'   => (string) ( $data['source_url'] ?? '' ),
            ),
            'ads'         => array(
                'bottom_product_ids' => $ads_product_ids,
                'products'           => $this->fetch_ads_products( $ads_product_ids, $post_id ),
                'enabled'            => ! empty( $ads_product_ids ),
            ),
        );
    }







    /**
     * @param WP_Post $post
     * @return array<string,mixed>
     */
    private function get_recipe_blocks_data( WP_Post $post ): array {
        if ( ! function_exists( 'parse_blocks' ) ) {
            return array();
        }

        $result = array(
            '_has_recipe_blocks' => false,
            '_has_named_blocks'  => false,
            '_has_hero_block'    => false,
            '_has_ingredients_block' => false,
            '_has_steps_block'   => false,
            'lead'           => '',
            'difficulty'     => '',
            'servings_value' => null,
            'servings_unit'  => '',
            'time_prep'      => '',
            'time_cook'      => '',
            'time_total'     => '',
            'ingredients'    => array(),
            'ingredients_photo_url' => '',
            'steps'          => array(),
            'tips'           => '',
            'source_label'   => '',
            'source_url'     => '',
        );

        $blocks = parse_blocks( (string) $post->post_content );
        $blocks = $this->flatten_blocks( $blocks );

        foreach ( $blocks as $block ) {
            if ( ! is_array( $block ) || empty( $block['blockName'] ) ) {
                continue;
            }

            $result['_has_named_blocks'] = true;

            $attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

            if ( 'go4taste-recipes/hero' === $block['blockName'] ) {
                $result['_has_recipe_blocks'] = true;
                $result['_has_hero_block']    = true;
                $result['lead']           = isset( $attrs['lead'] ) ? (string) $attrs['lead'] : $result['lead'];
                $result['difficulty']     = isset( $attrs['difficulty'] ) ? (string) $attrs['difficulty'] : $result['difficulty'];
                $result['servings_value'] = isset( $attrs['servingsValue'] ) ? $attrs['servingsValue'] : $result['servings_value'];
                $result['servings_unit']  = isset( $attrs['servingsUnit'] ) ? (string) $attrs['servingsUnit'] : $result['servings_unit'];
                $result['time_prep']      = isset( $attrs['timePrep'] ) ? (string) $attrs['timePrep'] : $result['time_prep'];
                $result['time_cook']      = isset( $attrs['timeCook'] ) ? (string) $attrs['timeCook'] : $result['time_cook'];
                $result['time_total']     = isset( $attrs['timeTotal'] ) ? (string) $attrs['timeTotal'] : $result['time_total'];
            }

            if ( 'go4taste-recipes/ingredients' === $block['blockName'] ) {
                $result['_has_recipe_blocks'] = true;
                $result['_has_ingredients_block'] = true;
                if ( isset( $attrs['itemsList'] ) && is_array( $attrs['itemsList'] ) ) {
                    $result['ingredients'] = $this->normalize_ingredients_list( $attrs['itemsList'] );
                } else {
                    $lines = $this->normalize_multiline_to_list( isset( $attrs['items'] ) ? (string) $attrs['items'] : '' );
                    $result['ingredients'] = array_map(
                        static function ( string $line ): array {
                            return array(
                                'name'   => $line,
                                'amount' => null,
                                'unit'   => '',
                            );
                        },
                        $lines
                    );
                }
                $result['ingredients_photo_url'] = isset( $attrs['photoUrl'] ) ? (string) $attrs['photoUrl'] : '';
            }

            if ( 'go4taste-recipes/steps' === $block['blockName'] ) {
                $result['_has_recipe_blocks'] = true;
                $result['_has_steps_block']   = true;
                if ( isset( $attrs['stepsList'] ) && is_array( $attrs['stepsList'] ) ) {
                    $result['steps'] = $this->normalize_steps_list( $attrs['stepsList'] );
                } else {
                    $result['steps'] = $this->normalize_multiline_to_list( isset( $attrs['steps'] ) ? (string) $attrs['steps'] : '' );
                }
            }

            if ( 'go4taste-recipes/notes' === $block['blockName'] ) {
                $result['_has_recipe_blocks'] = true;
                $result['tips']         = isset( $attrs['tips'] ) ? (string) $attrs['tips'] : $result['tips'];
                $result['source_label'] = isset( $attrs['sourceLabel'] ) ? (string) $attrs['sourceLabel'] : $result['source_label'];
                $result['source_url']   = isset( $attrs['sourceUrl'] ) ? (string) $attrs['sourceUrl'] : $result['source_url'];
            }

            if ( 'go4taste-recipes/tips' === $block['blockName'] ) {
                $result['_has_recipe_blocks'] = true;
                $result['tips'] = isset( $attrs['tips'] ) ? (string) $attrs['tips'] : $result['tips'];
            }

            if ( 'go4taste-recipes/source' === $block['blockName'] ) {
                $result['_has_recipe_blocks'] = true;
                $result['source_label'] = isset( $attrs['sourceLabel'] ) ? (string) $attrs['sourceLabel'] : $result['source_label'];
                $result['source_url']   = isset( $attrs['sourceUrl'] ) ? (string) $attrs['sourceUrl'] : $result['source_url'];
            }
        }

        return $result;
    }

    /**
     * @return array<string,mixed>
     */
    private function get_recipe_acf_data( int $post_id ): array {
        $ingredients_raw = $this->acf_field( 'recipe_ingredients', $post_id, array() );
        $steps_raw       = $this->acf_field( 'recipe_steps', $post_id, array() );

        return array(
            'lead'                  => (string) $this->acf_field( 'recipe_lead', $post_id, '' ),
            'difficulty'            => (string) $this->acf_field( 'recipe_difficulty', $post_id, '' ),
            'servings_value'        => $this->acf_field( 'recipe_servings_value', $post_id, null ),
            'servings_unit'         => (string) $this->acf_field( 'recipe_servings_unit', $post_id, '' ),
            'time_prep'             => (string) $this->acf_field( 'recipe_time_prep', $post_id, '' ),
            'time_cook'             => (string) $this->acf_field( 'recipe_time_cook', $post_id, '' ),
            'time_total'            => (string) $this->acf_field( 'recipe_time_total', $post_id, '' ),
            'ingredients'           => is_array( $ingredients_raw ) ? $this->normalize_ingredients_list( $ingredients_raw ) : array(),
            'ingredients_photo_url' => (string) $this->acf_field( 'recipe_ingredients_photo_url', $post_id, '' ),
            'steps'                 => is_array( $steps_raw ) ? $this->normalize_steps_list( $steps_raw ) : array(),
            'tips'                  => (string) $this->acf_field( 'recipe_tips', $post_id, '' ),
            'source_label'          => (string) $this->acf_field( 'recipe_source_label', $post_id, '' ),
            'source_url'            => (string) $this->acf_field( 'recipe_source_url', $post_id, '' ),
        );
    }

    /**
     * @param array<string,mixed> $primary
     * @param array<string,mixed> $fallback
     * @return array<string,mixed>
     */
    private function merge_recipe_data( array $primary, array $fallback ): array {
        $result = $primary;
        $keys   = array(
            'lead',
            'difficulty',
            'servings_value',
            'servings_unit',
            'time_prep',
            'time_cook',
            'time_total',
            'ingredients',
            'ingredients_photo_url',
            'steps',
            'tips',
            'source_label',
            'source_url',
        );

        foreach ( $keys as $key ) {
            $value = $result[ $key ] ?? null;
            if ( ! $this->has_value( $value ) && isset( $fallback[ $key ] ) ) {
                $result[ $key ] = $fallback[ $key ];
            }
        }

        return $result;
    }

    /**
     * @param array<int,mixed> $blocks
     * @return array<int,mixed>
     */
    private function flatten_blocks( array $blocks ): array {
        $flat = array();

        foreach ( $blocks as $block ) {
            if ( ! is_array( $block ) ) {
                continue;
            }

            $flat[] = $block;

            if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) ) {
                $flat = array_merge( $flat, $this->flatten_blocks( $block['innerBlocks'] ) );
            }
        }

        return $flat;
    }

    /**
     * @param array<int,mixed> $items
     * @return array<int,array{name:string,amount:float|null,unit:string}>
     */
    private function normalize_ingredients_list( array $items ): array {
        $normalized = array();

        foreach ( $items as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }

            $name = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
            if ( '' === $name ) {
                continue;
            }

            $amount = null;
            if ( isset( $row['amount'] ) && '' !== trim( (string) $row['amount'] ) && is_numeric( (string) $row['amount'] ) ) {
                $amount = (float) $row['amount'];
            }

            $normalized[] = array(
                'name'   => $name,
                'amount' => $amount,
                'unit'   => isset( $row['unit'] ) ? trim( (string) $row['unit'] ) : '',
            );
        }

        return $normalized;
    }

    /**
     * @param array<int,mixed> $steps
     * @return array<int,array{title:string,description:string,tip:string,images:array<int,string>}>
     */
    private function normalize_steps_list( array $steps ): array {
        $normalized = array();

        foreach ( $steps as $index => $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }

            $title       = isset( $row['heading'] ) ? trim( (string) $row['heading'] ) : '';
            if ( '' === $title && isset( $row['title'] ) ) {
                $title = trim( (string) $row['title'] );
            }

            $description = isset( $row['description'] ) ? trim( (string) $row['description'] ) : '';
            if ( '' === $description && isset( $row['text'] ) ) {
                $description = trim( (string) $row['text'] );
            }
            if ( '' === $description && isset( $row['step'] ) ) {
                $description = trim( (string) $row['step'] );
            }

            $tip         = isset( $row['tip'] ) ? trim( (string) $row['tip'] ) : '';
            $images      = $this->normalize_images_value( $row['images'] ?? array() );

            // imageUrl from block editor (single image per step)
            if ( empty( $images ) && ! empty( $row['imageUrl'] ) ) {
                $url = esc_url_raw( trim( (string) $row['imageUrl'] ) );
                if ( '' !== $url ) {
                    $images = array( $url );
                }
            }

            if ( '' === $description ) {
                continue;
            }

            if ( '' === $title ) {
                $title = sprintf( 'Krok %d', $index + 1 );
            }

            $normalized[] = array(
                'title'       => $title,
                'description' => $description,
                'tip'         => $tip,
                'images'      => $images,
            );
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function normalize_images_value( $value ): array {
        if ( is_string( $value ) ) {
            return $this->normalize_images_input( $value );
        }

        if ( ! is_array( $value ) ) {
            return array();
        }

        $items = array();

        foreach ( $value as $item ) {
            if ( is_string( $item ) ) {
                $url = trim( $item );
                if ( '' !== $url ) {
                    $items[] = $url;
                }
                continue;
            }

            if ( is_numeric( $item ) ) {
                $attachment_url = wp_get_attachment_url( (int) $item );
                if ( $attachment_url ) {
                    $items[] = (string) $attachment_url;
                }
                continue;
            }

            if ( is_array( $item ) ) {
                if ( isset( $item['url'] ) && '' !== trim( (string) $item['url'] ) ) {
                    $items[] = trim( (string) $item['url'] );
                    continue;
                }

                if ( isset( $item['id'] ) && is_numeric( $item['id'] ) ) {
                    $attachment_url = wp_get_attachment_url( (int) $item['id'] );
                    if ( $attachment_url ) {
                        $items[] = (string) $attachment_url;
                    }
                }
            }
        }

        $items = array_values( array_unique( $items ) );

        return array_values( array_filter( $items, static fn( string $url ): bool => '' !== $url ) );
    }

    /**
     * @return array<int,string>
     */
    private function normalize_images_input( string $raw ): array {
        if ( '' === trim( $raw ) ) {
            return array();
        }

        $items = preg_split( '/\r\n|\r|\n|,/', $raw );
        if ( ! is_array( $items ) ) {
            return array();
        }

        $items = array_map( static fn( string $line ): string => trim( $line ), $items );
        $items = array_filter( $items, static fn( string $line ): bool => '' !== $line );

        return array_values( $items );
    }

    private function is_recipe_debug_enabled(): bool {
        if ( ! isset( $_GET['g4t_recipe_debug'] ) ) {
            return false;
        }

        if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
            return true;
        }

        if ( ! function_exists( 'current_user_can' ) ) {
            return false;
        }

        return current_user_can( 'manage_options' );
    }

    /**
     * @param WP_Post $post
     * @return array<int,array<string,mixed>>
     */
    private function collect_blocks_debug( WP_Post $post ): array {
        if ( ! function_exists( 'parse_blocks' ) ) {
            return array();
        }

        $blocks = parse_blocks( (string) $post->post_content );
        $blocks = $this->flatten_blocks( $blocks );

        $debug_blocks = array();
        foreach ( $blocks as $index => $block ) {
            if ( ! is_array( $block ) ) {
                continue;
            }

            $debug_blocks[] = array(
                'index'       => $index,
                'blockName'   => isset( $block['blockName'] ) ? (string) $block['blockName'] : '',
                'attrs'       => isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array(),
                'inner_count' => isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? count( $block['innerBlocks'] ) : 0,
            );
        }

        return $debug_blocks;
    }

    /**
     * @param WP_Post $post
     * @return array<string,mixed>
     */
    private function collect_recipe_source_debug( WP_Post $post ): array {
        $post_id     = (int) $post->ID;
        $post_content = (string) $post->post_content;
        $block_data  = $this->get_recipe_blocks_data( $post );
        $has_blocks  = ! empty( $block_data['_has_recipe_blocks'] );
        $data_source = $has_blocks ? 'blocks' : 'no_blocks';

        $recipe_blocks_present = array();
        if ( ! empty( $block_data['_has_hero_block'] ) ) {
            $recipe_blocks_present[] = 'hero';
        }
        if ( ! empty( $block_data['_has_ingredients_block'] ) ) {
            $recipe_blocks_present[] = 'ingredients';
        }
        if ( ! empty( $block_data['_has_steps_block'] ) ) {
            $recipe_blocks_present[] = 'steps';
        }

        return array(
            'data_source'                       => $data_source,
            'has_recipe_blocks'                 => $has_blocks,
            'current_content_recipe_marker_count' => substr_count( $post_content, '<!-- wp:go4taste-recipes/' ),
            'recipe_blocks_present'             => $recipe_blocks_present,
        );
    }

    /**
     * @param WP_Post $post
     * @return array<string,mixed>
     */
    private function collect_recipe_revision_debug( WP_Post $post ): array {
        if ( ! function_exists( 'wp_get_post_revisions' ) ) {
            return array( 'available' => false );
        }

        $revisions = wp_get_post_revisions(
            (int) $post->ID,
            array(
                'posts_per_page' => 1,
                'order'          => 'DESC',
                'orderby'        => 'date',
            )
        );

        if ( empty( $revisions ) ) {
            return array(
                'available'        => true,
                'has_latest'       => false,
                'current_modified' => (string) $post->post_modified_gmt,
            );
        }

        $latest = array_values( $revisions )[0];
        if ( ! ( $latest instanceof WP_Post ) ) {
            return array(
                'available'  => true,
                'has_latest' => false,
            );
        }

        $analysis = $this->analyze_block_content( (string) $latest->post_content );

        return array(
            'available'              => true,
            'has_latest'             => true,
            'current_modified_gmt'   => (string) $post->post_modified_gmt,
            'latest_revision_id'     => (int) $latest->ID,
            'latest_revision_date_gmt' => (string) $latest->post_date_gmt,
            'latest_revision_author' => (int) $latest->post_author,
            'latest_revision_analysis' => $analysis,
        );
    }

    /**
     * @param WP_Post $post
     * @return array<string,mixed>
     */
    private function collect_recipe_autosave_debug( WP_Post $post ): array {
        if ( ! function_exists( 'wp_get_post_autosave' ) || ! function_exists( 'get_current_user_id' ) ) {
            return array( 'available' => false );
        }

        $autosave = wp_get_post_autosave( (int) $post->ID, get_current_user_id() );
        if ( ! ( $autosave instanceof WP_Post ) ) {
            return array(
                'available'   => true,
                'has_autosave' => false,
            );
        }

        return array(
            'available'          => true,
            'has_autosave'       => true,
            'autosave_id'        => (int) $autosave->ID,
            'autosave_date_gmt'  => (string) $autosave->post_date_gmt,
            'autosave_author'    => (int) $autosave->post_author,
            'autosave_analysis'  => $this->analyze_block_content( (string) $autosave->post_content ),
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function analyze_block_content( string $content ): array {
        $recipe_blocks = array();
        $has_named_blocks = false;

        if ( function_exists( 'parse_blocks' ) ) {
            $blocks = $this->flatten_blocks( parse_blocks( $content ) );
            foreach ( $blocks as $block ) {
                if ( ! is_array( $block ) || empty( $block['blockName'] ) ) {
                    continue;
                }

                $has_named_blocks = true;
                $block_name = (string) $block['blockName'];
                if ( 0 === strpos( $block_name, 'go4taste-recipes/' ) ) {
                    $recipe_blocks[] = $block_name;
                }
            }
        }

        return array(
            'content_length'      => strlen( $content ),
            'has_named_blocks'    => $has_named_blocks,
            'has_recipe_blocks'   => ! empty( $recipe_blocks ),
            'recipe_block_names'  => array_values( array_unique( $recipe_blocks ) ),
            'recipe_marker_count' => substr_count( $content, '<!-- wp:go4taste-recipes/' ),
        );
    }

    /**
     * @return array<int,string>
     */
    private function normalize_multiline_to_list( string $raw ): array {
        if ( '' === trim( $raw ) ) {
            return array();
        }

        $items = preg_split( '/\r\n|\r|\n/', $raw );
        if ( ! is_array( $items ) ) {
            return array();
        }

        $items = array_map( static fn( string $line ): string => trim( $line ), $items );
        $items = array_filter( $items, static fn( string $line ): bool => '' !== $line );

        return array_values( $items );
    }

    /**
     * @param array<int,string> $product_ids
     * @param int               $post_id
     * @return array<int,mixed>
     */
    private function fetch_ads_products( array $product_ids, int $post_id ): array {
        if ( empty( $product_ids ) || ! class_exists( 'Go4Taste_Ads' ) ) {
            return array();
        }

        $options = get_option( 'go4taste_ads_options' );
        $p_value  = isset( $options['p_parameter_value'] ) ? (string) $options['p_parameter_value'] : '';
        $lang     = isset( $options['prestashop_lang'] ) ? (string) $options['prestashop_lang'] : 'pl';
        $api_type = isset( $options['api_type'] ) ? (string) $options['api_type'] : 'prestashop_1_5';
        $api_url  = isset( $options['api_url'] ) ? (string) $options['api_url'] : '';

        $plugin_instance = Go4Taste_Ads::get_instance();
        $products        = array();

        foreach ( $product_ids as $product_id ) {
            $product_id = trim( (string) $product_id );
            if ( '' === $product_id ) {
                continue;
            }

            $product_data = $plugin_instance->fetch_product_data( $product_id, $p_value, $lang, $api_type, $api_url );
            if ( $product_data ) {
                $products[] = $product_data;
            }
        }

        return $products;
    }

    /**
     * @param string $field_key
     * @param int    $post_id
     * @param mixed  $default
     * @return mixed
     */
    private function acf_field( string $field_key, int $post_id, $default = null ) {
        if ( function_exists( 'get_field' ) ) {
            $value = get_field( $field_key, $post_id );
            if ( $this->has_value( $value ) ) {
                return $value;
            }
        }

        $meta = get_post_meta( $post_id, $field_key, true );

        return $this->has_value( $meta ) ? $meta : $default;
    }

    /**
     * @param mixed $value
     */
    private function has_value( $value ): bool {
        if ( is_array( $value ) ) {
            return ! empty( $value );
        }

        if ( is_string( $value ) ) {
            return '' !== trim( $value );
        }

        return null !== $value && false !== $value;
    }
}
