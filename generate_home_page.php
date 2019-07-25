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

// include $_SERVER['DOCUMENT_ROOT'] . "/baseball/includes/env.php";

include INCLUDES_PATH . '/get_dbconn.php';
include INCLUDES_PATH . '/get_all_owners_for_year.php';
include INCLUDES_PATH . '/get_all_players_for_year.php';
include INCLUDES_PATH . '/get_all_teams_html.php';
include INCLUDES_PATH . '/get_owner_drop_down_list.php';
include INCLUDES_PATH . '/get_owner_rows_html.php';
include INCLUDES_PATH . '/get_owner_stats_table.php';
include INCLUDES_PATH . '/get_player_stats_table.php';
// include INCLUDES_PATH . '/get_picked_player_table.php';

include INCLUDES_PATH . '/ordinalSuffix.php';

$GLOBALS["this_year"] = date("Y");

$dbconn = get_dbconn();

$season = date("Y");

$GLOBALS["season_under_way"] = true;

/************************************************************/
// get all of this year's players
$players = get_all_players_for_year();

// echo "the players are: ";

// echo json_encode($players);

/************************************************************/
// get all of this year's owners
$owners = get_all_owners_for_year($season, "Points DESC");

// echo "the owners are: ";

// echo json_encode($owners);

/************************************************************/
// Get last update time
// $c["last_updated"] = get_last_updated();

// /************************************************************/
// Get owner drop-down list
$c["owner_drop_down_list"] = get_owner_drop_down_list($season);

// echo $c["owner_drop_down_list"];

// /************************************************************/
// // get html rows for all of this year's owners
$c["owner_rows"] = get_owner_rows_html($owners);

// echo $c["owner_rows"];

// /************************************************************/
// // get html tables for all teams
$c["all_teams"] = get_all_teams_html($owners, $players, $season);

// echo $c["all_teams"];

// /************************************************************/
// // get list of picked players with the most points
// // get list of all players with the most points
$c["players_points"] = get_player_stats_table($season, "points", true);
$c["players_points_all"] = get_player_stats_table($season, "points", false);

// echo $c["players_points"];
// echo $c["players_points_all"];

// /************************************************************/
// // get list of picked players with the most value
// // get list of all players with the most value
$c["players_value"] = get_player_stats_table($season, "value", true);
$c["players_value_all"] = get_player_stats_table($season, "value", false);

// /************************************************************/
// // get list of most popular players
$c["players_picked"] = get_player_stats_table($season, "drafted", true);

// /************************************************************/

// // by default, do not show that categories that take a while to
// // get going at the beginning of the season
$c["visibility"] = "display:none";

if ($GLOBALS["season_under_way"]) {

// 	/************************************************************/
// 	// shows the hidden tables for these categories
	$c["visibility"] = "";

// 	/************************************************************/
// 	// get list of picked players with the most points yesterday
// 	// get list of all players with the most points yesterday
	$c["players_yesterday"] = get_player_stats_table($season, "yesterday", true);
	$c["players_yesterday_all"] = get_player_stats_table($season, "yesterday", false);

// 	/************************************************************/
// 	// get list of picked players with the most points recently
// 	// get list of all players with the most points recently
	$c["players_recent"] = get_player_stats_table($season, "recent", true);
	$c["players_recent_all"] = get_player_stats_table($season, "recent", false);

// 	/************************************************************/
// 	// get list of owners with the most points yesterday
// 	// get list of owners with the most points recently
	$c["owners_yesterday"] = get_owner_stats_table($season, "yesterday");
	$c["owners_recent"] = get_owner_stats_table($season, "recent");
}

// /************************************************************/

$c["season"] = $season;

$c["views"] = VIEWS;

$c["web_home"] = WEB_HOME;

// /************************************************************/

$content = file_get_contents(HTML_PATH . "/view_current_standings.html");

foreach ($c as $key => $value) {
	$content = str_replace("{{" . $key . "}}", $value, $content);
}

// /************************************************************/

$page = file_get_contents(HTML_PATH . "/page.html");

$page = str_replace("{{TITLE}}", "Diffendorf baseball " . $season, $page);

$page = str_replace("{{CONTENT}}", $content, $page);

foreach ($c as $key => $value) {
	$page = str_replace("{{" . $key . "}}", $value, $page);
}


file_put_contents("index.html", $page);

// echo $page;

// exit;

/************************************************************/
/* stats last updated query
/************************************************************/

function get_last_updated() {
	global $dbconn;

	$query = "SELECT Time FROM Updates ORDER BY id DESC LIMIT 1";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$row = mysqli_fetch_assoc($result);

	return $row["Time"];
}
