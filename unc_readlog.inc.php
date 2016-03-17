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
    $results = $DB->query("SELECT * FROM Dive ORDER BY DiveId DESC LIMIT 1");

    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $sample = $row['SampleBlob'];
    }
    $blob_length = strlen($sample) / 3;
    $dive = array();
    for ($i=0; $i< $blob_length; $i++) {
        $byte = substr($sample, $i * 4, 4);
        $dive[] = unpack('f', $byte);
    }
    return var_export($dive, true);
}