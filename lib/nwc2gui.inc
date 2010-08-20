<?php
dl('php_wxWidgets.dll');

function nwc2gui_wxArray($a) {
	if (!is_array($a)) trigger_error("Invalid argument; sequential array required",E_USER_ERROR);

	$wxArrayString = new wxArrayString();

	foreach ($a as $v) $wxArrayString->Add($v);

	return $wxArrayString;
}

?>