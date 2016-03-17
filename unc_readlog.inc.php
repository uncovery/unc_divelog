<?php

if (!defined('WPINC')) {
    die;
}

function unc_divelog_db_connect() {
    $error = NULL;
    $file = __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "user.db";
    $database = new SQLiteDatabase($file, 0666, $error);
    if (!$database) {
        $error = (file_exists($file)) ? "Impossible to open, check permissions" : "Impossible to create, check permissions";
        die($error);
    }
    return $database;
}

function unc_divelog_query() {
    $DB = unc_divelog_db_connect();
    // list all tables
    $query_error = NULL;
    $query = $DB->query("SELECT name FROM sqlite_master WHERE type='table'", SQLITE_ASSOC, $query_error);
    print $query->numRows();
    while ($row = $query->fetch()) {
        echo $row['name'] . "<br>";
    }
}