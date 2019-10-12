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

set_include_path(INCLUDES_PATH);

define("HTML_PATH", BASE_PATH . "/html");

define("VIEWS", WEB_HOME . "/views");

include INCLUDES_PATH . '/get_dbconn.php';

$dbconn = get_dbconn();

/*********************************************************************/

$season = 2019;
$first_day = 88;
$last_day = 269;

$query = "SELECT * FROM Players" . $season . " AS p,";
$query .= " Players AS P";
$query .= " WHERE p.Pos IS NOT NULL ";
$query .= " AND (p.Picked > 0 OR p.Points > 0)";
$query .= " AND p.Player_ID = P.Player_ID";
$query .= " AND p.Player_ID NOT IN";
$query .= " (SELECT DISTINCT player_id FROM player_points_all_time WHERE season=$season)";
$query .= " ORDER BY P.Lname ASC";

$result = mysqli_query($dbconn, $query);

if (mysqli_error($dbconn)) {
	echo mysqli_error($dbconn);
	exit;
}

while ($row = mysqli_fetch_assoc($result)) {

	// echo(json_encode($row));

	$id = "";

	$id .= $season;

	$player_id_str = "";

	$player_id_str .= $row["Player_ID"];

	while(strlen($player_id_str) < 5) {
		$player_id_str = "0" . $player_id_str;
	}

	$id .= $player_id_str;

	echo "\n" . $row["Lname"] . "\n";

	if ($row["Pos"] == "SP" || $row["Pos"] == "RP") { $p_type = $row["Pos"]; }
	else { $p_type = "Batter"; }

	$query = "REPLACE INTO players_all_time SET";
	$query .= " player_id=" . $row["Player_ID"];
	$query .= ", salary=" . $row["Salary"];
	$query .= ", team='" . $row["Team"] . "'";
	$query .= ", points=" . $row["Points"];
	$query .= ", pos='" . $row["Pos"] . "'";
	$query .= ", p_type='" . $p_type . "'";
	$query .= ", value=" . $row["Value"];
	$query .= ", season=" . $season;
	$query .= ", id=" . $id;

	// echo $query . "\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	for ($i=$first_day; $i <= $last_day; $i++) { 

		$id = "";

		$player_id = $row["Player_ID"];

		$day_str = "";

		$day_str .= $i;

		while(strlen($day_str) < 3) {
			$day_str = "0" . $day_str;
		}

		$player_id_str = $player_id;

		while(strlen($player_id_str) < 5) {
			$player_id_str = "0" . $player_id_str;
		}

		$id .= $season . $day_str . $player_id_str;

		$points_col = "Points" . $i;

		if (empty($row[$points_col])) { $points_col = NULL; }

		$query = "INSERT IGNORE INTO player_points_all_time SET";
		$query .= " id=" . floatval($id);
		$query .= ", player_id=" . $player_id;

		if (empty($row[$points_col])) {
			$query .= ", points=NULL";
		}
		else {
			$query .= ", points=" . $row[$points_col];
		}

		$query .= ", day=" . $i;
		$query .= ", season=" . $season;

		// echo $query . "\n";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
}
