<?php

date_default_timezone_set("America/New_York");

/*************************************************************/

$key_string = "<td>" . $this_year . " Regular Season</td>";

function update_player($row) {
	global $dbconn;
	global $this_year;
	global $today;

	$days = $today - 86;

	$recent_day = $today - 5;
	$yesterday = $today - 1;

	$player_id = $row["player_id"];
	$player_name = $row["FNF"];
	$ptype = $row["p_type"];
	$pos = $row["pos"];

	echo "\n*************************************\n";
	echo "the player id is: " . $player_id . "\n";
	echo "the player name is: " . $player_name . "\n";

	// get the player stats page. function includes error checking
	// for 1) url exists in Player table; 2) url can be opened; 
	// 3) url contains stats for this year
	$stats_page = get_stats_page($player_id, $row["URL"]);

	if ($stats_page == "") {

		$total_points = 0;
		$yday_points = 0;
		$recent_points = 0;
		$value = 0;

		$query = "UPDATE players_current SET points=" . $total_points;
		$query .= ", yesterday=" . $yday_points;
		$query .= ", recent=" . $recent_points;
		$query .= ", value=" . $value;
		$query .= " WHERE player_id=" . $player_id;
		$query .= " AND Season=" . $this_year;

		echo "\n" . $query;

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
	else {

		// parse the html of the stats page and calculate the total points
		// for the player
		$total_points = get_total_points_from_page($ptype, $stats_page);

		/*************************************************************/
		// get player's recent point total

		$recent_points = -1;

		$query = "SELECT points FROM players_points_current";
		$query .= " WHERE player_id=" . $player_id;
		$query .= " AND season=" . $this_year;
		$query .= " AND day=" . $recent_day;

		echo "\n" . $query . "\n";

		$rec_points_res = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		if (mysqli_num_rows($rec_points_res) === 0) {
			echo "\nWarning: could not find a recent points total.";
			$recent_points = -1;
		}
		else {

			$rec_points_row = mysqli_fetch_array($rec_points_res);

			$recent_points_total = $rec_points_row["points"];

			echo "\nthe recent points total is: " . $recent_points_total;

			if ($total_points < $recent_points_total) {
				echo "\nWarning: today total points are less than recent total points";
			}
			else {
				$recent_points = $total_points - $recent_points_total;
			}
		}

		/*************************************************************/
		// get player's yesterday point total

		$yday_points = -1;

		$query = "SELECT points FROM players_points_current";
		$query .= " WHERE player_id=" . $player_id;
		$query .= " AND season=" . $this_year;
		$query .= " AND day=" . $yesterday;

		echo "\n" . $query . "\n";

		$yday_points_res = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		if (mysqli_num_rows($yday_points_res) === 0) {
			echo "\nWarning: could not find a yesterday points total.";
			$yday_points = -1;
		}
		else {

			$yday_points_row = mysqli_fetch_array($yday_points_res);

			$yday_points_total = $yday_points_row["points"];

			echo "\nthe yesterday points total is: " . $yday_points_total;

			if ($total_points < $yday_points_total) {
				echo "\nWarning: today total points are less than yesterday total points";
				$yday_points = -1;
			}
			else {
				$yday_points = $total_points - $yday_points_total;
			}
		}

		echo "\nthe salary is: " . $row["salary"] . "\n";

		$value = intval($total_points / $row["salary"] / $days * 10000);

		// update_player_status("found stats", $player_id);

		$query = "UPDATE players_current SET points=" . $total_points;
		$query .= ", yesterday=" . $yday_points;
		$query .= ", recent=" . $recent_points;
		$query .= ", update_status='found stats'";
		$query .= ", updated=" . $today;
		$query .= ", value=" . $value;
		$query .= " WHERE player_id=" . $player_id;
		$query .= " AND Season=" . $this_year;

		echo "\n" . $query;

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}

	// $query = "UPDATE players_current SET points=" . $total_points;
	// $query .= ", yesterday=" . $yday_points;
	// $query .= ", recent=" . $recent_points;
	// $query .= ", updated=" . $today;
	// $query .= ", value=" . $value;
	// $query .= " WHERE player_id=" . $player_id;
	// $query .= " AND Season=" . $this_year;

	// echo "\n" . $query;

	// mysqli_query($dbconn, $query);

	// if (mysqli_error($dbconn)) {
	// 	echo mysqli_error($dbconn);
	// 	exit;
	// }

	$id = $player_id . "_" . $this_year . "_" . $today;

	$query = "REPLACE players_points_current SET";
	$query .= " id='" . $id . "'";
	$query .= ", player_id=" . $player_id;
	$query .= ", points=" . $total_points;
	$query .= ", day=" . $today;
	$query .= ", season=" . $this_year;

	echo "\n" . $query;

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$query = "UPDATE owner_roster_current SET";
	$query .= " points=(" . $total_points . " - prev_points)";
	$query .= " WHERE player_id=" . $player_id;
	$query .= " AND season=" . $this_year;

	echo "\n" . $query . "\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

function get_ptype($page) {
	if (strpos($page, "BLSV") === FALSE) {
		echo "\nthis is an SP";
		return "SP";
	}
	else {
		echo "\nthis is an RP";
		return "RP";
	}
}

function get_stats_page($player_id, $url) {
	global $key_string;

	// check to make sure there is a url in the player record
	if (!($url)) {
		$err_msg = "no url in Player table";

		echo $err_msg . "\n";

		update_player_status($err_msg, $player_id);

		return "";
	}
	echo "the url is:\n" . $url . "\n";

	// try to open player url
	if (!($page = file_get_contents($url))) {

		$err_msg = "could not open url";

		echo $err_msg . "\n";

		update_player_status($err_msg, $player_id);

		return "";
	}
	echo "opened the page.\n";

	// try to find the key string in the web page
	if (strpos($page, $key_string) === FALSE) {

		$err_msg = "could not find the key string in the web page";

		echo $err_msg . "\n";

		update_player_status($err_msg, $player_id);

		return "";
	}
	echo "found the key string.\n";

	return $page;
}

function get_total_points_from_page($ptype, $page) {
	global $dbconn;
	global $key_string;

	// get the "real" ptype for a pitcher
	if ($ptype === "SP" || $ptype === "RP") {
		echo "\ngetting the real ptype...";
		$ptype = get_ptype($page);
	}

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

