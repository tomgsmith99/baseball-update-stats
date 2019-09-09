<?php

function get_player_stats_table($season, $category, $picked_only=true, $limit = 5) {
	global $dbconn;
	$this_year = $GLOBALS["this_year"];

	$player_row = file_get_contents(HTML_PATH . "/player_row_tiny.html");

	if ($season === $this_year) {
		$table = "players_current";
	}
	else {
		$table = "players_all_time";
	}

	$query = "SELECT P.Player_ID, P.FNF, p." . $category;
	$query .= " FROM Players AS P, " . $table . " AS p";
	$query .= " WHERE P.Player_ID = p.player_id";
	$query .= " AND p.season=" . $season;

	if ($picked_only) {
		$query .= " AND p.picked > 0";
	}
	else {
		$query .= " AND p.picked = 0";
	}

	$query .= " ORDER BY " . $category;
	$query .= " DESC, P.Lname ASC LIMIT " . $limit;

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$i = 1;

	$these_players_html = "";

	while ($row = mysqli_fetch_assoc($result)) {

		$this_row = $player_row;
		$this_row = str_replace("{{rank}}", $i, $this_row);
		$this_row = str_replace("{{FNF}}", $row["FNF"], $this_row);
		$this_row = str_replace("{{CAT_VAL}}", $row[$category], $this_row);

		$these_players_html .= $this_row . "\n";

		$i++;
	}

	return $these_players_html;
}
