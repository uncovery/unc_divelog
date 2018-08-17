<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Get the photos for the dive from the gallery plugin
 * 
 * @global type $UNC_GALLERY
 * @param type $start_time
 * @param type $dive_time
 * @return type
 */
function uncd_gallery_data($start_time, $dive_time) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $start_obj = DateTime::createFromFormat("Y-m-d H:i:s", $start_time);
    $int = new DateInterval('PT' . $dive_time . 'S');
    $start_obj->add($int);
    $end_time = $start_obj->format("Y-m-d H:i:s");

    $args = array(
        'start_time' => $start_time, // UNIX timestamp
        'end_time' => $end_time,  // UNIX timestamp
        'featured' => false,
        'description' => false,
    );

    unc_gallery_display_var_init($args);

    $files = $UNC_GALLERY['display']['files'];

    $file_list = array();
    foreach ($files as $file) {
        $time = $file['file_date'];
        // for now, we just aggregate the images by minute so we do not show more than one image per minute
        $sec_from_start = strtotime($time) - strtotime($start_time);
        $file_list[$sec_from_start] = $file;
    }

    return $file_list;
}