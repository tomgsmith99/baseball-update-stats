<?php

include ".env.php";

$season = date("Y");

include "includes/get_dbconn.php";
include "includes/update_player.php";

/**************************************************************/
$dbconn = get_dbconn();

date_default_timezone_set("America/New_York");

$today = date("z");

/*************************************************************/
// get player_id from command line

$player_id = get_player_id();

$player = get_player($dbconn, $player_id);

/*************************************************************/
// update player

update_player($dbconn, $today, $season, $player);

/*************************************************************/

function get_player($dbconn, $player_id) {

	$query = "SELECT player_id, salary, pos, update_status, espn_stats_id, fnf";
	$query .= " FROM players_current_view";
	$query .= " WHERE player_id = " . $player_id;

	echo $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$row = mysqli_fetch_array($result);

	return $row;
}

function get_player_id() {
	global $argc, $argv;

	if (isset($argc)) {
		if ($argv[1]) {
			$player_id = $argv[1];
			echo "the player id is: " . $player_id . "\n";
			return $player_id;
		}
		else {
			echo "you need to supply a player_id as a command line argument.\n";
			echo "like this: php update_single_player.php 4244";
			exit;
		}
	}
	else {
		echo "argc and argv disabled\n";
	}
}
