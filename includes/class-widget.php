<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mikro_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'mikro_sidebar_widget',
            'Mikroinlägg',
            [ 'description' => 'Visar senaste mikroinlägg med länk till att skriva nytt.' ]
        );
    }

    public function widget( $args, $instance ): void {
        $antal          = ! empty( $instance['antal'] )          ? (int) $instance['antal'] : 5;
        $visa_plattform = ! empty( $instance['visa_plattform'] );

        $posts = get_posts( [
            'post_type'      => 'mikroinlagg',
            'posts_per_page' => $antal,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        echo $args['before_widget'];
        ?>
        <div class="mikro-widget">

            <div class="mikro-widget-header">
                <span class="mikro-widget-title">Mikroinlägg</span>
                <span class="mikro-widget-logo" aria-hidden="true">m</span>
            </div>

            <?php if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) : ?>
                <a
                    href="<?php echo esc_url( admin_url( 'admin.php?page=mikro-new' ) ); ?>"
                    class="mikro-widget-new-btn"
                >+ Skriv nytt mikroinlägg</a>
            <?php endif; ?>

            <?php if ( $posts ) : ?>
                <ul class="mikro-widget-list">
                    <?php foreach ( $posts as $post ) :
                        $timestamp   = get_post_time( 'U', false, $post->ID );
                        $time_label  = mikro_time_ago_sv( $timestamp );
                        $plattformar = get_the_terms( $post->ID, 'mikro_plattform' );
                        $plattform   = ( ! is_wp_error( $plattformar ) && $plattformar ) ? $plattformar[0] : null;
                    ?>
                        <li class="mikro-widget-item">
                            <a
                                href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>"
                                class="mikro-widget-link"
                            ><?php echo esc_html( get_the_title( $post->ID ) ); ?></a>
                            <span class="mikro-widget-meta">
                                <span class="mikro-widget-time">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <?php echo esc_html( $time_label ); ?>
                                </span>
                                <?php if ( $visa_plattform && $plattform ) :
                                    $slug = sanitize_title( $plattform->name );
                                    $icon = mikro_platform_icon( $slug );
                                ?>
                                    <span class="mikro-widget-platform mikro-plattform-<?php echo esc_attr( $slug ); ?>">
                                        <?php echo $icon; ?>
                                        <?php echo esc_html( $plattform->name ); ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a
                    href="<?php echo esc_url( get_post_type_archive_link( 'mikroinlagg' ) ); ?>"
                    class="mikro-widget-all-link"
                >Alla mikroinlägg &raquo;</a>
            <?php else : ?>
                <p class="mikro-widget-empty">Inga mikroinlägg publicerade ännu.</p>
            <?php endif; ?>

        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ): void {
        $antal          = ! empty( $instance['antal'] )          ? (int) $instance['antal'] : 5;
        $visa_plattform = ! empty( $instance['visa_plattform'] );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'antal' ) ); ?>">
                Antal inlägg att visa:
            </label>
            <input
                type="number"
                id="<?php echo esc_attr( $this->get_field_id( 'antal' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'antal' ) ); ?>"
                value="<?php echo esc_attr( $antal ); ?>"
                min="1" max="20"
                style="width:50px"
            >
        </p>
        <p>
            <input
                type="checkbox"
                id="<?php echo esc_attr( $this->get_field_id( 'visa_plattform' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'visa_plattform' ) ); ?>"
                value="1"
                <?php checked( $visa_plattform ); ?>
            >
            <label for="<?php echo esc_attr( $this->get_field_id( 'visa_plattform' ) ); ?>">
                Visa plattform-ikon
            </label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ): array {
        return [
            'antal'          => max( 1, min( 20, (int) $new_instance['antal'] ) ),
            'visa_plattform' => ! empty( $new_instance['visa_plattform'] ) ? 1 : 0,
        ];
    }
}

add_action( 'widgets_init', static function (): void {
    register_widget( 'Mikro_Widget' );
} );
