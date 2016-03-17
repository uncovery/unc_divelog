<?php

function unc_display_shortcode($atts = array()) {
    $a = shortcode_atts( array(
        'type' => 'day',    // display type
        'date' => 'latest', // which date?
        'dive' => false,    // specifix file?
        'options' => false, // we cannot set it to an array here
        'start_time' => false, // time of the day when we start displaying this date
        'end_time' => false, // time of the day when we stop displaying this date
        'description' => false, // description for the whole day
        'details' => false, // description for individual files
    ), $atts);

    phpinfo();

    // $out = unc_divelog_query();
    return $out;

}