<?php
/**
 * Archive template for mikroinlagg post type.
 */
get_header();

$paged       = (int) get_query_var( 'paged' ) ?: 1;
$hero_color  = get_option( 'mikro_hero_color',       '#0073aa' );
$hero_heading     = get_option( 'mikro_hero_heading',     'Mikroinlägg' );
$hero_subtitle    = get_option( 'mikro_hero_subtitle',    'MIKROINLÄGG' );
$hero_description = get_option( 'mikro_hero_description', 'Korta tankar, länkar och uppdateringar.' );
$color_style = 'style="--topic-color:' . esc_attr( $hero_color ) . '"';
?>

<section class="container">

    <div class="taxonomy-header" <?php echo $color_style; ?>>
        <?php if ( $hero_subtitle ) : ?>
            <p class="taxonomy-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
        <?php endif; ?>
        <h1 class="taxonomy-title taxonomy-title--center">
            <?php echo esc_html( $hero_heading ); ?>
        </h1>
        <?php if ( $hero_description ) : ?>
            <p class="taxonomy-description"><?php echo esc_html( $hero_description ); ?></p>
        <?php endif; ?>
    </div>

    <div class="layout-wrapper" style="margin-top:32px">

        <!-- ── Flöde ── -->
        <div class="main-content-area">

        <?php if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) :
            $current_user = wp_get_current_user();
            $avatar_url   = get_avatar_url( $current_user->ID, [ 'size' => 80 ] );
        ?>
        <div class="mikro-compose-bar" id="mikro-compose-trigger" role="button" tabindex="0" aria-label="Skriv nytt mikroinlägg">
            <img src="<?php echo esc_url( $avatar_url ); ?>" alt="" class="mikro-compose-avatar" width="40" height="40">
            <span class="mikro-compose-placeholder">What's new?</span>
            <button class="mikro-compose-post-btn" tabindex="-1" aria-hidden="true">Post</button>
        </div>
        <?php endif; ?>

        <div class="mikro-archive-feed">

        <?php if ( have_posts() ) : ?>

            <?php while ( have_posts() ) : the_post();
                $amnen        = get_the_terms( get_the_ID(), 'mikro_amne' );
                $plattformar  = get_the_terms( get_the_ID(), 'mikro_plattform' );
                $originallank = get_post_meta( get_the_ID(), 'mikro_originallank', true );
                $timestamp    = get_post_time( 'U', false, get_the_ID() );
                $time_label   = mikro_time_ago_sv( $timestamp );

                $amne     = ( ! is_wp_error( $amnen )       && $amnen )       ? $amnen[0]       : null;
                $platform = ( ! is_wp_error( $plattformar ) && $plattformar ) ? $plattformar[0] : null;
            ?>
                <?php
                $platform_color_attr = $platform ? mikro_platform_color( sanitize_title( $platform->name ) ) : '#2d3748';
                $platform_icon_attr  = $platform ? mikro_platform_icon( sanitize_title( $platform->name ), 22 ) : mikro_platform_icon( 'mikroblogg', 22 );
                $post_content_plain  = mb_substr( wp_strip_all_tags( get_the_content() ), 0, 300 );
                ?>
                <article class="mikro-card" id="post-<?php the_ID(); ?>"
                    data-post-id="<?php the_ID(); ?>"
                    data-author="<?php echo esc_attr( get_the_author() ); ?>"
                    data-time="<?php echo esc_attr( $time_label ); ?>"
                    data-content="<?php echo esc_attr( $post_content_plain ); ?>"
                    data-platform-color="<?php echo esc_attr( $platform_color_attr ); ?>"
                    data-platform-icon="<?php echo esc_attr( $platform_icon_attr ); ?>"
                >

                    <div class="mikro-card-row">

                        <!-- Avatar = plattformens ikon -->
                        <div class="mikro-card-avatar-col">
                            <?php if ( $platform ) :
                                $platform_url = get_term_link( $platform );
                            ?>
                                <a href="<?php echo esc_url( $platform_url ); ?>" tabindex="-1" aria-hidden="true">
                                    <?php echo mikro_platform_avatar( $platform ); ?>
                                </a>
                            <?php else : ?>
                                <?php echo mikro_platform_avatar( null ); ?>
                            <?php endif; ?>
                        </div>

                        <!-- Innehåll -->
                        <div class="mikro-card-body">

                            <div class="mikro-card-top">
                                <span class="mikro-card-author"><?php echo esc_html( get_the_author() ); ?></span>
                                <span class="mikro-card-dot" aria-hidden="true">&middot;</span>
                                <span class="mikro-card-time"><?php echo esc_html( $time_label ); ?></span>
                                <?php if ( $platform ) : ?>
                                    <span class="mikro-card-dot" aria-hidden="true">&middot;</span>
                                    <a href="<?php echo esc_url( get_term_link( $platform ) ); ?>" class="mikro-card-platform-name">
                                        <?php echo mikro_platform_icon( sanitize_title( $platform->name ), 12 ); ?>
                                        <?php echo esc_html( $platform->name ); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ( $amne ) : ?>
                                    <a href="<?php echo esc_url( get_term_link( $amne ) ); ?>" class="mikro-badge mikro-badge-amne" style="margin-left:auto">
                                        <?php echo esc_html( $amne->name ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php if ( get_the_title() ) : ?>
                                <h2 class="mikro-card-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                            <?php endif; ?>

                            <div class="mikro-card-content">
                                <?php echo wp_kses_post( wpautop( get_the_content() ) ); ?>
                            </div>

                            <?php if ( $originallank ) : ?>
                                <a href="<?php echo esc_url( $originallank ); ?>" class="mikro-card-link" target="_blank" rel="noopener noreferrer">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    Originalinlägg
                                </a>
                            <?php endif; ?>

                            <!-- Action bar -->
                            <?php
                            $like_count = (int) get_post_meta( get_the_ID(), 'wpblogtree_likes', true );
                            $comment_count = get_comments_number( get_the_ID() );
                            ?>
                            <div class="mikro-card-actions">
                                <button class="mikro-action-btn mikro-like-btn" data-post-id="<?php the_ID(); ?>" aria-label="Gilla" aria-pressed="false">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                    <span class="mikro-like-count"><?php echo $like_count > 0 ? $like_count : ''; ?></span>
                                    <span class="mikro-action-label">Gilla</span>
                                </button>
                                <button class="mikro-action-btn mikro-comment-btn" data-post-id="<?php the_ID(); ?>" aria-label="Kommentera">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    <span class="mikro-comment-count-<?php the_ID(); ?>"><?php if ( $comment_count > 0 ) echo (int) $comment_count; ?></span>
                                    <span class="mikro-action-label">Kommentera</span>
                                </button>
                                <button class="mikro-action-btn mikro-share-btn" data-url="<?php the_permalink(); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>" aria-label="Dela">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                    <span class="mikro-action-label">Dela</span>
                                </button>
                            </div>

                        </div><!-- .mikro-card-body -->
                    </div><!-- .mikro-card-row -->

                </article>

            <?php endwhile; ?>

            <div class="mikro-pagination">
                <?php
                echo paginate_links( [
                    'total'     => $GLOBALS['wp_query']->max_num_pages,
                    'current'   => max( 1, $paged ),
                    'prev_text' => '&larr;',
                    'next_text' => '&rarr;',
                    'mid_size'  => 1,
                    'type'      => 'list',
                ] );
                ?>
            </div>

        <?php else : ?>
            <p>Inga mikroinlägg publicerade ännu.</p>
        <?php endif; ?>

        </div><!-- .mikro-archive-feed -->
        </div><!-- .main-content-area -->

        <!-- ── Sidebar ── -->
        <aside class="sidebar">

            <div class="sidebar-widget mikro-sidebar-nav">
                <div class="mikro-widget-header" style="margin:-20px -20px 16px;border-radius:12px 12px 0 0;padding:12px 16px">
                    <span class="mikro-widget-title">Mikroinlägg</span>
                    <span class="mikro-widget-logo" aria-hidden="true">m</span>
                </div>
                <?php if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=mikro-new' ) ); ?>" class="mikro-widget-new-btn" style="margin:0 -20px 16px;display:block">
                    + Skriv nytt mikroinlägg
                </a>
                <?php endif; ?>
            </div>

            <?php
            $latest_q = new WP_Query( [
                'post_type'           => 'post',
                'posts_per_page'      => 5,
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => true,
                'no_found_rows'       => true,
            ] );
            if ( $latest_q->have_posts() ) : ?>
            <div class="sidebar-widget topic-sidebar-latest">
                <h3>Senaste inlägg</h3>
                <ul class="topic-sidebar-list">
                    <?php while ( $latest_q->have_posts() ) : $latest_q->the_post();
                        $pt = get_the_terms( get_the_ID(), 'topic' );
                        $tn = ( ! is_wp_error( $pt ) && ! empty( $pt ) ) ? $pt[0]->name : '';
                    ?>
                    <li>
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        <span class="sidebar-post-meta"><?php echo get_the_date(); ?><?php if ( $tn ) : ?> &middot; <?php echo esc_html( $tn ); ?><?php endif; ?></span>
                    </li>
                    <?php endwhile; wp_reset_postdata(); ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php
            $popular_q = new WP_Query( [
                'post_type'           => 'post',
                'posts_per_page'      => 5,
                'meta_key'            => 'wpblogtree_views',
                'orderby'             => 'meta_value_num',
                'order'               => 'DESC',
                'ignore_sticky_posts' => true,
                'no_found_rows'       => true,
            ] );
            if ( $popular_q->have_posts() ) : ?>
            <div class="sidebar-widget topic-sidebar-popular">
                <h3>Mest lästa</h3>
                <ol class="topic-sidebar-ranked">
                    <?php while ( $popular_q->have_posts() ) : $popular_q->the_post();
                        $pt = get_the_terms( get_the_ID(), 'topic' );
                        $tn = ( ! is_wp_error( $pt ) && ! empty( $pt ) ) ? $pt[0]->name : '';
                    ?>
                    <li><div>
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        <span class="sidebar-post-meta"><?php echo get_the_date(); ?><?php if ( $tn ) : ?> &middot; <?php echo esc_html( $tn ); ?><?php endif; ?></span>
                    </div></li>
                    <?php endwhile; wp_reset_postdata(); ?>
                </ol>
            </div>
            <?php endif; ?>

            <?php
            if ( function_exists( 'wpblogtree_sidebar_newsletter_widget' ) ) {
                wpblogtree_sidebar_newsletter_widget();
            }
            if ( function_exists( 'wpblogtree_sidebar_social_widget' ) ) {
                wpblogtree_sidebar_social_widget();
            }
            ?>

        </aside>

    </div><!-- .layout-wrapper -->

</section>

<?php if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) :
    $amnen       = get_terms( [ 'taxonomy' => 'mikro_amne',      'hide_empty' => false ] );
    $plattformar = get_terms( [ 'taxonomy' => 'mikro_plattform', 'hide_empty' => false ] );
    $current_user = wp_get_current_user();
    $avatar_url   = get_avatar_url( $current_user->ID, [ 'size' => 80 ] );
?>
<!-- ── Compose-modal ── -->
<div class="mikro-modal-overlay" id="mikro-modal" hidden aria-modal="true" role="dialog" aria-label="Skriv mikroinlägg">
    <div class="mikro-modal">

        <div class="mikro-modal-header">
            <button class="mikro-modal-cancel" id="mikro-modal-cancel">Avbryt</button>
            <span class="mikro-modal-title">Skriv Mikro Inlägg</span>
            <button class="mikro-modal-post-btn" id="mikro-modal-submit" data-action="publish">Post</button>
        </div>

        <div class="mikro-modal-body">
            <img src="<?php echo esc_url( $avatar_url ); ?>" alt="" class="mikro-compose-avatar" width="40" height="40">
            <div class="mikro-modal-fields">

                <textarea
                    id="mikro-modal-content"
                    class="mikro-modal-textarea"
                    placeholder="What's new?"
                    maxlength="500"
                    rows="4"
                    aria-label="Inläggstext"
                ></textarea>
                <div class="mikro-modal-char-count"><span id="mikro-modal-count">0</span> / 500</div>

                <div class="mikro-modal-meta">
                    <!-- Ämne -->
                    <select id="mikro-modal-amne" class="mikro-modal-select" aria-label="Välj ämne">
                        <option value="">Välj ämne...</option>
                        <?php if ( ! is_wp_error( $amnen ) && $amnen ) :
                            foreach ( $amnen as $a ) : ?>
                            <option value="<?php echo esc_attr( $a->term_id ); ?>"><?php echo esc_html( $a->name ); ?></option>
                        <?php endforeach; endif; ?>
                    </select>

                    <!-- Plattformar -->
                    <?php if ( ! is_wp_error( $plattformar ) && $plattformar ) : ?>
                    <div class="mikro-modal-platforms">
                        <?php foreach ( $plattformar as $p ) :
                            $slug = sanitize_title( $p->name );
                            $icon = mikro_platform_icon( $slug );
                        ?>
                        <label class="mikro-modal-platform-label">
                            <input type="checkbox" name="plattform[]" value="<?php echo esc_attr( $p->term_id ); ?>">
                            <?php echo $icon; ?>
                            <?php echo esc_html( $p->name ); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Länk -->
                    <input
                        type="url"
                        id="mikro-modal-link"
                        class="mikro-modal-input"
                        placeholder="Länk till originalinlägg (valfritt)..."
                    >

                    <!-- Exklusivt -->
                    <label class="mikro-modal-exclusive">
                        <input type="checkbox" id="mikro-modal-exclusive">
                        Publicera exklusivt på bloggen
                    </label>
                </div>

            </div>
        </div>

        <div class="mikro-modal-footer">
            <span class="mikro-modal-status" id="mikro-modal-status" aria-live="polite"></span>
            <div class="mikro-modal-actions">
                <button class="mikro-modal-draft-btn" id="mikro-modal-draft">Spara utkast</button>
            </div>
        </div>

    </div>
</div>
<?php endif; ?>

<?php if ( is_user_logged_in() ) :
    $cu         = wp_get_current_user();
    $cu_avatar  = get_avatar_url( $cu->ID, [ 'size' => 80 ] );
    $cu_name    = esc_html( $cu->display_name );
?>
<!-- ── Comment-modal ── -->
<div class="mikro-reply-overlay" id="mikro-reply-modal" hidden aria-modal="true" role="dialog" aria-label="Svara på mikroinlägg">
    <div class="mikro-reply-modal">

        <div class="mikro-reply-header">
            <button class="mikro-reply-cancel" id="mikro-reply-cancel">Avbryt</button>
            <span class="mikro-reply-title">Svar</span>
            <button class="mikro-reply-post-btn" id="mikro-reply-submit">Post</button>
        </div>

        <div class="mikro-reply-body">

            <!-- Original post (fylls i av JS) -->
            <div class="mikro-reply-thread-row">
                <div class="mikro-reply-avatar-col">
                    <span class="mikro-reply-orig-avatar" id="mikro-reply-orig-avatar"></span>
                    <span class="mikro-reply-thread-line" aria-hidden="true"></span>
                </div>
                <div class="mikro-reply-orig-content">
                    <div class="mikro-reply-orig-author" id="mikro-reply-orig-author"></div>
                    <div class="mikro-reply-orig-text" id="mikro-reply-orig-text"></div>
                </div>
            </div>

            <!-- Svarsfält -->
            <div class="mikro-reply-compose-row">
                <div class="mikro-reply-avatar-col">
                    <img src="<?php echo esc_url( $cu_avatar ); ?>" alt="" class="mikro-reply-user-avatar" width="38" height="38">
                </div>
                <div class="mikro-reply-compose-content">
                    <span class="mikro-reply-user-name"><?php echo $cu_name; ?></span>
                    <textarea
                        id="mikro-reply-content"
                        class="mikro-reply-textarea"
                        placeholder="Svara <?php echo $cu_name; ?>..."
                        rows="3"
                        aria-label="Skriv svar"
                    ></textarea>
                </div>
            </div>

        </div>

        <div class="mikro-reply-footer">
            <span class="mikro-reply-status" id="mikro-reply-status" aria-live="polite"></span>
        </div>

    </div>
</div>
<input type="hidden" id="mikro-reply-post-id" value="">
<?php endif; ?>

<?php get_footer(); ?>
