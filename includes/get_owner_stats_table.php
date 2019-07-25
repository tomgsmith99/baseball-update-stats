<?php

function get_owner_stats_table($season, $category, $limit = 5) {

	global $dbconn;

	$this_year = $GLOBALS["this_year"];

	if ($season === $this_year) {
		$table = "owners_current";
	}
	else {
		$table = "owners_all_time";
	}

	$query = "SELECT O.owner_id, O.FNF, o." . $category;
	$query .= " FROM owners AS O, " . $table . " AS o";
	$query .= " WHERE season=" . $season;
	$query .= " AND O.owner_id = o.owner_id";
	$query .= " ORDER BY " . $category . " DESC";
	$query .= " , Lname, Fname ASC";
	$query .= " LIMIT " . $limit;

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$owner_row = file_get_contents(HTML_PATH . "/player_row_tiny.html");

	$i = 1;

	$these_owners_html = "";

	while ($row = mysqli_fetch_assoc($result)) {

		$this_row = $owner_row;
		$this_row = str_replace("{{rank}}", $i, $this_row);
		$this_row = str_replace("{{FNF}}", $row["FNF"], $this_row);
		$this_row = str_replace("{{CAT_VAL}}", $row[$category], $this_row);

		$these_owners_html .= $this_row . "\n";

		$i++;
	}

	return $these_owners_html;
}
