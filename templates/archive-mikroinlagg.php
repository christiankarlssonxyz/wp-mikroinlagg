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
                <article class="mikro-card" id="post-<?php the_ID(); ?>">

                    <div class="mikro-card-meta">
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
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            Originalinlägg
                        </a>
                    <?php endif; ?>

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

<?php get_footer(); ?>
