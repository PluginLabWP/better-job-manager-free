<?php
/**
 * Plugin Name: Better Job Manager
 * Plugin URI: https://www.pluginlab.online
 * Description: A modern job board and employer workflow plugin for WordPress.
 * Version: 2.1.1
 * Author: PluginLab
 * Author URI: https://www.pluginlab.online
 * Text Domain: better-job-manager
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'BJM_VERSION', '2.1.1' );
define( 'BJM_FILE', __FILE__ );
define( 'BJM_PATH', plugin_dir_path( __FILE__ ) );
define( 'BJM_URL', plugin_dir_url( __FILE__ ) );

require_once BJM_PATH . 'includes/class-bjm-activator.php';
require_once BJM_PATH . 'includes/class-bjm-deactivator.php';
require_once BJM_PATH . 'includes/class-bjm-plugin.php';

register_activation_hook( __FILE__, array( 'BJM_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BJM_Deactivator', 'deactivate' ) );

function bjm_run_plugin() {
    $plugin = new BJM_Plugin();
    $plugin->run();
}

bjm_run_plugin();
