<?php

if (!defined('WPINC')) {
    die;
}

function uncd_divelog_admin_menu() {
    // the main page where we manage the options
    $main_options_page_hook_suffix = add_menu_page(
        'Uncovery Divelog Options', // $page_title,
        'Uncovery Divelog', // $menu_title,
        'manage_options', // $capability,
        'uncd_divelog_admin_menu', // $menu_slug,
        'uncd_divelog_admin_settings' // $function, $icon_url, $position
    );
    add_action('admin_print_scripts-' . $main_options_page_hook_suffix, 'uncd_divelog_add_css_and_js');
}

/**
 * This adds the Wordpress features for the admin pages
 *
 * @global type $UNC_DIVELOG
 */
function uncd_divelog_admin_init() {
    global $UNC_DIVELOG;

    add_settings_section(
        'uncd_divelog_pluginPage_section',
        __('Settings', 'wordpress'),
        'uncd_divelog_settings_section_callback',
        'uncd_divelog_settings_page' // need to match menu_slug
    );

    foreach ($UNC_DIVELOG['user_settings'] as $setting => $D) {
        $prefix = $UNC_DIVELOG['settings_prefix'];
        register_setting('uncd_divelog_settings_page', $prefix . $setting);
        $setting_value = get_option($prefix . $setting, $D['default']);
        $args = array('setting' => $prefix . $setting, 'value'=> $setting_value, 'help'=> $D['help'], 'default' => $D['default']);
        if ($D['type'] == 'text') {
            $callback = 'uncd_divelog_setting_text_field_render';
        } else {
            $callback = 'uncd_divelog_setting_drodown_render';
            $args['options'] = $D['options'];
        }
        add_settings_field(
            $prefix . $setting,
            __(ucwords(str_replace("_", " ", $setting)), 'wordpress'),
            $callback,
            'uncd_divelog_settings_page',
            'uncd_divelog_pluginPage_section',
            $args
        );
    }
}

/**
 * Generic function to render a text input for WP settings dialogues
 * @param type $A
 */
function uncd_divelog_setting_text_field_render($A) {
    $out = "<input type='text' name='{$A['setting']}' value='{$A['value']}'> {$A['help']} <strong>Default:</strong> '{$A['default']}'\n";
    echo $out;
}

/**
 * Generic function to render a dropdown input for WP settings dialogues
 * @param type $A
 */
function uncd_divelog_setting_drodown_render($A) {
    $out = "<select name=\"{$A['setting']}\">\n";
    foreach ($A['options'] as $option => $text) {
        $sel = '';
        if ($option == $A['value']) {
            $sel = 'selected';
        }
        $out .= "<option value=\"$option\" $sel>$text</option>\n";
    }
    $out .= "</select> {$A['help']} <strong>Default:</strong> '{$A['options'][$A['default']]}'\n";
    echo $out;
}

/**
 * Callback for the Settings-section. Since we have only one, no need to use this
 * Called in uncd_divelog_admin_init
 */
function uncd_divelog_settings_section_callback() {
    // echo __( 'Basic Settings', 'wordpress' );
}

/**
 * this will manage the settings
 */
function uncd_divelog_admin_settings() {
    uncd_divelog_add_css_and_js();
    remove_filter('the_content', 'wpautop');
    echo '<div class="wrap">
    <h2>Uncovery Divelog</h2>
    <script type="text/javascript">
        jQuery(document).ready(function() {
        // Initialize jquery-ui tabs
        jQuery(\'.uncd_jquery_tabs\').tabs();
        // Fade in sections that we wanted to pre-render
        jQuery(\'.uncd_fade_in\').fadeIn(\'fast\');
        });
    </script>
    <div class="uncd_jquery_tabs uncd_fade_in">
    <ul>' . "\n";

    # Set up tab titles
    echo "<li><a href='#tab1'><span>Settings</span></a></li>\n"
        . "<li><a href='#tab2'><span>Upload</span></a></li>\n"
        . "<li><a href='#tab3'><span>Manage Dives</span></a></li>\n"
        . "<li><a href='#tab4'><span>Documentation</span></a></li>\n"
        . "</ul>\n";

    echo "<div id='tab1'>\n";
    echo '<form method="post" action="options.php">'. "\n";
    settings_fields('uncd_divelog_settings_page');
    do_settings_sections( 'uncd_divelog_settings_page');
    submit_button();
    echo "</form>\n";
    echo "</div>\n";

    echo "<div id='tab2'>\n";
    echo uncd_divelog_admin_upload();
    echo "</div>\n";

    echo "<div id='tab3'>\n";
    echo uncd_divelog_admin_display_dives();
    echo "</div>\n";

    echo "<div id='tab4'>\n";
    echo uncd_divelog_admin_show_documentation();
    echo "</div>\n";

    echo "</div>";
}

/**
 * displayes the complete image catalogue for the admin
 * and then provide buttons for AJAX-loading of older content
 *
 * @global type $UNC_DIVELOG
 */
function uncd_divelog_admin_display_dives() {
    global $UNC_DIVELOG;
    $out = "<h2>Manage Dives</h2>\n";
    // get a standard short-tag output for latest date with datepicker
    $out .= uncd_display_shortcode(array('options'=> $UNC_DIVELOG['admin_date_selector']));
    echo $out;
}

/**
 * Show the documentation my parsing the README.md file through a markdown parser
 * We are using https://github.com/erusev/parsedown
 */
function uncd_divelog_admin_show_documentation() {
    require_once(__DIR__ .  DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'Parsedown.php');

    $markdown_docs = file_get_contents(__DIR__ .  DIRECTORY_SEPARATOR . 'README.md');
    $markdown_fixed = str_replace('/images/', plugins_url( '/images/', __FILE__ ), $markdown_docs);
    $Parsedown = new Parsedown();
    return $Parsedown->text($markdown_fixed);
}

function uncd_divelog_admin_upload() {
    return "Upload dialogue to be shown here!";
}