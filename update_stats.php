<?php

date_default_timezone_set("America/New_York");

$GLOBALS["this_year"] = date("Y");
$GLOBALS["today"] = date("z");

include "get_batch_of_players.php";
include "get_dbconn.php";
include "initialize_table.php";
include "update_single_player_func.php";

$GLOBALS["dbconn"] = get_dbconn();


/*************************************************************/

$dbconn = $GLOBALS["dbconn"];

$this_year = $GLOBALS["this_year"];

$key_string = "<td>" . $this_year . " Regular Season</td>";

$number_of_batches = 30;

$batch_size = 100;

$days = $GLOBALS["today"] - 86; // for calculating player value

$today = $GLOBALS["today"];

$pause_length = 2; // number of seconds to pause between batches

/*************************************************************/
// When was the last update?

// $last_checked = get_last_checked();

// echo "last checked is: " . $last_checked;

// exit;

/*************************************************************/
// if last_checked < today, initialize checked/updated cols
// for all players

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

	exit;
}
else { 
	echo "something went wrong. The players are not finished updating.\n";
	exit;
}

function update_last_updated() {
	global $dbconn;

	$dateString = date("l F j, o, g:i a");

	$query = "INSERT INTO updates SET Day = " . $GLOBALS["today"] . ", ";
	$query .= "Time = '" . $dateString . "', Year = " . $GLOBALS["this_year"];

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
	$this_year = $GLOBALS["this_year"];
	$today = $GLOBALS["today"];

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

	$query = "SELECT owner_id, points FROM owners_current";
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

		$query = "REPLACE ownerXpoints SET id='" . $id . "'";
		$query .= ", points=" . $row["points"];
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
	// update the owners_current table with
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
	// update the owners_current table with
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
	$this_year = $GLOBALS["this_year"];

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

	/************************************************/
	// update player picked (acquired + drafted) totals

	$query = "UPDATE players_current SET picked=(acquired + drafted)";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}
