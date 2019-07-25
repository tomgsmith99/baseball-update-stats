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
		$table = "PlayersMain";
	}

	$query = "SELECT acquired, drafted, picked, t1.player_id, points, pos";
	$query .= ", p_type, recent, salary, team, value";
	$query .= ", t2.FNF, t2.Player_ID";
	$query .= " FROM " . $table . " as t1, Players as t2";
	$query .= " WHERE t1.player_id = t2.Player_ID";

	if ($current_or_past === "past") {
		$query .= " AND t2.Season=" . $season;
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

	// 	$query = "SELECT * FROM playerXowner WHERE player_id=" . $player_id;
	// 	$query .= " AND season=" . $season;
	// 	$query .= " AND status='drafted'";

	// 	$r = mysqli_query($dbconn, $query);

	// 	if (mysqli_error($dbconn)) {
	// 		echo mysqli_error($dbconn);
	// 		exit;
	// 	}

	// 	$picked = mysqli_num_rows($r);

	// 	$players[$player_id]["picked"] = $picked;
	// }

	return $players;
}
