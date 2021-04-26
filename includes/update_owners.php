<?php

function update_owners($dbconn, $season, $today) {

	/*************************************************************/
	// update the ownersXpoints table
	update_ownersXpoints_table($dbconn, $season, $today);

	/*************************************************************/
	// update the ownersXseasons_current table with place
	update_place($dbconn, $season);

	/************************************************/
	// update the ownersXseasons_current table with
	// recent points, yesterday points
	update_points($dbconn, $season, $today, "recent");
	update_points($dbconn, $season, $today, "yesterday");
}

function update_ownersXpoints_table($dbconn, $season, $today) {

	$query = "SELECT owner_id, points FROM ownersXseasons_current_view WHERE season=$season";

	echo "\n" . $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	while ($row = mysqli_fetch_assoc($result)) {
		$owner_id = $row["owner_id"];

		$query = "REPLACE ownersXpoints SET";
		$query .= " points=" . $row["points"];
		$query .= ", season=" . $season;
		$query .= ", owner_id=" . $owner_id;
		$query .= ", day=" . $today;

		echo "\n" . $query . "\n";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
}

function update_place($dbconn, $season) {

	$query = "SELECT owner_id, points FROM ownersXseasons_current ORDER BY points DESC";

	echo "\n" . $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$prev_owner_points = 0;

	$i = 0;

	$place = 0;

	while ($row = mysqli_fetch_assoc($result)) {

		$i++;

		$owner_id = $row["owner_id"];

		if ($row["points"] != $prev_owner_points) {
			$place = $i;
		}

		$prev_owner_points = $row["points"];

		$query = "UPDATE ownersXseasons_current SET place=$place WHERE owner_id=$owner_id AND season=$season";

		echo "\n" . $query . "\n";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
}

function update_points($dbconn, $season, $today, $timeframe) {

	if ($timeframe == "recent") {
		$day = $today - 5;
	}
	else {
		$day = $today - 1;
	}

	$query = "UPDATE ownersXseasons_current oc
		INNER JOIN (
		  SELECT owner_id, points
		  FROM ownersXpoints
		  WHERE season=$season
		  AND day=$day
		) x ON oc.owner_id = x.owner_id
		SET oc.$timeframe = oc.points - x.points
	";

	echo "\n" . $query . "\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}
