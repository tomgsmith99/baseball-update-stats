<?php

function get_dbconn() {
	$dbpassword = getenv("DB_PASSWORD");
	$dbhost = getenv("DB_HOST");
	$dbusername = getenv("DB_USERNAME");
	$dbport = getenv("DB_PORT");

	$mysqli = new mysqli($dbhost, $dbusername, $dbpassword, "baseball", $dbport);

	if ($mysqli->connect_error) {
		echo "could not connect to db.";

		// did env vars get loaded?
		echo "the dbhost is: " . $dbhost;

		die('Connect Error (' . $mysqli->connect_errno . ') '
			. $mysqli->connect_error);
	}
	else {
		return $mysqli;
	}
}
