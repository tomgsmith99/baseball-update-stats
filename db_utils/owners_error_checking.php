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

$season = 2004;

/*********************************************************************/

// error-checking

$query = "SELECT DISTINCT owner_id FROM owner_rosters_all_time WHERE season=$season";

echo "\n$query";

$distinct_owners = mysqli_query($dbconn, $query);

if (mysqli_error($dbconn)) {
	echo mysqli_error($dbconn);
	exit;
}

while ($row = mysqli_fetch_assoc($distinct_owners)) {

	echo "\nchecking owner_id " . $row["owner_id"] . "...";

	$query = "SELECT SUM(drafted) AS number_drafted, SUM(acquired) AS number_acquired";
	$query .= ", SUM(benched) AS number_benched FROM owner_rosters_all_time WHERE";
	$query .= " season=$season AND owner_id=" . $row["owner_id"];

	$result = mysqli_query($dbconn, $query);

	$r = mysqli_fetch_assoc($result);

	if ($r["number_drafted"] != 12) {
		echo "\nWarning: the number of players drafted is: " . $r["number_drafted"];
		exit;
	}
	if ($r["number_acquired"] != $r["number_benched"]) {
		echo "\nWarning: the number of players acquired is " . $r["number_acquired"];
		echo "\nthe number of players benched is: " . $r["number_benched"];
		exit;
	}
}

echo "\nNo errors!\n";
