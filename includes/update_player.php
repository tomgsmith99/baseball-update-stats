<?php

function get_player_url($espn_stats_id, $player_type) {

	$url = 'https://www.espn.com/mlb/player/stats/_/id/' . $espn_stats_id;

	if ($player_type == 'pitcher') {
		$url .= '/category/pitching';
	}
	else {
		$url .= '/category/batting';
	}

	return $url;
}

function update_player($dbconn, $today, $season, $row) {

	if ($season == 2020) {
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
	$salary = $row['salary'];

	echo "\n*************************************\n";
	echo "the player id is: " . $player_id . "\n";
	echo "the player name is: " . $player_name . "\n";

	// get the player stats page. function includes error checking
	// for 1) url can be opened; 2) url contains stats for this year

	$player = get_stats($dbconn, $row, $season);

	$stats_fields = ["doubles", "hits", "hr", "ip", "k", "rbi", "runs", "saves", "sb", "triples", "walks", "wins"];

	foreach ($stats_fields as $field) {
		$final_stats[$field] = 0;
	}

	if ($player["status"] != "ok") {
		echo "\n" . $player["status"] . "...\n";
		$total_points = 0;
	}
	else {

		// parse the html of the stats page to get the stats
		// for the player

		$final_stats = assign_final_stats_values($final_stats, $player);

		update_raw_stats_in_player_db_table($dbconn, $final_stats, $player_id, $season);

		$query = "SELECT points FROM player_x_points_current WHERE player_id = $player_id AND season = $season";

		echo "$query\n";

		$result = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		$row = mysqli_fetch_array($result);

		$total_points = $row['points'];

		if ($player_id == 4687 && $season == 2022) {
			$total_points = $total_points + 377;
		}
		if ($player_id == 4628 && $season == 2022) { // bell
			$total_points = $total_points + 343;
		}
		if ($player_id == 2465 && $season == 2022) { // vazquez
			$total_points = $total_points + 222;
		}
		if ($player_id == 4312 && $season == 2022) { // benintendi
			$total_points = $total_points + 264;
		}
		if ($player_id == 4612 && $season == 2022) { // hader
			$total_points = $total_points + 393;
		}
		if ($player_id == 4488 && $season == 2022) {
			$total_points = $total_points + 134;
		}
		if ($player_id == 4453 && $season == 2022) { // rogers
			$total_points = $total_points + 379;
		}
		if ($player_id == 2113 && $season == 2022) { // syndergaard
			$total_points = $total_points + 194;
		}
		if ($player_id == 5148 && $season == 2022) {
			$total_points = $total_points + 68;
		}
		if ($player_id == 2172 && $season == 2022) { // gallo
			$total_points = $total_points + 179;
		}
		if ($player_id == 2537 && $season == 2022) { // peralta
			$total_points = $total_points + 227;
		}
		if ($player_id == 1986 && $season == 2022) { // peralta
			$total_points = $total_points + 168;
		}
		if ($player_id == 4571 && $season == 2022) { // mahle
			$total_points = $total_points + 268;
		}		
		if ($player_id == 4132 && $season == 2022) { // iglesias
			$total_points = $total_points + 263;
		}
		if ($player_id == 4190 && $season == 2022) { // iglesias
			$total_points = $total_points + 128;
		}
		if ($player_id == 4546 && $season == 2022) { // montgomery
			$total_points = $total_points + 241;
		}
		// if ($player_id == 4024 && $season == 2022) { // canha
		// 	$total_points = $total_points + 91;
		// }		
		/*************************************************************/
		// update player_x_season table with total points

		$query = "UPDATE player_x_season SET points = $total_points WHERE player_id = $player_id AND season = $season";

		echo "$query\n";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		/*************************************************************/
		// update player_x_points table with total points

		$query = "REPLACE INTO player_x_points (player_id, points, day, season) VALUES ($player_id, $total_points, $today, $season)";

		echo "$query\n";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		echo "updating the player_x_points table worked.\n";

		/*************************************************************/

		$value = intval($total_points / $salary / $days * 10000);

		echo "the player value is: " . $value;

		/*************************************************************/
		// get player's recent point total

		$recent_points = get_prev_points($dbconn, $player_id, $season, $today, $total_points, "recent");

		/*************************************************************/
		// get player's yesterday point total

		$yday_points = get_prev_points($dbconn, $player_id, $season, $today, $total_points, "yesterday");

		update_derived_vals_in_player_table($dbconn, $player_id, $recent_points, $season, $today, $value, $yday_points);

		echo "updating the player_x_season table worked.\n";
	}

	update_ownersXrosters($dbconn, $player_id, $season, $total_points);

	echo "updating the ownersXrosters table worked.\n";
}

function assign_final_stats_values($final_stats, $player) {

	if ($player["player_type"] == "batter") {
		$final_stats["runs"] = $player["stats"][3];
		$final_stats["hits"] = $player["stats"][4];
		$final_stats["doubles"] = $player["stats"][5];
		$final_stats["triples"] = $player["stats"][6];
		$final_stats["hr"] = $player["stats"][7];
		$final_stats["rbi"] = $player["stats"][8];
		$final_stats["walks"] = $player["stats"][9];
		$final_stats["sb"] = $player["stats"][12];
	}
	else {
		$final_stats["wins"] = $player["stats"][3];
		$final_stats["ip"] = intval($player["stats"][9]);
		$final_stats["saves"] = $player["stats"][16];
		$final_stats["k"] = $player["stats"][10];
	}

	print_r($final_stats);

	return $final_stats;
}

function get_prev_points($dbconn, $player_id, $season, $today, $total_points, $timeframe) {

	$points = -1;

	if ($timeframe == "recent") {
		$diff = 5;
	}
	else {
		$diff = 1;
	}

	$query = "SELECT points FROM player_x_points";
	$query .= " WHERE player_id=" . $player_id;
	$query .= " AND season=" . $season;
	$query .= " AND day=" . ($today - $diff);

	echo "\n" . $query . "\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	if (mysqli_num_rows($result) === 0) {
		echo "\nWarning: could not find a $timeframe points total.";
	}
	else {

		$row = mysqli_fetch_array($result);

		$prev_points_total = $row["points"];

		echo "\nthe points total on $timeframe day was: " . $prev_points_total;

		if ($total_points < $prev_points_total) {
			echo "\nWarning: today total points are less than $timeframe total points";
		}
		else {
			$points = $total_points - $prev_points_total;
		}
	}

	return $points;
}

function get_total_points($dbconn, $player_id, $season) {

	$query = "SELECT points, prev_points, salary FROM player_x_season WHERE player_id = $player_id AND season = $season";

	echo "$query\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$row = mysqli_fetch_array($result);

	return $row;
}

function get_stats($dbconn, $row, $season) {

	$player_id = $row['player_id'];

	$player_type = '';

	if ($row['pos'] == 'RP' || $row['pos'] == 'SP') {
		$player_type = 'pitcher';
	}
	else {
		$player_type = 'batter';
	}

	$url = get_player_url($row['espn_stats_id'], $player_type);

	$this_year_flag = '<td class="Table__TD">' . $season . "</td>";

	$player["status"] = "";
	$player["player_type"] = "";
	$player["stats"] = [];

	echo "the url is:\n" . $url . "\n";

	/*************************************************************/
	// try to open player url

	if (!($page = file_get_contents($url))) {

		$player["status"] = "could not open url";

		update_player_status($dbconn, $season, $player["status"], $player_id);

		return $player;
	}

	echo "opened the page...\n";

	/*************************************************************/
	// has the player made an appearance yet this year?

	if (strpos($page, $this_year_flag) === FALSE) {

		$player["status"] = "player has not played yet this year";

		update_player_status($dbconn, $season, $player["status"], $player_id);

		return $player;
	}

	echo "found the year $season in the player's stats page...\n";

	/*************************************************************/
	// are we looking at a batter or a pitcher?

	if ($player_type == "batter") {
		$key_string_01 = '<th title="Wins Above Replacement" class="Table__TH">WAR</th></tr></thead>';
	}
	else {
		$key_string_01 = '<th title="Blown Saves" class="Table__TH">BLSV</th></tr></thead>';
	}

	$player["player_type"] = $player_type;

	/*************************************************************/
	// try to find the key string in the web page

	if (strpos($page, $key_string_01) === FALSE) {

		$player["status"] = "could not find the key string in the web page";

		update_player_status($dbconn, $season, $player["status"], $player_id);

		return $player;
	}
	else {
		echo "found the stats key string in the page...\n";

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

		echo "the player's raw stats are...\n";
		print_r($stats);

		$player["status"] = "ok";
		$player["stats"] = $stats;

		update_player_status($dbconn, $season, "found stats", $player_id);

		return $player;
	}
}

function update_derived_vals_in_player_table($dbconn, $player_id, $recent_points, $season, $today, $value, $yday_points) {

	$query = "UPDATE player_x_season SET";
	$query .= " yesterday=" . $yday_points;
	$query .= ", recent=" . $recent_points;
	$query .= ", update_status='found stats'";
	$query .= ", updated=" . $today;
	$query .= ", value=" . $value;
	$query .= " WHERE player_id=" . $player_id;
	$query .= " AND season=" . $season;

	echo "\n" . $query . "\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

function update_ownersXrosters($dbconn, $player_id, $season, $total_points) {

	$query = "UPDATE owner_x_player SET";
	$query .= " points=(" . $total_points . " - prev_points)";
	$query .= " WHERE player_id=" . $player_id;
	$query .= " AND season=" . $season;
	$query .= " AND benched=0";

	echo "\n" . $query . "\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

function update_raw_stats_in_player_db_table($dbconn, $final_stats, $player_id, $season) {

	$query = "REPLACE INTO player_x_points_current (player_id, season, ";

	foreach ($final_stats as $col => $value) {
		$query .= "$col, ";
	}

	$query = rtrim($query, ", ");

	$query .= ") VALUES ($player_id, $season, ";

	foreach ($final_stats as $col => $value) {
		$query .= "$value, ";
	}

	$query = rtrim($query, ", ");

	$query .= ")";

	echo $query . "\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

function update_player_status($dbconn, $season, $status, $player_id) {

	$query = "UPDATE player_x_season SET";
	$query .= " update_status='" . $status . "'";
	$query .= ", checked=checked + 1";
	$query .= " WHERE player_id=$player_id AND season=$season";

	echo "\n" . $query;

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

// function update_players_points_current_table($dbconn, $player_id, $season, $today, $total_points) {

// 	$query = "REPLACE players_points_current SET";
// 	$query .= " player_id=" . $player_id;
// 	$query .= ", points=" . $total_points;
// 	$query .= ", day=" . $today;
// 	$query .= ", season=" . $season;

// 	echo "\n" . $query;

// 	mysqli_query($dbconn, $query);

// 	if (mysqli_error($dbconn)) {
// 		echo mysqli_error($dbconn);
// 		exit;
// 	}
// }
