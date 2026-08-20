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
        add_shortcode('lokalportalen_finn', array(__CLASS__, 'directory'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_styles'));
    }

    public static function register_styles(): void
    {
        wp_register_style('lokalportalen-core', plugins_url('assets/frontend.css', LP_CORE_FILE), array(), LP_CORE_VERSION);
    }

    public static function current_items(array $atts = array()): string
    {
        $atts = shortcode_atts(array('antall' => 8, 'kladder' => '0'), $atts, 'lokalportalen_aktuelt');
        $post_status = $atts['kladder'] === '1' && current_user_can('edit_posts') ? array('publish', 'draft') : 'publish';
        return self::render_query(new WP_Query(array(
            'post_type' => 'lp_current',
            'post_status' => $post_status,
            'posts_per_page' => min(30, max(1, absint($atts['antall']))),
            'no_found_rows' => true,
        )), 'lp-current-list');
    }

    public static function events(array $atts = array()): string
    {
        $atts = shortcode_atts(array('antall' => 8, 'kladder' => '0'), $atts, 'lokalportalen_arrangementer');
        $post_status = $atts['kladder'] === '1' && current_user_can('edit_posts') ? array('publish', 'draft') : 'publish';
        $now = current_time('Y-m-d\TH:i');
        return self::render_query(new WP_Query(array(
            'post_type' => 'lp_event',
            'post_status' => $post_status,
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
        $atts = shortcode_atts(array('kladder' => '0'), $atts, 'lokalportalen_forside');
        return '<section class="lokalportalen-overview"><div><h2>Aktuelt</h2>' . self::current_items(array('antall' => 6, 'kladder' => $atts['kladder'])) . '</div><div><h2>Arrangementer</h2>' . self::events(array('antall' => 6, 'kladder' => $atts['kladder'])) . '</div><div><h2>Finn i Hadsel</h2>' . self::directory(array('antall' => 9, 'kladder' => $atts['kladder'])) . '</div></section>';
    }

    public static function directory(array $atts = array()): string
    {
        $atts = shortcode_atts(array('antall' => 12, 'type' => '', 'kladder' => '0'), $atts, 'lokalportalen_finn');
        $types = array('lp_business', 'lp_experience', 'lp_organization');
        if ($atts['type'] && in_array($atts['type'], $types, true)) {
            $types = array($atts['type']);
        }
        $post_status = $atts['kladder'] === '1' && current_user_can('edit_posts') ? array('publish', 'draft') : 'publish';
        return self::render_query(new WP_Query(array(
            'post_type' => $types,
            'post_status' => $post_status,
            'posts_per_page' => min(60, max(1, absint($atts['antall']))),
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
        )), 'lp-directory-list');
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
            $venue = (string) get_post_meta(get_the_ID(), '_lp_venue', true);
            $website = (string) get_post_meta(get_the_ID(), '_lp_website', true);
            $address = (string) get_post_meta(get_the_ID(), '_lp_address', true);
            $external_image = (string) get_post_meta(get_the_ID(), '_lp_image_url', true);
            $post_type = get_post_type();
            echo '<article class="lp-card">';
            if (has_post_thumbnail()) {
                echo '<a class="lp-card__image" href="' . esc_url(get_permalink()) . '">' . get_the_post_thumbnail(get_the_ID(), 'medium_large', array('loading' => 'lazy')) . '</a>';
            } elseif ($external_image) {
                echo '<a class="lp-card__image" href="' . esc_url(get_permalink()) . '"><img loading="lazy" src="' . esc_url($external_image) . '" alt=""></a>';
            }
            echo '<div class="lp-card__meta">';
            if ($source_name) {
                echo '<span>' . esc_html($source_name) . '</span>';
            }
            if ($post_type === 'lp_current') {
                echo '<time datetime="' . esc_attr(get_the_date(DATE_W3C)) . '">' . esc_html(get_the_date('j. M Y')) . '</time>';
            }
            if ($start_at) {
                echo '<span>' . esc_html(wp_date('j. M Y H:i', strtotime($start_at))) . '</span>';
            }
            if ($venue) {
                echo '<span>' . esc_html($venue) . '</span>';
            } elseif ($address) {
                echo '<span>' . esc_html($address) . '</span>';
            }
            echo '</div>';
            echo '<h3><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
            if (get_the_excerpt()) {
                echo '<p>' . esc_html(get_the_excerpt()) . '</p>';
            }
            if ($source_url) {
                echo '<a class="lp-card__source" rel="noopener noreferrer" href="' . esc_url($source_url) . '">Les hos originalkilden <span aria-hidden="true">→</span></a>';
            } elseif ($website) {
                echo '<a class="lp-card__source" rel="noopener noreferrer" href="' . esc_url($website) . '">Besøk nettstedet <span aria-hidden="true">→</span></a>';
            }
            echo '</article>';
        }
        echo '</div>';
        wp_reset_postdata();
        return (string) ob_get_clean();
    }
}
