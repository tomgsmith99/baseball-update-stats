<?php

$dir = "baseball_update_stats";

if (file_exists('/Applications/MAMP/htdocs')) {
	$base_path = '/Applications/MAMP/htdocs';
	$web_home = '/' . $dir;
}
else {

	header('Location: http://baseball.tomgsmith.com/views/view_final_standings.php?season=2019');

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

include INCLUDES_PATH . '/generate_home_page.php';

/**************************************************************/

$home_page = get_home_page();

echo $home_page;

// if (array_key_exists("mode", $_GET)) {
// 	$mode = $_GET["mode"]; // dyn or static

// 	if ($mode != "dyn") {
// 		$mode = "static";
// 	}
// }
// else {

// 	$latest_update = get_latest_html_file();

// 	$mode = "static";
// }

// ************************************************************

// if ($mode === "static") {
// 	$home_page = file_get_contents(HTML_PATH . "/home_page/latest.html");
// }
// else {
// 	$home_page = get_home_page();
// }

// echo $home_page;

// function get_latest_html_file() {
// 	$files = scandir(HTML_PATH . '/home_page', SCANDIR_SORT_DESCENDING);

// 	$i=0;

// 	while ($files[$i] == "latest.html") {
// 		$i++;
// 	}

// 	$arr = explode(".", $files[$i]);

// 	$timestamp = $arr[0];

// 	echo "the timestamp is: " . $timestamp;

// 	$arr = getdate($timestamp);

// 	echo "<br>" . json_encode($arr);

// 	echo "<br>the yday is: " . $arr["yday"];

// 	// echo json_encode($files);

// 	echo "the latest file is: " . $files[$i];
// 	exit;

// }
