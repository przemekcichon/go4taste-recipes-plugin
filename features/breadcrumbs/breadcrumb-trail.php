<?php
/**
 * GO4TASTE Recipes Plugin — Breadcrumb trail
 *
 * Exposes the visible breadcrumb trail to the theme as `breadcrumbs` in the
 * Timber context, so the theme only renders it.
 *
 * WHY Yoast's trail:
 *
 * Yoast SEO prints the `BreadcrumbList` in its schema graph from its
 * breadcrumbs generator (home label and main taxonomy per post type are set
 * in Yoast → Settings → Breadcrumbs). Taking the visible trail from the same
 * generator keeps the UI and the structured data identical. Without Yoast
 * the trail falls back to "Przepisy → current page".
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize raw crumbs into `{text, url}` items.
 *
 * Markup is stripped from texts, crumbs without text are dropped, and the
 * last crumb (the current page) never links.
 *
 * @param array<int,mixed> $crumbs Items with `text` and optional `url` (Yoast generator shape).
 * @return array<int,array{text:string,url:string}>
 */
function g4t_breadcrumbs_normalize( array $crumbs ): array {
    $trail = array();

    foreach ( $crumbs as $crumb ) {
        if ( ! is_array( $crumb ) ) {
            continue;
        }

        $text = trim( html_entity_decode( wp_strip_all_tags( (string) ( $crumb['text'] ?? '' ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        if ( '' === $text ) {
            continue;
        }

        $trail[] = array(
            'text' => $text,
            'url'  => trim( (string) ( $crumb['url'] ?? '' ) ),
        );
    }

    if ( ! empty( $trail ) ) {
        $trail[ count( $trail ) - 1 ]['url'] = '';
    }

    return $trail;
}

/**
 * Fallback trail without Yoast: recipe home, then the current page.
 *
 * @param string $current_title Title of the current page.
 * @return array<int,array{text:string,url:string}>
 */
function g4t_breadcrumbs_fallback( string $current_title ): array {
    return g4t_breadcrumbs_normalize(
        array(
            array(
                'text' => __( 'Przepisy', 'go4taste-recipes-plugin' ),
                'url'  => home_url( '/' ),
            ),
            array( 'text' => $current_title ),
        )
    );
}

/**
 * Raw crumbs from Yoast's breadcrumbs generator for the current page, or null without Yoast.
 *
 * @return array<int,mixed>|null
 */
function g4t_breadcrumbs_yoast_crumbs(): ?array {
    if ( ! function_exists( 'YoastSEO' ) ) {
        return null;
    }

    try {
        $crumbs = YoastSEO()->meta->for_current_page()->breadcrumbs;
    } catch ( Throwable $e ) {
        return null;
    }

    return is_array( $crumbs ) ? $crumbs : null;
}

/**
 * Title of the current page for the fallback trail.
 *
 * @param array<string,mixed> $context
 */
function g4t_breadcrumbs_current_title( array $context ): string {
    $queried_object = get_queried_object();

    if ( $queried_object instanceof WP_Post ) {
        return get_the_title( $queried_object );
    }

    if ( $queried_object instanceof WP_Term ) {
        return $queried_object->name;
    }

    return isset( $context['title'] ) ? (string) $context['title'] : '';
}

/**
 * Filter callback: add `breadcrumbs` to the single and archive contexts.
 *
 * @param array<string,mixed> $context
 * @param array<string,mixed> $meta
 * @return array<string,mixed>
 */
function g4t_breadcrumbs_filter_context( array $context, array $meta = array() ): array {
    $crumbs = g4t_breadcrumbs_yoast_crumbs();
    $trail  = null !== $crumbs ? g4t_breadcrumbs_normalize( $crumbs ) : array();

    if ( empty( $trail ) ) {
        $trail = g4t_breadcrumbs_fallback( g4t_breadcrumbs_current_title( $context ) );
    }

    $context['breadcrumbs'] = $trail;

    return $context;
}
