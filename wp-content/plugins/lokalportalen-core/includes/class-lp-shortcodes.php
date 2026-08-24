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
        add_shortcode('lokalportalen_meldinger', array(__CLASS__, 'notices'));
        add_shortcode('lokalportalen_jobber', array(__CLASS__, 'jobs'));
        add_shortcode('lokalportalen_forside', array(__CLASS__, 'portal'));
        add_shortcode('lokalportalen_finn', array(__CLASS__, 'directory'));
        add_shortcode('lokalportalen_promo', array(__CLASS__, 'promo'));
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
        $atts = shortcode_atts(array(
            'kladder' => '0',
            'promo' => '1',
            'promo_tittel' => 'Opplev Stokmarknes',
            'promo_tekst' => 'Oppdag byen, Hurtigrutehistorien og sentrum med VisitStokmarknes.',
            'promo_url' => 'https://visitstokmarknes.com/',
            'promo_lenketekst' => 'Besøk VisitStokmarknes',
        ), $atts, 'lokalportalen_forside');
        $promo = $atts['promo'] === '1' ? self::promo(array(
            'tittel' => $atts['promo_tittel'],
            'tekst' => $atts['promo_tekst'],
            'url' => $atts['promo_url'],
            'lenketekst' => $atts['promo_lenketekst'],
        )) : '';
        return '<section class="lokalportalen-overview"><div><h2>Praktiske meldinger</h2>' . self::notices(array('antall' => 6, 'kladder' => $atts['kladder'])) . '</div><div><h2>Aktuelt</h2>' . self::current_items(array('antall' => 6, 'kladder' => $atts['kladder'])) . '</div><div><h2>Arrangementer</h2>' . self::events(array('antall' => 6, 'kladder' => $atts['kladder'])) . '</div><div><h2>Ledige stillinger</h2>' . self::jobs(array('antall' => 6, 'kladder' => $atts['kladder'])) . '</div>' . $promo . '<div><h2>Finn i Hadsel</h2>' . self::directory(array('antall' => 9, 'kladder' => $atts['kladder'])) . '</div></section>';
    }

    public static function jobs(array $atts = array()): string
    {
        $atts = shortcode_atts(array('antall' => 12, 'kladder' => '0'), $atts, 'lokalportalen_jobber');
        $post_status = $atts['kladder'] === '1' && current_user_can('edit_posts') ? array('publish', 'draft') : 'publish';
        $today = current_time('Y-m-d');
        return self::render_query(new WP_Query(array(
            'post_type' => 'lp_job',
            'post_status' => $post_status,
            'posts_per_page' => min(60, max(1, absint($atts['antall']))),
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => '_lp_application_deadline', 'value' => $today, 'compare' => '>=', 'type' => 'DATE'),
                array('key' => '_lp_application_deadline', 'value' => '', 'compare' => '='),
                array('key' => '_lp_application_deadline', 'compare' => 'NOT EXISTS'),
            ),
            'no_found_rows' => true,
        )), 'lp-job-list');
    }

    public static function notices(array $atts = array()): string
    {
        $atts = shortcode_atts(array('antall' => 8, 'kladder' => '0'), $atts, 'lokalportalen_meldinger');
        $post_status = $atts['kladder'] === '1' && current_user_can('edit_posts') ? array('publish', 'draft') : 'publish';
        $now = current_time('Y-m-d\TH:i');
        return self::render_query(new WP_Query(array(
            'post_type' => 'lp_notice',
            'post_status' => $post_status,
            'posts_per_page' => min(30, max(1, absint($atts['antall']))),
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => '_lp_expires_at', 'value' => $now, 'compare' => '>=', 'type' => 'CHAR'),
                array('key' => '_lp_expires_at', 'value' => '', 'compare' => '='),
                array('key' => '_lp_expires_at', 'compare' => 'NOT EXISTS'),
            ),
            'no_found_rows' => true,
        )), 'lp-notice-list');
    }

    public static function directory(array $atts = array()): string
    {
        $atts = shortcode_atts(array('antall' => 12, 'type' => '', 'kladder' => '0', 'filtre' => '1'), $atts, 'lokalportalen_finn');
        $type_labels = array(
            'lp_business' => 'Virksomheter',
            'lp_experience' => 'Opplevelser',
            'lp_organization' => 'Lag og foreninger',
        );
        $types = array_keys($type_labels);
        if ($atts['type'] && in_array($atts['type'], $types, true)) {
            $types = array($atts['type']);
        }
        $selected_type = isset($_GET['lp_type']) ? sanitize_key(wp_unslash($_GET['lp_type'])) : '';
        if ($atts['type'] === '' && isset($type_labels[$selected_type])) {
            $types = array($selected_type);
        }
        $selected_location = isset($_GET['lp_location']) ? absint($_GET['lp_location']) : 0;
        $selected_category = isset($_GET['lp_category']) ? absint($_GET['lp_category']) : 0;
        $search = isset($_GET['lp_q']) ? sanitize_text_field(wp_unslash($_GET['lp_q'])) : '';
        $tax_query = array('relation' => 'AND');
        if ($selected_location > 0) {
            $tax_query[] = array('taxonomy' => 'lp_location', 'field' => 'term_id', 'terms' => array($selected_location));
        }
        if ($selected_category > 0) {
            $tax_query[] = array('taxonomy' => 'lp_category', 'field' => 'term_id', 'terms' => array($selected_category));
        }
        $post_status = $atts['kladder'] === '1' && current_user_can('edit_posts') ? array('publish', 'draft') : 'publish';
        $query_args = array(
            'post_type' => $types,
            'post_status' => $post_status,
            'posts_per_page' => min(60, max(1, absint($atts['antall']))),
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
        );
        if ($search !== '') {
            $query_args['s'] = $search;
        }
        if (count($tax_query) > 1) {
            $query_args['tax_query'] = $tax_query;
        }
        $filters = $atts['filtre'] === '1' ? self::directory_filters($type_labels, $selected_type, $selected_location, $selected_category, $search, $atts['type'] === '') : '';
        return $filters . self::render_query(new WP_Query($query_args), 'lp-directory-list');
    }

    public static function promo(array $atts = array()): string
    {
        $atts = shortcode_atts(array(
            'tittel' => '',
            'tekst' => '',
            'url' => '',
            'lenketekst' => 'Les mer',
        ), $atts, 'lokalportalen_promo');
        $url = esc_url((string) $atts['url']);
        if ($url === '' || trim((string) $atts['tittel']) === '') {
            return '';
        }
        wp_enqueue_style('lokalportalen-core');
        return sprintf(
            '<aside class="lp-promo" aria-label="Anbefalt"><div><span class="lp-promo__label">Tips</span><h2>%s</h2><p>%s</p></div><a class="lp-promo__link" href="%s" rel="noopener noreferrer">%s <span aria-hidden="true">→</span></a></aside>',
            esc_html((string) $atts['tittel']),
            esc_html((string) $atts['tekst']),
            $url,
            esc_html((string) $atts['lenketekst'])
        );
    }

    private static function directory_filters(array $type_labels, string $selected_type, int $selected_location, int $selected_category, string $search, bool $show_type): string
    {
        $locations = get_terms(array('taxonomy' => 'lp_location', 'hide_empty' => true));
        $categories = get_terms(array('taxonomy' => 'lp_category', 'hide_empty' => true));
        if (is_wp_error($locations)) {
            $locations = array();
        }
        if (is_wp_error($categories)) {
            $categories = array();
        }
        ob_start();
        echo '<form class="lp-directory-filters" method="get" action="' . esc_url((string) get_permalink()) . '">';
        echo '<div><label for="lp-q">Søk</label><input id="lp-q" name="lp_q" type="search" value="' . esc_attr($search) . '" placeholder="Navn eller nøkkelord"></div>';
        if ($show_type) {
            echo '<div><label for="lp-type">Type</label><select id="lp-type" name="lp_type"><option value="">Alle typer</option>';
            foreach ($type_labels as $value => $label) {
                echo '<option value="' . esc_attr($value) . '"' . selected($selected_type, $value, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select></div>';
        }
        self::term_select('lp-location', 'lp_location', 'Sted', 'Alle steder', $locations, $selected_location);
        self::term_select('lp-category', 'lp_category', 'Kategori', 'Alle kategorier', $categories, $selected_category);
        echo '<div class="lp-directory-filters__actions"><button type="submit">Finn</button><a href="' . esc_url((string) get_permalink()) . '">Nullstill</a></div>';
        echo '</form>';
        return (string) ob_get_clean();
    }

    private static function term_select(string $id, string $name, string $label, string $empty_label, array $terms, int $selected_term): void
    {
        echo '<div><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label><select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '"><option value="">' . esc_html($empty_label) . '</option>';
        foreach ($terms as $term) {
            echo '<option value="' . (int) $term->term_id . '"' . selected($selected_term, (int) $term->term_id, false) . '>' . esc_html($term->name) . '</option>';
        }
        echo '</select></div>';
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
            $expires_at = (string) get_post_meta(get_the_ID(), '_lp_expires_at', true);
            $venue = (string) get_post_meta(get_the_ID(), '_lp_venue', true);
            $website = (string) get_post_meta(get_the_ID(), '_lp_website', true);
            $address = (string) get_post_meta(get_the_ID(), '_lp_address', true);
            $employer = (string) get_post_meta(get_the_ID(), '_lp_employer', true);
            $deadline = (string) get_post_meta(get_the_ID(), '_lp_application_deadline', true);
            $employment_type = (string) get_post_meta(get_the_ID(), '_lp_employment_type', true);
            $position_percentage = (string) get_post_meta(get_the_ID(), '_lp_position_percentage', true);
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
            if ($post_type === 'lp_job' && $employer) {
                echo '<span>' . esc_html($employer) . '</span>';
            }
            if ($post_type === 'lp_current') {
                echo '<time datetime="' . esc_attr(get_the_date(DATE_W3C)) . '">' . esc_html(get_the_date('j. M Y')) . '</time>';
            }
            if ($start_at) {
                echo '<span>' . esc_html(wp_date('j. M Y H:i', strtotime($start_at))) . '</span>';
            }
            if ($post_type === 'lp_notice' && $expires_at) {
                echo '<span>Gjelder til ' . esc_html(wp_date('j. M Y H:i', strtotime($expires_at))) . '</span>';
            }
            if ($post_type === 'lp_job' && $deadline) {
                echo '<span>Søknadsfrist ' . esc_html(wp_date('j. M Y', strtotime($deadline))) . '</span>';
            }
            if ($post_type === 'lp_job' && $employment_type) {
                echo '<span>' . esc_html($employment_type) . '</span>';
            }
            if ($post_type === 'lp_job' && $position_percentage) {
                echo '<span>' . esc_html($position_percentage) . '</span>';
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
