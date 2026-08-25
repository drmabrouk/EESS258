<?php
/**
 * Plugin Name: Educational Electronic Systems Services (EESS)
 * Plugin URI: https://eess.online
 * Description: خدمات الأنظمة الإلكترونية التعليمية (EESS) - نظام شامل لإدارة السلوك، المخالفات، والتقارير المدرسية.
 * Version: 98.0.0
 * Author: Educational Electronic Systems Services (EESS)
 * Author URI: https://eess.online
 * Language: ar
 * Text Domain: school-management
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SM_VERSION', '98.0.0');
define('SM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_school_management() {
    require_once SM_PLUGIN_DIR . 'includes/class-sm-activator.php';
    SM_Activator::activate();

    // Non-destructive inline column migration
    global $wpdb;
    $wpdb->query("ALTER TABLE {$wpdb->prefix}sm_documents ADD COLUMN IF NOT EXISTS category varchar(100) DEFAULT 'الوثائق الإدارية'");
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_school_management() {
    require_once SM_PLUGIN_DIR . 'includes/class-sm-deactivator.php';
    SM_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_school_management');
register_deactivation_hook(__FILE__, 'deactivate_school_management');

/**
 * Load Centralized Organization Structure Helper
 */
require_once SM_PLUGIN_DIR . 'includes/class-eess-org-helper.php';

/**
 * Core class used to maintain the plugin.
 */
require_once SM_PLUGIN_DIR . 'includes/class-school-management.php';

function run_school_management() {
    $plugin = new School_Management();
    $plugin->run();
}

run_school_management();
