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
    'chart_time_option' => array(
        'help' => 'Do you want to show the actual time below the charts or the minutes since the dive started?.',
        'default' => 'minutes',
        'type' => 'dropdown',
        'options' => array('time' => 'Actual Time', 'minutes' => 'Minutes'),
    ),
    'headline' => array(
        'help' => 'Headline to be shown above every chart. Can be empty.',
        'default' => htmlentities('<h3>Dive Profile & Temperature</h3>'),
        'type' => 'text',
    ),
    'google_api_key' => array(
        'help' => 'Your google API key to display maps.',
        'default' => '',
        'type' => 'text',
    ),
);

$UNC_DIVELOG['settings_prefix'] = 'uncd_divelog_';