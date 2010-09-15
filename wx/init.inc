<?php
if (!function_exists('wxEntry')) dl(class_exists("COM") ? 'php_wxWidgets.dll' : "wxwidgets.so");

if (!defined("WXPHP_NO_INIT_IMAGE_HANDLING")) include 'init-imghandling.inc';

class wxphp_ArrayString extends wxArrayString {
	function __construct($a)
	{
		if (!is_array($a)) trigger_error("Invalid argument; sequential array required",E_USER_ERROR);

		parent::__construct();
		foreach ($a as $v) $this->Add($v);
	}
}

?>
