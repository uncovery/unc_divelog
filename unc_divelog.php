<?php
/*
Plugin Name: Uncovery divelog
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

global $XMPP_ERROR;
require_once('/home/includes/xmpp_error/xmpp_error.php');
$XMPP_ERROR['config']['project_name'] = 'unc_divelog';
$XMPP_ERROR['config']['enabled'] = true;
$XMPP_ERROR['config']['ignore_warnings'] = array('jaxl');

require_once( plugin_dir_path( __FILE__ ) . "unc_config.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_readlog.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_display.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_backend.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_db_formats.inc.php");

// actions on activating and deactivating the plugin
register_activation_hook( __FILE__, 'unc_divelog_plugin_activate');
register_deactivation_hook( __FILE__, 'unc_divelog_plugin_deactivate');
register_uninstall_hook( __FILE__, 'unc_divelog_plugin_uninstall');

// shortcode for the [unc_divelog] replacements
add_shortcode('unc_divelog', 'unc_display_shortcode');

if (is_admin()){ // admin actions
    add_action('admin_init', 'unc_divelog_admin_init');
    // add an admin menu
    add_action('admin_menu', 'unc_divelog_admin_menu');
}

add_action('wp_enqueue_scripts', 'unc_display_amcharts_scripts', 1);

// get the settings from the system and set the global variables
// this iterates the user settings that are supposed to be in the wordpress config
// and gets them from there, setting the default if not available
// inserts them into the global
foreach ($UNC_DIVELOG['user_settings'] as $setting => $D) {
    $UNC_DIVELOG[$setting] = get_option($UNC_DIVELOG['settings_prefix'] . $setting, $D['default']);
}


/**
 * standard wordpress function to activate the plugin.
 * creates the uploads folder
 *
 * @global type $UNC_DIVELOG
 */
function unc_divelog_plugin_activate() {
    // nothing to do?
}

/**
 * standard wordpress function to deactivate the plugin.
 *
 * @global type $UNC_DIVELOG
 */
function unc_divelog_plugin_deactivate() {
    global $UNC_DIVELOG;
    // deactivate all settings
    $prefix = $UNC_DIVELOG['settings_prefix'];
    foreach ($UNC_DIVELOG['user_settings'] as $setting => $D) {
        unregister_setting('unc_divelog_settings_page', $prefix . $setting);
    }
}

function unc_divelog_plugin_uninstall() {
    global $UNC_DIVELOG;

    //delete all settings properly
    $prefix = $UNC_DIVELOG['settings_prefix'];
    foreach ($UNC_DIVELOG['user_settings'] as $setting => $D) {
        delete_option($prefix . $setting);
    }
    // register_uninstall_hook($file, $callback)
}

function unc_display_amcharts_scripts() {

}

/**
 * function that includes all the CSS and JS that are needed.
 *
 */
function unc_divelog_add_css_and_js() {
    wp_enqueue_script('jquery-ui');
    wp_enqueue_style('jquery_ui_css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css');

    wp_enqueue_script('unc_divelog_js', plugin_dir_url( __FILE__ ) . 'js/unc_divelog.js');
    wp_enqueue_style('unc_divelog_css', plugin_dir_url( __FILE__ ) . 'css/unc_divelog.css');

    wp_enqueue_script('jquery-form', '/wp-includes/js/jquery/jquery.form.js');
    wp_enqueue_script('jquery-ui-tabs', '/wp-includes/js/jquery/ui/tabs.min.js');
    wp_enqueue_script('jquery-ui-datepicker', '/wp-includes/js/jquery/ui/jquery.ui.datepicker.min.js');

    wp_register_script('unc_divelog_amchart_js', plugin_dir_url( __FILE__ ) . 'js/amcharts.js', array(), '3.19.5', false );
    wp_register_script('unc_divelog_amchart_light_js', plugin_dir_url( __FILE__ ) . 'js/light.js', array('unc_divelog_amchart_js'), '3.19.5', false );
    wp_register_script('unc_divelog_amchart_serial_js', plugin_dir_url( __FILE__ ) . 'js/serial.js', array('unc_divelog_amchart_js'), '3.19.5', false );
    wp_enqueue_script('unc_divelog_amchart_js');
    wp_enqueue_script('unc_divelog_amchart_light_js');
    wp_enqueue_script('unc_divelog_amchart_serial_js');
}