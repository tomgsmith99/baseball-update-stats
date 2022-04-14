<?php

function update_owners($dbconn, $season, $today) {

	/*************************************************************/
	// update the ownersXpoints table
	update_ownersXpoints_table($dbconn, $season, $today);

	echo "updated owner_x_points\n";

	/*************************************************************/
	// update the ownersXseasons_current table with place
	update_place($dbconn, $season);

	echo "updated owner place\n";

	/************************************************/
	// update the ownersXseasons_current table with
	// recent points, yesterday points
	update_points($dbconn, $season, $today, "recent");
	update_points($dbconn, $season, $today, "yesterday");

	echo "updated yesterday and recent points\n";
}

function update_ownersXpoints_table($dbconn, $season, $today) {

	$query = "SELECT owner_id, points FROM owner_x_points_current WHERE season=$season";

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

		$query = "UPDATE ownersXseasons SET";
		$query .= " points=" . $row["points"];
		$query .= " WHERE season=$season AND owner_id=$owner_id";

		echo "\n" . $query . "\n";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
}

function update_place($dbconn, $season) {

	$query = "SELECT owner_id, points FROM owner_x_points_current WHERE season=$season ORDER BY points DESC";

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

		$query = "UPDATE ownersXseasons SET place=$place WHERE owner_id=$owner_id AND season=$season";

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

	$query = "SELECT * FROM ownersXseasons WHERE season=$season";

	echo "\n" . $query . "\n";

	$result = mysqli_query($dbconn, $query);

	while ($row = mysqli_fetch_assoc($result)) {

		$owner_id = $row["owner_id"];

		$total_points = $row["points"];

		echo "the owner total points are: " . $total_points;

		$query = "SELECT points FROM ownersXpoints WHERE season=$season AND day=$day AND owner_id=$owner_id";

		echo "\n" . $query . "\n";

		$res = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		$r = mysqli_fetch_assoc($res);

		$points = $total_points - $r["points"];

		$query = "UPDATE ownersXseasons SET $timeframe=$points WHERE owner_id=$owner_id AND season=$season";

		echo "\n" . $query . "\n";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
}
