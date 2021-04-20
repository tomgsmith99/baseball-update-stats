<?php

$cwd = "";

if (getcwd() == "/home/tomgsmith99") {
	$cwd = "/home/tomgsmith99/bin/baseball-update-stats/";
}

include $cwd . ".env.php";

// web paths
define("WEB_HOME", $web_home);
define("VIEWS", WEB_HOME . "/views");

// filesystem paths
define("BASE_PATH", $base_path . '/' . $dir);
define("INCLUDES_PATH", BASE_PATH . "/includes");
define("HTML_PATH", BASE_PATH . "/html");

/**************************************************************/

define("QUERIES_PATH", BASE_PATH . "/queries");

include INCLUDES_PATH . "/get_dbconn.php";

$dbconn = get_dbconn();

/**************************************************************/

date_default_timezone_set("America/New_York");

$this_year = date("Y");

$today = date("z");

include INCLUDES_PATH . "/get_batch_of_players.php";
include INCLUDES_PATH . "/update_single_player_func.php";

/*************************************************************/
// get player_id from command line

if (isset($argc)) {
	if ($argv[1]) {
		$player_id = $argv[1];
		echo "the player id is: " . $player_id . "\n";
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

/*************************************************************/
// get player row from DB

$batch_of_players = get_batch_of_players(1, $player_id);

$row = mysqli_fetch_array($batch_of_players);

echo json_encode($row);

/*************************************************************/
// update player

update_player($row);
