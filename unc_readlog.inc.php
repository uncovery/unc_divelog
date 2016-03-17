<?php

if (!defined('WPINC')) {
    die;
}

function unc_divelog_db_connect() {
    $error = NULL;
    $file = __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "user.db";
    $database = new SQLite3($file, SQLITE3_OPEN_READONLY);
    if (!$database) {
        echo "Error";
    }
    return $database;
}

function unc_divelog_query() {
    $DB = unc_divelog_db_connect();
    // list all tables
    $query_error = NULL;
    $query = $DB->query("SELECT name FROM sqlite_master WHERE type='table'", SQLITE_ASSOC, $query_error);

    $out = "tables: " . $query->numRows() . "<br>";
    while ($row = $query->fetch()) {
        $out .= $row['name'] . "<br>";
    }
    return $out;
}