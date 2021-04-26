<?php

date_default_timezone_set('America/New_York');

include "update_player.php";

/*************************************************************/

function update_players($dbconn, $season, $today) {

	$number_of_batches = 30;

	$batch_size = 100;

	$pause_length = 2; // number of seconds to pause between batches

	$start_time = time();

	initialize_players_table($dbconn, $season);

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


function initialize_players_table($dbconn) {

	$query = "UPDATE players_current SET checked=0, updated=0";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
	else {
		echo "initialized players_current table...\n";
	}
}
// Double-check to see that players are done.

// if (players_are_done()) {

// 	echo "\nplayers are complete. Now updating owners...\n";

// 	update_owners();

// 	update_picked();

// 	update_last_updated();

// 	summarize();

// 	exit;
// }
// else {
// 	echo "something went wrong. The players are not finished updating.\n";
// 	exit;
// }

// function summarize() {
// 	global $start_time;

// 	echo "\n*****************************\n";
// 	echo "Summary\n";

// 	$end_time = time();

// 	$elapsed_time = $end_time - $start_time;

// 	echo "\nelapsed time: " . $elapsed_time . " seconds";
// }
