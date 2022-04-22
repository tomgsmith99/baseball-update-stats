<?php

date_default_timezone_set('America/New_York');

include ".env.php";

include "includes/get_dbconn.php";

include "includes/update_owners.php";

include "includes/update_players.php";

/*************************************************************/

$dbconn = get_dbconn();

$season = date("Y");

$today = date("z");

$start_time = time();

/*************************************************************/

get_command_line_args($dbconn, $season, $today, $argv);

initialize_players_table($dbconn, $season);

update_players($dbconn, $season, $today);

echo "updating players is complete.";

update_owners($dbconn, $season, $today);

update_last_updated($dbconn);

summarize($start_time);

/*************************************************************/

function get_command_line_args($dbconn, $season, $today, $argv) {

	if (in_array('--player', $argv)) {

	    $key = array_search('--player', $argv);

	    $id = $argv[$key + 1];

	    echo "the player id is: $id\n";

	    $query = "SELECT player_id, salary, pos, update_status, espn_stats_id, fnf FROM player_x_season_detail WHERE player_id = $id AND season = $season";

		echo $query . "\n";

		$result = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		$row = mysqli_fetch_array($result);

		if (count($row) == 1) {
			update_player($dbconn, $today, $season, $row);
		}
		else {
			echo "Could not find player_id $id\n";
		}

	    exit;
	}

	if (in_array('--players', $argv)) {

		update_players($dbconn, $season, $today);

	    exit;
	}

	if (in_array('--owners', $argv)) {

		update_owners($dbconn, $season, $today);

	    exit;
	}

}

function summarize($start_time) {

	echo "\n*****************************\n";
	echo "Summary\n";

	$end_time = time();

	$elapsed_time = $end_time - $start_time;

	echo "\nelapsed time: " . $elapsed_time . " seconds";
}

function update_last_updated($dbconn) {

	$update_desc = date("D F jS, o, g:ia");

	$query = "INSERT INTO updates SET update_desc='" . $update_desc . "'";

	echo "\n$query\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}
