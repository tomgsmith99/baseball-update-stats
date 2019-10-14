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
include INCLUDES_PATH . "/get_page.php";

include INCLUDES_PATH . '/ordinalSuffix.php';

/**************************************************************/

$dbconn = get_dbconn();

/************************************************************/

if (!(array_key_exists("owner_id", $_GET))) {
	echo "this page needs an owner_id to function.";
	exit();
}
else {

	$owner_id = intval($_GET["owner_id"]);

	$query = "SELECT DISTINCT Owner_ID FROM OwnersMain";

	$result = mysqli_query($dbconn, $query);

	$owner_ids = [];

	while ($row = mysqli_fetch_assoc($result)) {
		$owner_ids[] = $row["Owner_ID"];
	}

	if (!(in_array($owner_id, $owner_ids))) {
		echo "that owner id is not valid.";
		exit;
	}
}

/********************************************************/

$content = file_get_contents(HTML_PATH . "/owner.html");

/************************************************************/
/* main query
/************************************************************/

$query = "SELECT * FROM Owners as t1, Members as t2";
$query .= " WHERE Owner_ID = $owner_id AND Member_ID = Owner_ID";

$result = mysqli_query($dbconn, $query);

$row = mysqli_fetch_assoc($result);

$owner_name = $row["Fname"] . " " . $row["Lname"];

if ($row["Suffix"]) {
	$owner_name .= " " . $row["Suffix"];
}

$content = str_replace("{{OWNER_NAME}}", $owner_name, $content);

$content = str_replace("{{MOST_RECENT_APP}}", $row["MostRecentApp"], $content);

$content = str_replace("{{ROOKIE_YEAR}}", $row["RookieYear"], $content);

$content = str_replace("{{APPEARANCES}}", $row["Appearances"], $content);
$content = str_replace("{{APP_DESC}}", $row["AppDesc"], $content);

$content = str_replace("{{CHAMPIONSHIPS}}", $row["Championships"], $content);
$content = str_replace("{{CHAMP_DESC}}", $row["ChampDesc"], $content);

$content = str_replace("{{BEST_FINISH}}", ordinal_suffix($row["BestFinish"]), $content);
$content = str_replace("{{BEST_FINISH_DESC}}", $row["BestFinishDesc"], $content);

$content = str_replace("{{TOP_SIX}}", $row["TopSix"], $content);
$content = str_replace("{{TOP_SIX_DESC}}", $row["TopSixDesc"], $content);

$content = str_replace("{{AVG_FINISH}}", $row["AvgFinish"], $content);
$content = str_replace("{{AVG_FINISH_DESC}}", $row["AvgFinishDesc"], $content);

$rating_desc = $row["RatingDesc"];

if ($row["OverallRating"] == 0) {
	$rating_desc = "Not enough appearances to qualify.";
}

$content = str_replace("{{RATING}}", $row["OverallRating"], $content);
$content = str_replace("{{RATING_DESC}}", $rating_desc, $content);

$content = str_replace("{{IMG_URL}}", $row["HeadShot"], $content);

/**************************************************************/

$title = "choose a season";

$page = get_page($content, $title);

echo $page;

exit;

/************************************************************/

$player_db_cols = ["Pos", "FNF", "Team", "Salary", "Points", "Value"];

$query = "SELECT p.Player_ID, p.FNF, m.Pos, m.Salary, m.Team, m.Points, m.Value, m.Season ";
$query .= "FROM Players p, PlayersMain m, playerXowner x ";
$query .= "WHERE p.Player_ID = m.Player_ID ";
$query .= "AND x.owner_id = " . $owner_id . " ";
$query .= "AND x.main_id = m.id";

$players_result = mysqli_query($dbconn, $query);

while ($row = mysqli_fetch_assoc($players_result)) {

	$player_id = $row["Player_ID"];
	$season = $row["Season"];

	foreach ($player_db_cols as $col) {
		$players[$player_id][$season][$col] = $row[$col];
	}
}

/************************************************************/

$query = "SELECT * FROM OwnersMain, Members as t2 ";
$query .= "WHERE Owner_ID = " . $owner_id . " ";
$query .= "AND Member_ID = Owner_ID ORDER BY Year DESC";

$result = mysqli_query($dbconn, $query);

$all_teams = "";

$season_links = "";

$team_template = file_get_contents(HTML_PATH . "/team.html");

$player_row_template = file_get_contents(HTML_PATH . "/player_row_short.html");

$owner_db_cols = ["FNF", "Owner_ID", "TeamName", "Points", "Year", "Salary"];


while ($row = mysqli_fetch_assoc($result)) {

	$active_players = "";

	$place = ordinal_suffix($row["Place"]);

	$this_team = str_replace("{{place}}", $place, $team_template);

	foreach ($owner_db_cols as $db_col) {
		$this_team = str_replace("{{" . $db_col . "}}", $row[$db_col], $this_team);
	}

	foreach ($GLOBALS["roster"] as $pos) {

		$col_name = $pos["col_name"];

		$points_col = $pos["col_base"] . "_Points";

		$points = $row[$points_col];

		// $points = get_points($col_name, $row, $row["Year"]);

		if ($col_name === "Bench01_ID") {
			if ($row["Bench01_ID"] > 0) {
				$active_players .= "<tr style='text-align: center'><td colspan = '6'>Benched players</td></tr>\n";

				// $points = $row["Bench01_Points"];
			}
		}

		else if ($col_name === "Bench02_ID") {
			if ($row["Bench02_ID"] > 0) {
				// $points = $row["Bench02_Points"];
			}
		}

		else {
			// $points = $players[$col_name]["Points"];
		}

		if ($row[$col_name] > 0) {
			$player_row = $player_row_template;

			$player_id = $row[$col_name];

			$player_data = $players[$player_id][$row["Year"]];

			foreach ($player_db_cols as $db_col) {

				if ($db_col === "Points") {
					$player_row = str_replace("{{Points}}", $points, $player_row);
				}
				else {
					$player_row = str_replace("{{" . $db_col . "}}", $player_data[$db_col], $player_row);
				}
			}
			$active_players .= $player_row . "\n";
		}
	}

	$this_team = str_replace("{{active_players}}", $active_players, $this_team);

	$all_teams .= $this_team . "\n";

}

$content = str_replace("{{TEAMS}}", $all_teams, $content);

$content = str_replace("{{SEASON_LINKS}}", $season_links, $content);

/************************************************************/

$page = file_get_contents(INCLUDES_PATH . "/template.html");

$page = str_replace("%CONTENT%", $content, $page);

$page = str_replace("%TITLE%", "Diffendorf Family Baseball: " . $owner_name, $page);

echo $page;

function get_points($id_col, $start_col, $owner_row, $year) {

	global $players;

	if ($col === "Bench01_ID") {
		return $owner_row["Bench01_Points"];
	}

	if ($col === "Bench02_ID") {
		return $owner_row["Bench02_Points"];
	}

	else {
		$player_id = $owner_row[$col];

		return $players[$player_id][$year]["Points"];
	}
}
