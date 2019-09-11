<?php

$dir = "baseball_update_stats";

if (file_exists('/Applications/MAMP/htdocs')) {
	$base_path = '/Applications/MAMP/htdocs';
	$web_home = '/' . $dir;
}
else {
	$base_path = '/var/www/html';
	$web_home = '';
}

// web paths
define("WEB_HOME", $web_home);
define("VIEWS", WEB_HOME . "/views");

// filesystem paths
define("BASE_PATH", $base_path . '/' . $dir);
define("INCLUDES_PATH", BASE_PATH . "/includes");
define("HTML_PATH", BASE_PATH . "/html");

/**************************************************************/

include INCLUDES_PATH . "/show_page.php";
include INCLUDES_PATH . '/get_dbconn.php';

/**************************************************************/

$dbconn = get_dbconn();

/**************************************************************/

$query = "SELECT t1.Owner_ID, t1.Points, t1.Place, t1.Year, t2.FNF";
$query .= " FROM OwnersMain as t1, Members as t2";
$query .= " WHERE t1.Owner_ID = t2.Member_ID AND Place < 7";
$query .= " ORDER BY Year DESC, Place, t2.Lname, t2.Fname ASC";

$result = mysqli_query($dbconn, $query);

$season = 0;

$grid = "";

while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

	if ($row["Year"] < $season || $season == 0) {
		$season = $row["Year"];
		if ($grid == "") { $grid = "\n<tr>"; }
		else { $grid .= "</tr>\n<tr>"; }

		$grid .= "<td><a href = '{{views}}/view_final_standings.php?season=" . $row["Year"] . "'>";
		$grid .= $row["Year"] . "</td>";
	}

	$grid .= "<td><a href = '{{views}}/owner.php?owner_id=" . $row["Owner_ID"] . "'>";
	$grid .= $row["FNF"] . "</a></td>";
}

$grid .= "</tr>";

/**************************************************************/

$content = file_get_contents(HTML_PATH . "/finishes.html");

$content = str_replace("{{ROWS}}", $grid, $content);

/**************************************************************/

$title = "all-time finishes";

show_page($content, $title);
