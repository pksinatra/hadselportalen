<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class LP_Admin
{
    public static function register_hooks(): void
    {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_lp_run_import', array(__CLASS__, 'run_import'));
        add_action('admin_notices', array(__CLASS__, 'notice'));
        add_filter('manage_lp_current_posts_columns', array(__CLASS__, 'current_columns'));
        add_action('manage_lp_current_posts_custom_column', array(__CLASS__, 'render_current_column'), 10, 2);
        add_filter('manage_lp_source_posts_columns', array(__CLASS__, 'source_columns'));
        add_action('manage_lp_source_posts_custom_column', array(__CLASS__, 'render_source_column'), 10, 2);
    }

    public static function menu(): void
    {
        add_menu_page('Lokalportalen', 'Lokalportalen', 'edit_posts', 'lokalportalen', array(__CLASS__, 'dashboard'), 'dashicons-admin-site-alt3', 25);
        add_submenu_page('lokalportalen', 'Oversikt', 'Oversikt', 'edit_posts', 'lokalportalen', array(__CLASS__, 'dashboard'));
    }

    public static function dashboard(): void
    {
        if (!current_user_can('edit_posts')) {
            return;
        }
        $sources = get_posts(array('post_type' => 'lp_source', 'post_status' => 'publish', 'posts_per_page' => -1));
        ?>
        <div class="wrap">
            <h1>Lokalportalen</h1>
            <p>Importer én kilde om gangen. Nye kilder bør stå i kladdemodus til innhold og delingsgrunnlag er kontrollert.</p>
            <table class="widefat striped">
                <thead><tr><th>Kilde</th><th>Status</th><th>Modus</th><th>Sist importert</th><th>Handling</th></tr></thead>
                <tbody>
                <?php if (!$sources) : ?>
                    <tr><td colspan="5">Ingen publiserte kilder er opprettet.</td></tr>
                <?php endif; ?>
                <?php foreach ($sources as $source) : ?>
                    <tr>
                        <td><?php echo esc_html($source->post_title); ?></td>
                        <td><?php echo get_post_meta($source->ID, '_lp_source_active', true) === '1' ? 'Aktiv' : 'Inaktiv'; ?></td>
                        <td><?php echo get_post_meta($source->ID, '_lp_publish_mode', true) === 'publish' ? 'Automatisk' : 'Til godkjenning'; ?></td>
                        <td><?php echo esc_html((string) get_post_meta($source->ID, '_lp_last_import_at', true)); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="lp_run_import">
                                <input type="hidden" name="source_id" value="<?php echo (int) $source->ID; ?>">
                                <?php wp_nonce_field('lp_run_import_' . $source->ID); ?>
                                <?php submit_button('Importer nå', 'secondary small', 'submit', false); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function run_import(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_die('Du har ikke tilgang til denne handlingen.');
        }
        $source_id = isset($_POST['source_id']) ? absint($_POST['source_id']) : 0;
        check_admin_referer('lp_run_import_' . $source_id);
        $result = LP_Importer::import_source($source_id);
        $redirect = add_query_arg(array(
            'page' => 'lokalportalen',
            'lp_created' => (int) $result['created'],
            'lp_skipped' => (int) $result['skipped'],
            'lp_filtered' => (int) $result['filtered'],
            'lp_errors' => count($result['errors']),
        ), admin_url('admin.php'));
        wp_safe_redirect($redirect);
        exit;
    }

    public static function notice(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'lokalportalen' || !isset($_GET['lp_created'])) {
            return;
        }
        $created = absint($_GET['lp_created']);
        $skipped = absint($_GET['lp_skipped'] ?? 0);
        $filtered = absint($_GET['lp_filtered'] ?? 0);
        $errors = absint($_GET['lp_errors'] ?? 0);
        printf('<div class="notice %s is-dismissible"><p>%s</p></div>', $errors ? 'notice-warning' : 'notice-success', esc_html(sprintf('Import ferdig: %d nye, %d duplikater, %d filtrert, %d feil.', $created, $skipped, $filtered, $errors)));
    }

    public static function current_columns(array $columns): array
    {
        $columns['lp_source'] = 'Kilde';
        $columns['lp_original'] = 'Original';
        return $columns;
    }

    public static function render_current_column(string $column, int $post_id): void
    {
        if ($column === 'lp_source') {
            echo esc_html((string) get_post_meta($post_id, '_lp_source_name', true));
        } elseif ($column === 'lp_original') {
            $url = (string) get_post_meta($post_id, '_lp_source_url', true);
            if ($url) {
                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">Åpne kilde</a>';
            }
        }
    }

    public static function source_columns(array $columns): array
    {
        $columns['lp_status'] = 'Importstatus';
        return $columns;
    }

    public static function render_source_column(string $column, int $post_id): void
    {
        if ($column !== 'lp_status') {
            return;
        }
        $active = get_post_meta($post_id, '_lp_source_active', true) === '1' ? 'Aktiv' : 'Inaktiv';
        $last = (string) get_post_meta($post_id, '_lp_last_import_at', true);
        echo esc_html($active . ($last ? ' · ' . $last : ''));
    }
}
