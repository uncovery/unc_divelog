<?php
/*
Plugin Name: Uncovery Divelog
Plugin URI:  https://github.com/uncovery/unc_divelog
Description: A divelog visualizer
Version:     0.1
Author:      Uncovery
Author URI:  http://uncovery.net
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $UNC_DIVELOG;

$UNC_DIVELOG['datapath'] = $file = __DIR__ . DIRECTORY_SEPARATOR . "data";

global $XMPP_ERROR;
if (file_exists('/home/includes/xmpp_error/xmpp_error.php')) {
    require_once('/home/includes/xmpp_error/xmpp_error.php');
    $XMPP_ERROR['config']['project_name'] = 'UNC_DIVELOG';
    $XMPP_ERROR['config']['enabled'] = true;
    $XMPP_ERROR['config']['ignore_warnings'] = array('jaxl', 'iwp-client', 'multi-column-tag-map', 'wp-admin');
    $XMPP_ERROR['config']['track_globals'] = array('UNC_DIVELOG');
}

require_once( plugin_dir_path( __FILE__ ) . "unc_config.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_readlog.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_display.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_backend.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_divesites.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_db_formats.inc.php");


// actions on activating and deactivating the plugin
register_activation_hook( __FILE__, 'uncd_divelog_plugin_activate');
register_deactivation_hook( __FILE__, 'uncd_divelog_plugin_deactivate');
register_uninstall_hook( __FILE__, 'uncd_divelog_plugin_uninstall');

// shortcode for the [unc_divelog] replacements
add_shortcode('unc_divelog', 'uncd_display_shortcode');
add_shortcode('unc_divesites', 'uncd_divesites_shortcode');

/// Ajax actions
add_action('wp_ajax_nopriv_uncd_divelog_datepicker', 'uncd_display_ajax_datepicker');
add_action('wp_ajax_uncd_divelog_datepicker', 'uncd_display_ajax_datepicker');

if (is_admin()){ // admin actions
    add_action('admin_init', 'uncd_divelog_admin_init');
    // add an admin menu
    add_action('admin_menu', 'uncd_divelog_admin_menu');
}

add_action( 'wp_enqueue_scripts', 'uncd_divelog_add_css_and_js' );

// get the settings from the system and set the global variables
// this iterates the user settings that are supposed to be in the wordpress config
// and gets them from there, setting the default if not available
// inserts them into the global
foreach ($UNC_DIVELOG['user_settings'] as $setting => $D) {
    $UNC_DIVELOG[$setting] = get_option($UNC_DIVELOG['settings_prefix'] . $setting, $D['default']);
}

$dive_log_library_path = $UNC_DIVELOG['unc_dive_library_location'];
if (file_exists($dive_log_library_path)) {
    require_once($dive_log_library_path);
    $UNC_DIVELOG['library_active'] = true;
} else {
    $UNC_DIVELOG['library_active'] = false;
}

/**
 * standard wordpress function to activate the plugin.
 * creates the uploads folder
 *
 * @global type $UNC_DIVELOG
 */
function uncd_divelog_plugin_activate() {
    // nothing to do?
}

/**
 * standard wordpress function to deactivate the plugin.
 *
 * @global type $UNC_DIVELOG
 */
function uncd_divelog_plugin_deactivate() {
    global $UNC_DIVELOG;
    // deactivate all settings
    $prefix = $UNC_DIVELOG['settings_prefix'];
    foreach ($UNC_DIVELOG['user_settings'] as $setting => $D) {
        unregister_setting('uncd_divelog_settings_page', $prefix . $setting);
    }
}

function uncd_divelog_plugin_uninstall() {
    global $UNC_DIVELOG;

    //delete all settings properly
    $prefix = $UNC_DIVELOG['settings_prefix'];
    foreach ($UNC_DIVELOG['user_settings'] as $setting => $D) {
        delete_option($prefix . $setting);
    }
    // register_uninstall_hook($file, $callback)
}

/**
 * function that includes all the CSS and JS that are needed.
 *
 */
function uncd_divelog_add_css_and_js() {
    global $UNC_DIVELOG;
    wp_enqueue_script('jquery-ui');
    wp_enqueue_style('jquery_ui_css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css');

    wp_enqueue_script('uncd_divelog_js', plugin_dir_url( __FILE__ ) . 'js/unc_divelog.js');
    wp_enqueue_style('uncd_divelog_css', plugin_dir_url( __FILE__ ) . 'css/unc_divelog.css');

    wp_enqueue_script('jquery-form', '/wp-includes/js/jquery/jquery.form.js');
    wp_enqueue_script('jquery-ui-tabs', '/wp-includes/js/jquery/ui/tabs.min.js');
    wp_enqueue_script('jquery-ui-datepicker', '/wp-includes/js/jquery/ui/jquery.ui.datepicker.min.js');

    wp_register_script('uncd_divelog_amchart_js', plugin_dir_url( __FILE__ ) . 'js/amcharts.js', array(), '3.19.5', false);
    wp_register_script('uncd_divelog_amchart_light_js', plugin_dir_url( __FILE__ ) . 'js/light.js', array('uncd_divelog_amchart_js'), '3.19.5', false);
    wp_register_script('uncd_divelog_amchart_serial_js', plugin_dir_url( __FILE__ ) . 'js/serial.js', array('uncd_divelog_amchart_js'), '3.19.5', false);

    // load google maps if it's set

    wp_enqueue_script('uncd_divelog_amchart_js');
    wp_enqueue_script('uncd_divelog_amchart_light_js');
    wp_enqueue_script('uncd_divelog_amchart_serial_js');
    wp_enqueue_script('uncd_divelog_makerwithlabel_js');
}