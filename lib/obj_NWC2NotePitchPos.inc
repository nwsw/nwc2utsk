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
	
function GetAccidentalPitchOffset()
{
	if ($this->Accidental) return intval(nw_aafield($GLOBALS['nwcAccidentalKeys'],$this->Accidental,2))-2;
	return 0;
}

function GetNoteName($clef)
{
  $n = 56 + nwcGetClefStdCenterTone($clef) + $this->Position;

  return nw_aafield($GLOBALS['nwcNoteNames'],intval($n % 7),"Z");
}

function &ReconstructClipText()
{
	$s = $this->Accidental.$this->Position.$this->Notehead;
	if ($this->Tied) $s .= "^";
	return $s;
}
};

// ------------------------------------
// Utility vars and functions
// ------------------------------------
$nwcNoteNames = array('A','B','C','D','E','F','G');
$nwcNoteKeys = array_flip($nwcNoteNames);
$nwcAccidentals = array('v','b','n','#','x');
$nwcAccidentalKeys = array_flip($nwcAccidentals);
$nwcClefCenterTones = array(
	"Treble"	=>intval((4*7) + $nwcNoteKeys['B']),
	"Bass"		=>intval((2*7) + $nwcNoteKeys['D']),
	"Alto"		=>intval((3*7) + $nwcNoteKeys['C']),
	"Tenor"		=>intval((3*7) + $nwcNoteKeys['A']),
	"Drum"		=>intval((2*7) + $nwcNoteKeys['D'])
	);

function nwcGetClefStdCenterTone($clef)
{
	return nw_aafield($GLOBALS['nwcClefCenterTones'],$clef,$GLOBALS['nwcClefCenterTones']['Treble']);
}

?>