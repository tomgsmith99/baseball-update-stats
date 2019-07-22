<?php

date_default_timezone_set("America/New_York");

include "get_batch_of_players.php";
include "get_dbconn.php";
include "update_single_player_func.php";

$GLOBALS["dbconn"] = get_dbconn();

/*************************************************************/

$dbconn = $GLOBALS["dbconn"];

/*************************************************************/
// get player_id from command line

if (isset($argc)) {
	if ($argv[1]) {
		$player_id = $argv[1];
		echo "the player id is: " . $player_id . "\n";
	}
	else {
		echo "you need to supply a player_id as a command line argument.\n";
		exit;
	}
}
else {
	echo "argc and argv disabled\n";
}

/*************************************************************/
// get player row from DB

$batch_of_players = get_batch_of_players(1, $player_id);

$row = mysqli_fetch_array($batch_of_players);

echo json_encode($row);

/*************************************************************/
// update player

update_player($row);



// while ($row = mysqli_fetch_array($batch_of_players)) {
// 	// update_player($row);
// 	echo json_encode($row);

// }




