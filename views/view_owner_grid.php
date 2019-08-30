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

$query = "SELECT * FROM Owners, Members ";
$query .= " WHERE Owner_ID = Member_ID";
$query .= " AND Appearances > 0";
$query .= " AND Member_ID != 63";
$query .= " AND FamilyStatus = 1";
$query .= " ORDER BY Lname, Fname, Suffix";

$result = mysqli_query($dbconn, $query);

/************************************/

$owners = "";

$row_template = file_get_contents(HTML_PATH . "/owner_grid_row.html");

while ($row = mysqli_fetch_array($result)) {

	if ($row["AvgFinish"] > 24) {
		$avg_finish = "-";
	}
	else {
		$avg_finish = $row["AvgFinish"];
	}

	if ($row["OverallRating"] < 400) {
		$overall_rating = "-";
	}
	else {
		$overall_rating = $row["OverallRating"];
	}

	$this_row = $row_template;

	$this_row = str_replace("{{OWNER}}", $row["LNF"], $this_row);

	$this_row = str_replace("{{APPEARANCES}}", $row["Appearances"], $this_row);

	$this_row = str_replace("{{CHAMPIONSHIPS}}", $row["Championships"], $this_row);

	$this_row = str_replace("{{TOP_SIX}}", $row["TopSix"], $this_row);

	$this_row = str_replace("{{AVG_FINISH}}", $avg_finish, $this_row);

	$this_row = str_replace("{{RATING}}", $overall_rating, $this_row);

	$owners .= "\n" . $this_row;
}

$content = file_get_contents(HTML_PATH . "/owner_grid.html");
$content = str_replace("{{OWNERS}}", $owners, $content);

show_page($content, "owner grid");
