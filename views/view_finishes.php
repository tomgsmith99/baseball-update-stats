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

include INCLUDES_PATH . "/get_page.php";
include INCLUDES_PATH . '/get_dbconn.php';

/**************************************************************/

$dbconn = get_dbconn();

/**************************************************************/

$query = "SELECT o.Owner_ID, o.Points, o.Place, o.Year, m.FNF, s.status";
$query .= " FROM OwnersMain AS o, Members AS m, Seasons AS s";
$query .= " WHERE o.Owner_ID = m.Member_ID AND o.Place < 7";
$query .= " AND o.Year=s.Season_ID AND s.status='closed'";
$query .= " ORDER BY o.Year DESC, Place, m.Lname, m.Fname ASC";

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

	// $grid .= "<td><a href = '{{views}}/owner.php?owner_id=" . $row["Owner_ID"] . "'>";
	// $grid .= $row["FNF"] . "</a></td>";

	$grid .= "<td>" . $row["FNF"] . "</a></td>";
}

$grid .= "</tr>";

/**************************************************************/

$content = file_get_contents(HTML_PATH . "/finishes.html");

$content = str_replace("{{ROWS}}", $grid, $content);

/**************************************************************/

$title = "all-time finishes";

$page = get_page($content, $title);

echo $page;

exit;
