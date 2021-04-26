<?php

date_default_timezone_set('America/New_York');

include ".env.php";

include "includes/get_dbconn.php";

include "includes/update_owners.php";

include "includes/update_picked.php";

include "includes/update_players.php";

/*************************************************************/

$dbconn = get_dbconn();

$season = date("Y");

$today = date("z");

$start_time = time();

/*************************************************************/

update_players($dbconn, $season, $today);

update_owners($dbconn, $season, $today);

update_picked($dbconn, $season, $today);

update_last_updated($dbconn);

summarize($start_time);

/*************************************************************/

function summarize($start_time) {

	echo "\n*****************************\n";
	echo "Summary\n";

	$end_time = time();

	$elapsed_time = $end_time - $start_time;

	echo "\nelapsed time: " . $elapsed_time . " seconds";
}

function update_last_updated($dbconn) {

	$update_desc = date("D F jS, o, g:ia");

	$query = "INSERT INTO updates SET update_desc='" . $update_desc . "'";

	echo "\n$query\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}
