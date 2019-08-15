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

set_include_path(INCLUDES_PATH);

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

