<?php

if (file_exists('/Applications/MAMP/htdocs')) {
	$base_path = '/Applications/MAMP/htdocs';
}
else {
	$base_path = '/var/www/html';
}

$dir = "baseball_update_stats";

define("WEB_HOME", "/" . $dir);
define("BASE_PATH", $base_path . WEB_HOME);
define("INCLUDES_PATH", BASE_PATH . "/includes");

include INCLUDES_PATH . '/get_dbconn.php';
include INCLUDES_PATH . '/roster_info.php';

$dbconn = get_dbconn();

/*********************************************************************/

$season = 2019;

/*********************************************************************/

$query = "SELECT DISTINCT player_id FROM players_all_time WHERE season=$season";

$distinct_players = mysqli_query($dbconn, $query);

while ($row = mysqli_fetch_assoc($distinct_players)) {

	$player_id = $row["player_id"];

	echo "\nlooking at player_id " . $player_id;

	$query = "SELECT SUM(drafted) AS number_drafted, SUM(acquired) AS number_acquired";
	$query .= ", SUM(benched) AS number_benched FROM owner_rosters_all_time WHERE";
	$query .= " season=$season AND player_id=$player_id";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$r = mysqli_fetch_assoc($result);

	echo json_encode($r);

	echo "\nthis player was drafted " . $r["number_drafted"] . " times";
	echo "\nthis player was acquired " . $r["number_acquired"] . " times";
	echo "\nthis player was benched " . $r["number_benched"] . " times";

	if ($r["number_drafted"] || $r["number_acquired"]) {

		$query = "UPDATE players_all_time SET";
		$query .= " acquired=" . $r["number_acquired"];
		$query .= ", drafted=" . $r["number_drafted"];
		$query .= ", benched=" . $r["number_benched"];
		$query .= ", picked=" . ($r["number_acquired"] + $r["number_drafted"]);
		$query .= " WHERE player_id=$player_id";
		$query .= " AND season=$season";

		echo "\n$query";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
}

echo "\n\n";
