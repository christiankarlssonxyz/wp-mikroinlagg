<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mikro_CPT {

    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'register_post_type' ] );
        add_action( 'init', [ __CLASS__, 'register_taxonomies' ] );
        add_action( 'init', [ __CLASS__, 'register_meta' ] );
    }

    public static function register_post_type(): void {
        register_post_type( 'mikroinlagg', [
            'labels' => [
                'name'               => 'Mikroinlägg',
                'singular_name'      => 'Mikroinlägg',
                'add_new'            => 'Skriv nytt',
                'add_new_item'       => 'Skriv nytt mikroinlägg',
                'edit_item'          => 'Redigera mikroinlägg',
                'view_item'          => 'Visa mikroinlägg',
                'all_items'          => 'Alla mikroinlägg',
                'search_items'       => 'Sök mikroinlägg',
                'not_found'          => 'Inga mikroinlägg hittades.',
                'not_found_in_trash' => 'Inga mikroinlägg i papperskorgen.',
                'menu_name'          => 'Mikroinlägg',
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'mikro', 'with_front' => false ],
            'capability_type'    => 'post',
            'has_archive'        => 'mikro',
            'hierarchical'       => false,
            'supports'           => [ 'title', 'editor', 'author', 'comments' ],
            'show_in_rest'       => true,
        ] );
    }

    public static function register_taxonomies(): void {
        // Ämne
        register_taxonomy( 'mikro_amne', 'mikroinlagg', [
            'labels' => [
                'name'          => 'Ämnen',
                'singular_name' => 'Ämne',
                'all_items'     => 'Alla ämnen',
                'edit_item'     => 'Redigera ämne',
                'add_new_item'  => 'Lägg till ämne',
                'menu_name'     => 'Ämnen',
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'mikro-amne' ],
        ] );

        // Plattform
        register_taxonomy( 'mikro_plattform', 'mikroinlagg', [
            'labels' => [
                'name'          => 'Plattformar',
                'singular_name' => 'Plattform',
                'all_items'     => 'Alla plattformar',
                'edit_item'     => 'Redigera plattform',
                'add_new_item'  => 'Lägg till plattform',
                'menu_name'     => 'Plattformar',
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'mikro-plattform' ],
        ] );

        // Taggar
        register_taxonomy( 'mikro_taggar', 'mikroinlagg', [
            'labels' => [
                'name'          => 'Taggar',
                'singular_name' => 'Tagg',
                'all_items'     => 'Alla taggar',
                'add_new_item'  => 'Lägg till tagg',
                'menu_name'     => 'Taggar',
            ],
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'mikro-tagg' ],
        ] );
    }

    public static function register_meta(): void {
        $fields = [
            'mikro_originallank' => [
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'description'       => 'URL till originalinlägget på social plattform.',
            ],
            'mikro_exklusivt' => [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'description'       => 'Publiceras exklusivt på bloggen.',
            ],
            'mikro_pinned' => [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'description'       => 'Pinnat inlägg visas överst i flödet.',
            ],
        ];

        foreach ( $fields as $key => $args ) {
            register_post_meta( 'mikroinlagg', $key, array_merge( [
                'single'       => true,
                'show_in_rest' => true,
            ], $args ) );
        }
    }
}

Mikro_CPT::init();
