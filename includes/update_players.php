<?php

date_default_timezone_set('America/New_York');

include "update_player.php";

/*************************************************************/

function update_players($dbconn, $season, $today) {

	$number_of_batches = 30;

	$batch_size = 100;

	$pause_length = 2; // number of seconds to pause between batches

	$start_time = time();

	/*************************************************************/
	// update players

	for ($i = 1; $i <= $number_of_batches; $i++) {

		if (players_are_done($dbconn, $today, $season)) {

			echo "players are complete.";

			break;
		}
		else {

			echo "\n*************************\n";
			echo "starting batch $i...\n";

			update_batch_of_players($dbconn, $today, $season, $batch_size);

			sleep($pause_length);
		}
	}
}

function get_batch_of_players($dbconn, $today, $season, $batch_size) {

	$query = "SELECT p.player_id, p.espn_stats_id, p.fnf, pxs.pos, pxs.salary FROM playersXseasons AS pxs, players AS p WHERE p.player_id = pxs.player_id AND p.player_id NOT IN (5248, 5433) AND updated < $today AND checked < 2 AND pxs.season = $season ORDER BY checked ASC LIMIT $batch_size";

	echo $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	return $result;
}

function initialize_players_table($dbconn, $season) {

	$query = "UPDATE playersXseasons SET checked=0, updated=0 WHERE season=$season";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
	else {
		echo "initialized playersXseasons table...\n";
	}
}

function players_are_done($dbconn, $today, $season) {
// ************* check if stats are all done for today *****************/

	$query = "SELECT checked, updated FROM playersXseasons";
	$query .= " WHERE updated < " . $today;
	$query .= " AND checked < 2 AND season=$season";

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

function update_batch_of_players($dbconn, $today, $season, $batch_size) {

	// gets a mysqli_result
	$batch_of_players = get_batch_of_players($dbconn, $today, $season, $batch_size);

	while ($row = mysqli_fetch_array($batch_of_players)) {
		update_player($dbconn, $today, $season, $row);
	}

	echo "updating the batch of players worked.";
}
