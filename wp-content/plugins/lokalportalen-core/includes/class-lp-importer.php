<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class LP_Importer
{
    private const CRON_HOOK = 'lp_hourly_import';

    public static function register_hooks(): void
    {
        add_action(self::CRON_HOOK, array(__CLASS__, 'run_scheduled'));
        add_action('init', array(__CLASS__, 'ensure_schedule'));
    }

    public static function ensure_schedule(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            self::schedule();
        }
    }

    public static function schedule(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 300, 'hourly', self::CRON_HOOK);
        }
    }

    public static function unschedule(): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public static function run_scheduled(): void
    {
        $sources = get_posts(array(
            'post_type' => 'lp_source',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => '_lp_source_active',
            'meta_value' => '1',
            'fields' => 'ids',
        ));
        foreach ($sources as $source_id) {
            self::import_source((int) $source_id);
        }
    }

    public static function import_source(int $source_id): array
    {
        $result = array('source_id' => $source_id, 'created' => 0, 'skipped' => 0, 'errors' => array());
        $source = get_post($source_id);
        $url = esc_url_raw((string) get_post_meta($source_id, '_lp_source_url', true));

        if (!$source || $source->post_type !== 'lp_source' || !$url) {
            $result['errors'][] = 'Kilden eller feed-URL-en er ugyldig.';
            self::log($result);
            return $result;
        }

        require_once ABSPATH . WPINC . '/feed.php';
        $feed = fetch_feed($url);
        if (is_wp_error($feed)) {
            $result['errors'][] = $feed->get_error_message();
            self::log($result);
            return $result;
        }

        $max_items = (int) apply_filters('lp_import_max_items', 20, $source_id);
        $items = $feed->get_items(0, $feed->get_item_quantity($max_items));
        $status = get_post_meta($source_id, '_lp_publish_mode', true) === 'publish' ? 'publish' : 'draft';

        foreach ($items as $item) {
            $permalink = esc_url_raw((string) $item->get_permalink());
            $external_id = sanitize_text_field((string) ($item->get_id() ?: hash('sha256', $permalink)));
            if (self::exists($external_id, $permalink)) {
                $result['skipped']++;
                continue;
            }

            $title = sanitize_text_field(wp_strip_all_tags((string) $item->get_title()));
            $description = wp_trim_words(wp_strip_all_tags((string) $item->get_description()), 45, '…');
            $date = $item->get_date('Y-m-d H:i:s');
            $post_id = wp_insert_post(array(
                'post_type' => 'lp_current',
                'post_status' => $status,
                'post_title' => $title ?: 'Uten tittel',
                'post_excerpt' => $description,
                'post_content' => $description,
                'post_date' => $date ?: current_time('mysql'),
                'meta_input' => array(
                    '_lp_source_id' => $source_id,
                    '_lp_source_name' => $source->post_title,
                    '_lp_source_url' => $permalink,
                    '_lp_external_id' => $external_id,
                    '_lp_imported_at' => current_time('mysql', true),
                ),
            ), true);

            if (is_wp_error($post_id)) {
                $result['errors'][] = $post_id->get_error_message();
            } else {
                $result['created']++;
            }
        }

        update_post_meta($source_id, '_lp_last_import_at', current_time('mysql', true));
        update_post_meta($source_id, '_lp_last_import_summary', wp_json_encode($result));
        self::log($result);
        return $result;
    }

    private static function exists(string $external_id, string $url): bool
    {
        $meta_query = array('relation' => 'OR');
        if ($external_id !== '') {
            $meta_query[] = array('key' => '_lp_external_id', 'value' => $external_id);
        }
        if ($url !== '') {
            $meta_query[] = array('key' => '_lp_source_url', 'value' => $url);
        }
        if (count($meta_query) === 1) {
            return false;
        }

        $query = new WP_Query(array(
            'post_type' => 'lp_current',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => $meta_query,
        ));
        return $query->have_posts();
    }

    private static function log(array $result): void
    {
        $source_name = get_the_title((int) $result['source_id']) ?: 'Ukjent kilde';
        wp_insert_post(array(
            'post_type' => 'lp_import_log',
            'post_status' => 'publish',
            'post_title' => sprintf('%s – %s', $source_name, current_time('Y-m-d H:i:s')),
            'post_content' => wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ));
    }
}

