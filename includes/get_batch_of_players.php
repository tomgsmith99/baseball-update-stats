<?php

/*************************************************************/

function get_batch_of_players($batch_size, $player_id=0) {
	global $dbconn;
	global $today;

	if ($player_id == 0) {
		$query = "SELECT player_id, salary, pos, update_status, espn_stats_id, fnf";
		$query .= " FROM players_current_view";
		$query .= " WHERE updated < " . $today;
		$query .= " AND checked < 2";
		$query .= " ORDER BY checked ASC";
		$query .= " LIMIT " . $batch_size;
	}
	else {
		$query = "SELECT player_id, salary, pos, update_status, espn_stats_id, fnf";
		$query .= " FROM players_current_view";
		$query .= " WHERE player_id = " . $player_id;
	}

	echo $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	return $result;
}
