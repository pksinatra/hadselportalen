<?php
/**
 * Plugin Name: Lokalportalen Core
 * Description: Strukturert innhold og kildebasert import for lokale informasjonsportaler.
 * Version: 0.3.2
 * Author: HadselPortalen
 * Text Domain: lokalportalen
 * Requires at least: 6.6
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('LP_CORE_VERSION', '0.3.2');
define('LP_CORE_FILE', __FILE__);
define('LP_CORE_DIR', plugin_dir_path(__FILE__));

require_once LP_CORE_DIR . 'includes/class-lp-post-types.php';
require_once LP_CORE_DIR . 'includes/class-lp-meta-boxes.php';
require_once LP_CORE_DIR . 'includes/class-lp-importer.php';
require_once LP_CORE_DIR . 'includes/class-lp-admin.php';
require_once LP_CORE_DIR . 'includes/class-lp-shortcodes.php';

final class LP_Core
{
    public static function boot(): void
    {
        LP_Post_Types::register_hooks();
        LP_Meta_Boxes::register_hooks();
        LP_Importer::register_hooks();
        LP_Admin::register_hooks();
        LP_Shortcodes::register_hooks();
    }

    public static function activate(): void
    {
        LP_Post_Types::register();
        LP_Importer::schedule();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        LP_Importer::unschedule();
        flush_rewrite_rules();
    }
}

register_activation_hook(__FILE__, array('LP_Core', 'activate'));
register_deactivation_hook(__FILE__, array('LP_Core', 'deactivate'));
LP_Core::boot();
