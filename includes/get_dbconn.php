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


// function get_dbconn() {

// 	$raw = file_get_contents(BASE_PATH . "/.env.json");

// 	$vals = json_decode($raw);

// 	$dbpassword = $vals->DB_PASSWORD;
// 	$dbhost = $vals->DB_HOST;
// 	$dbusername = $vals->DB_USERNAME;
// 	$dbport = $vals->DB_PORT;

// 	// $mysqli = new mysqli($dbhost, $dbusername, $dbpassword, "baseball", $dbport);

// 	$mysqli = new mysqli($dbhost, $dbusername, "gr45%%ZP", "baseball", $dbport);

// 	if ($mysqli->connect_error) {
// 		echo "\nWarning: could not connect to db.\n";

// 		// did env vars get loaded?
// 		echo "\nthe dbhost is: " . $dbhost . "\n";

// 		die('Connect Error (' . $mysqli->connect_errno . ') '
// 			. $mysqli->connect_error);
// 	}
// 	else {
// 		echo "dbconn successful!\n";
// 		return $mysqli;
// 	}
// }
