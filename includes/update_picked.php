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

	$query = "UPDATE playersXseasons pxs
		INNER JOIN (
		  SELECT player_id, SUM($column) as total
		  FROM ownersXrosters
		  WHERE season=$season
		  GROUP BY player_id
		) x ON pxc.player_id = x.player_id
		SET pxs.$column = x.total 
	";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

