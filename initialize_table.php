<?php

function initialize_table($last_checked) {
	global $dbconn;

	if ($last_checked < $GLOBALS["today"]) {

		$query = "UPDATE players_current SET checked=0,  updated=0";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
}
