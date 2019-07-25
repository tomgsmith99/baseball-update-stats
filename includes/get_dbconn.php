<?php

function get_dbconn() {

	$raw = file_get_contents(BASE_PATH . "/.env.json");

	$vals = json_decode($raw);

	$dbpassword = $vals->DB_PASSWORD;
	$dbhost = $vals->DB_HOST;
	$dbusername = $vals->DB_USERNAME;
	$dbport = $vals->DB_PORT;

	$mysqli = new mysqli($dbhost, $dbusername, $dbpassword, "baseball", $dbport);

	if ($mysqli->connect_error) {
		echo "\nWarning: could not connect to db.\n";

		// did env vars get loaded?
		echo "\nthe dbhost is: " . $dbhost . "\n";

		die('Connect Error (' . $mysqli->connect_errno . ') '
			. $mysqli->connect_error);
	}
	else {
		return $mysqli;
	}
}
