<?php
/**
 * Save Source Detection & UI Tracking
 *
 * Detects whether a save came from ACF, Block Editor, or other source,
 * and tracks UI switches to warn users about potential data conflicts.
 *
 * @package Go4Taste_Recipes_Plugin
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect where the save request is coming from
 *
 * @return string 'acf'|'blocks'|'reverse_sync'|'unknown'
 */
function g4t_detect_save_source(): string {
	// 1. ACF save (has $_POST['acf'])
	if ( isset( $_POST['acf'] ) && is_array( $_POST['acf'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return 'acf';
	}

	// 2. Block editor (REST API request)
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return 'blocks';
	}

	// 3. Reverse sync (admin action)
	if ( doing_action( 'admin_post_g4t_reverse_sync' ) ) {
		return 'reverse_sync';
	}

	// 4. Fallback: check if post_content has blocks
	global $post;
	if ( $post && strpos( $post->post_content, '<!-- wp:go4taste-recipes/' ) !== false ) {
		return 'blocks';
	}

	// 5. Unknown / programmatic
	return 'unknown';
}

/**
 * Track which UI was last used for editing
 *
 * @param int    $post_id Post ID.
 * @param string $source  Source identifier ('acf', 'blocks', etc.).
 */
function g4t_track_edit_source( int $post_id, string $source ): void {
	update_post_meta( $post_id, '_recipe_last_edit_source', sanitize_key( $source ) );
	update_post_meta( $post_id, '_recipe_last_edit_time', time() );
}

/**
 * Admin notice when user switches between UIs
 *
 * Warns if recipe was edited in different UI within last 24 hours.
 */
function g4t_maybe_show_ui_switch_notice(): void {
	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'post' ) {
		return;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return;
	}

	$last_source = get_post_meta( $post_id, '_recipe_last_edit_source', true );
	$last_time   = (int) get_post_meta( $post_id, '_recipe_last_edit_time', true );

	if ( ! $last_source || ! $last_time ) {
		return;
	}

	// Detect current UI context
	$current_ui = function_exists( 'get_current_screen' ) && $screen->is_block_editor ? 'blocks' : 'acf';

	// Warn if switching UI within last 24h
	if ( $last_source !== $current_ui && ( time() - $last_time ) < DAY_IN_SECONDS ) {
		$ui_names = array(
			'acf'    => __( 'ACF Meta Boxes', 'go4taste-recipes-plugin' ),
			'blocks' => __( 'Block Editor', 'go4taste-recipes-plugin' ),
		);

		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'Uwaga:', 'go4taste-recipes-plugin' ),
			sprintf(
				/* translators: %1$s: previous UI name, %2$s: time ago */
				esc_html__( 'Ten przepis był ostatnio edytowany w %1$s (%2$s temu). Dane mogły ulec zmianie.', 'go4taste-recipes-plugin' ),
				isset( $ui_names[ $last_source ] ) ? esc_html( $ui_names[ $last_source ] ) : esc_html( $last_source ),
				esc_html( human_time_diff( $last_time ) )
			)
		);
	}
}
