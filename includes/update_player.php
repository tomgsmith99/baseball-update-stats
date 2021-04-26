<?php

function update_player($dbconn, $today, $season, $row) {

	$base_url = "https://www.espn.com/mlb/player/stats/_/id/";
	$pitcher_flag = '<th title="Blown Saves" class="Table__TH">BLSV</th>';
	$this_year_flag = '<td class="Table__TD">' . $season . "</td>";

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

	echo "\n*************************************\n";
	echo "the player id is: " . $player_id . "\n";
	echo "the player name is: " . $player_name . "\n";

	// get the player stats page. function includes error checking
	// for 1) url can be opened; 2) url contains stats for this year

	$url = $base_url . $row["espn_stats_id"];

	$player = get_stats($dbconn, $pitcher_flag, $player_id, $season, $this_year_flag, $url);

	$stats_fields = ["doubles", "hits", "hr", "ip", "k", "rbi", "runs", "saves", "sb", "triples", "walks", "wins"];

	foreach ($stats_fields as $field) {
		$final_stats[$field] = 0;
	}

	if ($player["status"] != "ok") {
		echo $player["status"] . "...\n";
		$total_points = 0;
	}
	else {

		// parse the html of the stats page to get the stats
		// for the player

		$final_stats = assign_final_stats_values($final_stats, $player);

		update_raw_stats_in_player_db_table($dbconn, $final_stats, $player_id, $season);

		/*************************************************************/
		// get_total_points_from_page($stats_page, $player_id);

		$vals = get_total_points($dbconn, $player_id, $season);

		$total_points = $vals["points"];

		$salary = $vals["salary"];

		$value = intval($total_points / $salary / $days * 10000);

		/*************************************************************/
		// get player's recent point total

		$recent_points = get_prev_points($dbconn, $player_id, $season, $today, $total_points, "recent");

		/*************************************************************/
		// get player's yesterday point total

		$yday_points = get_prev_points($dbconn, $player_id, $season, $today, $total_points, "yesterday");

		update_derived_vals_in_player_table($dbconn, $player_id, $recent_points, $season, $today, $value, $yday_points);
	}

	update_players_points_current_table($dbconn, $player_id, $season, $today, $total_points);

	update_ownersXrosters_current_table($dbconn, $player_id, $season, $total_points);

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

	$query = "SELECT points FROM players_points_current";
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

	$query = "SELECT points, salary FROM players_current WHERE player_id = $player_id AND season = $season";

	echo "$query\n";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$row = mysqli_fetch_array($result);

	return $row;
}

function get_stats($dbconn, $pitcher_flag, $player_id, $season, $this_year_flag, $url) {

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

	if (strpos($page, $pitcher_flag) === FALSE) {
		$player_type = "batter";

		$key_string_01 = '<th title="Wins Above Replacement" class="Table__TH">WAR</th></tr></thead>';
	}
	else {
		$player_type = "pitcher";

		$key_string_01 = '<th title="Blown Saves" class="Table__TH">BLSV</th></tr></thead>';
	}

	echo "the player type is: " . $player_type . "...\n";

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

	$query = "UPDATE players_current SET";
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

function update_ownersXrosters_current_table($dbconn, $player_id, $season, $total_points) {

	$query = "UPDATE ownersXrosters_current SET";
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

	$query = "UPDATE players_current SET ";

	foreach ($final_stats as $col => $value) {
		$query .= "$col=$value, ";
	}

	$query = rtrim($query, ", ");

	$query .= " WHERE player_id=$player_id AND season=$season";

	echo $query . "\n";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

function update_player_status($dbconn, $season, $status, $player_id) {

	$query = "UPDATE players_current SET";
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

function update_players_points_current_table($dbconn, $player_id, $season, $today, $total_points) {

	$query = "REPLACE players_points_current SET";
	$query .= " player_id=" . $player_id;
	$query .= ", points=" . $total_points;
	$query .= ", day=" . $today;
	$query .= ", season=" . $season;

	echo "\n" . $query;

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

