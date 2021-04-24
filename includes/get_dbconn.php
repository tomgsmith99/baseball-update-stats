<?php

function get_dbconn() {
	$mysqli = new mysqli($GLOBALS["DB_HOST"], $GLOBALS["DB_USERNAME"], $GLOBALS["DB_PASSWORD"], "baseball", $GLOBALS["DB_PORT"]);

	if ($mysqli->connect_error) {
		echo "could not connect to db.";

		die('Connect Error (' . $mysqli->connect_errno . ') '
			. $mysqli->connect_error);
	}
	else {
		echo "successfully connected to db.\n";
		return $mysqli;
	}
}
