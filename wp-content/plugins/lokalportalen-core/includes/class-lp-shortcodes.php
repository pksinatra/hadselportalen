<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class LP_Shortcodes
{
    public static function register_hooks(): void
    {
        add_shortcode('lokalportalen_aktuelt', array(__CLASS__, 'current_items'));
        add_shortcode('lokalportalen_arrangementer', array(__CLASS__, 'events'));
        add_shortcode('lokalportalen_forside', array(__CLASS__, 'portal'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_styles'));
    }

    public static function register_styles(): void
    {
        wp_register_style('lokalportalen-core', plugins_url('assets/frontend.css', LP_CORE_FILE), array(), LP_CORE_VERSION);
    }

    public static function current_items(array $atts = array()): string
    {
        $atts = shortcode_atts(array('antall' => 8), $atts, 'lokalportalen_aktuelt');
        return self::render_query(new WP_Query(array(
            'post_type' => 'lp_current',
            'post_status' => 'publish',
            'posts_per_page' => min(30, max(1, absint($atts['antall']))),
            'no_found_rows' => true,
        )), 'lp-current-list');
    }

    public static function events(array $atts = array()): string
    {
        $atts = shortcode_atts(array('antall' => 8), $atts, 'lokalportalen_arrangementer');
        $now = current_time('Y-m-d\TH:i');
        return self::render_query(new WP_Query(array(
            'post_type' => 'lp_event',
            'post_status' => 'publish',
            'posts_per_page' => min(30, max(1, absint($atts['antall']))),
            'meta_key' => '_lp_start_at',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => '_lp_end_at', 'value' => $now, 'compare' => '>=', 'type' => 'CHAR'),
                array('key' => '_lp_end_at', 'compare' => 'NOT EXISTS'),
            ),
            'no_found_rows' => true,
        )), 'lp-event-list');
    }

    public static function portal(array $atts = array()): string
    {
        return '<section class="lokalportalen-overview"><div><h2>Aktuelt</h2>' . self::current_items(array('antall' => 6)) . '</div><div><h2>Arrangementer</h2>' . self::events(array('antall' => 6)) . '</div></section>';
    }

    private static function render_query(WP_Query $query, string $class): string
    {
        wp_enqueue_style('lokalportalen-core');
        if (!$query->have_posts()) {
            return '<p>Ingen oppføringer akkurat nå.</p>';
        }
        ob_start();
        echo '<div class="' . esc_attr($class) . '">';
        while ($query->have_posts()) {
            $query->the_post();
            $source_url = (string) get_post_meta(get_the_ID(), '_lp_source_url', true);
            $source_name = (string) get_post_meta(get_the_ID(), '_lp_source_name', true);
            $start_at = (string) get_post_meta(get_the_ID(), '_lp_start_at', true);
            echo '<article class="lp-card">';
            echo '<div class="lp-card__meta">';
            if ($source_name) {
                echo '<span>' . esc_html($source_name) . '</span>';
            }
            echo '<time datetime="' . esc_attr(get_the_date(DATE_W3C)) . '">' . esc_html(get_the_date('j. M Y')) . '</time>';
            if ($start_at) {
                echo '<span>' . esc_html(wp_date('j. M Y H:i', strtotime($start_at))) . '</span>';
            }
            echo '</div>';
            echo '<h3><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
            if (get_the_excerpt()) {
                echo '<p>' . esc_html(get_the_excerpt()) . '</p>';
            }
            if ($source_url) {
                echo '<a class="lp-card__source" rel="noopener noreferrer" href="' . esc_url($source_url) . '">Les hos originalkilden <span aria-hidden="true">→</span></a>';
            }
            echo '</article>';
        }
        echo '</div>';
        wp_reset_postdata();
        return (string) ob_get_clean();
    }
}
