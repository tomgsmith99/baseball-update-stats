<?php

include BASE_PATH . "/.env.php";

/*******************************************/

function get_dbconn() {

	$mysqli = new mysqli($GLOBALS["DB_HOST"], $GLOBALS["DB_USERNAME"], $GLOBALS["DB_PASSWORD"]
, "baseball", $GLOBALS["DB_PORT"]);

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
