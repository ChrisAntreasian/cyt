<?php

//connect to the database
$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_TABLE);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error . "<br />";
    die();
}