<?php

date_default_timezone_set('America/New_York');

include ".env.php";

include "includes/get_dbconn.php";
include "includes/get_batch_of_players.php";
include "includes/initialize_table.php";
include "includes/update_single_player_func.php";

/*************************************************************/

$dbconn = get_dbconn();

$this_year = date("Y");

$today = date("z");

/*************************************************************/

$number_of_batches = 30;

$batch_size = 100;

$pause_length = 2; // number of seconds to pause between batches

$start_time = time();

/*************************************************************/

initialize_table();

/*************************************************************/
// update players

for ($i = 1; $i <= $number_of_batches; $i++) {

	if (players_are_done()) {

		echo "players are complete.";

		break;
	}
	else {

		echo "\n*************************\n";
		echo "starting batch $i...\n";

		update_players();

		sleep($pause_length);
	}
}

// Double-check to see that players are done.

if (players_are_done()) {

	echo "\nplayers are complete. Now updating owners...\n";

	update_owners();

	update_picked();

	update_last_updated();

	summarize();

	exit;
}
else {
	echo "something went wrong. The players are not finished updating.\n";
	exit;
}

function summarize() {
	global $start_time;

	echo "\n*****************************\n";
	echo "Summary\n";

	$end_time = time();

	$elapsed_time = $end_time - $start_time;

	echo "\nelapsed time: " . $elapsed_time . " seconds";
}

function update_last_updated() {
	global $dbconn;

	$update_desc = date("D F jS, o, g:ia");

	$query = "INSERT INTO updates SET update_desc='" . $update_desc . "'";

	echo "\n$query\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

function players_are_done() {
// ************* check if stats are all done for today *****************/
	global $dbconn;
	global $today;

	$query = "SELECT checked, updated FROM players_current";
	$query .= " WHERE updated < " . $today;
	$query .= " AND checked < 2";

	echo $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$num_rows = mysqli_num_rows($result);

	echo "\nthe number of players that have not been updated or checked is: " . $num_rows . "\n";

	if ((int)$num_rows === 0) { return TRUE; }
	else { return FALSE; }
}

function update_players() {
	global $batch_size;

	// gets a mysqli_result
	$batch_of_players = get_batch_of_players($batch_size);

	while ($row = mysqli_fetch_array($batch_of_players)) {
		update_player($row);
	}
}

function update_owners() {
	global $dbconn;
	global $this_year;
	global $today;

	$query = file_get_contents("queries/update_current_owners.sql");
	$query = str_replace("{{season}}", $this_year, $query);

	echo "\n" . $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	/************************************************/
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

		$id = $this_year . "_" . $today . "_" . $owner_id;

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

	/************************************************/
	// update the ownersXseasons_current table with place

	$query = "SELECT owner_id FROM ownersXseasons_current ORDER BY points DESC";

	echo "\n" . $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

// places = {}

// prev_owner_points = 0

// i = 0

// place = 0

// for row in rows:

// 	i += 1

// 	owner_id = row["owner_id"]

// 	if row["points"] == prev_owner_points:
// 		places[owner_id] = place
// 	else:
// 		places[owner_id] = i
// 		place = i

// 	prev_owner_points = row["points"]

	$places = [];

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

	// $place = 1;

	// while ($row = mysqli_fetch_assoc($result)) {
	// 	$query = "UPDATE ownersXseasons_current SET place = " . $place;
	// 	$query .= " WHERE owner_id = " . $row["owner_id"];

	// 	echo "\n" . $query . "\n";

	// 	mysqli_query($dbconn, $query);

	// 	if (mysqli_error($dbconn)) {
	// 		echo mysqli_error($dbconn);
	// 		exit;
	// 	}

	// 	$place++;
	// }

	/************************************************/
	// update the ownersXseasons_current table with
	// yesterday points

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