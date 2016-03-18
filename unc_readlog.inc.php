<?php

if (!defined('WPINC')) {
    die;
}

function unc_divelog_db_connect($filename) {
    $file = __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . $filename;
    $database = new SQLite3($file, SQLITE3_OPEN_READONLY);
    if (!$database) {
        echo "Error $file";
    }
    return $database;
}

/**
 * This is the function that does the data loading and converion.
 *
 * @global type $UNC_DIVELOG
 * @param type $dive_number
 * @param type $format
 * @return type
 */
function unc_divelog_query($dive_number, $format) {
    global $UNC_DIVELOG;

    // first, get the data formats and fieldnames from the DB for the given $format
    $DS = $UNC_DIVELOG['data_structure'][$format]['fieldmap'];

    // let's make a SQL SELECT statement that uses the desired fieldnames
    $sql_elements = array();
    foreach ($DS as $data_field => $data_info) {
        $sql_elements[] = $data_info['field_name'] . ' as ' . $data_field;
    }
    $sql_select = implode(", ", $sql_elements);

    // insert the SELECT into the query
    $query = "SELECT $sql_select FROM Dive WHERE dive_number = $dive_number;";

    // TODO: This needs to be set by default since there is only one
    // or through the shortcode
    $DB = unc_divelog_db_connect("user.db");

    // get my results
    $results = $DB->query($query);
    $row = $results->fetchArray(SQLITE3_ASSOC);

    $data_set = array();
    // get my data and convert it so that it's readable
    foreach ($DS as $field_name => $field_data) {
        // if we need to convert something
        if (isset($field_data['format'])) {
            $data_set[$field_name] = unc_divelog_data_convert($field_data['format'], $row[$field_name]);
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
function unc_divelog_data_convert($format, $data) {
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
            $date_str = $date->format("Y-m-d H:m:s");
            return $date_str;
        case 'D4i_SampleBlob':
            //  see here: http://lists.subsurface-divelog.org/pipermail/subsurface/2014-November/015798.html
            $data_clipped = substr($data,4);
            $data_str = chunk_split($data_clipped, 46, "|");
            $dive_array = explode("|", $data_str);
            // pattern is in the format 1400 A470CD40 FFFFFF7F 1E FFFF7F7 FFFFF7F7 FFFFF7F7F
            $pattern = "/(?'time'[0-9A-F]{2})[0-9A-F]{2}(?'depth'[0-9A-F]{8})[0-9A-F]{8}(?'temp'[1-9A-F]{2}).*/";
            $fields = array('depth' => 'binary_float', 'temp' => 'hex', 'time' => 'hex');
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
                    $converted = unc_divelog_data_convert($format, $data);
                    $dive_path[$i][$field] = $converted; // D4i measures every 20 seconds
                }
                $i++;
            }
            return $dive_path;
    }
}