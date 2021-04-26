<?php

function update_picked($dbconn, $season, $today) {

	/************************************************/
	// update player acquired totals
	update_count($dbconn, $season, "acquired");

	/************************************************/
	// update player drafted totals
	update_count($dbconn, $season, "drafted");

}

function update_count($dbconn, $season, $column) {

	$query = "UPDATE players_current pc
		INNER JOIN (
		  SELECT player_id, SUM($column) as total
		  FROM ownersXrosters_current
		  WHERE season=$season
		  GROUP BY player_id
		) x ON pc.player_id = x.player_id
		SET pc.$column = x.total
	";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

