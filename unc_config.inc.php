<?php

// This is used to automatically / dynamically create the settings menu
$UNC_GALLERY['user_settings'] = array(
    'admin_date_selector' => array(
        'help' => 'Chose if you want to have a calendar or a dropdown list for the Admin page.',
        'default' => 'calendar',
        'type' => 'dropdown',
        'options' => array('calendar' => 'Calendar', 'datelist' => 'Date List'),
    ),
);