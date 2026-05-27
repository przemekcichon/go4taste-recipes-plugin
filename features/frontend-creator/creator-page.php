<?php
/**
 * GO4TASTE Recipes Plugin — Recipe Creator Page Template
 *
 * This file is loaded by the 'template_include' hook when a page has the
 * 'g4t-recipe-creator' template assigned.
 *
 * CRITICAL: acf_form_head() MUST be called before get_header()!
 * This initializes ACF form processing (nonce validation, post save logic, etc).
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ==================== CRITICAL: ACF form initialization ====================
// This must run BEFORE get_header() to properly handle form submission nonces.
acf_form_head();
// ============================================================================

// Get current page/post for context
$current_page = get_queried_object();
$post_id      = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 'new_post'; // phpcs:ignore WordPress.Security.NonceVerification

// Check access permissions
$can_create = g4t_current_user_can_create_recipe();
$can_edit   = false;

// If editing, verify access to that specific post
if ( 'new_post' !== $post_id ) {
    $recipe_post = g4t_get_recipe_for_editing( $post_id );
    $can_edit    = false !== $recipe_post;
}

// Determine form mode
$is_edit_mode = $can_edit && 'new_post' !== $post_id;
$form_config  = g4t_get_recipe_form_config( $post_id );
$force_gate   = ! $can_create;
$gate_email   = '';
$published_post_url = '';
$creator_branding      = g4t_get_recipe_creator_branding();
$creator_brand_mark_url = isset( $creator_branding['mark_image_url'] ) ? (string) $creator_branding['mark_image_url'] : '';
$creator_brand_name     = isset( $creator_branding['name'] ) ? (string) $creator_branding['name'] : '';
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    $gate_email   = $current_user instanceof WP_User ? (string) $current_user->user_email : '';
}

$saved  = isset( $_GET['saved'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['saved'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
if ( '1' === $saved && 'published' === $status ) {
    $published_post_id = isset( $_GET['post_id'] ) && is_numeric( (string) $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
    if ( $published_post_id > 0 ) {
        $published_post_url = (string) get_permalink( $published_post_id );
    }
}

// Match prototype surface: no WP admin bar overlay in the standalone creator UI.
show_admin_bar( false );

// ================================ Render page ================================

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'g4t-recipe-creator-page' ); ?>>
<?php
echo '<div class="g4t-recipe-creator-wrapper" data-g4t-force-gate="' . esc_attr( $force_gate ? '1' : '0' ) . '" data-g4t-user-email="' . esc_attr( $gate_email ) . '">';
echo '<div class="g4t-recipe-creator-context" data-g4t-published-post-url="' . esc_attr( $published_post_url ) . '"></div>';

// Edit mode: verify post exists and is accessible
if ( 'new_post' !== $post_id && ! $can_edit ) {
    echo '<div class="g4t-recipe-creator-error">';
    echo '<p>' . esc_html__( 'Recipe not found or you do not have permission to edit it.', 'go4taste-recipes-plugin' ) . '</p>';
    echo '</div>';
    echo '</div>';
    exit;
}

// Include markup template (6-step wizard)
require_once plugin_dir_path( __FILE__ ) . 'creator-template.php';

// ACF form (handles submission, nonce validation, post saving)
if ( $can_create ) {
    echo '<div class="g4t-acf-form-bridge" hidden>';
    acf_form( $form_config );
    echo '</div>';
}

echo '</div>'; // .g4t-recipe-creator-wrapper

wp_footer();
?>
</body>
</html>
