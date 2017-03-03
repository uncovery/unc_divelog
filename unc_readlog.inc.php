<?php

if (!defined('WPINC')) {
    die;
}

function uncd_divelog_db_connect($filename) {
    $file = __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . $filename;
    $database = new SQLite3($file, SQLITE3_OPEN_READONLY);
    if (!$database) {
        echo "Error $file";
    }
    return $database;
}

/**
 * This is the function that does the data loading and conversion.
 *
 * @global type $UNC_DIVELOG
 * @param type $dive_number
 * @param type $format
 * @return type
 */
function uncd_divelog_query() {
    global $UNC_DIVELOG;
    $D = $UNC_DIVELOG['display'];

    // first, get the data formats and fieldnames from the DB for the given $format
    $DS = $UNC_DIVELOG['data_structure'][$D['data_format']]['fieldmap'];

    // let's make a SQL SELECT statement that uses the desired fieldnames
    $sql_elements = array();
    foreach ($DS as $data_field => $data_info) {
        $sql_elements[] = $data_info['field_name'] . ' as ' . $data_field;
    }
    $sql_select = implode(", ", $sql_elements);

    // insert the SELECT into the query
    $query = "SELECT $sql_select FROM Dive WHERE dive_number = {$D['dive_id']};";
    $UNC_DIVELOG['debug'][] = $query;
    // TODO: This needs to be set by default since there is only one
    // or through the shortcode
    $DB = uncd_divelog_db_connect($D['source']);

    // get my results
    $results = $DB->query($query);
    $row = $results->fetchArray(SQLITE3_ASSOC);

    $data_set = array();
    // get my data and convert it so that it's readable
    foreach ($DS as $field_name => $field_data) {
        // if we need to convert something
        if (isset($field_data['format'])) {
            $data_set[$field_name] = uncd_divelog_data_convert($field_data['format'], $row[$field_name]);
        } else {
            $data_set[$field_name] = $row[$field_name];
        }
    }
    return $data_set;
}

/**
 * Raw data conversion
 *
 * @param string $format
 * @param type $data
 * @return type
 */
function uncd_divelog_data_convert($format, $data) {
    switch ($format) {
        // four-digit hex format of a float such as D7 A3 D0 3F = 1.63...
        case 'binary_float':
            $bin = hex2bin($data);
            $float = unpack("f", $bin);
            return round($float[1], 1);
        // simple hex such as 1F = 31
        case 'hex':
            $dec = hexdec($data);
            return $dec;
        // suunto UNIX-like timestamp
        case 'seconds_since_0001':
            $number_of_seconds = 62135600400;
            $seconds = $data / 10000000 - $number_of_seconds;
            $date = new DateTime();
            $date->setTimestamp($seconds);
            $date_str = $date->format("Y-m-d H:i:s");
            return $date_str;
        case 'D4i_SampleBlob':
            //  see here: http://lists.subsurface-divelog.org/pipermail/subsurface/2014-November/015798.html
            $data_clipped = substr($data, 4);
            $data_str = chunk_split($data_clipped, 46, "|");
            $dive_array = explode("|", $data_str);
            // pattern is in the format 1400 A470CD40 FFFFFF7F 1E FFFF7F7 FFFFF7F7 FFFFF7F7F
            $pattern = "/(?'time'[0-9A-F]{2})[0-9A-F]{2}(?'depth'[0-9A-F]{8})[0-9A-F]{8}(?'temp'[0-9A-F]{2}).*/";
            $fields = array('temp' => 'hex', 'depth' => 'binary_float', 'time' => 'hex');
            $dive_path = array();
            $i = 0;
            foreach ($dive_array as $dive_str) {
                $results = false;
                preg_match($pattern, $dive_str, $results);
                foreach ($fields as $field => $format) {
                    if (!isset($results[$field])) {
                        continue;
                    }
                    $data = $results[$field];
                    $converted = uncd_divelog_data_convert($format, $data);
                    $dive_path[$i][$field] = $converted; // D4i measures every 20 seconds
                }
                $i++;
            }
            return $dive_path;
    }
}

function uncd_divelog_enumerate_dives() {
    global $UNC_DIVELOG;
    $D = $UNC_DIVELOG['display'];

    $DB = uncd_divelog_db_connect($D['source']);

    $DS = $UNC_DIVELOG['data_structure'][$D['data_format']]['fieldmap'];

    $date_field = $DS['start_time']['field_name'];
    $date_format = $DS['start_time']['format'];
    $dive_number = $DS['dive_number']['field_name'];


    $filter = '';
    if (isset($UNC_DIVELOG['data_structure'][$D['data_format']]['filter'])) {
        $sql_filter = $UNC_DIVELOG['data_structure'][$D['data_format']]['filter'];
        $filter = 'WHERE ' . $sql_filter;
    }

    // insert the SELECT into the query
    $query = "SELECT $date_field as date_str, $dive_number as dive_number FROM Dive $filter ORDER BY $date_field DESC;";
    $results = $DB->query($query);

    $dive_dates = array();
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $date = uncd_divelog_data_convert($date_format, $row['date_str']);
        $dive_id = $row['dive_number'];
        $date_obj = new DateTime($date);
        $day = $date_obj->format("Y-m-d");
        $time = $date_obj->format("H:i:s");
        $dive_dates[$day][$dive_id] = $time;
    }
    return $dive_dates;
}

/**
 * get the date of the last dive
 *
 * @global type $UNC_DIVELOG
 * @return type
 */
function uncd_divelog_dive_latest() {
    global $UNC_DIVELOG;
    $D = $UNC_DIVELOG['display'];

    $DB = uncd_divelog_db_connect($D['source']);

    $DS = $UNC_DIVELOG['data_structure'][$D['data_format']]['fieldmap'];

    $date_field = $DS['start_time']['field_name'];
    $date_format = $DS['start_time']['format'];

    $filter = '';
    if (isset($UNC_DIVELOG['data_structure'][$D['data_format']]['filter'])) {
        $sql_filter = $UNC_DIVELOG['data_structure'][$D['data_format']]['filter'];
        $filter = 'WHERE ' . $sql_filter;
    }

    $query = "SELECT $date_field as date_str FROM Dive $filter ORDER BY $date_field DESC LIMIT 1";
    $results = $DB->query($query);
    if (count($results) == 0) {
        return false;
    }
    $row = $results->fetchArray(SQLITE3_ASSOC);
    $date = uncd_divelog_data_convert($date_format, $row['date_str']);
    $date_obj = new DateTime($date);
    $day = $date_obj->format("Y-m-d");
    return $day;
}

function uncd_gallery_data($start_time, $dive_time) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $start_obj = DateTime::createFromFormat("Y-m-d H:i:s", $start_time);
    $start_obj->add(new DateInterval('PT' . $dive_time . 'S'));
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

function uncd_gallery_splice($start_time, $files, $dive) {
    // find seconds from start for all photos
    $out_data = array();
    foreach ($files as $file_time => $file_info) {

    }
    // time interval between start time and file time = seconds

    // divide by steps and find location (dive step number) in the sequence

    // find how many seconds behind the required step

    // get the depth at that second by averaging

    // insert data


}