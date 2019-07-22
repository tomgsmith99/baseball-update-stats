<?php

date_default_timezone_set("America/New_York");

include "get_dbconn.php";
include "initialize_table.php";

$GLOBALS["dbconn"] = get_dbconn();
$GLOBALS["today"] = date("z");

/*************************************************************/

$dbconn = $GLOBALS["dbconn"];

$this_year = date("Y");

$key_string = "<td>" . $this_year . " Regular Season</td>";

$number_of_batches = 30;

$batch_size = 100;

$days = $GLOBALS["today"] - 86; // for calculating player value

$today = $GLOBALS["today"];

$pause_length = 2; // number of seconds to pause between batches

/*************************************************************/
// When was the last update?

$last_checked = get_last_checked();

/*************************************************************/
// if last_checked < today, initialize checked/updated cols
// for all players

initialize_table($last_checked);

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

	echo "players are complete. Now updating owners...\n";

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

function get_batch_of_players($batch_size) {
	global $dbconn;
	global $today;

	$query = "SELECT t1.player_id, t1.p_type, t1.salary, t1.pos";
	$query .= ", t1.update_status, t2.URL, t2.FNF";
	$query .= " FROM players_current AS t1";
	$query .= ", Players as t2";
	$query .= " WHERE t1.updated < " . $today;
	$query .= " AND t1.checked < 2";
	$query .= " AND t1.player_id = t2.Player_ID";
	$query .= " ORDER BY t1.checked ASC";
	$query .= " LIMIT " . $batch_size;

	echo $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	return $result;
}

function get_last_checked() {
	global $dbconn;

	$query = "SELECT MAX(Day) AS last_updated FROM updates ";
	$query .= "WHERE Year=" . $GLOBALS["this_year"];

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$row = mysqli_fetch_array($result);

	return $row["last_checked"];
}

function get_stats_page($player_id, $url) {
	global $key_string;

	// check to make sure there is a url in the player record
	if (!($url)) {
		$err_msg = "no url in Player table";

		echo $err_msg . "\n";

		update_player_status($err_msg, $player_id);

		return;
	}
	echo "the url is:\n" . $url . "\n";

	// try to open player url
	if (!($page = file_get_contents($url))) {

		$err_msg = "could not open url";

		echo $err_msg . "\n";

		update_player_status($err_msg, $player_id);

		return;
	}
	echo "opened the page.\n";

	// try to find the key string in the web page
	if (strpos($page, $key_string) === FALSE) {

		$err_msg = "could not find the key string in the web page";

		echo $err_msg . "\n";

		update_player_status($err_msg, $player_id);

		return;
	}
	echo "found the key string.\n";

	return $page;
}

function get_total_points_from_page($ptype, $page) {
	global $dbconn;
	global $key_string;

	$arr = explode($key_string, $page);

	$arr = explode('</tr><tr class="evenrow"><td>Career</td>', $arr[1]);

	$stats_html = $arr[0];

	$arr = explode('<td class="textright">', $stats_html);

	$stats = [];

	$i = 0;

	foreach ($arr as $field) {

		$x = explode("</td>", $field);

		$stats[$i] = $x[0];

		$i++;
	}

	echo "\n" . json_encode($stats);

	if ($ptype === "Batter") {
		$runs = 	$stats[3];
		$hits = 	$stats[4];
		$doubles = 	$stats[5];
		$triples = 	$stats[6];
		$hr = 		$stats[7];
		$rbi = 		$stats[8];
		$bb = 		$stats[9];
		$sb = 		$stats[11];
		$singles = $hits - $hr - $triples - $doubles;

		$points = $runs + $singles + ($doubles * 2) + ($triples * 3) + ($hr * 4) + $rbi + $bb + ($sb * 2);
	}
	else if ($ptype === "SP") {
		$ip = 	(int)$stats[5];
		$k = 	$stats[11];
		$w = 	$stats[12];

		$points = $ip + $k + ($w * 10);

	}
	else if ($ptype === "RP") {
		$ip = 	(int)$stats[5];
		$k = 	$stats[11];
		$w = 	$stats[12];
		$sv = 	$stats[14];

		$points = $ip + $k + ($w * 10) + ($sv * 10);
	}

	return $points;
}

function update_player_status($status, $player_id) {
	global $dbconn;

	$query = "UPDATE players_current SET";
	$query .= " update_status='" . $status . "'";
	$query .= ", checked=checked + 1";
	$query .= " WHERE player_id=" . $player_id;

	echo "\n" . $query;

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

function update_player($row) {
	global $days;
	global $dbconn;
	global $players;

	$player_id = $row["player_id"];
	$player_name = $row["FNF"];
	$ptype = $row["p_type"];
	$pos = $row["pos"];

	$yesterday = $GLOBALS["yesterday"];
	$recent_day = $GLOBALS["recent_day"];

	echo "\n*************************************\n";
	echo "the player id is: " . $player_id . "\n";
	echo "the player name is: " . $player_name . "\n";

	// get the player stats page. function includes error checking
	// for 1) url exists in Player table; 2) url can be opened; 
	// 3) url contains stats for this year
	$stats_page = get_stats_page($player_id, $row["URL"]);

	// parse the html of the stats page and calculate the total points
	// for the player
	$total_points = get_total_points_from_page($ptype, $stats_page);

	$recent_total_pts = $players_points_current[$player_id][$recent_day];

	if ($total_points < $recent_total_pts) {
		echo "\nWarning: today total points are less than recent total points";

		$total_points = $recent_total_pts;

		$recent_points = 0;
	}
	else {
		$recent_points = $total_points - $recent_total_pts;
	}

	$yday_total_pts = $players_points_current[$player_id][$yesterday];

	if ($total_points < $yday_total_pts) {
		echo "\nWarning: today total points are less than yesterday's total points";

		$total_points = $yday_total_pts;

		$yesterday_points = 0;
	}
	else {
		$yesterday_points = $total_points - $yday_total_pts;
	}

	$players[$player_id]["points"] = $total_points;

	echo "\nthe salary is: " . $row["salary"] . "\n";

	$value = intval($points / $row["salary"] / $days * 10000);

	$query = "UPDATE players_current SET Points=" . $total_points;
	$query .= ", Yesterday=" . $yesterday_points;
	$query .= ", Recent=" . $recent_points;
	$query .= ", Updated=" . $GLOBALS["today"];
	$query .= ", update_status='updated'";
	$query .= " WHERE player_id=" . $player_id;
	$query .= " AND season=" . $GLOBALS["this_year"];

	echo "\n" . $query;

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$id = $player_id . "_" . $GLOBALS["this_year"] . "_" . $GLOBALS["today"];

	$query = "REPLACE players_points_current SET";
	$query .= " id='" . $id . "'";
	$query .= ", player_id=" . $player_id;
	$query .= ", points=" . $total_points;
	$query .= ", day=" . $GLOBALS["today"];
	$query .= ", season=" . $GLOBALS["this_year"];

	echo "\n" . $query;

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$query = "UPDATE owner_roster_current SET";
	$query .= " points=(" . $total_points . " - prev_points)";
	$query .= " WHERE player_id=" . $player_id;
	$query .= " AND season=" . $GLOBALS["this_year"];

	echo "\n" . $query;

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}
