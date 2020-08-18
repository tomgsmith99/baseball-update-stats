<?php

function initialize_table() {
	global $dbconn;

	$query = "UPDATE players_current SET checked=0, updated=0";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
	else {
		echo "initialized players_current table...\n";
	}
}
