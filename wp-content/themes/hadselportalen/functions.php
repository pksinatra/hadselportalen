<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function hp_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
    register_nav_menus(array('primary' => 'Hovedmeny'));
}
add_action('after_setup_theme', 'hp_theme_setup');

/**
 * The current front page still has Elementor's header/footer page template
 * assigned. Keep this theme's dedicated front page authoritative while
 * Elementor remains installed during the migration.
 */
function hp_front_page_template(string $template): string
{
    if (is_front_page()) {
        $front_page = get_template_directory() . '/front-page.php';
        if (is_readable($front_page)) {
            return $front_page;
        }
    }

    return $template;
}
add_filter('template_include', 'hp_front_page_template', 999);

function hp_theme_assets(): void
{
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style('hadselportalen', get_stylesheet_uri(), array(), $version);
    wp_enqueue_script('hadselportalen', get_template_directory_uri() . '/assets/site.js', array(), $version, true);
}
add_action('wp_enqueue_scripts', 'hp_theme_assets');

function hp_primary_fallback(): void
{
    echo '<ul>';
    echo '<li><a href="' . esc_url(home_url('/')) . '">HadselPortalen</a></li>';
    echo '<li><a href="' . esc_url(home_url('/naeringslivet-i-hadsel')) . '">Næringslivet</a></li>';
    echo '<li><a href="' . esc_url(home_url('/ledige-stillinger-i-hadsel')) . '">Jobb</a></li>';
    echo '</ul>';
}
