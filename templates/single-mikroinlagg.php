<?php
/**
 * Single template for mikroinlagg post type.
 */
get_header();

if ( have_posts() ) :
    while ( have_posts() ) : the_post();

        $post_id      = get_the_ID();
        $amnen        = get_the_terms( $post_id, 'mikro_amne' );
        $plattformar  = get_the_terms( $post_id, 'mikro_plattform' );
        $taggar       = get_the_terms( $post_id, 'mikro_taggar' );
        $originallank = get_post_meta( $post_id, 'mikro_originallank', true );
        $exklusivt    = (bool) get_post_meta( $post_id, 'mikro_exklusivt', true );
        $timestamp    = get_post_time( 'U', false, $post_id );
        $time_label   = mikro_time_ago_sv( $timestamp );
        $author_id    = (int) get_the_author_meta( 'ID' );
        $author_name  = get_the_author();
        $initials     = '';
        foreach ( explode( ' ', $author_name ) as $part ) {
            $initials .= mb_strtoupper( mb_substr( $part, 0, 1 ) );
        }
        $initials     = mb_substr( $initials, 0, 2 );
        $avatar_url   = get_avatar_url( $author_id, [ 'size' => 84 ] );

        $amne     = ( ! is_wp_error( $amnen )       && $amnen )       ? $amnen[0]       : null;
        $platform = ( ! is_wp_error( $plattformar ) && $plattformar ) ? $plattformar[0] : null;

        $likes    = (int) get_post_meta( $post_id, 'wpblogtree_likes', true );
        $liked_by = get_post_meta( $post_id, 'wpblogtree_liked_by', true );
        if ( ! is_array( $liked_by ) ) {
            $liked_by = [];
        }
        $is_liked = is_user_logged_in() && in_array( get_current_user_id(), $liked_by, true );
?>
<div class="single-topbar">
    <a href="<?php echo esc_url( get_post_type_archive_link( 'mikroinlagg' ) ); ?>" class="topbar-home mikro-back-link">&larr; Alla mikroinlägg</a>
</div>

<section class="container">
<div class="layout-wrapper" style="margin-top:32px">
<div class="main-content-area">
<div class="mikro-single-wrap" style="max-width:100%;margin:0;padding:0">

    <article class="mikro-single-card" id="post-<?php the_ID(); ?>">

        <!-- Author row -->
        <div class="mikro-single-author">
            <div class="mikro-author-avatar">
                <?php if ( $avatar_url ) : ?>
                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $author_name ); ?>" width="42" height="42">
                <?php else : ?>
                    <?php echo esc_html( $initials ); ?>
                <?php endif; ?>
            </div>
            <div class="mikro-author-info">
                <p class="mikro-author-name"><?php echo esc_html( $author_name ); ?></p>
                <div class="mikro-author-meta">
                    <span><?php echo esc_html( $time_label ); ?></span>

                    <?php if ( $amne ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $amne ) ); ?>" class="mikro-badge mikro-badge-amne">
                            <?php echo esc_html( $amne->name ); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ( $platform ) :
                        $slug = sanitize_title( $platform->name );
                        $icon = mikro_platform_icon( $slug );
                    ?>
                        <a href="<?php echo esc_url( get_term_link( $platform ) ); ?>" class="mikro-badge mikro-badge-platform">
                            <?php echo $icon; ?>
                            <?php echo esc_html( $platform->name ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="mikro-single-content">
            <?php echo wp_kses_post( wpautop( get_the_content() ) ); ?>
        </div>

        <!-- Original link -->
        <?php if ( $originallank && ! $exklusivt ) : ?>
            <a href="<?php echo esc_url( $originallank ); ?>" class="mikro-original-link" target="_blank" rel="noopener noreferrer">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                Visa originalinlägg
            </a>
        <?php endif; ?>

        <!-- Tags -->
        <?php if ( ! is_wp_error( $taggar ) && $taggar ) : ?>
            <div class="mikro-single-tags">
                <?php foreach ( $taggar as $tag ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="mikro-badge">
                        #<?php echo esc_html( $tag->name ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="mikro-single-actions">
            <button
                class="mikro-action-btn wpblogtree-like-btn<?php echo $is_liked ? ' is-liked' : ''; ?>"
                data-post-id="<?php echo esc_attr( $post_id ); ?>"
                aria-pressed="<?php echo $is_liked ? 'true' : 'false'; ?>"
            >
                <svg class="heart-icon" width="15" height="15" viewBox="0 0 24 24" fill="<?php echo $is_liked ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                Gilla<?php if ( $likes > 0 ) echo ' <span class="like-count">(' . $likes . ')</span>'; ?>
            </button>
            <button class="mikro-action-btn comment-toggle-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Kommentera<?php $cc = get_comments_number(); if ( $cc > 0 ) echo ' (' . $cc . ')'; ?>
            </button>
            <button class="mikro-action-btn share-btn" data-url="<?php echo esc_attr( get_permalink() ); ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                Dela
            </button>
        </div>

        <!-- Comments -->
        <div class="mikro-single-comments">
            <?php comments_template(); ?>
        </div>

    </article>

</div><!-- .mikro-single-wrap -->
</div><!-- .main-content-area -->

<aside class="sidebar">

    <!-- Alltid synlig Mikroinlägg-widget -->
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
        <a href="<?php echo esc_url( get_post_type_archive_link( 'mikroinlagg' ) ); ?>" class="sidebar-post-meta" style="font-size:13px">
            &larr; Alla mikroinlägg
        </a>
    </div>

    <?php
    $latest_q = new WP_Query( [
        'post_type'           => 'post',
        'posts_per_page'      => 5,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
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
    $mikro_q = new WP_Query( [
        'post_type'      => 'mikroinlagg',
        'posts_per_page' => 5,
        'post_status'    => 'publish',
        'post__not_in'   => [ get_the_ID() ],
    ] );
    if ( $mikro_q->have_posts() ) : ?>
    <div class="sidebar-widget topic-sidebar-latest">
        <h3>Fler mikroinlägg</h3>
        <ul class="topic-sidebar-list">
            <?php while ( $mikro_q->have_posts() ) : $mikro_q->the_post();
                $ts    = get_post_time( 'U', false, get_the_ID() );
                $label = mikro_time_ago_sv( $ts );
            ?>
            <li>
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                <span class="sidebar-post-meta"><?php echo esc_html( $label ); ?></span>
            </li>
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>
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

<script>
(function() {
    // Share button
    var shareBtn = document.querySelector('.mikro-action-btn.share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            var url = this.dataset.url;
            if (navigator.share) {
                navigator.share({ url: url }).catch(function() {});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    shareBtn.textContent = 'Kopierad!';
                    setTimeout(function() {
                        shareBtn.textContent = 'Dela';
                    }, 2000);
                });
            }
        });
    }
})();
</script>

<?php
    endwhile;
endif;

get_footer();
?>
