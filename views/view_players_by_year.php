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

/***********************************/

include INCLUDES_PATH . '/get_dbconn.php';
include INCLUDES_PATH . '/show_page.php';

/***********************************/

$dbconn = get_dbconn();

/***********************************/

if (!($_GET["season"])) {
	echo "you must indicate a season.";
}
else {
	$season = intval($_GET["season"]);
}

/***********************************/
// which player table should we use?

$query = "SELECT status FROM Seasons WHERE Season_ID=" . $season;

$result = mysqli_query($dbconn, $query);

if (mysqli_error($dbconn)) {
	echo mysqli_error($dbconn);
	exit;
}

$row = mysqli_fetch_assoc($result);

if ($row["status"] == "current") {
	$player_table = "players_current";
}
else {
	$player_table = "players_main";
}

/***********************************/
// do we want to show all players, or just players who
// have been picked?
// default is to show only picked players

if (array_key_exists("show", $_GET)) {
	if ($_GET["show"] === "all") { $show = "all"; }
	else { $show = "picked"; }
}
else { $show = "picked"; }

/***********************************/
// how should players be sorted?

$order_by = "points";

if (array_key_exists("order_by", $_GET)) {
	$fields = ["points", "value", "picked"];

	if (in_array($_GET["order_by"], $fields)) {
		$order_by = $_GET["order_by"];
	}
}

/***********************************/
// build query

$query = "SELECT * FROM " . $player_table . " AS pt, Players AS p";
$query .= " WHERE pt.player_id = p.player_id";
$query .= " AND season=" . $season;

if ($show == "picked") {
	$query .= " AND picked > 0";
}

$query .= " ORDER BY " . $order_by . " DESC";
$query .= ", p.LNF ASC";

$result = mysqli_query($dbconn, $query);

if (mysqli_error($dbconn)) {
	echo mysqli_error($dbconn);
	exit;
}

/***********************************/

$players_html = "";

$player_row_template = file_get_contents(HTML_PATH . "/player_row.html");

$fields = ["pos", "team", "salary", "points", "value", "drafted", "acquired", "picked", "LNF"];

while ($row = mysqli_fetch_assoc($result)) {

	$player = $player_row_template;

	foreach ($fields as $field) {
		$player = str_replace("{{" . $field . "}}", $row[$field], $player);
	}

	$players_html .= $player . "\n";
}

/***********************************/

$this_url = VIEWS . "/view_players_by_year.php?season=" . $season;

$this_url .= "&show=";

if ($show === "all") {
	$self_href = $this_url . "picked";
	$self_desc = "Show only picked players";
}
else {
	$self_href = $this_url . "all";
	$self_desc = "Show all players";
}

/***********************************/

$content = file_get_contents(HTML_PATH . "/view_players_by_year.html");

$content = str_replace("{{SEASON}}", $season, $content);

$content = str_replace("{{BODY}}", $players_html, $content);

$content = str_replace("{{SELF_DESC}}", $self_desc, $content);

$content = str_replace("{{SELF_HREF}}", $self_href, $content);

/***********************************/

show_page($content, "Players " . $season);

// $page = file_get_contents(HTML_PATH . "/page.html");

// $page = str_replace("{{TITLE}}", "Diffendorf Baseball: Players $season", $page);

// $page = str_replace("{{CONTENT}}", $content, $page);

// $page = str_replace("{{web_home}}", WEB_HOME, $page);

// echo $page;

// exit;
