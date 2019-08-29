<?php

function show_page($content, $title) {

	$page = file_get_contents(HTML_PATH . "/page.html");

	$page = str_replace("{{TITLE}}", "Diffendorf baseball: " . $title, $page);

	$page = str_replace("{{CONTENT}}", $content, $page);

	$page = str_replace("{{web_home}}", WEB_HOME, $page);

	echo $page;

	exit;
}
