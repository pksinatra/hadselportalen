<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class LP_Meta_Boxes
{
    private const FIELDS = array(
        '_lp_source_url' => array('Original/kilde-URL', 'url'),
        '_lp_website' => array('Nettside', 'url'),
        '_lp_external_id' => array('Ekstern ID', 'text'),
        '_lp_start_at' => array('Start', 'datetime-local'),
        '_lp_end_at' => array('Slutt', 'datetime-local'),
        '_lp_expires_at' => array('Utløper', 'datetime-local'),
        '_lp_latitude' => array('Breddegrad', 'number'),
        '_lp_longitude' => array('Lengdegrad', 'number'),
        '_lp_checked_at' => array('Sist kontrollert', 'datetime-local'),
    );

    public static function register_hooks(): void
    {
        add_action('add_meta_boxes', array(__CLASS__, 'add_boxes'));
        add_action('save_post', array(__CLASS__, 'save'), 10, 2);
        add_action('init', array(__CLASS__, 'register_meta'));
    }

    public static function register_meta(): void
    {
        foreach (array('lp_source', 'lp_place', 'lp_current', 'lp_event') as $post_type) {
            foreach (array_keys(self::FIELDS) as $key) {
                register_post_meta($post_type, $key, array(
                    'type' => 'string',
                    'single' => true,
                    'show_in_rest' => true,
                    'sanitize_callback' => array(__CLASS__, 'sanitize_meta'),
                    'auth_callback' => static fn(): bool => current_user_can('edit_posts'),
                ));
            }
        }
    }

    public static function sanitize_meta($value, string $key = ''): string
    {
        if (in_array($key, array('_lp_source_url', '_lp_website'), true)) {
            return esc_url_raw((string) $value);
        }
        return sanitize_text_field((string) $value);
    }

    public static function add_boxes(): void
    {
        foreach (array('lp_place', 'lp_current', 'lp_event') as $post_type) {
            add_meta_box('lp_details', 'Portaldata', array(__CLASS__, 'render_details'), $post_type, 'normal', 'default');
        }

        add_meta_box('lp_source_settings', 'Kildeinnstillinger', array(__CLASS__, 'render_source'), 'lp_source', 'normal', 'default');
    }

    public static function render_details(WP_Post $post): void
    {
        wp_nonce_field('lp_save_meta', 'lp_meta_nonce');
        $allowed = array('_lp_source_url', '_lp_website', '_lp_external_id', '_lp_checked_at', '_lp_latitude', '_lp_longitude');
        if ($post->post_type === 'lp_event') {
            $allowed = array_merge($allowed, array('_lp_start_at', '_lp_end_at', '_lp_expires_at'));
        }
        self::render_fields($post, $allowed);
    }

    public static function render_source(WP_Post $post): void
    {
        wp_nonce_field('lp_save_meta', 'lp_meta_nonce');
        self::render_fields($post, array('_lp_source_url', '_lp_website'));
        $mode = get_post_meta($post->ID, '_lp_publish_mode', true) ?: 'draft';
        $active = get_post_meta($post->ID, '_lp_source_active', true);
        ?>
        <p><label for="lp_publish_mode"><strong>Publiseringsmodus</strong></label><br>
            <select id="lp_publish_mode" name="lp_meta[_lp_publish_mode]">
                <option value="draft" <?php selected($mode, 'draft'); ?>>Til godkjenning (kladd)</option>
                <option value="publish" <?php selected($mode, 'publish'); ?>>Publiser automatisk</option>
            </select>
        </p>
        <p><label><input type="checkbox" name="lp_meta[_lp_source_active]" value="1" <?php checked($active, '1'); ?>> Aktiv kilde</label></p>
        <?php
    }

    private static function render_fields(WP_Post $post, array $keys): void
    {
        foreach ($keys as $key) {
            [$label, $type] = self::FIELDS[$key];
            $value = (string) get_post_meta($post->ID, $key, true);
            $step = $type === 'number' ? ' step="any"' : '';
            printf(
                '<p><label for="%1$s"><strong>%2$s</strong></label><br><input class="widefat" id="%1$s" name="lp_meta[%1$s]" type="%3$s" value="%4$s"%5$s></p>',
                esc_attr($key), esc_html($label), esc_attr($type), esc_attr($value), $step
            );
        }
    }

    public static function save(int $post_id, WP_Post $post): void
    {
        if (!isset($_POST['lp_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lp_meta_nonce'])), 'lp_save_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $supported = array('lp_source', 'lp_place', 'lp_current', 'lp_event');
        if (!in_array($post->post_type, $supported, true)) {
            return;
        }

        $incoming = isset($_POST['lp_meta']) && is_array($_POST['lp_meta']) ? wp_unslash($_POST['lp_meta']) : array();
        foreach (array_keys(self::FIELDS) as $key) {
            if (array_key_exists($key, $incoming)) {
                update_post_meta($post_id, $key, self::sanitize_meta($incoming[$key], $key));
            }
        }
        if ($post->post_type === 'lp_source') {
            $mode = isset($incoming['_lp_publish_mode']) && $incoming['_lp_publish_mode'] === 'publish' ? 'publish' : 'draft';
            update_post_meta($post_id, '_lp_publish_mode', $mode);
            update_post_meta($post_id, '_lp_source_active', isset($incoming['_lp_source_active']) ? '1' : '0');
        }
    }
}

