<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class LP_Post_Types
{
    public static function register_hooks(): void
    {
        add_action('init', array(__CLASS__, 'register'));
    }

    public static function register(): void
    {
        self::post_type('lp_source', 'Kilde', 'Kilder', 'dashicons-rss', false, array('title'));
        self::post_type('lp_place', 'Sted', 'Steder', 'dashicons-location-alt', true, array('title', 'editor', 'excerpt', 'thumbnail'));
        self::post_type('lp_current', 'Aktuelt', 'Aktuelt', 'dashicons-megaphone', true, array('title', 'editor', 'excerpt', 'thumbnail', 'author'));
        self::post_type('lp_event', 'Arrangement', 'Arrangementer', 'dashicons-calendar-alt', true, array('title', 'editor', 'excerpt', 'thumbnail', 'author'));
        self::post_type('lp_business', 'Virksomhet', 'Virksomheter', 'dashicons-store', true, array('title', 'editor', 'excerpt', 'thumbnail', 'author'));
        self::post_type('lp_experience', 'Opplevelse', 'Opplevelser', 'dashicons-palmtree', true, array('title', 'editor', 'excerpt', 'thumbnail', 'author'));
        self::post_type('lp_organization', 'Lag eller forening', 'Lag og foreninger', 'dashicons-groups', true, array('title', 'editor', 'excerpt', 'thumbnail', 'author'));

        register_post_type('lp_import_log', array(
            'labels' => array('name' => 'Importlogg', 'singular_name' => 'Importlogg'),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'lokalportalen',
            'capability_type' => 'post',
            'capabilities' => array('create_posts' => 'do_not_allow'),
            'map_meta_cap' => true,
            'supports' => array('title', 'editor'),
        ));

        $taxonomy_labels = array(
            'lp_location' => array('Stedskategorier', 'Stedskategori'),
            'lp_category' => array('Portalkategorier', 'Portalkategori'),
        );

        foreach ($taxonomy_labels as $taxonomy => $labels) {
            register_taxonomy($taxonomy, array('lp_place', 'lp_current', 'lp_event', 'lp_business', 'lp_experience', 'lp_organization'), array(
                'labels' => array('name' => $labels[0], 'singular_name' => $labels[1]),
                'public' => true,
                'show_in_rest' => true,
                'hierarchical' => true,
                'rewrite' => array('slug' => str_replace('lp_', '', $taxonomy)),
            ));
        }
    }

    private static function post_type(string $key, string $singular, string $plural, string $icon, bool $public, array $supports): void
    {
        register_post_type($key, array(
            'labels' => array(
                'name' => $plural,
                'singular_name' => $singular,
                'add_new_item' => 'Legg til ' . mb_strtolower($singular),
                'edit_item' => 'Rediger ' . mb_strtolower($singular),
                'all_items' => $plural,
            ),
            'public' => $public,
            'show_ui' => true,
            'show_in_menu' => 'lokalportalen',
            'show_in_rest' => true,
            'has_archive' => $public,
            'rewrite' => $public ? array('slug' => str_replace('lp_', '', $key)) : false,
            'menu_icon' => $icon,
            'supports' => $supports,
        ));
    }
}
