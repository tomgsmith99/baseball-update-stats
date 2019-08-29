<?php

session_start();

$now = time();
if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) {
	session_unset();
	session_destroy();
	session_start();
}

$_SESSION['discard_after'] = $now + 300;

/**************************************************************/

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
include INCLUDES_PATH . '/get_all_owners_for_year.php';
include INCLUDES_PATH . '/get_all_players_for_year.php';
include INCLUDES_PATH . '/get_owner_drop_down_list.php';
include INCLUDES_PATH . '/show_page.php';

$dbconn = get_dbconn();

$season = date("Y");

/**************************************************************/

// get password
get_password();

// get owner_id
get_owner_id();

// get player to be dropped
get_drop_player_id();

// get player to be added
get_add_player_id();

// confirm the transaction
confirm_transaction();

/**************************************************************/

$title = "make a trade";

function get_add_player_id() {

	global $title;

	if (array_key_exists("add_player_id", $_POST) && $_POST["add_player_id"]) {
		$_SESSION["add_player_id"] = filter_var($_POST["add_player_id"], FILTER_SANITIZE_STRING);
	}
	if (!(array_key_exists("add_player_id", $_SESSION) && $_SESSION["add_player_id"])) {

		$content = file_get_contents(HTML_PATH . "/get_add_player_id.html");

		$players = get_player_list();

		$content = str_replace("{{players}}", $players, $content);

		$fields = ["bank", "budget", "drop_player_fnf", "drop_player_salary", "owner_fnf"];

		foreach ($fields as $field) {
			$bullseye = "{{" . $field . "}}";
			$content = str_replace($bullseye, $_SESSION[$field], $content);
		}

		show_page($content, $title);
	}
}

function get_drop_player_id() {

	if (array_key_exists("drop_player_id", $_POST) && $_POST["drop_player_id"]) {
		$_SESSION["drop_player_id"] = filter_var($_POST["drop_player_id"], FILTER_SANITIZE_STRING);
	}
	if (!(array_key_exists("drop_player_id", $_SESSION) && $_SESSION["drop_player_id"])) {
		$content = get_team_form($_SESSION["owner_id"]);

		show_page($content, $title);
	}
}

function get_owner_id() {

	global $season;
	global $title;

	if (array_key_exists("owner_id", $_POST) && $_POST["owner_id"]) {
		$_SESSION["owner_id"] = filter_var($_POST["owner_id"], FILTER_SANITIZE_STRING);
	}
	if (!(array_key_exists("owner_id", $_SESSION) && $_SESSION["owner_id"])) {
		$content = file_get_contents(HTML_PATH . "/get_owner_id.html");

		$owner_drop_down_list = get_owner_drop_down_list($season, 'post');

		$content = str_replace("{{OWNER_LIST}}", $owner_drop_down_list, $content);

		show_page($content, $title);
	}
}

function get_password() {

	global $title;

	if (array_key_exists("password", $_POST) && $_POST["password"]) {
		$password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
		$password = strtolower($password);

		if ($password == "scbmj" || $password == "scwmj") {
			$_SESSION["authn"] = "authenticated";
		}
	}

	if (!(array_key_exists("authn", $_SESSION) && $_SESSION["authn"] === "authenticated")) {

		$content = file_get_contents(HTML_PATH . "/authenticate.html");

		show_page($content, $title);
	}
}

function confirm_transaction() {

	global $title;

	if (array_key_exists("confirmed", $_POST) && $_POST["confirmed"]) {
		$_SESSION["confirmed"] = $_POST["confirmed"];
	}
	if (!(array_key_exists("confirmed", $_SESSION) && $_SESSION["confirmed"] == 1)) {

		$content = file_get_contents(HTML_PATH . "/confirm_trade.html");

		get_new_player();

		$fields = ["add_player_fnf", "add_player_salary", "bank", "budget", "diff", "drop_player_fnf", "drop_player_salary", "new_bank", "owner_fnf"];

		foreach ($fields as $field) {
			$bullseye = "{{" . $field . "}}";
			$content = str_replace($bullseye, $_SESSION[$field], $content);
		}

		show_page($content, $title);
	}
}

/**************************************************************/
// Confirm trade

if (array_key_exists("confirmed", $_POST) && $_POST["confirmed"]) {
	$_SESSION["confirmed"] = $_POST["confirmed"];
}
if (array_key_exists("confirmed", $_SESSION) && $_SESSION["confirmed"] == 1) {

	// insert new player into owner_roster_current table
	$day = date("z");

	$year = date("Y");

	$id = $year . "-" . $_SESSION['owner_id'] . "-" . $_SESSION['add_player_id'];

	// get current points for added player
	$query = "SELECT points from players_current WHERE player_id=" . $_SESSION["add_player_id"];

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$added_player = mysqli_fetch_assoc($result);

	$prev_points = $added_player["points"];

	/*******************************************************/

	$query = "INSERT INTO owner_roster_current SET";
	$query .= " id='" . $id . "', owner_id=" . $_SESSION["owner_id"];
	$query .= ", player_id=" . $_SESSION["add_player_id"];
	$query .= ", start_date=" . $day;
	$query .= ", bench_date=0, prev_points=" . $prev_points;
	$query .= ", points=0, season=" . $year;
	$query .= ", acquired=1, drafted=0, benched=0";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	/*******************************************************/

	$query = "UPDATE owner_roster_current SET";
	$query .= " bench_date=" . $day . ", benched=1";
	$query .= " WHERE owner_id=" . $_SESSION["owner_id"];
	$query .= " AND season=" . $year;
	$query .= " AND player_id=" . $_SESSION["drop_player_id"];

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$content = file_get_contents(HTML_PATH . "/trade_success.html");

	$fields = ["add_player_fnf", "add_player_salary", "bank", "budget", "diff", "drop_player_fnf", "drop_player_salary", "new_bank", "owner_fnf"];

	foreach ($fields as $field) {
		$bullseye = "{{" . $field . "}}";
		$content = str_replace($bullseye, $_SESSION[$field], $content);
	}

	show_page($content, $title);
}

function get_new_player() {
	global $dbconn;

	$player_id = $_SESSION["add_player_id"];

	$query = "SELECT p.FNF, pc.points, pc.salary";
	$query .= " FROM players_current AS pc, Players AS p";
	$query .= " WHERE pc.player_id=" . $player_id;
	$query .= " AND pc.player_id=p.player_id";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$row = mysqli_fetch_assoc($result);

	$_SESSION["add_player_fnf"] = $row["FNF"];

	$_SESSION["add_player_salary"] = $row["salary"];

	$_SESSION["add_player_points"] = $row["points"];

	$_SESSION["diff"] = $_SESSION["add_player_salary"] - $_SESSION["drop_player_salary"];

	if ($_SESSION["add_player_salary"] > $_SESSION["drop_player_salary"]) {
		$_SESSION["new_bank"] = $_SESSION["bank"] - $_SESSION["diff"];
	}
	else {
		$_SESSION["new_bank"] = $_SESSION["bank"];
	}
}

function get_player_list() {
	global $dbconn;

	$owner_id = $_SESSION["owner_id"];
	$drop_player_id = $_SESSION["drop_player_id"];

	// get the dropped player's position, salary, name
	$query = "SELECT pc.pos, pc.salary, p.FNF";
	$query .= " FROM players_current as pc, Players as p";
	$query .= " WHERE pc.player_id=" . $drop_player_id;
	$query .= " AND pc.player_id=p.Player_ID";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$row = mysqli_fetch_assoc($result);

	$_SESSION["drop_player_salary"] = $drop_player_salary = $row["salary"];
	$_SESSION["drop_player_fnf"] = $fnf = $row["FNF"];
	$_SESSION["budget"] = $budget = ($_SESSION["drop_player_salary"] + $_SESSION["bank"]);
	$_SESSION["drop_player_pos"] = $pos = $row["pos"];

	// Select all players that are the right position, budget, and
	// not already on the owner's team

	$query = "SELECT pc.player_id, p.FNF, pc.salary, pc.points, pc.team";
	$query .= " FROM players_current AS pc, Players as p";
	$query .= " WHERE pc.salary < " . $budget;
	$query .= " AND pc.pos='" . $pos . "'";
	$query .= " AND pc.player_id = p.player_id";
	$query .= " AND pc.player_id NOT IN";
	$query .= " (SELECT player_id FROM owner_roster_current";
	$query .= " WHERE owner_id = " . $owner_id . ")";
	$query .= " ORDER BY pc.salary DESC, p.Lname ASC";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$players = "";

	$player_row_template = file_get_contents(HTML_PATH . '/player_row_short_with_add.html');

	$action = VIEWS . '/view_submit_trade.php';

	while ($row = mysqli_fetch_assoc($result)) {
		$player_row = $player_row_template;
		$player_row = str_replace('{{FNF}}', $row['FNF'], $player_row);
		$player_row = str_replace('{{team}}', $row['team'], $player_row);
		$player_row = str_replace('{{salary}}', $row['salary'], $player_row);
		$player_row = str_replace('{{player_id}}', $row['player_id'], $player_row);
		$player_row = str_replace('{{points}}', $row['points'], $player_row);
		$player_row = str_replace('{{action}}', $action, $player_row);

		$players .= $player_row;
	}

	return $players;
}

function get_team_form($owner_id) {
	global $dbconn;

	$query = "SELECT * FROM owners_current AS oc, owners AS o";
	$query .= " WHERE o.owner_id=" . $owner_id;
	$query .= " AND o.owner_id=oc.owner_id";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$owner = mysqli_fetch_assoc($result);

	$_SESSION["bank"] = $owner["bank"];

	$_SESSION["owner_fnf"] = $owner["FNF"];

	/**************************************************************/

	$fields = ["action", "font_style", "pos", "FNF", "team", "salary", "points", "value", "player_id"];

	$player_row_template = file_get_contents(HTML_PATH . '/player_row_short_with_drop.html');

	// get active players

	$query = "SELECT o.acquired, o.player_id, o.points, pc.pos, pc.salary, pc.team, pc.value, p.FNF";
	$query .= " FROM owner_roster_current AS o, players_current AS pc, roster_order AS ro";
	$query .= ", Players AS p";
	$query .= " WHERE owner_id=" . $owner_id;
	$query .= " AND o.benched=0";
	$query .= " AND o.player_id=pc.player_id";
	$query .= " AND o.player_id=p.Player_ID";
	$query .= " AND pc.pos=ro.pos";
	$query .= " ORDER BY ro.i ASC, pc.salary DESC";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$active_players_html = "";

	while ($row = mysqli_fetch_assoc($result)) {

		if ($row["acquired"] == 1) { $row["font_style"] = "italic"; }
		else { $row["font_style"] = "normal"; }

		$this_player_html = $player_row_template;

		$row["action"] = VIEWS . "/view_submit_trade.php";

		foreach ($fields as $field) {
			$this_player_html = str_replace('{{' . $field . '}}', $row[$field], $this_player_html);
		}

		$active_players_html .= $this_player_html;
	}

	/**************************************************************/
	// get benched players

	$benched_players_html = "";

	$query = "SELECT o.player_id, o.points, pc.pos, pc.salary, pc.team, pc.value, p.FNF";
	$query .= " FROM owner_roster_current AS o, players_current AS pc";
	$query .= " , Players AS p";
	$query .= " WHERE owner_id=" . $owner_id;
	$query .= " AND o.benched=1";
	$query .= " AND o.player_id=pc.player_id";
	$query .= " AND o.player_id=p.Player_ID";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	if (mysqli_num_rows($result) != 0) {
		while ($row = mysqli_fetch_assoc($result)) {

			$row["action"] = VIEWS . "/view_submit_trade.php";

			$this_player_html = $player_row_template;

			foreach ($fields as $field) {
				$this_player_html = str_replace('{{' . $field . '}}', $row[$field], $this_player_html);
			}

			$benched_players_html .= $this_player_html;
		}
	}

	/**************************************************************/

	$content = file_get_contents(HTML_PATH . "/team_drop.html");

	$content = str_replace("{{active_players}}", $active_players_html, $content);
	$content = str_replace("{{benched_players}}", $benched_players_html, $content);
	$content = str_replace("{{FNF}}", $owner["FNF"], $content);
	$content = str_replace("{{team_name}}", $owner["team_name"], $content);
	$content = str_replace("{{bank}}", $owner["bank"], $content);
	$content = str_replace("{{salary}}", $owner["salary"], $content);
	$content = str_replace("{{points}}", $owner["points"], $content);

	return $content;
}
