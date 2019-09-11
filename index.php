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

include INCLUDES_PATH . '/generate_home_page.php';

/**************************************************************/

if (array_key_exists("mode", $_GET)) {
	$mode = $_GET["mode"]; // dyn or static

	if ($mode != "dyn") {
		$mode = "static";
	}
}
else {
	$mode = "static";
}

/**************************************************************/

if ($mode === "static") {
	$home_page = file_get_contents(HTML_PATH . "/home_page/latest.html");
}
else {
	$home_page = get_home_page();
}

echo $home_page;
