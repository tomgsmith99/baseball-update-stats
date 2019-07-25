<?php

/************************************************************/

function get_owner_rows_html($owners) {

	$team_row_template = file_get_contents(HTML_PATH . "/team_row.html");

	$i = 1;

	$cols = ["FNF", "owner_id", "team_name", "points", "head_shot"];

	$owner_rows_html = "";

	foreach ($owners as $owner_id => $row) {

		$this_row = str_replace("{{place}}", $i, $team_row_template);

		foreach ($cols as $col) {
			$this_row = str_replace("{{" . $col . "}}", $row[$col], $this_row);
		}

		$owner_rows_html .= $this_row . "\n";

		$i++;
	}

	return $owner_rows_html;
}
