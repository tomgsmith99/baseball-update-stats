<?php

date_default_timezone_set("America/New_York");

/*************************************************************/

$pitcher_flag = '<th title="Blown Saves" class="Table__TH">BLSV</th>';

$base_url = "https://www.espn.com/mlb/player/stats/_/id/";

// global $this_year;

$this_year = date("Y");

echo "this year is: " . $this_year;

$this_year_flag = '<td class="Table__TD">' . $this_year . "</td>";

function update_player($row) {
	global $base_url;
	global $dbconn;
	global $this_year;
	global $today;

	$player_type = "";

	// $days = $today - 86;

	if ($this_year == 2020) {
		$first_day = 205;
	}
	else {
		$first_day = 86;
	}

	$days = $today - $first_day;

	$recent_day = $today - 5;
	$yesterday = $today - 1;

	$player_id = $row["player_id"];
	$player_name = $row["fnf"];
	$pos = $row["pos"];

	echo "\n*************************************\n";
	echo "the player id is: " . $player_id . "\n";
	echo "the player name is: " . $player_name . "\n";

	// get the player stats page. function includes error checking
	// for 1) url exists in Player table; 2) url can be opened; 
	// 3) url contains stats for this year

	$url = $base_url . $row["espn_stats_id"];

	$stats_page = get_stats_page($player_id, $url);

	if ($stats_page != "") {

		// parse the html of the stats page and calculate the total points
		// for the player
		get_total_points_from_page($stats_page, $player_id);

		$query = "SELECT points, salary FROM players_current WHERE player_id = $player_id AND season = $this_year";

		$result = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		$row = mysqli_fetch_array($result);

		$total_points = $row["points"];

		echo "the total points are: " . $total_points;

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
				$recent_points = -1;
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

		update_player_status("found stats", $player_id);

		$query = "UPDATE players_current SET";
		$query .= " yesterday=" . $yday_points;
		$query .= ", recent=" . $recent_points;
		$query .= ", update_status='found stats'";
		$query .= ", updated=" . $today;
		$query .= ", value=" . $value;
		$query .= " WHERE player_id=" . $player_id;
		$query .= " AND season=" . $this_year;

		echo "\n" . $query;

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}

	else {

		$total_points = 0;

		$query = "UPDATE players_current SET";
		$query .= " yesterday=-1";
		$query .= ", recent=-1";
		$query .= ", updated=" . $today;
		$query .= ", value=-1";
		$query .= " WHERE player_id=" . $player_id;
		$query .= " AND season=" . $this_year;

		echo "\n" . $query;

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}

	$query = "REPLACE players_points_current SET";
	$query .= " player_id=" . $player_id;
	$query .= ", points=" . $total_points;
	$query .= ", day=" . $today;
	$query .= ", season=" . $this_year;

	echo "\n" . $query;

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$query = "UPDATE ownersXrosters_current SET";
	$query .= " points=(" . $total_points . " - prev_points)";
	$query .= " WHERE player_id=" . $player_id;
	$query .= " AND season=" . $this_year;
	$query .= " AND benched=0";

	echo "\n" . $query . "\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

function get_stats_page($player_id, $url) {

	global $pitcher_flag;

	global $this_year_flag;

	global $player_type;

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

	// has the player made an appearance yet this year?
	if (strpos($page, $this_year_flag) === FALSE) {

		$err_msg = "player has not played yet this year";

		echo $err_msg . "\n";

		update_player_status($err_msg, $player_id);

		echo $err_msg;

		return "";
	}

	if (strpos($page, $pitcher_flag) === FALSE) {
		$player_type = "batter";

		$key_string_01 = '<th title="Wins Above Replacement" class="Table__TH">WAR</th></tr></thead>';
	}
	else {
		$player_type = "pitcher";

		$key_string_01 = '<th title="Blown Saves" class="Table__TH">BLSV</th></tr></thead>';
	}

	echo "the player type is: " . $player_type . "\n";

	// try to find the key string in the web page
	if (strpos($page, $key_string_01) === FALSE) {

		$err_msg = "could not find the key string in the web page";

		echo $err_msg . "\n";

		update_player_status($err_msg, $player_id);

		return "";
	}
	else {
		$arr = explode($key_string_01, $page);

		$arr = explode("</tbody>", $arr[1]);

		$stats_table = $arr[0];

		$row_regex = '<tr class="Table__TR Table__TR--sm Table__even" data-idx="[0-9]*">';

		$arr = preg_split($row_regex, $stats_table);

		$arr_size = sizeof($arr);

		$this_year_row = $arr[$arr_size - 3];

		$arr = explode('<td class="Table__TD">', $this_year_row);

		foreach ($arr as $cell) {
			$x = rtrim($cell, "</td>");
			$x = rtrim($cell, "</td></tr");
			$stats[] = $x;
		}

		print_r($stats);

		return $stats;
	}
}

function get_total_points_from_page($stats, $player_id) {

	global $dbconn;

	global $player_type;

	global $this_year;

	if ($player_type == "batter") {
		$runs = $stats[3];
		$hits = $stats[4];
		$doubles = $stats[5];
		$triples = $stats[6];
		$hr = $stats[7];
		$rbi = $stats[8];
		$walks = $stats[9];
		$sb = $stats[12];

		$query = "UPDATE players_current SET runs = $runs, hits = $hits, doubles = $doubles, triples = $triples, hr = $hr, rbi = $rbi, walks = $walks, sb = $sb WHERE player_id = $player_id AND season = $this_year";

		echo $query . "\n";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
	else {
		$wins = $stats[3];
		$ip = intval($stats[9]);
		$saves = $stats[16];
		$k = $stats[10];

		$query = "UPDATE players_current SET wins = $wins, saves = $saves, ip = $ip, k = $k WHERE player_id = $player_id AND season = $this_year";

		echo $query . "\n";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}

	print_r($final_stats);

	echo "\ntotal points: " . $points;

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
