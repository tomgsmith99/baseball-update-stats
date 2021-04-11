<?php

date_default_timezone_set('America/New_York');

$cwd = "";

if (getcwd() == "/home/tomgsmith99") {
	$cwd = "/home/tomgsmith99/bin/baseball-update-stats/";
}

include $cwd . ".env.php";

echo "\nthe base path is: " . $base_path;
echo "\nthe web home is: " . $web_home;
echo "\n";

// web paths
define("WEB_HOME", $web_home);
define("VIEWS", WEB_HOME . "/views");

// filesystem paths
define("BASE_PATH", $base_path . '/' . $dir);
define("INCLUDES_PATH", BASE_PATH . "/includes");
define("HTML_PATH", BASE_PATH . "/html");

/*************************************************************/

define("QUERIES_PATH", BASE_PATH . "/queries");

include INCLUDES_PATH . "/get_dbconn.php";

$dbconn = get_dbconn();

$this_year = date("Y");

$today = date("z");

include INCLUDES_PATH . "/get_batch_of_players.php";
include INCLUDES_PATH . "/initialize_table.php";
include INCLUDES_PATH . "/update_single_player_func.php";
// include INCLUDES_PATH . "/upload_logs_to_s3.php";
// include INCLUDES_PATH . "/generate_home_page.php";

/*************************************************************/

$number_of_batches = 30;

$batch_size = 100;

$pause_length = 2; // number of seconds to pause between batches

$start_time = time();

/*************************************************************/
// When was the last update?

// $last_checked = get_last_checked();

// echo "last checked is: " . $last_checked;

// exit;


// upload_logs_to_s3();

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

	summarize();

	// write_home_page();

	// if (file_exists('/tmp/cron_debug_log.log')) {
	// 	upload_logs_to_s3();
	// }

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
	$this_year = $GLOBALS["this_year"];
	$today = $GLOBALS["today"];

	$query = file_get_contents(QUERIES_PATH . "/update_current_owners.sql");
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

	$place = 1;

	while ($row = mysqli_fetch_assoc($result)) {
		$query = "UPDATE ownersXseasons_current SET place = " . $place;
		$query .= " WHERE owner_id = " . $row["owner_id"];

		echo "\n" . $query . "\n";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		$place++;
	}

	/************************************************/
	// update the ownersXseasons_current table with
	// yesterday points

	$query = file_get_contents(QUERIES_PATH . "/update_owners_recent_pts.sql");
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

	$query = file_get_contents(QUERIES_PATH . "/update_owners_recent_pts.sql");
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

	$query = file_get_contents(QUERIES_PATH . "/update_player_picked.sql");
	$query = str_replace("{{column}}", "acquired", $query);
	$query = str_replace("{{season}}", $this_year, $query);

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	/************************************************/
	// update player drafted totals

	$query = file_get_contents(QUERIES_PATH . "/update_player_picked.sql");
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
