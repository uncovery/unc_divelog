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
    echo var_export($row['test'], true);
}