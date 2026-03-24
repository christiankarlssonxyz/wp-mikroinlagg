<?php
/**
 * Plugin Name: WPBlogTree Mikroinlägg
 * Plugin URI:  https://github.com/christiankarlssonxyz/wp-mikroinlagg
 * Description: Mikroinlägg – ett eget socialt flöde på bloggen. Korta inlägg (max 500 tecken) med ämne, taggar, plattform och originallänk.
 * Version:     1.2.0
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
define( 'MIKRO_VERSION', '1.2.0' );

// ── GitHub-uppdateringar via plugin-update-checker ────────────────────────────
require_once MIKRO_DIR . 'vendor/plugin-update-checker/load-v5p5.php';
$mikro_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/christiankarlssonxyz/wp-mikroinlagg/',
    __FILE__,
    'wp-mikroinlagg'
);
$mikro_update_checker->setBranch( 'main' );
$mikro_update_checker->getVcsApi()->enableReleaseAssets();

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
    // Widget CSS on all pages
    wp_enqueue_style(
        'wp-mikroinlagg-widget',
        MIKRO_URL . 'assets/css/frontend.css',
        [],
        MIKRO_VERSION
    );

    if ( is_singular( 'mikroinlagg' ) || is_post_type_archive( 'mikroinlagg' ) ) {
        wp_enqueue_style(
            'wp-mikroinlagg',
            MIKRO_URL . 'assets/css/frontend.css',
            [],
            MIKRO_VERSION
        );
    }

    // JS på arkivsidan: compose-modal för redaktörer, gilla/dela för alla inloggade
    if ( is_post_type_archive( 'mikroinlagg' ) && is_user_logged_in() ) {
        wp_enqueue_script(
            'wp-mikroinlagg-compose',
            MIKRO_URL . 'assets/js/compose.js',
            [],
            MIKRO_VERSION,
            true
        );
        wp_localize_script( 'wp-mikroinlagg-compose', 'mikroCompose', [
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'mikro_compose' ),
            'likesNonce'   => wp_create_nonce( 'wpblogtree_nonce' ),
            'commentNonce' => wp_create_nonce( 'mikro_comment' ),
            'isLoggedIn'   => true,
            'canEdit'      => current_user_can( 'edit_posts' ),
        ] );
    } elseif ( is_post_type_archive( 'mikroinlagg' ) ) {
        // Utloggade: bara dela-funktionen (ingen AJAX-nonce behövs)
        wp_enqueue_script(
            'wp-mikroinlagg-compose',
            MIKRO_URL . 'assets/js/compose.js',
            [],
            MIKRO_VERSION,
            true
        );
        wp_localize_script( 'wp-mikroinlagg-compose', 'mikroCompose', [
            'ajaxurl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => '',
            'likesNonce' => '',
            'isLoggedIn' => false,
            'canEdit'    => false,
        ] );
    }
}

// ── AJAX: frontend compose ────────────────────────────────────────────────────
add_action( 'wp_ajax_mikro_compose', 'mikro_ajax_compose' );
function mikro_ajax_compose(): void {
    if ( ! check_ajax_referer( 'mikro_compose', 'nonce', false ) ) {
        wp_send_json_error( 'Säkerhetsfel.', 403 );
    }
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Behörighet saknas.', 403 );
    }

    $content = mb_substr( sanitize_textarea_field( $_POST['content'] ?? '' ), 0, 500 );
    $title   = sanitize_text_field( $_POST['title'] ?? '' );
    $action  = sanitize_key( $_POST['publish_action'] ?? 'draft' );
    $status  = ( $action === 'publish' ) ? 'publish' : 'draft';

    if ( empty( $content ) ) {
        wp_send_json_error( 'Innehåll saknas.' );
    }

    if ( empty( $title ) ) {
        $title = mb_substr( wp_strip_all_tags( $content ), 0, 70 );
        if ( mb_strlen( $content ) > 70 ) $title .= '…';
    }

    $post_id = wp_insert_post( [
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $status,
        'post_type'    => 'mikroinlagg',
        'post_author'  => get_current_user_id(),
    ], true );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( 'Kunde inte spara inlägget.' );
    }

    // Ämne
    if ( ! empty( $_POST['amne'] ) ) {
        wp_set_post_terms( $post_id, [ (int) $_POST['amne'] ], 'mikro_amne' );
    }

    // Plattform
    if ( ! empty( $_POST['plattform'] ) && is_array( $_POST['plattform'] ) ) {
        wp_set_post_terms( $post_id, array_map( 'intval', $_POST['plattform'] ), 'mikro_plattform' );
    }

    // Meta
    if ( ! empty( $_POST['originallank'] ) ) {
        update_post_meta( $post_id, 'mikro_originallank', esc_url_raw( $_POST['originallank'] ) );
    }
    update_post_meta( $post_id, 'mikro_exklusivt', ! empty( $_POST['exklusivt'] ) ? 1 : 0 );
    update_post_meta( $post_id, 'mikro_pinned', 0 );

    wp_send_json_success( [
        'status'    => $status,
        'permalink' => get_permalink( $post_id ),
        'message'   => $status === 'publish' ? 'Mikroinlägg publicerat!' : 'Utkast sparat!',
    ] );
}

// ── AJAX: frontend comment ────────────────────────────────────────────────────
add_action( 'wp_ajax_mikro_comment', 'mikro_ajax_comment' );
function mikro_ajax_comment(): void {
    if ( ! check_ajax_referer( 'mikro_comment', 'nonce', false ) ) {
        wp_send_json_error( 'Säkerhetsfel.', 403 );
    }
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Du måste vara inloggad.', 403 );
    }

    $post_id = (int) ( $_POST['post_id'] ?? 0 );
    $content = sanitize_textarea_field( $_POST['content'] ?? '' );

    if ( ! $post_id || empty( $content ) ) {
        wp_send_json_error( 'Ogiltiga uppgifter.' );
    }

    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'mikroinlagg' ) {
        wp_send_json_error( 'Inlägget hittades inte.' );
    }

    $user = wp_get_current_user();
    $comment_id = wp_insert_comment( [
        'comment_post_ID'      => $post_id,
        'comment_content'      => $content,
        'comment_author'       => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_author_url'   => $user->user_url,
        'user_id'              => $user->ID,
        'comment_approved'     => 1,
        'comment_type'         => 'comment',
    ] );

    if ( ! $comment_id ) {
        wp_send_json_error( 'Kunde inte spara kommentaren.' );
    }

    wp_send_json_success( [
        'message' => 'Svar publicerat!',
        'count'   => (int) get_comments_number( $post_id ),
    ] );
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

// ── Platform helpers ──────────────────────────────────────────────────────────

/** SVG-ikoner indexerade på plattformens slug. Returnerar tom sträng om okänd. */
function mikro_platform_icon( string $slug, int $size = 14 ): string {
    $s = $size;
    $icons = [
        'mastodon'   => '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="currentColor" aria-hidden="true"><path d="M21.327 8.566c0-4.339-2.843-5.61-2.843-5.61-1.433-.658-3.894-.935-6.451-.956h-.063c-2.557.021-5.016.298-6.45.956 0 0-2.843 1.272-2.843 5.61 0 .993-.019 2.181.012 3.441.103 4.243.778 8.425 4.701 9.463 1.809.479 3.362.579 4.612.51 2.268-.126 3.542-.809 3.542-.809l-.075-1.646s-1.622.511-3.444.449c-1.804-.061-3.707-.191-3.999-2.368a4.514 4.514 0 0 1-.04-.621s1.77.433 4.014.536c1.372.063 2.658-.08 3.965-.236 2.506-.299 4.688-1.843 4.962-3.254.434-2.223.398-5.424.398-5.424zm-3.353 5.59h-2.081V9.057c0-1.075-.452-1.62-1.357-1.62-1 0-1.501.647-1.501 1.927v2.791h-2.069V9.364c0-1.28-.501-1.927-1.502-1.927-.905 0-1.357.545-1.357 1.62v5.099H6.026V8.903c0-1.074.273-1.927.823-2.558.567-.631 1.307-.955 2.228-.955 1.065 0 1.872.409 2.405 1.228l.518.869.519-.869c.533-.819 1.34-1.228 2.405-1.228.92 0 1.661.324 2.228.955.549.631.822 1.484.822 2.558v5.253z"/></svg>',
        'threads'    => '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="currentColor" aria-hidden="true"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.471 12.01v-.017c.029-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.018 5.137.791 6.92 2.233 1.785 1.443 2.84 3.47 3.137 6.028l.006.05h-2.543l-.004-.037c-.435-3.277-2.332-4.887-5.78-4.926-2.406.006-4.24.741-5.45 2.185-1.042 1.248-1.58 2.964-1.6 5.106v.017c.02 2.14.558 3.855 1.6 5.1 1.21 1.444 3.043 2.178 5.45 2.184 2.106.01 3.542-.52 4.433-1.62.72-.882 1.12-2.164 1.186-3.813H12.5v-2.28h7.157v1.14c0 2.842-.757 5.081-2.252 6.656-1.496 1.574-3.67 2.37-6.458 2.37-.005 0-.508 0-.761-.015z"/></svg>',
        'facebook'   => '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'x'          => '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'mikroblogg' => '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="currentColor" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>',
    ];
    return $icons[ strtolower( $slug ) ] ?? '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="10"/></svg>';
}

/** Returnerar bakgrundsfärg för plattformens avatar-cirkel. */
function mikro_platform_color( string $slug ): string {
    $colors = [
        'mastodon'   => '#6364ff',
        'threads'    => '#000000',
        'facebook'   => '#1877f2',
        'x'          => '#000000',
        'mikroblogg' => '#15803d',
    ];
    return $colors[ strtolower( $slug ) ] ?? '#2d3748';
}

/** Renderar en cirkulär platform-avatar (44px). */
function mikro_platform_avatar( ?object $platform ): string {
    if ( $platform ) {
        $slug  = sanitize_title( $platform->name );
        $color = mikro_platform_color( $slug );
        $icon  = mikro_platform_icon( $slug, 22 );
        $label = esc_attr( $platform->name );
    } else {
        $color = '#2d3748';
        $icon  = '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
        $label = 'Mikroinlägg';
    }
    return '<span class="mikro-platform-avatar" style="background:' . esc_attr( $color ) . '" aria-label="' . $label . '">' . $icon . '</span>';
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
