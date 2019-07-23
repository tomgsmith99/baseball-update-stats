<?php

date_default_timezone_set("America/New_York");

/*************************************************************/

$dbconn = $GLOBALS["dbconn"];

$today = $GLOBALS["today"];

function get_batch_of_players($batch_size, $player_id=0) {
	global $dbconn;
	global $today;

	if ($player_id == 0) {
		$query = "SELECT t1.player_id, t1.p_type, t1.salary, t1.pos";
		$query .= ", t1.update_status, t2.URL, t2.FNF";
		$query .= " FROM players_current AS t1";
		$query .= ", Players as t2";
		$query .= " WHERE t1.updated < " . $today;
		$query .= " AND t1.checked < 2";
		$query .= " AND t1.player_id = t2.Player_ID";
		$query .= " ORDER BY t1.checked ASC";
		$query .= " LIMIT " . $batch_size;
	}
	else {
		$query = "SELECT t1.player_id, t1.p_type, t1.salary, t1.pos";
		$query .= ", t1.update_status, t2.URL, t2.FNF";
		$query .= " FROM players_current AS t1";
		$query .= ", Players as t2";
		$query .= " WHERE t1.player_id = t2.Player_ID";
		$query .= " AND t1.player_id = " . $player_id;
	}

	echo $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	return $result;
}
