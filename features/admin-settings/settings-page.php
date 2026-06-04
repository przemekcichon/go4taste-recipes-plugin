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
                'source_mode'             => Go4Taste_Recipes_Plugin::MODE_POST,
                'existing_post_type'      => 'post',
                'creator_min_role'        => 'editor',
                'creator_brand_mark'      => 0,
                'creator_brand_name'      => '',
                'creator_heartbeat_roles' => array(),
                'unregister_category'     => false,
                'unregister_post_tag'     => false,
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
        'g4t_settings_field_source_mode',
        'go4taste-recipes-settings',
        'go4taste_recipes_section'
    );

    add_settings_field(
        'go4taste_recipes_existing_post_type',
        __( 'Existing Post Type Key', 'go4taste-recipes-plugin' ),
        'g4t_settings_field_existing_post_type',
        'go4taste-recipes-settings',
        'go4taste_recipes_section'
    );

    add_settings_field(
        'go4taste_recipes_creator_min_role',
        __( 'Recipe Creator Minimum Role', 'go4taste-recipes-plugin' ),
        'g4t_settings_field_creator_min_role',
        'go4taste-recipes-settings',
        'go4taste_recipes_section'
    );

    add_settings_field(
        'go4taste_recipes_creator_brand_mark',
        __( 'Recipe Creator Brand Mark', 'go4taste-recipes-plugin' ),
        'g4t_settings_field_creator_brand_mark',
        'go4taste-recipes-settings',
        'go4taste_recipes_section'
    );

    add_settings_field(
        'go4taste_recipes_creator_brand_name',
        __( 'Recipe Creator Brand Name', 'go4taste-recipes-plugin' ),
        'g4t_settings_field_creator_brand_name',
        'go4taste-recipes-settings',
        'go4taste_recipes_section'
    );

    add_settings_field(
        'go4taste_recipes_creator_heartbeat_roles',
        __( 'Recipe Creator Heartbeat Autosave Roles', 'go4taste-recipes-plugin' ),
        'g4t_settings_field_creator_heartbeat_roles',
        'go4taste-recipes-settings',
        'go4taste_recipes_section'
    );

    add_settings_section(
        'go4taste_recipes_section_post_type',
        __( 'Post Type Configuration', 'go4taste-recipes-plugin' ),
        'g4t_render_settings_section_post_type',
        'go4taste-recipes-settings'
    );

    add_settings_field(
        'go4taste_recipes_unregister_category',
        __( 'Odepnij Kategorie od wpisów', 'go4taste-recipes-plugin' ),
        'g4t_settings_field_unregister_category',
        'go4taste-recipes-settings',
        'go4taste_recipes_section_post_type'
    );

    add_settings_field(
        'go4taste_recipes_unregister_post_tag',
        __( 'Odepnij Tagi od wpisów', 'go4taste-recipes-plugin' ),
        'g4t_settings_field_unregister_post_tag',
        'go4taste-recipes-settings',
        'go4taste_recipes_section_post_type'
    );
}

/**
 * Named callback wrappers for add_settings_field — no anonymous closures allowed per coding conventions.
 * Each retrieves the plugin instance from the global and delegates to the render function.
 */
function g4t_settings_field_source_mode(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_render_source_mode_field( $g4t_recipes_plugin );
    }
}

function g4t_settings_field_existing_post_type(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_render_existing_post_type_field( $g4t_recipes_plugin );
    }
}

function g4t_settings_field_creator_min_role(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_render_creator_min_role_field( $g4t_recipes_plugin );
    }
}

function g4t_settings_field_creator_brand_mark(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_render_creator_brand_mark_field( $g4t_recipes_plugin );
    }
}

function g4t_settings_field_creator_brand_name(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_render_creator_brand_name_field( $g4t_recipes_plugin );
    }
}

function g4t_settings_field_creator_heartbeat_roles(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_render_creator_heartbeat_roles_field( $g4t_recipes_plugin );
    }
}

function g4t_settings_field_unregister_category(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_render_unregister_taxonomy_field( $g4t_recipes_plugin, 'unregister_category' );
    }
}

function g4t_settings_field_unregister_post_tag(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_render_unregister_taxonomy_field( $g4t_recipes_plugin, 'unregister_post_tag' );
    }
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
 * Enqueue assets for plugin settings page.
 */
function g4t_enqueue_settings_assets( string $hook_suffix ): void {
    if ( 'toplevel_page_go4taste-recipes-settings' !== $hook_suffix ) {
        return;
    }

    wp_enqueue_media();
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
 * @return array<string,mixed>
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

    $creator_min_role = isset( $input['creator_min_role'] ) ? sanitize_key( (string) $input['creator_min_role'] ) : 'editor';
    $allowed_roles    = array( 'subscriber', 'contributor', 'author', 'editor', 'administrator' );
    if ( ! in_array( $creator_min_role, $allowed_roles, true ) ) {
        $creator_min_role = 'editor';
    }

    $creator_brand_mark = isset( $input['creator_brand_mark'] ) ? absint( $input['creator_brand_mark'] ) : 0;

    $creator_brand_name = isset( $input['creator_brand_name'] ) ? sanitize_text_field( (string) $input['creator_brand_name'] ) : '';
    if ( function_exists( 'mb_substr' ) ) {
        $creator_brand_name = trim( mb_substr( $creator_brand_name, 0, 80 ) );
    } else {
        $creator_brand_name = trim( substr( $creator_brand_name, 0, 80 ) );
    }

    $creator_heartbeat_roles = array();
    if ( isset( $input['creator_heartbeat_roles'] ) && is_array( $input['creator_heartbeat_roles'] ) ) {
        foreach ( $input['creator_heartbeat_roles'] as $role ) {
            $role = sanitize_key( (string) $role );
            if ( in_array( $role, $allowed_roles, true ) ) {
                $creator_heartbeat_roles[] = $role;
            }
        }
        $creator_heartbeat_roles = array_values( array_unique( $creator_heartbeat_roles ) );
    }

    // Checkboxes are absent from $_POST when unchecked — treat absence as false.
    $unregister_category = ! empty( $input['unregister_category'] );
    $unregister_post_tag = ! empty( $input['unregister_post_tag'] );

    g4t_clear_recipe_options_cache();

    return array(
        'source_mode'             => $mode,
        'existing_post_type'      => $existing_post_type,
        'creator_min_role'        => $creator_min_role,
        'creator_brand_mark'      => $creator_brand_mark,
        'creator_brand_name'      => $creator_brand_name,
        'creator_heartbeat_roles' => $creator_heartbeat_roles,
        'unregister_category'     => $unregister_category,
        'unregister_post_tag'     => $unregister_post_tag,
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
    $options = g4t_get_recipe_options();
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
    $options = g4t_get_recipe_options();
    $value   = isset( $options['existing_post_type'] ) ? (string) $options['existing_post_type'] : 'post';
    ?>
    <input type="text" name="<?php echo esc_attr( Go4Taste_Recipes_Plugin::OPTION_KEY ); ?>[existing_post_type]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
    <p class="description">Uzywane tylko dla trybu "Istniejacy CPT". Podaj klucz post type, np. recipe lub przepisy.</p>
    <?php
}

/**
 * Render the creator minimum role select field.
 */
function g4t_render_creator_min_role_field( Go4Taste_Recipes_Plugin $plugin ): void {
    $options = g4t_get_recipe_options();
    $role    = isset( $options['creator_min_role'] ) ? (string) $options['creator_min_role'] : 'editor';
    ?>
    <select name="<?php echo esc_attr( Go4Taste_Recipes_Plugin::OPTION_KEY ); ?>[creator_min_role]">
        <option value="subscriber" <?php selected( $role, 'subscriber' ); ?>><?php esc_html_e( 'Subscriber', 'go4taste-recipes-plugin' ); ?></option>
        <option value="contributor" <?php selected( $role, 'contributor' ); ?>><?php esc_html_e( 'Contributor', 'go4taste-recipes-plugin' ); ?></option>
        <option value="author" <?php selected( $role, 'author' ); ?>><?php esc_html_e( 'Author', 'go4taste-recipes-plugin' ); ?></option>
        <option value="editor" <?php selected( $role, 'editor' ); ?>><?php esc_html_e( 'Editor', 'go4taste-recipes-plugin' ); ?></option>
        <option value="administrator" <?php selected( $role, 'administrator' ); ?>><?php esc_html_e( 'Administrator', 'go4taste-recipes-plugin' ); ?></option>
    </select>
    <p class="description"><?php esc_html_e( 'Minimum role required to create and edit recipes via the front-end form.', 'go4taste-recipes-plugin' ); ?></p>
    <?php
}

/**
 * Render the creator brand mark image field.
 */
function g4t_render_creator_brand_mark_field( Go4Taste_Recipes_Plugin $plugin ): void {
    $options        = g4t_get_recipe_options();
    $attachment_id  = isset( $options['creator_brand_mark'] ) ? absint( $options['creator_brand_mark'] ) : 0;
    $attachment_url = $attachment_id > 0 ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
    ?>
    <div class="g4t-brand-mark-wrap" data-input-name="<?php echo esc_attr( Go4Taste_Recipes_Plugin::OPTION_KEY ); ?>[creator_brand_mark]">
        <input type="hidden" class="g4t-brand-mark-id" name="<?php echo esc_attr( Go4Taste_Recipes_Plugin::OPTION_KEY ); ?>[creator_brand_mark]" value="<?php echo esc_attr( (string) $attachment_id ); ?>" />
        <div class="g4t-brand-mark-preview" style="margin-bottom:10px;">
            <img src="<?php echo esc_url( $attachment_url ? $attachment_url : '' ); ?>" alt="" style="max-width:80px; max-height:80px; border-radius:10px; border:1px solid #dcdcde; display:<?php echo $attachment_url ? 'block' : 'none'; ?>;" />
        </div>
        <button type="button" class="button g4t-brand-mark-select"><?php esc_html_e( 'Wybierz obrazek', 'go4taste-recipes-plugin' ); ?></button>
        <button type="button" class="button g4t-brand-mark-remove" style="margin-left:8px;" <?php disabled( 0 === $attachment_id ); ?>><?php esc_html_e( 'Usuń', 'go4taste-recipes-plugin' ); ?></button>
    </div>
    <p class="description"><?php esc_html_e( 'Logo/ikonka do topbara kreatora (wybór z biblioteki mediów).', 'go4taste-recipes-plugin' ); ?></p>
    <script>
      (function(){
        var script = document.currentScript;
        var root = script && script.parentElement ? script.parentElement.querySelector('.g4t-brand-mark-wrap') : null;
        if (!root || root.__g4tBound) { return; }

        var input = root.querySelector('.g4t-brand-mark-id');
        var selectBtn = root.querySelector('.g4t-brand-mark-select');
        var removeBtn = root.querySelector('.g4t-brand-mark-remove');
        var previewImg = root.querySelector('.g4t-brand-mark-preview img');
        if (!input || !selectBtn || !removeBtn || !previewImg) { return; }

        root.__g4tBound = true;
        var frame = null;

        function setPreview(url, id) {
          input.value = id ? String(id) : '0';
          if (url) {
            previewImg.src = url;
            previewImg.style.display = 'block';
            removeBtn.disabled = false;
          } else {
            previewImg.removeAttribute('src');
            previewImg.style.display = 'none';
            removeBtn.disabled = true;
          }
        }

        selectBtn.addEventListener('click', function(){
          if (typeof wp === 'undefined' || !wp.media) { return; }
          if (!frame) {
            frame = wp.media({
              title: 'Wybierz obrazek',
              button: { text: 'Użyj tego obrazka' },
              multiple: false,
              library: { type: 'image' }
            });
            frame.on('select', function(){
              var attachment = frame.state().get('selection').first().toJSON();
              setPreview((attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url), attachment.id || 0);
            });
          }
          frame.open();
        });

        removeBtn.addEventListener('click', function(){
          setPreview('', 0);
        });
      })();
    </script>
    <?php
}

/**
 * Render the creator brand name text field.
 */
function g4t_render_creator_brand_name_field( Go4Taste_Recipes_Plugin $plugin ): void {
    $options = g4t_get_recipe_options();
    $value   = isset( $options['creator_brand_name'] ) ? (string) $options['creator_brand_name'] : '';
    ?>
    <input type="text" name="<?php echo esc_attr( Go4Taste_Recipes_Plugin::OPTION_KEY ); ?>[creator_brand_name]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" maxlength="80" />
    <p class="description"><?php esc_html_e( 'Brand name shown next to the logo mark in the recipe creator top bar.', 'go4taste-recipes-plugin' ); ?></p>
    <?php
}

/**
 * Render heartbeat autosave roles checkboxes.
 */
function g4t_render_creator_heartbeat_roles_field( Go4Taste_Recipes_Plugin $plugin ): void {
    $options        = g4t_get_recipe_options();
    $enabled_roles  = isset( $options['creator_heartbeat_roles'] ) && is_array( $options['creator_heartbeat_roles'] )
        ? array_map( 'sanitize_key', $options['creator_heartbeat_roles'] )
        : array();
    $all_roles = array(
        'subscriber'    => __( 'Subscriber', 'go4taste-recipes-plugin' ),
        'contributor'   => __( 'Contributor', 'go4taste-recipes-plugin' ),
        'author'        => __( 'Author', 'go4taste-recipes-plugin' ),
        'editor'        => __( 'Editor', 'go4taste-recipes-plugin' ),
        'administrator' => __( 'Administrator', 'go4taste-recipes-plugin' ),
    );

    foreach ( $all_roles as $role_key => $role_label ) {
        $input_id = 'g4t_creator_heartbeat_role_' . $role_key;
        ?>
        <label for="<?php echo esc_attr( $input_id ); ?>" style="display:block; margin-bottom:6px;">
            <input
                type="checkbox"
                id="<?php echo esc_attr( $input_id ); ?>"
                name="<?php echo esc_attr( Go4Taste_Recipes_Plugin::OPTION_KEY ); ?>[creator_heartbeat_roles][]"
                value="<?php echo esc_attr( $role_key ); ?>"
                <?php checked( in_array( $role_key, $enabled_roles, true ) ); ?>
            />
            <?php echo esc_html( $role_label ); ?>
        </label>
        <?php
    }

    echo '<p class="description">';
    esc_html_e( 'Dla zaznaczonych ról kreator uruchomi heartbeat i będzie okresowo zapisywał wersję roboczą (draft).', 'go4taste-recipes-plugin' );
    echo '</p>';
}

/**
 * Render the Post Type Configuration section description.
 */
function g4t_render_settings_section_post_type(): void {
    echo '<p>' . esc_html__( 'Kontrola wbudowanych taksonomii WordPressa. Dotyczy tylko trybu "Standardowe post".', 'go4taste-recipes-plugin' ) . '</p>';
}

/**
 * Render a single taxonomy unregister checkbox field.
 *
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance.
 * @param string                  $key    Option key: 'unregister_category' or 'unregister_post_tag'.
 */
function g4t_render_unregister_taxonomy_field( Go4Taste_Recipes_Plugin $plugin, string $key ): void {
    $options  = g4t_get_recipe_options();
    $checked  = ! empty( $options[ $key ] );
    $input_id = 'g4t_' . $key;

    $descriptions = array(
        'unregister_category' => __( 'WordPress nie będzie wymuszał przypisania "Uncategorized" do wpisów bez kategorii. Metabox Kategorie znika z edytora.', 'go4taste-recipes-plugin' ),
        'unregister_post_tag' => __( 'Metabox Tagi znika z edytora wpisów.', 'go4taste-recipes-plugin' ),
    );
    $desc = $descriptions[ $key ] ?? '';
    ?>
    <label for="<?php echo esc_attr( $input_id ); ?>">
        <input
            type="checkbox"
            id="<?php echo esc_attr( $input_id ); ?>"
            name="<?php echo esc_attr( Go4Taste_Recipes_Plugin::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
            value="1"
            <?php checked( $checked ); ?>
        />
        <?php esc_html_e( 'Tak, odepnij', 'go4taste-recipes-plugin' ); ?>
    </label>
    <?php if ( $desc ) : ?>
        <p class="description"><?php echo esc_html( $desc ); ?></p>
    <?php endif;
}

/**
 * Render admin notices for missing dependencies.
 */
function g4t_render_dependency_notices( Go4Taste_Recipes_Plugin $plugin ): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $strategy = g4t_get_source_strategy();
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

