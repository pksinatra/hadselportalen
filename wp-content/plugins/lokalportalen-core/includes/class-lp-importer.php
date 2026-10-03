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

        if (get_post_meta($source_id, '_lp_source_type', true) === 'dx_culture') {
            self::import_dx_culture($source, $url, $result);
            update_post_meta($source_id, '_lp_last_import_at', current_time('mysql', true));
            update_post_meta($source_id, '_lp_last_import_summary', wp_json_encode($result));
            self::log($result);
            return $result;
        }

        if (get_post_meta($source_id, '_lp_source_type', true) === 'teamtailor_jobs') {
            self::import_teamtailor_jobs($source, $url, $result);
            update_post_meta($source_id, '_lp_last_import_at', current_time('mysql', true));
            update_post_meta($source_id, '_lp_last_import_summary', wp_json_encode($result));
            self::log($result);
            return $result;
        }

        if (get_post_meta($source_id, '_lp_source_type', true) === 'webcruiter_jobs') {
            self::import_webcruiter_jobs($source, $url, $result);
            update_post_meta($source_id, '_lp_last_import_at', current_time('mysql', true));
            update_post_meta($source_id, '_lp_last_import_summary', wp_json_encode($result));
            self::log($result);
            return $result;
        }

        if (get_post_meta($source_id, '_lp_source_type', true) === 'nav_jobs') {
            self::import_nav_jobs($source, $url, $result);
            update_post_meta($source_id, '_lp_last_import_at', current_time('mysql', true));
            update_post_meta($source_id, '_lp_last_import_summary', wp_json_encode($result));
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
            $description = self::excerpt_from_html((string) ($item->get_description() ?: $item->get_content()));
            $timestamp = (int) ($item->get_date('U') ?: 0);
            if (!self::passes_filters($title . ' ' . $description, $timestamp, $include, $exclude, $max_age_days)) {
                $result['filtered']++;
                continue;
            }
            $external_id = sanitize_text_field((string) ($item->get_id() ?: hash('sha256', $permalink)));
            if (self::exists('lp_current', $external_id, $permalink)) {
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

    private static function import_dx_culture(WP_Post $source, string $url, array &$result): void
    {
        $parts = wp_parse_url($url);
        $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        $script_url = '';
        if (preg_match('~\.js(?:\?.*)?$~i', $url)) {
            $script_url = $url;
        } else {
            $page_response = wp_remote_get($url, array('timeout' => 20, 'user-agent' => 'Lokalportalen/' . LP_CORE_VERSION));
            if (is_wp_error($page_response)) {
                $result['errors'][] = $page_response->get_error_message();
                return;
            }
            $html = wp_remote_retrieve_body($page_response);
            if (!preg_match('~src=["\']([^"\']*path---kulturprogram[^"\']+\.js)["\']~i', $html, $script_match)) {
                $result['errors'][] = 'Fant ikke DX-kulturprogrammets datafil. Oppdater kilden med gjeldende path---kulturprogram-fil.';
                return;
            }
            $script_url = str_starts_with($script_match[1], 'http') ? $script_match[1] : rtrim($origin, '/') . '/' . ltrim($script_match[1], '/');
        }
        $script_response = wp_remote_get($script_url, array('timeout' => 20, 'user-agent' => 'Lokalportalen/' . LP_CORE_VERSION));
        if (is_wp_error($script_response)) {
            $result['errors'][] = $script_response->get_error_message();
            return;
        }
        $javascript = wp_remote_retrieve_body($script_response);
        $pattern = '~\{id:"([^"]+_culture_culture)",title:"((?:\\\\.|[^"])*)",image:"((?:\\\\.|[^"])*)",description:(?:null|"((?:\\\\.|[^"])*)"),link:"([^"]+)",category:"([^"]*)",begin:"([^"]+)".*?tickets:\[\{.*?date:"([^"]+)".*?location:"([^"]*)".*?link:"([^"]*)"~s';
        if (!preg_match_all($pattern, $javascript, $matches, PREG_SET_ORDER)) {
            $result['errors'][] = 'DX-datafilen inneholdt ingen gjenkjennelige arrangementer.';
            return;
        }

        $status = get_post_meta($source->ID, '_lp_publish_mode', true) === 'publish' ? 'publish' : 'draft';
        $max_items = max(1, (int) (get_post_meta($source->ID, '_lp_max_items', true) ?: 30));
        foreach (array_slice($matches, 0, $max_items) as $match) {
            $external_id = sanitize_text_field($match[1]);
            $title = sanitize_text_field(stripcslashes($match[2]));
            $image = esc_url_raw(stripcslashes($match[3]));
            $description = sanitize_text_field(stripcslashes($match[4] ?? ''));
            $detail_url = esc_url_raw(rtrim($origin, '/') . '/' . ltrim($match[5], '/'));
            $category = sanitize_text_field($match[6]);
            $start = sanitize_text_field($match[8] ?: $match[7]);
            $venue = sanitize_text_field(stripcslashes($match[9]));
            $booking_url = esc_url_raw(stripcslashes($match[10]));
            if (strtotime($start) < current_time('timestamp')) {
                $result['filtered']++;
                continue;
            }
            if (self::exists('lp_event', $external_id, $detail_url)) {
                $result['skipped']++;
                continue;
            }
            $post_id = wp_insert_post(array(
                'post_type' => 'lp_event',
                'post_status' => $status,
                'post_title' => $title ?: 'Arrangement uten tittel',
                'post_excerpt' => $description ?: $category,
                'post_content' => $description,
                'meta_input' => array(
                    '_lp_source_id' => $source->ID,
                    '_lp_source_name' => $source->post_title,
                    '_lp_source_url' => $detail_url,
                    '_lp_external_id' => $external_id,
                    '_lp_start_at' => str_replace(' ', 'T', $start),
                    '_lp_end_at' => str_replace(' ', 'T', $start),
                    '_lp_venue' => $venue,
                    '_lp_booking_url' => $booking_url,
                    '_lp_image_url' => $image,
                    '_lp_imported_at' => current_time('mysql', true),
                ),
            ), true);
            if (is_wp_error($post_id)) {
                $result['errors'][] = $post_id->get_error_message();
            } else {
                if ($category !== '') {
                    wp_set_object_terms($post_id, array($category), 'lp_category', true);
                }
                $result['created']++;
            }
        }
    }

    private static function import_teamtailor_jobs(WP_Post $source, string $url, array &$result): void
    {
        require_once ABSPATH . WPINC . '/feed.php';
        $feed = fetch_feed($url);
        if (is_wp_error($feed)) {
            $result['errors'][] = $feed->get_error_message();
            return;
        }

        $status = get_post_meta($source->ID, '_lp_publish_mode', true) === 'publish' ? 'publish' : 'draft';
        $include = self::keywords((string) get_post_meta($source->ID, '_lp_include_keywords', true));
        $exclude = self::keywords((string) get_post_meta($source->ID, '_lp_exclude_keywords', true));
        $items = $feed->get_items(0, $feed->get_item_quantity(200));
        $seen = array();

        foreach ($items as $item) {
            $permalink = esc_url_raw((string) $item->get_permalink());
            $external_id = sanitize_text_field((string) ($item->get_id() ?: hash('sha256', $permalink)));
            $locations = self::teamtailor_locations($item);
            $location_text = implode(' ', $locations);
            if (!self::passes_filters($location_text, 0, $include, $exclude, 0)) {
                $result['filtered']++;
                continue;
            }
            $seen[] = $external_id;
            $title = sanitize_text_field(wp_strip_all_tags((string) $item->get_title()));
            $description = self::excerpt_from_html((string) ($item->get_description() ?: $item->get_content()));
            $division = self::first_teamtailor_value($item, 'division');
            $department = self::first_teamtailor_value($item, 'department');
            $address = implode(', ', array_filter($locations));
            $date = $item->get_date('Y-m-d H:i:s');
            $existing_id = self::find_existing_id('lp_job', $external_id, $permalink);
            if ($existing_id > 0) {
                $post_data = array(
                    'ID' => $existing_id,
                    'post_status' => $status,
                    'post_title' => $title ?: 'Ledig stilling',
                    'post_excerpt' => $description,
                    'post_content' => $description,
                );
                if ($date) {
                    $post_data['post_date'] = $date;
                }
                wp_update_post($post_data);
                update_post_meta($existing_id, '_lp_source_id', $source->ID);
                update_post_meta($existing_id, '_lp_source_name', $source->post_title);
                update_post_meta($existing_id, '_lp_source_url', $permalink);
                update_post_meta($existing_id, '_lp_employer', $division ?: $source->post_title);
                update_post_meta($existing_id, '_lp_department', $department);
                update_post_meta($existing_id, '_lp_address', $address);
                update_post_meta($existing_id, '_lp_imported_at', current_time('mysql', true));
                if (get_post_meta($existing_id, '_lp_removed_at', true)) {
                    delete_post_meta($existing_id, '_lp_removed_at');
                }
                $result['skipped']++;
                continue;
            }
            $post_id = wp_insert_post(array(
                'post_type' => 'lp_job',
                'post_status' => $status,
                'post_title' => $title ?: 'Ledig stilling',
                'post_excerpt' => $description,
                'post_content' => $description,
                'post_date' => $date ?: current_time('mysql'),
                'meta_input' => array(
                    '_lp_source_id' => $source->ID,
                    '_lp_source_name' => $source->post_title,
                    '_lp_source_url' => $permalink,
                    '_lp_external_id' => $external_id,
                    '_lp_employer' => $division ?: $source->post_title,
                    '_lp_department' => $department,
                    '_lp_address' => $address,
                    '_lp_imported_at' => current_time('mysql', true),
                ),
            ), true);
            if (is_wp_error($post_id)) {
                $result['errors'][] = $post_id->get_error_message();
            } else {
                $result['created']++;
            }
        }

        self::hide_missing_jobs($source->ID, $seen);
    }

    private static function teamtailor_locations($item): array
    {
        $tags = $item->get_item_tags('https://teamtailor.com/locations', 'locations') ?: array();
        $values = array();
        foreach (array('name', 'address', 'zip', 'city', 'country') as $tag) {
            $values = array_merge($values, self::xml_tag_values($tags, $tag));
        }
        return array_values(array_unique(array_filter(array_map('sanitize_text_field', $values))));
    }

    private static function import_webcruiter_jobs(WP_Post $source, string $url, array &$result): void
    {
        $query = array();
        parse_str((string) (wp_parse_url($url, PHP_URL_QUERY) ?: ''), $query);
        $company_id = isset($query['companylock']) ? preg_replace('/[^0-9]/', '', (string) $query['companylock']) : '';
        if ($company_id === '') {
            $result['errors'][] = 'Webcruiter-URL-en mangler companylock.';
            return;
        }

        $endpoint = 'https://candidate.webcruiter.com/api/odvert/companysearch/' . $company_id;
        $payload = array(
            'take' => 100,
            'skip' => 0,
            'page' => 1,
            'pageSize' => 100,
            'sort' => array(array('field' => '1', 'dir' => 'desc')),
            'filter' => array('logic' => 'and', 'filters' => array()),
        );
        $response = wp_remote_post($endpoint, array(
            'timeout' => 25,
            'user-agent' => 'Lokalportalen/' . LP_CORE_VERSION,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($payload),
        ));
        if (is_wp_error($response)) {
            $result['errors'][] = $response->get_error_message();
            return;
        }
        if (wp_remote_retrieve_response_code($response) !== 200) {
            $result['errors'][] = 'Webcruiter svarte med HTTP ' . wp_remote_retrieve_response_code($response) . '.';
            return;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || !isset($data['Data']) || !is_array($data['Data'])) {
            $result['errors'][] = 'Webcruiter returnerte et ukjent dataformat.';
            return;
        }

        $status = get_post_meta($source->ID, '_lp_publish_mode', true) === 'publish' ? 'publish' : 'draft';
        $include = self::keywords((string) get_post_meta($source->ID, '_lp_include_keywords', true));
        $exclude = self::keywords((string) get_post_meta($source->ID, '_lp_exclude_keywords', true));
        $max_items = max(1, min(100, (int) (get_post_meta($source->ID, '_lp_max_items', true) ?: 100)));
        $seen = array();
        $type_labels = array('Regular' => 'Fast', 'Temp' => 'Vikariat', 'Contract' => 'Engasjement', 'Hourly-work' => 'Tilkalling');

        foreach (array_slice($data['Data'], 0, $max_items) as $job) {
            if (empty($job['IsInternet'])) {
                $result['filtered']++;
                continue;
            }
            $workplace = sanitize_text_field((string) ($job['Workplace'] ?? $job['Workplace2'] ?? $job['Workplace3'] ?? ''));
            $department = sanitize_text_field((string) ($job['WorkPlaceFacet'] ?? ''));
            if (!self::passes_filters($workplace . ' ' . $department, 0, $include, $exclude, 0)) {
                $result['filtered']++;
                continue;
            }
            $external_id = sanitize_text_field((string) ($job['Id'] ?? ''));
            $permalink = esc_url_raw((string) ($job['OpenAdvertUrl'] ?? ''));
            if ($external_id === '' || $permalink === '') {
                $result['errors'][] = 'En Webcruiter-annonse manglet ID eller URL.';
                continue;
            }
            $seen[] = $external_id;
            $title = sanitize_text_field(wp_strip_all_tags((string) ($job['Heading'] ?? $job['HeadingNotOverruled'] ?? 'Ledig stilling')));
            $description = self::excerpt_from_html((string) ($job['Presentation'] ?? ''));
            $employer = sanitize_text_field((string) ($job['CompanyName'] ?? $source->post_title));
            $deadline = sanitize_text_field(substr((string) ($job['ApplicationDeadline'] ?? ''), 0, 10));
            $job_type = sanitize_text_field((string) ($job['JobType'] ?? ''));
            $employment_type = $type_labels[$job_type] ?? $job_type;
            $published = sanitize_text_field((string) ($job['PublishedDate'] ?? ''));
            $published_timestamp = $published !== '' ? strtotime(str_replace('/', '-', $published)) : false;
            $post_data = array(
                'post_type' => 'lp_job',
                'post_status' => $status,
                'post_title' => $title ?: 'Ledig stilling',
                'post_excerpt' => $description,
                'post_content' => $description,
                'meta_input' => array(
                    '_lp_source_id' => $source->ID,
                    '_lp_source_name' => $source->post_title,
                    '_lp_source_url' => $permalink,
                    '_lp_external_id' => $external_id,
                    '_lp_employer' => $employer,
                    '_lp_department' => $department,
                    '_lp_address' => $workplace,
                    '_lp_employment_type' => $employment_type,
                    '_lp_application_deadline' => $deadline,
                    '_lp_imported_at' => current_time('mysql', true),
                ),
            );
            if ($published_timestamp) {
                $post_data['post_date'] = wp_date('Y-m-d H:i:s', $published_timestamp);
            }
            $existing_id = self::find_existing_id('lp_job', $external_id, $permalink);
            if ($existing_id > 0) {
                $post_data['ID'] = $existing_id;
                if (get_post_meta($existing_id, '_lp_removed_at', true)) {
                    delete_post_meta($existing_id, '_lp_removed_at');
                }
                $updated = wp_update_post($post_data, true);
                if (is_wp_error($updated)) {
                    $result['errors'][] = $updated->get_error_message();
                } else {
                    $result['skipped']++;
                }
                continue;
            }
            $post_id = wp_insert_post($post_data, true);
            if (is_wp_error($post_id)) {
                $result['errors'][] = $post_id->get_error_message();
            } else {
                $result['created']++;
            }
        }

        self::hide_missing_jobs($source->ID, $seen);
    }

    private static function import_nav_jobs(WP_Post $source, string $url, array &$result): void
    {
        $token = (string) get_post_meta($source->ID, '_lp_nav_token', true);
        if ($token === '') {
            $token = defined('NAV_STILLING_FEED_TOKEN') ? (string) NAV_STILLING_FEED_TOKEN : (string) getenv('NAV_STILLING_FEED_TOKEN');
        }
        if ($token === '') {
            $result['errors'][] = 'NAV_STILLING_FEED_TOKEN er ikke konfigurert på serveren.';
            return;
        }
        $parts = wp_parse_url($url);
        if (($parts['host'] ?? '') !== 'pam-stilling-feed.nav.no') {
            $result['errors'][] = 'NAV-kilden må bruke pam-stilling-feed.nav.no.';
            return;
        }

        $base = 'https://pam-stilling-feed.nav.no';
        $cursor = (string) get_post_meta($source->ID, '_lp_nav_cursor_url', true);
        $status = get_post_meta($source->ID, '_lp_publish_mode', true) === 'publish' ? 'publish' : 'draft';
        $max_age_days = max(1, min(180, (int) (get_post_meta($source->ID, '_lp_max_age_days', true) ?: 180)));
        $max_pages = 5;
        $start_since = (string) get_post_meta($source->ID, '_lp_nav_start_since', true);
        if ($start_since === '') {
            $start_since = gmdate('D, d M Y H:i:s', time() - ($max_age_days * DAY_IN_SECONDS)) . ' GMT';
            update_post_meta($source->ID, '_lp_nav_start_since', $start_since);
            $cursor = '';
            delete_post_meta($source->ID, '_lp_nav_cursor_url');
            delete_post_meta($source->ID, '_lp_nav_etag');
            delete_post_meta($source->ID, '_lp_nav_last_modified');
        }
        $request_url = $cursor !== '' ? $cursor : $url;

        for ($page = 0; $page < $max_pages; $page++) {
            $headers = array('Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token);
            if ($start_since !== '') {
                $headers['If-Modified-Since'] = $start_since;
            } else {
                $etag = (string) get_post_meta($source->ID, '_lp_nav_etag', true);
                $last_modified = (string) get_post_meta($source->ID, '_lp_nav_last_modified', true);
                if ($etag !== '') {
                    $headers['If-None-Match'] = $etag;
                }
                if ($last_modified !== '') {
                    $headers['If-Modified-Since'] = $last_modified;
                }
            }
            $response = wp_remote_get($request_url, array('timeout' => 30, 'user-agent' => 'Lokalportalen/' . LP_CORE_VERSION, 'headers' => $headers));
            if (is_wp_error($response)) {
                $result['errors'][] = $response->get_error_message();
                return;
            }
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 304) {
                return;
            }
            if ($response_code !== 200) {
                $result['errors'][] = 'NAV svarte med HTTP ' . $response_code . '.';
                return;
            }
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
                $result['errors'][] = 'NAV returnerte et ukjent dataformat.';
                return;
            }

            foreach ($data['items'] as $item) {
                $entry = isset($item['_feed_entry']) && is_array($item['_feed_entry']) ? $item['_feed_entry'] : array();
                $external_id = sanitize_text_field((string) ($entry['uuid'] ?? $item['id'] ?? ''));
                if ($external_id === '') {
                    $result['errors'][] = 'En NAV-oppføring manglet UUID.';
                    continue;
                }
                if (($entry['status'] ?? '') !== 'ACTIVE') {
                    self::hide_job_by_external_id($external_id);
                    $result['filtered']++;
                    continue;
                }
                if (mb_strtoupper((string) ($entry['municipal'] ?? '')) !== 'HADSEL') {
                    $result['filtered']++;
                    continue;
                }

                $detail_path = (string) ($item['url'] ?? '');
                $detail_url = str_starts_with($detail_path, 'http') ? $detail_path : $base . '/' . ltrim($detail_path, '/');
                $detail_response = wp_remote_get($detail_url, array('timeout' => 20, 'user-agent' => 'Lokalportalen/' . LP_CORE_VERSION, 'headers' => array('Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token)));
                if (is_wp_error($detail_response) || wp_remote_retrieve_response_code($detail_response) !== 200) {
                    $result['errors'][] = 'Kunne ikke hente NAV-detaljer for ' . $external_id . '.';
                    continue;
                }
                $detail = json_decode(wp_remote_retrieve_body($detail_response), true);
                if (!is_array($detail) || ($detail['status'] ?? '') !== 'ACTIVE') {
                    self::hide_job_by_external_id($external_id);
                    $result['filtered']++;
                    continue;
                }
                $job = is_array($detail['ad_content'] ?? null) ? $detail['ad_content'] : (is_array($detail['json'] ?? null) ? $detail['json'] : array());
                if (!$job) {
                    self::hide_job_by_external_id($external_id);
                    $result['filtered']++;
                    continue;
                }
                $deadline = sanitize_text_field(substr((string) ($job['applicationDue'] ?? $job['expires'] ?? ''), 0, 10));
                if ($deadline !== '' && $deadline < current_time('Y-m-d')) {
                    self::hide_job_by_external_id($external_id);
                    $result['filtered']++;
                    continue;
                }
                $locations = is_array($job['workLocations'] ?? null) ? $job['workLocations'] : array();
                $location = is_array($locations[0] ?? null) ? $locations[0] : array();
                $address = implode(', ', array_filter(array_map('sanitize_text_field', array(
                    (string) ($location['address'] ?? ''),
                    trim((string) ($location['postalCode'] ?? '') . ' ' . (string) ($location['city'] ?? '')),
                ))));
                $employer_data = is_array($job['employer'] ?? null) ? $job['employer'] : array();
                $title = sanitize_text_field(wp_strip_all_tags((string) ($job['title'] ?? $job['jobtitle'] ?? $entry['title'] ?? 'Ledig stilling')));
                $description = self::excerpt_from_html((string) ($job['description'] ?? $item['content_text'] ?? ''));
                $permalink = esc_url_raw((string) ($job['applicationUrl'] ?? $job['sourceurl'] ?? $job['link'] ?? ''));
                $employer = sanitize_text_field((string) ($employer_data['name'] ?? $entry['businessName'] ?? ''));
                $employment_type = sanitize_text_field((string) ($job['engagementtype'] ?? ''));
                $position_percentage = sanitize_text_field((string) ($job['extent'] ?? ''));
                $published_timestamp = strtotime((string) ($job['published'] ?? ''));
                $post_data = array(
                    'post_type' => 'lp_job',
                    'post_status' => $status,
                    'post_title' => $title ?: 'Ledig stilling',
                    'post_excerpt' => $description,
                    'post_content' => $description,
                    'meta_input' => array(
                        '_lp_source_id' => $source->ID,
                        '_lp_source_name' => $source->post_title,
                        '_lp_source_url' => $permalink,
                        '_lp_external_id' => $external_id,
                        '_lp_employer' => $employer,
                        '_lp_address' => $address,
                        '_lp_employment_type' => $employment_type,
                        '_lp_position_percentage' => $position_percentage,
                        '_lp_application_deadline' => $deadline,
                        '_lp_imported_at' => current_time('mysql', true),
                    ),
                );
                if ($published_timestamp) {
                    $post_data['post_date'] = wp_date('Y-m-d H:i:s', $published_timestamp);
                }
                $existing_id = self::find_existing_id('lp_job', $external_id, $permalink);
                if ($existing_id > 0) {
                    $post_data['ID'] = $existing_id;
                    delete_post_meta($existing_id, '_lp_removed_at');
                    $saved = wp_update_post($post_data, true);
                    if (is_wp_error($saved)) {
                        $result['errors'][] = $saved->get_error_message();
                    } else {
                        $result['skipped']++;
                    }
                } else {
                    $saved = wp_insert_post($post_data, true);
                    if (is_wp_error($saved)) {
                        $result['errors'][] = $saved->get_error_message();
                    } else {
                        $result['created']++;
                    }
                }
            }

            $next_path = (string) ($data['next_url'] ?? '');
            $current_path = (string) ($data['feed_url'] ?? '');
            $next_url = $next_path !== '' ? (str_starts_with($next_path, 'http') ? $next_path : $base . '/' . ltrim($next_path, '/')) : '';
            if ($next_url !== '') {
                $request_url = $next_url;
                $cursor = $next_url;
                update_post_meta($source->ID, '_lp_nav_cursor_url', $next_url);
                delete_post_meta($source->ID, '_lp_nav_etag');
                delete_post_meta($source->ID, '_lp_nav_last_modified');
                continue;
            }
            $poll_url = $current_path !== '' ? (str_starts_with($current_path, 'http') ? $current_path : $base . '/' . ltrim($current_path, '/')) : $request_url;
            update_post_meta($source->ID, '_lp_nav_cursor_url', $poll_url);
            update_post_meta($source->ID, '_lp_nav_etag', (string) wp_remote_retrieve_header($response, 'etag'));
            update_post_meta($source->ID, '_lp_nav_last_modified', (string) wp_remote_retrieve_header($response, 'last-modified'));
            delete_post_meta($source->ID, '_lp_nav_start_since');
            return;
        }
    }

    private static function hide_job_by_external_id(string $external_id): void
    {
        $job_id = self::find_existing_id('lp_job', $external_id, '');
        if ($job_id > 0 && !get_post_meta($job_id, '_lp_removed_at', true)) {
            wp_update_post(array('ID' => $job_id, 'post_status' => 'draft'));
            update_post_meta($job_id, '_lp_removed_at', current_time('mysql', true));
        }
    }

    private static function excerpt_from_html(string $html, int $max_words = 45): string
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/<(?:br\s*\/?|\/p|\/div|\/li|\/h[1-6])\s*>/iu', "\n", $html) ?? $html;
        $plain = html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = preg_split('/\R+/u', $plain) ?: array($plain);
        $excerpt = array();
        $remaining = $max_words;
        $truncated = false;

        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/[\t ]+/u', ' ', $line));
            if ($line === '') {
                continue;
            }
            $words = preg_split('/\s+/u', $line) ?: array();
            if (count($words) > $remaining) {
                $excerpt[] = implode(' ', array_slice($words, 0, $remaining));
                $truncated = true;
                break;
            }
            $excerpt[] = implode(' ', $words);
            $remaining -= count($words);
            if ($remaining <= 0) {
                $truncated = true;
                break;
            }
        }

        $text = implode("\n\n", $excerpt);
        return $truncated && $text !== '' ? $text . '…' : $text;
    }

    private static function first_teamtailor_value($item, string $tag): string
    {
        $tags = $item->get_item_tags('https://teamtailor.com/locations', $tag) ?: array();
        return sanitize_text_field((string) ($tags[0]['data'] ?? ''));
    }

    private static function xml_tag_values(array $nodes, string $tag): array
    {
        $values = array();
        foreach ($nodes as $key => $node) {
            if ($key === $tag && is_array($node)) {
                foreach ($node as $entry) {
                    if (is_array($entry) && isset($entry['data'])) {
                        $values[] = (string) $entry['data'];
                    }
                }
            }
            if (is_array($node)) {
                $values = array_merge($values, self::xml_tag_values($node, $tag));
            }
        }
        return $values;
    }

    private static function hide_missing_jobs(int $source_id, array $seen): void
    {
        $job_ids = get_posts(array(
            'post_type' => 'lp_job',
            'post_status' => array('publish', 'draft'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => '_lp_source_id',
            'meta_value' => $source_id,
        ));
        foreach ($job_ids as $job_id) {
            $external_id = (string) get_post_meta((int) $job_id, '_lp_external_id', true);
            if ($external_id !== '' && !in_array($external_id, $seen, true) && !get_post_meta((int) $job_id, '_lp_removed_at', true)) {
                wp_update_post(array('ID' => (int) $job_id, 'post_status' => 'draft'));
                update_post_meta((int) $job_id, '_lp_removed_at', current_time('mysql', true));
            }
        }
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

    private static function exists(string $post_type, string $external_id, string $url): bool
    {
        return self::find_existing_id($post_type, $external_id, $url) > 0;
    }

    private static function find_existing_id(string $post_type, string $external_id, string $url): int
    {
        $meta_query = array('relation' => 'OR');
        if ($external_id !== '') {
            $meta_query[] = array('key' => '_lp_external_id', 'value' => $external_id);
        }
        if ($url !== '') {
            $meta_query[] = array('key' => '_lp_source_url', 'value' => $url);
        }
        if (count($meta_query) === 1) {
            return 0;
        }

        $query = new WP_Query(array(
            'post_type' => $post_type,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => $meta_query,
        ));
        return $query->have_posts() ? (int) $query->posts[0] : 0;
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
