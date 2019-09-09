<?php

/***********************************************************/
/* get all of this year's players
/***********************************************************/

function get_all_players_for_year($current_or_past='current', $season=0) {
	global $dbconn;

	if ($current_or_past === "current") {
		$table = "players_current";
	}
	else {
		// $table = "PlayersMain";
		$table = "players_all_time";
	}

	$query = "SELECT acquired, drafted, picked, t1.player_id, points, pos";
	$query .= ", p_type, recent, salary, team, value";
	$query .= ", t2.FNF, t2.Player_ID";
	$query .= " FROM " . $table . " as t1, Players as t2";
	$query .= " WHERE t1.player_id = t2.Player_ID";

	if ($current_or_past === "past") {
		$query .= " AND t1.season=" . $season;
	}

	// echo "\n" . $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	while ($row = mysqli_fetch_assoc($result)) {

		$player_id = $row["player_id"];

		$players[$player_id] = $row;
	}

	return $players;
}
