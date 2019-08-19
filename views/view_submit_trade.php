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

set_include_path(INCLUDES_PATH);

define("HTML_PATH", BASE_PATH . "/html");

define("VIEWS", WEB_HOME . "/views");

include INCLUDES_PATH . '/get_dbconn.php';
include INCLUDES_PATH . '/get_all_owners_for_year.php';
include INCLUDES_PATH . '/get_all_players_for_year.php';
include INCLUDES_PATH . '/get_owner_drop_down_list.php';

$GLOBALS["this_year"] = date("Y");

$dbconn = get_dbconn();

$season = date("Y");

// $GLOBALS["season_under_way"] = true;

/************************************************************/

// include $_SERVER['DOCUMENT_ROOT'] . "/baseball/includes/env.php";

// include INCLUDES_PATH . '/get_all_owners_for_year.php';
// include INCLUDES_PATH . '/get_owner_drop_down_list.php';

// $dbconn = $GLOBALS["dbconn"];

// $season = $GLOBALS["this_year"];

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

function get_add_player_id() {

	if (array_key_exists("add_player_id", $_POST) && $_POST["add_player_id"]) {
		$_SESSION["add_player_id"] = filter_var($_POST["add_player_id"], FILTER_SANITIZE_STRING);
	}
	if (!(array_key_exists("add_player_id", $_SESSION) && $_SESSION["add_player_id"])) {

		$content = file_get_contents(HTML_PATH . "/get_add_player_id.html");

		$players = get_player_list();

		$content = str_replace("{{PLAYERS}}", $players, $content);

		$fields = ["bank", "budget", "drop_player_fnf", "drop_player_salary", "owner_fnf"];

		foreach ($fields as $field) {
			$bullseye = "{{" . $field . "}}";
			$content = str_replace($bullseye, $_SESSION[$field], $content);
		}

		show_page($content);
	}
}

function get_drop_player_id() {

	if (array_key_exists("drop_player_id", $_POST) && $_POST["drop_player_id"]) {
		$_SESSION["drop_player_id"] = filter_var($_POST["drop_player_id"], FILTER_SANITIZE_STRING);
	}
	if (!(array_key_exists("drop_player_id", $_SESSION) && $_SESSION["drop_player_id"])) {
		$content = file_get_contents(HTML_PATH . "/get_drop_player_id.html");

		$team = get_team_form($_SESSION["owner_id"], $GLOBALS["this_year"]);

		$content = str_replace("{{TEAM}}", $team, $content);

		show_page($content);
	}
}

function get_owner_id() {

	if (array_key_exists("owner_id", $_POST) && $_POST["owner_id"]) {
		$_SESSION["owner_id"] = filter_var($_POST["owner_id"], FILTER_SANITIZE_STRING);
	}
	if (!(array_key_exists("owner_id", $_SESSION) && $_SESSION["owner_id"])) {
		$content = file_get_contents(HTML_PATH . "/get_owner_id.html");

		$owner_drop_down_list = get_owner_drop_down_form($GLOBALS["this_year"]);

		$content = str_replace("{{OWNER_LIST}}", $owner_drop_down_list, $content);

		show_page($content);
	}
}

function get_password() {

	if (array_key_exists("password", $_POST) && $_POST["password"]) {
		$password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
		$password = strtolower($password);

		if ($password == "scbmj" || $password == "scwmj") {
			$_SESSION["authn"] = "authenticated";
		}
	}

	if (!(array_key_exists("authn", $_SESSION) && $_SESSION["authn"] === "authenticated")) {

		$content = file_get_contents(HTML_PATH . "/authenticate.html");

		show_page($content);
	}
}

function show_page($content) {

	$page = file_get_contents(HTML_PATH . "/page.html");

	$page = str_replace("{{TITLE}}", "Diffendorf baseball: Make a trade", $page);

	$page = str_replace("{{CONTENT}}", $content, $page);

	echo $page;

	exit;
}

function confirm_transaction() {

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

		show_page($content);
	}
}

/**************************************************************/
// Confirm trade

if (array_key_exists("confirmed", $_POST) && $_POST["confirmed"]) {
	$_SESSION["confirmed"] = $_POST["confirmed"];
}
if (array_key_exists("confirmed", $_SESSION) && $_SESSION["confirmed"] == 1) {

	$day = $GLOBALS["today"];

	$bench_id_col = $_SESSION["bench_col"] . "_ID";

	$bench_points_col = $_SESSION["bench_col"] . "_Points";

	$drop_date_col = $_SESSION["bench_col"] . "_Drop";

	$drop_points = $_SESSION["drop_player_points"];

	/**********************************************/
	// get position of dropped player

	foreach ($GLOBALS["roster_active"] as $pos => $data) {
		$id_col = $data["col_name"];

		$query = "SELECT * FROM OwnersMain";
		$query .= " WHERE " . $id_col . "=" . $_SESSION["drop_player_id"];
		$query .= " AND Owner_ID=" . $_SESSION["owner_id"];
		$query .= " AND Year=" . $GLOBALS["this_year"];

		echo "<p>$query";

		$result = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		if (mysqli_num_rows($result) === 1) {
			$start_date_col = $data["col_base"] . "_Start";
			$add_id_col = $id_col;
			$add_points_col = $data["col_base"] . "_Points";

			break;
		}
	}

	$query = "UPDATE OwnersMain SET " . $bench_id_col . "=" . $_SESSION["drop_player_id"];
	$query .= ", " . $bench_points_col . "=" . $_SESSION["drop_player_points"];
	$query .= ", " . $drop_date_col . "=" . $GLOBALS["today"];
	$query .= ", " . $start_date_col . "=" . $GLOBALS["today"];
	$query .= ", " . $add_id_col . "=" . $_SESSION["add_player_id"];
	$query .= ", " . $add_points_col . "=0";
	$query .= " WHERE Owner_ID=" . $_SESSION["owner_id"];
	$query .= " AND Year=" . $GLOBALS["season"];

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$page = str_replace("%CONTENT%", "<p>Trade successfully submitted.</p>", $page);

	echo $page;

	exit;

}

exit;

function get_owner_drop_down_form($season) {
	global $dbconn;

	$query = "SELECT OwnersMain.Owner_ID, Members.LNF FROM OwnersMain, Members ";
	$query .= "WHERE OwnersMain.Year=" . $season . " ";
	$query .= "AND OwnersMain.Owner_ID=Members.Member_ID ";
	$query .= "ORDER BY Members.Lname, Members.Fname ASC";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$owners = "";

	while ($row = mysqli_fetch_assoc($result)) {
		$owners .= "<option value='" . $row["Owner_ID"] . "'>";
		$owners .= $row["LNF"] . "</option>\n";
	}

	return $owners;
}

function get_new_player() {
	global $dbconn;

	$player_id = $_SESSION["add_player_id"];

	// get the dropped player's position, salary, name
	$query = "SELECT * FROM Players, ";
	$query .= $GLOBALS["typt"] . " AS typt";
	$query .= " WHERE typt.Player_ID=" . $player_id;
	$query .= " AND typt.Player_ID=Players.Player_ID";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$row = mysqli_fetch_assoc($result);

	$_SESSION["add_player_fnf"] = $row["FNF"];

	$_SESSION["add_player_salary"] = $row["Salary"];

	$_SESSION["add_player_points"] = $row["Points"];

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
	$query = "SELECT typt.Pos, typt.Salary, typt.Points, Players.FNF";
	$query .= " FROM " . $GLOBALS["typt"] . " AS typt,";
	$query .= " Players";
	$query .= " WHERE typt.Player_ID=" . $drop_player_id;
	$query .= " AND typt.Player_ID=Players.Player_ID";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$row = mysqli_fetch_assoc($result);

	$_SESSION["drop_player_salary"] = $drop_player_salary = $row["Salary"];
	$_SESSION["drop_player_fnf"] = $fnf = $row["FNF"];
	$_SESSION["budget"] = $budget = ($_SESSION["drop_player_salary"] + $_SESSION["bank"]);
	$_SESSION["drop_player_pos"] = $pos = $row["Pos"];
	$_SESSION["drop_player_points"] = $row["Points"];

	// Select all players that are the right position, budget, and
	// not already on the owner's team
	$query = "SELECT Players.Player_ID, Players.FNF, typt.Salary";
	$query .= " FROM Players, " . $GLOBALS["typt"] . " AS typt";
	$query .= " WHERE Players.Player_ID=typt.Player_ID";
	$query .= " AND typt.Salary <= " . $budget;
	$query .= " AND typt.Player_ID != " . $drop_player_id;
	$query .= " AND typt.Pos = '" . $pos . "'";

	$query .= " AND typt.Player_ID NOT IN";
	$query .= " (SELECT OF1_ID";
	$query .= " FROM OwnersMain WHERE Owner_ID=" . $owner_id;
	$query .= " AND Year=" . $GLOBALS["this_year"] . ")";

	$query .= " AND typt.Player_ID NOT IN";
	$query .= " (SELECT OF2_ID";
	$query .= " FROM OwnersMain WHERE Owner_ID=" . $owner_id;
	$query .= " AND Year=" . $GLOBALS["this_year"] . ")";

	$query .= " AND typt.Player_ID NOT IN";
	$query .= " (SELECT OF3_ID";
	$query .= " FROM OwnersMain WHERE Owner_ID=" . $owner_id;
	$query .= " AND Year=" . $GLOBALS["this_year"] . ")";

	$query .= " AND typt.Player_ID NOT IN";
	$query .= " (SELECT SP1_ID";
	$query .= " FROM OwnersMain WHERE Owner_ID=" . $owner_id;
	$query .= " AND Year=" . $GLOBALS["this_year"] . ")";

	$query .= " AND typt.Player_ID NOT IN";
	$query .= " (SELECT SP2_ID";
	$query .= " FROM OwnersMain WHERE Owner_ID=" . $owner_id;
	$query .= " AND Year=" . $GLOBALS["this_year"] . ")";

	$query .= " AND typt.Player_ID NOT IN";
	$query .= " (SELECT SP3_ID";
	$query .= " FROM OwnersMain WHERE Owner_ID=" . $owner_id;
	$query .= " AND Year=" . $GLOBALS["this_year"] . ")";

	$query .= " ORDER BY typt.Salary DESC, Players.Lname ASC";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$players = "";

	while ($row = mysqli_fetch_assoc($result)) {
		$players .= "<tr>";
		$players .= "<td>" . $row["FNF"] . "</td>";
		$players .= "<td align='right'>$" . $row["Salary"] . "</td>";
		$players .= "<td><input type='radio' name='add_player_id' value='" . $row["Player_ID"] . "'>";
		$players .= "</td>";
		$players .= "</tr>\n";
	}

	return $players;
}

function get_team_form($owner_id, $season) {
	global $dbconn;

	$query = "SELECT * FROM OwnersMain, Members WHERE Owner_ID=" . $owner_id;
	$query .= " AND Year=" . $season;
	$query .= " AND OwnersMain.Owner_ID = Members.Member_ID";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$row = mysqli_fetch_assoc($result);

	$_SESSION["bank"] = $row["Bank"];

	$_SESSION["owner_fnf"] = $row["FNF"];

	if ($row["Bench01_ID"] > 0 && $row["Bench02_ID"] > 0) {
		return "<p>this owner has no trades left.</p>";
	}
	
	if ($row["Bench01_ID"] > 0) {
		$_SESSION["bench_col"] = "Bench02";
	}
	else {
		$_SESSION["bench_col"] = "Bench01";
	}

	$team = "<p>" . $row["FNF"] . "</p>\n";

	$team .= "<table class='table table-sm table-bordered'>\n";

	foreach ($GLOBALS["roster_active"] as $pos => $data) {

		$player_id = $row[$data["col_name"]];

		$query = "SELECT * FROM " . $GLOBALS["typt"] . " AS typt";
		$query .= ", Players";
		$query .= " WHERE Players.Player_ID=" . $player_id;
		$query .= " AND Players.Player_ID=typt.Player_ID";

		$result = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		$player = mysqli_fetch_assoc($result);

		$team .= "<tr>";
		$team .= "<td>" . $pos . "</td>";
		$team .= "<td>" . $player["FNF"] . "</td>";
		$team .= "<td>" . $player["Team"] . "</td>";
		$team .= "<td align='right'>$" . $player["Salary"] . "</td>";

		$team .= "<td align='center'><input type='radio' name='drop_player_id' value='" . $player["Player_ID"] . "'>";
		$team .= "</td>";
		$team .= "</tr>\n";
	}

	$team .= "</table>";

	return $team;
}
