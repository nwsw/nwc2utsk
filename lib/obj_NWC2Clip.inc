<?php

// -------------
class NWC2Clip {
// -------------

var $Version="";
var $Mode="";
var $Items=array();

function NWC2Clip($srcfile) 
{
	$this->Items = file($srcfile);

	if (!is_array($this->Items))
		trigger_error("Clip text from NoteWorthy Composer 2 is required as input to the NWC2Clip object",E_USER_ERROR);

	if (count($this->Items) < 2)
		trigger_error("Format error in the clip text",E_USER_ERROR);

	$hdr = trim(array_shift($this->Items));

	if (preg_match('/^'.NWC2_STARTCLIP.'\(([0-9]+)\.([0-9]+)\,([a-zA-Z0-9_]+)/',$hdr,$m)) {
		$this->Version = "$m[1].$m[2]";
		$this->Mode = $m[3];
		}
	else trigger_error("Unrecognized notation clip format",E_USER_ERROR);
	//
	$ftr = false;
	while (($ftr != NWC2_ENDCLIP) && (count($this->Items) > 0)) $ftr = trim(array_pop($this->Items));
	//
	if ($ftr != NWC2_ENDCLIP) trigger_error("Could not find clip ending tag",E_USER_ERROR);
}

function GetClipHeader()
{ 
	return NWC2_STARTCLIP."(".$this->Version.",".$this->Mode.")";
}
};

?>