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

