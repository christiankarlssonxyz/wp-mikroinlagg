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

    <div class="mikro-archive-feed">

        <?php if ( have_posts() ) : ?>

            <?php while ( have_posts() ) : the_post();
                $amnen       = get_the_terms( get_the_ID(), 'mikro_amne' );
                $plattformar = get_the_terms( get_the_ID(), 'mikro_plattform' );
                $taggar      = get_the_terms( get_the_ID(), 'mikro_taggar' );
                $originallank = get_post_meta( get_the_ID(), 'mikro_originallank', true );
                $timestamp   = get_post_time( 'U', false, get_the_ID() );
                $time_label  = mikro_time_ago_sv( $timestamp );

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

</section>

<?php get_footer(); ?>
