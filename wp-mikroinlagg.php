<?php
/**
 * Plugin Name: WPBlogTree Mikroinlägg
 * Plugin URI:  https://github.com/ckarlsson/wp-mikroinlagg
 * Description: Mikroinlägg – ett eget socialt flöde på bloggen. Korta inlägg (max 500 tecken) med ämne, taggar, plattform och originallänk.
 * Version:     1.0.0
 * Author:      Christian Karlsson
 * Author URI:  https://karlsson.xyz
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-mikroinlagg
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MIKRO_DIR',     plugin_dir_path( __FILE__ ) );
define( 'MIKRO_URL',     plugin_dir_url( __FILE__ ) );
define( 'MIKRO_VERSION', '1.0.0' );

require_once MIKRO_DIR . 'includes/class-cpt.php';
require_once MIKRO_DIR . 'includes/class-admin.php';
require_once MIKRO_DIR . 'includes/class-widget.php';

// ── Template loader ──────────────────────────────────────────────────────────
add_filter( 'template_include', 'mikro_template_include' );
function mikro_template_include( string $template ): string {
    if ( is_post_type_archive( 'mikroinlagg' ) ) {
        $theme = locate_template( 'archive-mikroinlagg.php' );
        return $theme ?: MIKRO_DIR . 'templates/archive-mikroinlagg.php';
    }
    if ( is_singular( 'mikroinlagg' ) ) {
        $theme = locate_template( 'single-mikroinlagg.php' );
        return $theme ?: MIKRO_DIR . 'templates/single-mikroinlagg.php';
    }
    return $template;
}

// ── Frontend assets ──────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'mikro_enqueue_frontend' );
function mikro_enqueue_frontend(): void {
    if ( is_singular( 'mikroinlagg' ) || is_post_type_archive( 'mikroinlagg' ) ) {
        wp_enqueue_style(
            'wp-mikroinlagg',
            MIKRO_URL . 'assets/css/frontend.css',
            [],
            MIKRO_VERSION
        );
    }
    // Always load widget CSS (widget can appear on any page)
    wp_enqueue_style(
        'wp-mikroinlagg-widget',
        MIKRO_URL . 'assets/css/frontend.css',
        [],
        MIKRO_VERSION
    );
}

// ── Swedish relative time helper ─────────────────────────────────────────────
function mikro_time_ago_sv( int $timestamp ): string {
    $diff = (int) current_time( 'timestamp' ) - $timestamp;

    if ( $diff < 60 ) {
        return 'Just nu';
    }
    if ( $diff < 3600 ) {
        $min = (int) round( $diff / 60 );
        return $min . ' min sedan';
    }
    if ( $diff < 86400 ) {
        $h = (int) round( $diff / 3600 );
        return $h . ' tim sedan';
    }
    if ( $diff < 172800 ) {
        return 'Igår';
    }
    $days = (int) round( $diff / 86400 );
    if ( $days < 7 ) {
        return 'för ' . $days . ' dagar sedan';
    }
    $weeks = (int) round( $diff / 604800 );
    if ( $weeks < 5 ) {
        return 'för ' . $weeks . ' veckor sedan';
    }
    return date_i18n( 'j F Y', $timestamp );
}

// ── Platform icon helper ─────────────────────────────────────────────────────
function mikro_platform_icon( string $slug ): string {
    $icons = [
        'mastodon'   => '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M21.327 8.566c0-4.339-2.843-5.61-2.843-5.61-1.433-.658-3.894-.935-6.451-.956h-.063c-2.557.021-5.016.298-6.45.956 0 0-2.843 1.272-2.843 5.61 0 .993-.019 2.181.012 3.441.103 4.243.778 8.425 4.701 9.463 1.809.479 3.362.579 4.612.51 2.268-.126 3.542-.809 3.542-.809l-.075-1.646s-1.622.511-3.444.449c-1.804-.061-3.707-.191-3.999-2.368a4.514 4.514 0 0 1-.04-.621s1.77.433 4.014.536c1.372.063 2.658-.08 3.965-.236 2.506-.299 4.688-1.843 4.962-3.254.434-2.223.398-5.424.398-5.424zm-3.353 5.59h-2.081V9.057c0-1.075-.452-1.62-1.357-1.62-1 0-1.501.647-1.501 1.927v2.791h-2.069V9.364c0-1.28-.501-1.927-1.502-1.927-.905 0-1.357.545-1.357 1.62v5.099H6.026V8.903c0-1.074.273-1.927.823-2.558.567-.631 1.307-.955 2.228-.955 1.065 0 1.872.409 2.405 1.228l.518.869.519-.869c.533-.819 1.34-1.228 2.405-1.228.92 0 1.661.324 2.228.955.549.631.822 1.484.822 2.558v5.253z"/></svg>',
        'threads'    => '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.471 12.01v-.017c.029-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.018 5.137.791 6.92 2.233 1.785 1.443 2.84 3.47 3.137 6.028l.006.05h-2.543l-.004-.037c-.435-3.277-2.332-4.887-5.78-4.926-2.406.006-4.24.741-5.45 2.185-1.042 1.248-1.58 2.964-1.6 5.106v.017c.02 2.14.558 3.855 1.6 5.1 1.21 1.444 3.043 2.178 5.45 2.184 2.106.01 3.542-.52 4.433-1.62.72-.882 1.12-2.164 1.186-3.813H12.5v-2.28h7.157v1.14c0 2.842-.757 5.081-2.252 6.656-1.496 1.574-3.67 2.37-6.458 2.37-.005 0-.508 0-.761-.015z"/></svg>',
        'facebook'   => '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'mikroblogg' => '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
    ];
    return $icons[ strtolower( $slug ) ] ?? '';
}

// ── Activation / Deactivation ────────────────────────────────────────────────
register_activation_hook( __FILE__, 'mikro_activate' );
function mikro_activate(): void {
    Mikro_CPT::register_post_type();
    Mikro_CPT::register_taxonomies();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'mikro_deactivate' );
function mikro_deactivate(): void {
    flush_rewrite_rules();
}
