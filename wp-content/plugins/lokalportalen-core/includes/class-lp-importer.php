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
        $result = array('source_id' => $source_id, 'created' => 0, 'skipped' => 0, 'filtered' => 0, 'errors' => array());
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

        $configured_max = (int) get_post_meta($source_id, '_lp_max_items', true);
        $max_items = (int) apply_filters('lp_import_max_items', $configured_max > 0 ? $configured_max : 20, $source_id);
        $items = $feed->get_items(0, $feed->get_item_quantity($max_items));
        $status = get_post_meta($source_id, '_lp_publish_mode', true) === 'publish' ? 'publish' : 'draft';
        $max_age_days = max(0, (int) get_post_meta($source_id, '_lp_max_age_days', true));
        $include = self::keywords((string) get_post_meta($source_id, '_lp_include_keywords', true));
        $exclude = self::keywords((string) get_post_meta($source_id, '_lp_exclude_keywords', true));

        foreach ($items as $item) {
            $permalink = esc_url_raw((string) $item->get_permalink());
            $title = sanitize_text_field(wp_strip_all_tags((string) $item->get_title()));
            $description = wp_trim_words(wp_strip_all_tags((string) ($item->get_description() ?: $item->get_content())), 45, '…');
            $timestamp = (int) ($item->get_date('U') ?: 0);
            if (!self::passes_filters($title . ' ' . $description, $timestamp, $include, $exclude, $max_age_days)) {
                $result['filtered']++;
                continue;
            }
            $external_id = sanitize_text_field((string) ($item->get_id() ?: hash('sha256', $permalink)));
            if (self::exists($external_id, $permalink)) {
                $result['skipped']++;
                continue;
            }

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

    private static function keywords(string $csv): array
    {
        $items = array_map('trim', explode(',', mb_strtolower($csv)));
        return array_values(array_unique(array_filter($items, static fn(string $item): bool => $item !== '')));
    }

    private static function passes_filters(string $text, int $timestamp, array $include, array $exclude, int $max_age_days): bool
    {
        $haystack = mb_strtolower($text);
        foreach ($exclude as $word) {
            if (str_contains($haystack, $word)) {
                return false;
            }
        }
        if ($include) {
            $matched = false;
            foreach ($include as $word) {
                if (str_contains($haystack, $word)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }
        return $max_age_days === 0 || $timestamp === 0 || $timestamp >= time() - ($max_age_days * DAY_IN_SECONDS);
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
