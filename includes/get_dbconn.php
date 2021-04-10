<?php

include BASE_PATH . "/.env.php";

/*******************************************/

function get_dbconn($local_or_remote = "remote") {

	if ($local_or_remote == "local") {
		$mysqli = new mysqli($GLOBALS["local_DB_HOST"], $GLOBALS["local_DB_USERNAME"], $GLOBALS["local_DB_PASSWORD"], "baseball", $GLOBALS["local_DB_PORT"]);
	}
	else {
		$mysqli = new mysqli($GLOBALS["DB_HOST"], $GLOBALS["DB_USERNAME"], $GLOBALS["DB_PASSWORD"], "baseball", $GLOBALS["DB_PORT"]);
	}

	if ($mysqli->connect_error) {
		echo "could not connect to db.";

		die('Connect Error (' . $mysqli->connect_errno . ') '
			. $mysqli->connect_error);
	}
	else {
		return $mysqli;
	}
}
