<?php

date_default_timezone_set('America/New_York');

include ".env.php";

include "includes/get_dbconn.php";

/*************************************************************/

$dbconn = get_dbconn();

$this_year = date("Y");

$today = date("z");

/*************************************************************/
// Get the owner's total points by summing all their players
// from the ownersXrosters_current table
// Update the ownersXseasons_current table with the point total

$query = file_get_contents("queries/update_current_owners.sql");
$query = str_replace("{{season}}", $this_year, $query);

echo "\n" . $query . "\n";

$result = mysqli_query($dbconn, $query);

if (mysqli_error($dbconn)) {
	echo mysqli_error($dbconn);
	exit;
}

/*************************************************************/
// update the ownersXpoints table

$query = "SELECT owner_id, points FROM ownersXseasons_current";
$query .= " WHERE season=" . $this_year;

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
	$query .= ", season=" . $this_year;
	$query .= ", owner_id=" . $owner_id;
	$query .= ", day=" . $today;

	echo "\n" . $query . "\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

/*************************************************************/
// update the ownersXseasons_current table with place

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

	if ($row["points"] == $prev_owner_points) {
		// $places[$owner_id] = $place;
	}
	else {
		// $places[$owner_id] = $i;
		$place = $i;
	}

	$prev_owner_points = $row["points"];

	$query = "UPDATE ownersXseasons_current SET place = " . $place;
	$query .= " WHERE owner_id = " . $row["owner_id"] . " AND season = " . $this_year;

	echo "\n" . $query . "\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

$query = file_get_contents("queries/update_owners_recent_pts.sql");
$query = str_replace("{{season}}", $this_year, $query);
$query = str_replace("{{day}}", ($today - 1), $query);
$query = str_replace("{{column}}", "yesterday", $query);

echo "\n" . $query . "\n";

mysqli_query($dbconn, $query);

if (mysqli_error($dbconn)) {
	echo mysqli_error($dbconn);
	exit;
}

/************************************************/
// update the ownersXseasons_current table with
// recent points

$query = file_get_contents("queries/update_owners_recent_pts.sql");
$query = str_replace("{{season}}", $this_year, $query);
$query = str_replace("{{day}}", ($today - 5), $query);
$query = str_replace("{{column}}", "recent", $query);

echo "\n" . $query . "\n";

mysqli_query($dbconn, $query);

if (mysqli_error($dbconn)) {
	echo mysqli_error($dbconn);
	exit;
}


function update_picked() {
	global $dbconn;
	global $this_year;

	/************************************************/
	// update player acquired totals

	$query = file_get_contents("queries/update_player_picked.sql");
	$query = str_replace("{{column}}", "acquired", $query);
	$query = str_replace("{{season}}", $this_year, $query);

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	/************************************************/
	// update player drafted totals

	$query = file_get_contents("queries/update_player_picked.sql");
	$query = str_replace("{{column}}", "drafted", $query);
	$query = str_replace("{{season}}", $this_year, $query);

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}
