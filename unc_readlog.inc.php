<?php

if (!defined('WPINC')) {
    die;
}

function unc_divelog_db_connect() {

    $file = __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "user.db";
    $database = new SQLite3($file, SQLITE3_OPEN_READONLY);
    if (!$database) {
        echo "Error $file";
    }
    return $database;
}

function unc_divelog_query() {
    $DB = unc_divelog_db_connect();
    // list all tables
    $results = $DB->query("SELECT quote(SampleBlob) as test FROM Dive ORDER BY DiveId DESC LIMIT 1");

    $row = $results->fetchArray(SQLITE3_ASSOC);

    /*
    The first 3 bytes are irrelevant.
    Bytes 4-7 contain the depth as floating point number (read like in DM4)
    Bytes 8-10 contain the pressure (read bytes backwards and convert to
    decimal). result is millibar
    Bytes 11-12 contain the temperature in Celsius (read bytes forwards and
    convert to decimal)
    The last 4 Bytes are always FFFF7F7F
    */

    $data = var_export(substr($row['test'],7), true);

    $data_arr = chunk_split($data, 32);
    var_dump($data_arr);

}