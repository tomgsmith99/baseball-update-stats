<?php

/***********************************************************/
/* get all of this year's owners
/***********************************************************/

function get_all_owners_for_year($season, $order_by) {
	global $dbconn;

	if ($season === $GLOBALS["this_year"]) {
		$table = "owners_current";
	}
	else {
		$table = "owners_all_time";
	}

	$owners = [];

	$query = "SELECT * FROM " . $table . " AS o";
	$query .= ", owners AS O";
	$query .= " WHERE season = " . $season;
	$query .= " AND o.owner_id = O.owner_id";
	$query .= " ORDER BY " . $order_by;

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	while ($row = mysqli_fetch_assoc($result)) {
		$owners[$row["owner_id"]] = $row;
	}

	return $owners;
}
