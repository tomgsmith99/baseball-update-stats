<?php

$dir = "baseball_update_stats";

if (file_exists('/Applications/MAMP/htdocs')) {
	$base_path = '/Applications/MAMP/htdocs';
	$web_home = '/' . $dir;
}
else {
	$base_path = '/var/www/html';
	$web_home = '';
}

// web paths
define("WEB_HOME", $web_home);
define("VIEWS", WEB_HOME . "/views");

// filesystem paths
define("BASE_PATH", $base_path . '/' . $dir);
define("INCLUDES_PATH", BASE_PATH . "/includes");
define("HTML_PATH", BASE_PATH . "/html");

/**************************************************************/

include INCLUDES_PATH . '/get_dbconn.php';

/**************************************************************/

$dbconn = get_dbconn();

/**************************************************************/

include INCLUDES_PATH . '/get_all_owners_for_year.php';
include INCLUDES_PATH . '/get_all_players_for_year.php';
include INCLUDES_PATH . '/get_all_teams_html.php';
include INCLUDES_PATH . '/get_owner_drop_down_list.php';
include INCLUDES_PATH . '/get_owner_rows_html.php';
include INCLUDES_PATH . '/get_owner_stats_table.php';
include INCLUDES_PATH . '/get_player_stats_table.php';
include INCLUDES_PATH . '/ordinalSuffix.php';
include INCLUDES_PATH . "/get_page.php";

/**************************************************************/

if (!($_GET["season"])) {
	echo "you must indicate a season.";
}
else {
	$season = intval($_GET["season"]);
}

/************************************************************/
// get all of this year's owners
$owners = get_all_owners_for_year($season, "Points DESC, Lname ASC, Fname ASC, Suffix ASC");

/************************************************************/
// get html rows for all of this year's owners
$c["owner_rows"] = get_owner_rows_html($owners);
$c["season"] = $season;

if ($season > 2004) {

	/************************************************************/
	// get all of this year's players
	$players = get_all_players_for_year('past', $season);

	/************************************************************/
	// Get owner drop-down list
	$c["owner_drop_down_list"] = get_owner_drop_down_list($season);

	/************************************************************/
	// get html tables for all teams
	$c["all_teams"] = get_all_teams_html($owners, $players, $season);

	/************************************************************/
	// get list of picked players with the most points
	// get list of all players with the most points
	$c["players_points"] = get_player_stats_table($season, "points", true);
	$c["players_points_all"] = get_player_stats_table($season, "points", false);

	/************************************************************/
	// get list of picked players with the most value
	// get list of all players with the most value
	$c["players_value"] = get_player_stats_table($season, "value", true);
	$c["players_value_all"] = get_player_stats_table($season, "value", false);

	/************************************************************/
	// get list of most popular players
	$c["players_picked"] = get_player_stats_table($season, "picked", true);
	$c["players_drafted"] = get_player_stats_table($season, "drafted", true);
	$c["players_acquired"] = get_player_stats_table($season, "acquired", true);
	$c["players_benched"] = get_player_stats_table($season, "benched", true);

	/************************************************************/
	// shows the hidden tables for these categories
	$c["visibility"] = "";

	/************************************************************/

	$content = file_get_contents(HTML_PATH . "/final_standings.html");

	foreach ($c as $key => $value) {
		$content = str_replace("{{" . $key . "}}", $value, $content);
	}
}
else {
	$content = file_get_contents(HTML_PATH . "/final_standings_b4_2005.html");
	$content = str_replace("{{owner_rows}}", $c["owner_rows"], $content);
	$content = str_replace("{{season}}", $c["season"], $content);
}

$title = "final standings " . $season;

$page = get_page($content, $title);

echo $page;

exit;
