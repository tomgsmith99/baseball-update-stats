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

include INCLUDES_PATH . '/get_dbconn.php';
include INCLUDES_PATH . '/roster_info.php';

$dbconn = get_dbconn();

/*********************************************************************/

for ($season=2002; $season >= 1993; $season--) {

	$query = "SELECT * FROM OwnersMain WHERE Year=$season";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	while ($row = mysqli_fetch_assoc($result)) {

		echo(json_encode($row));

		$owner_id = $row["Owner_ID"];

		$team_name = mysqli_real_escape_string($dbconn, $row["TeamName"]);

		$query = "REPLACE INTO owners_all_time SET";
		$query .= " id='" . $season . "_" . $owner_id . "'";
		$query .= ", owner_id=" . $owner_id;
		$query .= ", team_name='" . $team_name . "'";
		$query .= ", points=" . $row["Points"];
		$query .= ", salary=" . $row["Salary"];

		if (empty($row["Bank"])) {
			$query .= ", bank=NULL";
		}
		else {
			$query .= ", bank=" . $row["Bank"];
		}
		$query .= ", season=" . $season;

		echo "\n$query";

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
}
