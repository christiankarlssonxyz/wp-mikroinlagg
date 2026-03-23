<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mikro_Admin {

    public static function init(): void {
        add_action( 'admin_menu',                      [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_post_mikro_save',           [ __CLASS__, 'handle_save' ] );
        add_action( 'admin_post_mikro_save_settings',  [ __CLASS__, 'handle_save_settings' ] );
        add_action( 'admin_enqueue_scripts',           [ __CLASS__, 'enqueue_assets' ] );
    }

    // ── Menu ─────────────────────────────────────────────────────────────────

    public static function add_menu(): void {
        add_menu_page(
            'Mikroinlägg',
            'Mikroinlägg',
            'edit_posts',
            'mikro-new',
            [ __CLASS__, 'render_new_page' ],
            'dashicons-format-status',
            25
        );

        add_submenu_page(
            'mikro-new',
            'Skriv nytt mikroinlägg',
            '+ Skriv nytt mikroinlägg',
            'edit_posts',
            'mikro-new',
            [ __CLASS__, 'render_new_page' ]
        );

        add_submenu_page(
            'mikro-new',
            'Visa alla mikroinlägg',
            'Visa alla mikroinlägg',
            'edit_posts',
            'edit.php?post_type=mikroinlagg'
        );

        add_submenu_page(
            'mikro-new',
            'Inställningar – Mikroinlägg',
            'Inställningar',
            'manage_options',
            'mikro-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    // ── Assets ───────────────────────────────────────────────────────────────

    public static function enqueue_assets( string $hook ): void {
        $mikro_hooks = [ 'toplevel_page_mikro-new', 'mikroinlagg_page_mikro-settings' ];
        if ( ! in_array( $hook, $mikro_hooks, true ) ) {
            return;
        }
        wp_enqueue_style(
            'wp-mikroinlagg-admin',
            MIKRO_URL . 'assets/css/admin.css',
            [],
            MIKRO_VERSION
        );
        if ( $hook === 'toplevel_page_mikro-new' ) {
            wp_enqueue_script(
                'wp-mikroinlagg-admin',
                MIKRO_URL . 'assets/js/admin.js',
                [],
                MIKRO_VERSION,
                true
            );
        }
        if ( $hook === 'mikroinlagg_page_mikro-settings' ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
        }
    }

    // ── Render form ───────────────────────────────────────────────────────────

    public static function render_new_page(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Du saknar behörighet att skriva mikroinlägg.' );
        }

        $amnen       = get_terms( [ 'taxonomy' => 'mikro_amne',      'hide_empty' => false ] );
        $plattformar = get_terms( [ 'taxonomy' => 'mikro_plattform', 'hide_empty' => false ] );

        // Fallback platform list when no terms exist yet
        $default_platforms = [ 'Mastodon', 'Threads', 'Facebook', 'Mikroblogg' ];

        // Status messages
        $message = '';
        if ( isset( $_GET['mikro_saved'] ) ) {
            $pid  = intval( $_GET['mikro_saved'] );
            $post = get_post( $pid );
            if ( $post ) {
                if ( $post->post_status === 'publish' ) {
                    $url     = esc_url( get_permalink( $pid ) );
                    $message = '<div class="notice notice-success is-dismissible"><p>Mikroinlägg publicerat! <a href="' . $url . '" target="_blank">Visa inlägg &rarr;</a></p></div>';
                } elseif ( $post->post_status === 'future' ) {
                    $message = '<div class="notice notice-info is-dismissible"><p>Mikroinlägg schemalagt för publicering.</p></div>';
                } else {
                    $message = '<div class="notice notice-success is-dismissible"><p>Utkast sparat!</p></div>';
                }
            }
        }
        if ( isset( $_GET['mikro_error'] ) ) {
            $message = '<div class="notice notice-error is-dismissible"><p>Det gick inte att spara mikroinlägget. Försök igen.</p></div>';
        }

        $today     = current_time( 'Y-m-d' );
        $now_time  = current_time( 'H:i' );
        $form_url  = esc_url( admin_url( 'admin-post.php' ) );
        ?>
        <div class="wrap mikro-admin-wrap">

            <div class="mikro-admin-header">
                <div class="mikro-admin-logo">m</div>
                <h1 class="mikro-admin-title">Skriv Mikro Inlägg</h1>
            </div>

            <?php echo $message; ?>

            <form method="post" action="<?php echo $form_url; ?>" id="mikro-form" novalidate>
                <input type="hidden" name="action" value="mikro_save">
                <?php wp_nonce_field( 'mikro_save_post', 'mikro_nonce' ); ?>

                <div class="mikro-layout">

                    <!-- ── Main column ── -->
                    <div class="mikro-main">

                        <div class="mikro-field-group">
                            <label class="mikro-label" for="mikro-title">Inläggsrubrik</label>
                            <input
                                type="text"
                                id="mikro-title"
                                name="mikro_title"
                                class="mikro-input mikro-title-input"
                                placeholder=""
                                autocomplete="off"
                            >
                        </div>

                        <div class="mikro-field-group">
                            <label class="mikro-label" for="mikro-content">Inläggstext</label>
                            <div class="mikro-editor-wrap">
                                <div class="mikro-toolbar" role="toolbar" aria-label="Textformatering">
                                    <button type="button" class="mikro-tool" data-tag="b"    title="Fet (Ctrl+B)"><strong>B</strong></button>
                                    <button type="button" class="mikro-tool" data-tag="i"    title="Kursiv (Ctrl+I)"><em>I</em></button>
                                    <button type="button" class="mikro-tool" data-tag="u"    title="Understruken"><u>U</u></button>
                                    <button type="button" class="mikro-tool" data-tag="s"    title="Genomstruken"><s>S</s></button>
                                    <button type="button" class="mikro-tool" data-tag="bq"   title="Citat">&#10077;</button>
                                    <button type="button" class="mikro-tool" data-tag="ul"   title="Punktlista">&#8801;</button>
                                </div>
                                <textarea
                                    id="mikro-content"
                                    name="mikro_content"
                                    class="mikro-textarea"
                                    maxlength="500"
                                    rows="10"
                                    placeholder=""
                                    required
                                    aria-required="true"
                                    aria-describedby="mikro-char-count"
                                ></textarea>
                                <div class="mikro-char-bar">
                                    <span id="mikro-char-count"><span id="mikro-count">0</span> / 500</span>
                                </div>
                            </div>
                        </div>

                        <div class="mikro-form-actions">
                            <button type="submit" name="mikro_action" value="draft"   class="button mikro-btn-draft">Spara Utkast</button>
                            <span class="mikro-or">eller</span>
                            <button type="submit" name="mikro_action" value="publish" class="button button-primary mikro-btn-publish">Publicera Inlägg</button>
                        </div>

                    </div><!-- .mikro-main -->

                    <!-- ── Sidebar ── -->
                    <div class="mikro-sidebar">

                        <!-- Ämne -->
                        <div class="mikro-sidebar-box">
                            <h3 class="mikro-box-title">Välj Ämne:</h3>
                            <select name="mikro_amne" id="mikro-amne" class="mikro-select">
                                <option value="">Välj ämne...</option>
                                <?php if ( ! is_wp_error( $amnen ) && $amnen ) : ?>
                                    <?php foreach ( $amnen as $amne ) : ?>
                                        <option value="<?php echo esc_attr( $amne->term_id ); ?>">
                                            <?php echo esc_html( $amne->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Taggar -->
                        <div class="mikro-sidebar-box">
                            <h3 class="mikro-box-title">Taggar:</h3>
                            <div class="mikro-tag-wrap">
                                <input
                                    type="text"
                                    id="mikro-tag-input"
                                    class="mikro-input"
                                    placeholder="Lägg till taggar..."
                                    autocomplete="off"
                                    aria-label="Skriv en tagg och tryck Enter"
                                >
                                <div id="mikro-tags-container" class="mikro-tags-container" aria-live="polite"></div>
                                <input type="hidden" name="mikro_taggar" id="mikro-taggar" value="">
                            </div>
                        </div>

                        <!-- Plattform -->
                        <div class="mikro-sidebar-box">
                            <h3 class="mikro-box-title">Plattform:</h3>
                            <div class="mikro-platform-grid">
                                <?php
                                if ( ! is_wp_error( $plattformar ) && $plattformar ) :
                                    foreach ( $plattformar as $p ) :
                                ?>
                                    <label class="mikro-platform-label">
                                        <input type="checkbox" name="mikro_plattform[]" value="<?php echo esc_attr( $p->term_id ); ?>">
                                        <?php echo esc_html( $p->name ); ?>
                                    </label>
                                <?php
                                    endforeach;
                                else :
                                    foreach ( $default_platforms as $p ) :
                                ?>
                                    <label class="mikro-platform-label">
                                        <input type="checkbox" name="mikro_plattform_new[]" value="<?php echo esc_attr( strtolower( $p ) ); ?>">
                                        <?php echo esc_html( $p ); ?>
                                    </label>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>

                        <!-- Originallänk -->
                        <div class="mikro-sidebar-box">
                            <h3 class="mikro-box-title">Infoga länk till originalinlägget:</h3>
                            <input
                                type="url"
                                name="mikro_originallank"
                                class="mikro-input"
                                placeholder="Ange URL till originalinlägget..."
                                autocomplete="url"
                            >
                            <label class="mikro-check-label">
                                <input type="checkbox" name="mikro_exklusivt" value="1">
                                Publicera exklusivt på bloggen
                            </label>
                        </div>

                        <!-- Tidsinställning -->
                        <div class="mikro-sidebar-box">
                            <h3 class="mikro-box-title">Tidsinställning:</h3>
                            <div class="mikro-time-row">
                                <span class="mikro-time-label">Datum:</span>
                                <input
                                    type="date"
                                    name="mikro_datum"
                                    class="mikro-input-sm"
                                    value="<?php echo esc_attr( $today ); ?>"
                                >
                                <input
                                    type="time"
                                    name="mikro_tid"
                                    class="mikro-input-sm"
                                    value="<?php echo esc_attr( $now_time ); ?>"
                                >
                                <span class="mikro-calendar-icon" aria-hidden="true">&#128197;</span>
                            </div>
                        </div>

                    </div><!-- .mikro-sidebar -->

                </div><!-- .mikro-layout -->
            </form>

        </div><!-- .mikro-admin-wrap -->
        <?php
    }

    // ── Handle form submission ────────────────────────────────────────────────

    public static function handle_save(): void {
        // Security checks
        if (
            ! isset( $_POST['mikro_nonce'] ) ||
            ! wp_verify_nonce( $_POST['mikro_nonce'], 'mikro_save_post' )
        ) {
            wp_die( 'Säkerhetsfel: ogiltigt nonce.' );
        }
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Du saknar behörighet.' );
        }

        $redirect_base = admin_url( 'admin.php?page=mikro-new' );

        // Sanitize inputs
        $action  = sanitize_key( $_POST['mikro_action'] ?? 'draft' );
        $status  = ( $action === 'publish' ) ? 'publish' : 'draft';
        $content = sanitize_textarea_field( $_POST['mikro_content'] ?? '' );
        $title   = sanitize_text_field( $_POST['mikro_title']   ?? '' );
        $datum   = sanitize_text_field( $_POST['mikro_datum']   ?? current_time( 'Y-m-d' ) );
        $tid     = sanitize_text_field( $_POST['mikro_tid']     ?? current_time( 'H:i' ) );

        // Enforce 500 char limit
        $content = mb_substr( $content, 0, 500 );

        if ( empty( $content ) ) {
            wp_safe_redirect( add_query_arg( 'mikro_error', '1', $redirect_base ) );
            exit;
        }

        // Auto-title from content if not provided
        if ( empty( $title ) ) {
            $title = mb_substr( wp_strip_all_tags( $content ), 0, 70 );
            if ( mb_strlen( $content ) > 70 ) {
                $title .= '…';
            }
        }

        // Handle scheduling
        $post_date_local = $datum . ' ' . $tid . ':00';
        $post_date_gmt   = get_gmt_from_date( $post_date_local );
        if ( $status === 'publish' && $post_date_local > current_time( 'mysql' ) ) {
            $status = 'future';
        }

        $post_id = wp_insert_post( [
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => $status,
            'post_type'     => 'mikroinlagg',
            'post_author'   => get_current_user_id(),
            'post_date'     => $post_date_local,
            'post_date_gmt' => $post_date_gmt,
        ], true );

        if ( is_wp_error( $post_id ) ) {
            wp_safe_redirect( add_query_arg( 'mikro_error', '1', $redirect_base ) );
            exit;
        }

        // ── Ämne taxonomy ──
        if ( ! empty( $_POST['mikro_amne'] ) ) {
            $term_id = intval( $_POST['mikro_amne'] );
            if ( $term_id > 0 ) {
                wp_set_post_terms( $post_id, [ $term_id ], 'mikro_amne' );
            }
        }

        // ── Plattform – existing terms ──
        if ( ! empty( $_POST['mikro_plattform'] ) && is_array( $_POST['mikro_plattform'] ) ) {
            $term_ids = array_map( 'intval', $_POST['mikro_plattform'] );
            wp_set_post_terms( $post_id, $term_ids, 'mikro_plattform' );
        }

        // ── Plattform – create terms on first use ──
        if ( ! empty( $_POST['mikro_plattform_new'] ) && is_array( $_POST['mikro_plattform_new'] ) ) {
            $term_ids = [];
            foreach ( $_POST['mikro_plattform_new'] as $raw_name ) {
                $name = ucfirst( sanitize_text_field( $raw_name ) );
                $term = term_exists( $name, 'mikro_plattform' );
                if ( ! $term ) {
                    $term = wp_insert_term( $name, 'mikro_plattform' );
                }
                if ( ! is_wp_error( $term ) ) {
                    $term_ids[] = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
                }
            }
            if ( $term_ids ) {
                wp_set_post_terms( $post_id, $term_ids, 'mikro_plattform' );
            }
        }

        // ── Taggar ──
        if ( ! empty( $_POST['mikro_taggar'] ) ) {
            $tags = array_map( 'sanitize_text_field', explode( ',', $_POST['mikro_taggar'] ) );
            $tags = array_filter( array_unique( $tags ) );
            if ( $tags ) {
                wp_set_post_terms( $post_id, array_values( $tags ), 'mikro_taggar' );
            }
        }

        // ── Meta ──
        if ( ! empty( $_POST['mikro_originallank'] ) ) {
            update_post_meta( $post_id, 'mikro_originallank', esc_url_raw( $_POST['mikro_originallank'] ) );
        }
        update_post_meta( $post_id, 'mikro_exklusivt', ! empty( $_POST['mikro_exklusivt'] ) ? 1 : 0 );
        update_post_meta( $post_id, 'mikro_pinned',    0 );

        wp_safe_redirect( add_query_arg( 'mikro_saved', $post_id, $redirect_base ) );
        exit;
    }

    // ── Settings page ─────────────────────────────────────────────────────────

    public static function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Behörighet saknas.' );
        }

        $color       = get_option( 'mikro_hero_color',       '#0073aa' );
        $heading     = get_option( 'mikro_hero_heading',     'Mikroinlägg' );
        $subtitle    = get_option( 'mikro_hero_subtitle',    'MIKROINLÄGG' );
        $description = get_option( 'mikro_hero_description', 'Korta tankar, länkar och uppdateringar.' );

        $message = '';
        if ( isset( $_GET['mikro_settings_saved'] ) ) {
            $message = '<div class="notice notice-success is-dismissible"><p>Inställningarna sparades.</p></div>';
        }
        ?>
        <div class="wrap mikro-admin-wrap">

            <div class="mikro-admin-header">
                <div class="mikro-admin-logo">m</div>
                <h1 class="mikro-admin-title">Inställningar – Mikroinlägg</h1>
            </div>

            <?php echo $message; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="background:#fff;border:1px solid #dde1e7;border-top:none;border-radius:0 0 8px 8px;padding:28px;max-width:600px">
                <input type="hidden" name="action" value="mikro_save_settings">
                <?php wp_nonce_field( 'mikro_save_settings', 'mikro_settings_nonce' ); ?>

                <h2 style="margin-top:0;font-size:16px;border-bottom:1px solid #eee;padding-bottom:12px;margin-bottom:20px">Hero-sektion (arkivsidan)</h2>

                <!-- Förhandsgranskning -->
                <div id="mikro-hero-preview" style="
                    background: linear-gradient(to bottom, <?php echo esc_attr( $color ); ?>55, transparent);
                    border-top: 2px solid <?php echo esc_attr( $color ); ?>;
                    border-bottom: 2px solid <?php echo esc_attr( $color ); ?>;
                    text-align: center;
                    padding: 32px 20px;
                    border-radius: 6px;
                    margin-bottom: 24px;
                    transition: background 0.3s, border-color 0.3s;
                ">
                    <p style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:<?php echo esc_attr( $color ); ?>;margin:0 0 4px" id="preview-subtitle"><?php echo esc_html( $subtitle ); ?></p>
                    <h3 style="font-size:26px;margin:0 0 6px;font-weight:700" id="preview-heading"><?php echo esc_html( $heading ); ?></h3>
                    <p style="color:#666;font-size:14px;margin:0" id="preview-description"><?php echo esc_html( $description ); ?></p>
                </div>

                <!-- Färg -->
                <div class="mikro-field-group">
                    <label class="mikro-label" for="mikro-hero-color">Gradientfärg</label>
                    <input
                        type="text"
                        id="mikro-hero-color"
                        name="mikro_hero_color"
                        value="<?php echo esc_attr( $color ); ?>"
                        class="mikro-color-picker"
                        data-default-color="#0073aa"
                    >
                    <p class="description" style="margin-top:6px">Väljer topfärg i hero-gradienten. Samma princip som ämnes-sidornas temafärg.</p>
                </div>

                <!-- Rubrik -->
                <div class="mikro-field-group" style="margin-top:16px">
                    <label class="mikro-label" for="mikro-hero-heading">Rubrik</label>
                    <input
                        type="text"
                        id="mikro-hero-heading"
                        name="mikro_hero_heading"
                        value="<?php echo esc_attr( $heading ); ?>"
                        class="mikro-input"
                    >
                </div>

                <!-- Etikett ovan rubrik -->
                <div class="mikro-field-group" style="margin-top:16px">
                    <label class="mikro-label" for="mikro-hero-subtitle">Etikett (ovan rubrik, versaler)</label>
                    <input
                        type="text"
                        id="mikro-hero-subtitle"
                        name="mikro_hero_subtitle"
                        value="<?php echo esc_attr( $subtitle ); ?>"
                        class="mikro-input"
                    >
                </div>

                <!-- Beskrivning -->
                <div class="mikro-field-group" style="margin-top:16px">
                    <label class="mikro-label" for="mikro-hero-description">Beskrivning</label>
                    <input
                        type="text"
                        id="mikro-hero-description"
                        name="mikro_hero_description"
                        value="<?php echo esc_attr( $description ); ?>"
                        class="mikro-input"
                    >
                </div>

                <div style="margin-top:24px">
                    <button type="submit" class="button button-primary mikro-btn-publish">Spara inställningar</button>
                </div>
            </form>

        </div>

        <script>
        jQuery(function($) {
            // Aktivera WP color picker
            $('.mikro-color-picker').wpColorPicker({
                change: function(event, ui) {
                    var color = ui.color.toString();
                    updatePreview(color);
                },
                clear: function() {
                    updatePreview('#0073aa');
                }
            });

            function updatePreview(color) {
                var hex55 = color + '55';
                $('#mikro-hero-preview').css({
                    'background': 'linear-gradient(to bottom, ' + hex55 + ', transparent)',
                    'border-top-color': color,
                    'border-bottom-color': color
                });
                $('#mikro-hero-preview #preview-subtitle').css('color', color);
            }

            // Live-förhandsgranskning av textfält
            $('#mikro-hero-heading').on('input', function() {
                $('#preview-heading').text($(this).val() || 'Mikroinlägg');
            });
            $('#mikro-hero-subtitle').on('input', function() {
                $('#preview-subtitle').text($(this).val());
            });
            $('#mikro-hero-description').on('input', function() {
                $('#preview-description').text($(this).val());
            });
        });
        </script>
        <?php
    }

    public static function handle_save_settings(): void {
        if (
            ! isset( $_POST['mikro_settings_nonce'] ) ||
            ! wp_verify_nonce( $_POST['mikro_settings_nonce'], 'mikro_save_settings' )
        ) {
            wp_die( 'Säkerhetsfel.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Behörighet saknas.' );
        }

        $color = sanitize_hex_color( $_POST['mikro_hero_color'] ?? '' ) ?: '#0073aa';
        update_option( 'mikro_hero_color',       $color );
        update_option( 'mikro_hero_heading',     sanitize_text_field( $_POST['mikro_hero_heading']     ?? 'Mikroinlägg' ) );
        update_option( 'mikro_hero_subtitle',    sanitize_text_field( $_POST['mikro_hero_subtitle']    ?? 'MIKROINLÄGG' ) );
        update_option( 'mikro_hero_description', sanitize_text_field( $_POST['mikro_hero_description'] ?? '' ) );

        wp_safe_redirect( add_query_arg( 'mikro_settings_saved', '1', admin_url( 'admin.php?page=mikro-settings' ) ) );
        exit;
    }
}

Mikro_Admin::init();
