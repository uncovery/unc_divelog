<?php

if (!defined('WPINC')) {
    die;
}

// This is used to automatically / dynamically create the settings menu
$UNC_DIVELOG['user_settings'] = array(
    'admin_date_selector' => array(
        'help' => 'Chose if you want to have a calendar or a dropdown list for the Admin page.',
        'default' => 'calendar',
        'type' => 'dropdown',
        'options' => array('calendar' => 'Calendar', 'datelist' => 'Date List'),
    ),
);

$UNC_DIVELOG['settings_prefix'] = 'uncd_divelog_';