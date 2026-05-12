<?php
/**
 * GO4TASTE Recipes Plugin — Admin Settings Page
 *
 * Registers the plugin's options page in wp-admin and handles settings validation.
 *
 * Settings control:
 *  - source_mode: Which post type stores recipes (post | existing_cpt | recipes_cpt)
 *  - existing_post_type: CPT key when using existing_cpt mode
 *
 * WHY source_mode is critical:
 *
 * This setting determines which post type receives the recipe block template,
 * taxonomy assignments, and view model context filters. Changing it affects:
 *  - Which admin post list shows recipes
 *  - Which URLs render recipe templates
 *  - Which taxonomies appear in the editor
 *
 * The settings are cached in the main plugin class (get_options()) and cleared
 * when the settings page is saved.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register plugin settings with WordPress Settings API.
 *
 * WHY whitelist-style validation:
 *
 * The sanitize callback (g4t_sanitize_options) uses a whitelist of allowed keys
 * (source_mode, existing_post_type). Any other keys in the submitted $_POST data
 * are silently discarded. This prevents malicious data injection if an attacker
 * adds hidden form fields.
 *
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance for context.
 */
function g4t_register_settings( Go4Taste_Recipes_Plugin $plugin ): void {
    register_setting(
        'go4taste_recipes_options_group',
        Go4Taste_Recipes_Plugin::OPTION_KEY,
        array(
            'type'              => 'array',
            'sanitize_callback' => static function ( $input ) use ( $plugin ) {
                return g4t_sanitize_options( $input, $plugin );
            },
            'default'           => array(
                'source_mode'        => Go4Taste_Recipes_Plugin::MODE_POST,
                'existing_post_type' => 'post',
            ),
        )
    );

    add_settings_section(
        'go4taste_recipes_section',
        __( 'GO4TASTE Recipes Source', 'go4taste-recipes-plugin' ),
        'g4t_render_settings_section',
        'go4taste-recipes-settings'
    );

    add_settings_field(
        'go4taste_recipes_source_mode',
        __( 'Recipes Source Mode', 'go4taste-recipes-plugin' ),
        static function () use ( $plugin ) {
            g4t_render_source_mode_field( $plugin );
        },
        'go4taste-recipes-settings',
        'go4taste_recipes_section'
    );

    add_settings_field(
        'go4taste_recipes_existing_post_type',
        __( 'Existing Post Type Key', 'go4taste-recipes-plugin' ),
        static function () use ( $plugin ) {
            g4t_render_existing_post_type_field( $plugin );
        },
        'go4taste-recipes-settings',
        'go4taste_recipes_section'
    );
}

/**
 * Register the admin menu page for plugin settings.
 */
function g4t_register_options_page( Go4Taste_Recipes_Plugin $plugin ): void {
    add_menu_page(
        __( 'GO4TASTE Recipes', 'go4taste-recipes-plugin' ),
        __( 'GO4TASTE Recipes', 'go4taste-recipes-plugin' ),
        'manage_options',
        'go4taste-recipes-settings',
        static function () use ( $plugin ) {
            g4t_render_options_page( $plugin );
        },
        'dashicons-carrot',
        58
    );
}

/**
 * Render the settings page HTML.
 */
function g4t_render_options_page( Go4Taste_Recipes_Plugin $plugin ): void {
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
 * Sanitize and validate the plugin options input.
 *
 * @param mixed $input
 * @return array<string,string>
 */
function g4t_sanitize_options( $input, Go4Taste_Recipes_Plugin $plugin ): array {
    $input = is_array( $input ) ? $input : array();

    $mode = isset( $input['source_mode'] ) ? sanitize_key( (string) $input['source_mode'] ) : Go4Taste_Recipes_Plugin::MODE_POST;
    if ( ! in_array( $mode, array( Go4Taste_Recipes_Plugin::MODE_POST, Go4Taste_Recipes_Plugin::MODE_EXISTING_CPT, Go4Taste_Recipes_Plugin::MODE_RECIPES_CPT ), true ) ) {
        $mode = Go4Taste_Recipes_Plugin::MODE_POST;
    }

    $existing_post_type = isset( $input['existing_post_type'] ) ? sanitize_key( (string) $input['existing_post_type'] ) : 'post';
    if ( '' === $existing_post_type ) {
        $existing_post_type = 'post';
    }

    $plugin->clear_options_cache();

    return array(
        'source_mode'        => $mode,
        'existing_post_type' => $existing_post_type,
    );
}

/**
 * Render the settings section description.
 */
function g4t_render_settings_section(): void {
    echo '<p>Wybierz zrodlo danych recipe: post, istniejacy CPT albo dedykowany CPT recipe.</p>';
}

/**
 * Render the source mode select field.
 */
function g4t_render_source_mode_field( Go4Taste_Recipes_Plugin $plugin ): void {
    $options = $plugin->get_options();
    $mode    = isset( $options['source_mode'] ) ? (string) $options['source_mode'] : Go4Taste_Recipes_Plugin::MODE_POST;
    ?>
    <select name="<?php echo esc_attr( Go4Taste_Recipes_Plugin::OPTION_KEY ); ?>[source_mode]">
        <option value="post" <?php selected( $mode, Go4Taste_Recipes_Plugin::MODE_POST ); ?>>Standardowe post</option>
        <option value="existing_cpt" <?php selected( $mode, Go4Taste_Recipes_Plugin::MODE_EXISTING_CPT ); ?>>Istniejacy CPT</option>
        <option value="recipes_cpt" <?php selected( $mode, Go4Taste_Recipes_Plugin::MODE_RECIPES_CPT ); ?>>Dedykowany CPT recipe</option>
    </select>
    <?php
}

/**
 * Render the existing post type text field.
 */
function g4t_render_existing_post_type_field( Go4Taste_Recipes_Plugin $plugin ): void {
    $options = $plugin->get_options();
    $value   = isset( $options['existing_post_type'] ) ? (string) $options['existing_post_type'] : 'post';
    ?>
    <input type="text" name="<?php echo esc_attr( Go4Taste_Recipes_Plugin::OPTION_KEY ); ?>[existing_post_type]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
    <p class="description">Uzywane tylko dla trybu "Istniejacy CPT". Podaj klucz post type, np. recipe lub przepisy.</p>
    <?php
}

/**
 * Render admin notices for missing dependencies.
 */
function g4t_render_dependency_notices( Go4Taste_Recipes_Plugin $plugin ): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $strategy = $plugin->resolve_source_strategy();
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
