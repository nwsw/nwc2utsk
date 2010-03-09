<?php

// ---------------------
class NWC2NotePitchPos {
// ---------------------

var $Accidental="";
var $Position=0;
var $Notehead="";
var $Tied="";

function NWC2NotePitchPos($pos)
{
	if (preg_match('/^([\#bnxv]{0,1})(\-{0,1}[0-9]+)([oxXz]{0,1})([\^]{0,1})/',$pos,$m)) {
		$this->Accidental=$m[1];
		$this->Position=$m[2];
		$this->Notehead=$m[3];
		$this->Tied=$m[4];
		}
}
	
function &ReconstructClipText()
{
	$s = $this->Accidental.$this->Position.$this->Notehead;
	if ($this->Tied) $s .= "^";
	return $s;
}
};

?>