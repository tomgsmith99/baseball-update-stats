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

$season = 2019;

/*********************************************************************/

$query = "SELECT * FROM owners_all_time AS o, Members AS M WHERE season=" . $season;
$query .= " AND o.owner_id=M.Member_ID";
$query .= " ORDER BY Points DESC, Lname, Fname";

echo $query;

$owners = mysqli_query($dbconn, $query);

$i = 1;

while ($row = mysqli_fetch_assoc($owners)) {

	$owner_id = $row["owner_id"];

	echo "\nlooking at player_id " . $player_id;

	$query = "UPDATE OwnersMain SET Points = " . $row["points"];
	$query .= ", Place=" . $i;
	$query .= " WHERE Owner_ID=" . $row["owner_id"];
	$query .= " AND Year=" . $season;

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$i++;
}

echo "\n\n";
