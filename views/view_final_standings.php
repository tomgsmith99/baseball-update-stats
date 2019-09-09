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
define("HTML_PATH", BASE_PATH . "/html");
define("VIEWS", WEB_HOME . "/views");

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
include INCLUDES_PATH . "/show_page.php";

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

show_page($content, $title);

exit;

// /************************************************************/

// $page = file_get_contents(HTML_PATH . "/page.html");

// $page = str_replace("{{TITLE}}", "Diffendorf baseball " . $season, $page);

// $page = str_replace("{{CONTENT}}", $content, $page);

// foreach ($c as $key => $value) {
// 	$page = str_replace("{{" . $key . "}}", $value, $page);
// }

// function get_home_page() {
// 	global $page;

// 	write_home_page();

// 	return $page;
// }


// /************************************************************/

// if ($season < 2004) {
// 	$content = file_get_contents(HTML_PATH . "/view_final_standings_b4_2004.html");
// }
// else {
// 	$content = file_get_contents(HTML_PATH . "/view_final_standings.html");
// }

// $content = str_replace("{{SEASON}}", $season, $content);

// $content = str_replace("{{WEB_HOME}}", WEB_HOME, $content);

// /************************************************************/
// /* main player query
// /************************************************************/

// if ($season < 2003) {} // Player data not available before 2003
// else {
// 	if ($season == 2003) {
// 		$query = "SELECT Drafted, Acquired, Picked, t2.Fname, t2.Lname, Points, t2.Player_ID, Pos, Salary, Team, Value FROM Players" . $season . " as t1, Players as t2 WHERE t1.Player_ID = t2.Player_ID ORDER BY Points DESC";
// 	}
// 	else if ($season > 2003 && $season < 2008) {
// 		$query = "SELECT Drafted, Acquired, Picked, t2.Fname, t2.Lname, Points, t2.Player_ID, Pos, PType, Salary, Team, Value FROM Players" . $season . " as t1, Players as t2 WHERE t1.Player_ID = t2.Player_ID ORDER BY Points DESC";
// 	}
// 	else {
// 		$query = "SELECT DraftedBy, AcquiredBy, PickedBy, t2.Fname, t2.Lname, Picked, PickedOrig, PickedUp, Points, t2.Player_ID, Pos, PType, Salary, Team, Value FROM Players" . $season . " as t1, Players as t2 WHERE t1.Player_ID = t2.Player_ID ORDER BY Points DESC";
// 	}

// 	$result = mysqli_query($dbconn, $query);

// 	if (mysqli_error($dbconn)) {
// 		error_log(mysqli_error($dbconn));
// 	}

// 	while ($row = mysqli_fetch_assoc($result)) {
// 		$GLOBALS["players"][$row["Player_ID"]] = $row;
// 	}
// }

// // Now all the year's picked players are stored in a global arr
// /************************************************************/

// // Owner_ID #63 is the dummy test team every year
// $base_query = "SELECT * FROM OwnersMain INNER JOIN Members ON OwnersMain.Owner_ID = Members.Member_ID WHERE Owner_ID <> 63 AND Year = $season";

// /***********************************************************/
// /* owner drop-down list
// /***********************************************************/
// $owner_drop_down = "";

// $all_teams = "";

// $team_row_template = file_get_contents(HTML_PATH . "/team_row.html");

// $query = $base_query . " ORDER BY Lname, Fname ASC";

// $result = mysqli_query($dbconn, $query);

// while ($row = mysqli_fetch_assoc($result)) {

// 	$this_team = new Team($row, $season);

// 	$owner_drop_down .= $this_team->get_owner_name_with_link("dropdown-item", TRUE, "LNF") . "\n";

// 	$team_html_rows[$this_team->place] = $this_team->get_table_row();

// 	if ($season > 2002) {
// 		$all_teams .= $this_team->get_html_table($row["Owner_ID"]);
// 	}
// }

// $content = str_replace("{{ALL_TEAMS}}", $all_teams, $content);

// $content = str_replace("{{OWNER_LINKS}}", $owner_drop_down, $content);

// /***********************************************************/
// /* main table
// /***********************************************************/

// $team_rows = "";

// for ($i = 1; $i < sizeof($team_html_rows) + 1; $i++) {
// 	$team_rows .= $team_html_rows[$i];
// }

// $content = str_replace("{{OWNER_ROWS}}", $team_rows, $content);

// //---------------------------------------------

// // Right-hand stats column begins here
// // some stats are available only after 2002

// if ($season > 2003) {

// 	$list_size = 5;

// 	$cats = array("Points", "Value", "Picked");

// 	foreach ($cats as $cat) {
// 		$query = "SELECT Player_ID FROM Players" . $season;
// 		$query .= " WHERE Picked > 0 ORDER BY " . $cat;
// 		$query .= " DESC, Lname ASC LIMIT " . $list_size;

// 		$result = mysqli_query($dbconn, $query);

// 		$i = 1;

// 		$these_players = "";

// 		while ($row = mysqli_fetch_assoc($result)) {
// 			$this_player = new Player($GLOBALS["players"][$row["Player_ID"]], $season);

// 			$these_players .= $this_player->get_standings_row($i, $cat);
// 			$i++;
// 		}

// 		$content = str_replace("{{PLAYERS_" . $cat . "}}", $these_players, $content);
// 	}
// }

// /***************************************************************/

// // $page = file_get_contents(INCLUDES_PATH . "/template.html");

// show_page($content, $season);

// // $page = str_replace("%TITLE%", "Diffendorf baseball " . $season, $page);

// // $page = str_replace("%CONTENT%", $content, $page);

// // echo $page;
