<?php

// -----------------------------------------------------------------------
class NWC2NotePitchPos {
// -----------------------------------------------------------------------
var $Accidental="";
var $Position=0;
var $Notehead="";
var $Tied="";
var $Color="";

function NWC2NotePitchPos($pos)
{
	if (preg_match('/^([\#bnxv]{0,1})(\-{0,1}[0-9]+)([oxXzyYabcdefghijklmnpqrstuvw]{0,1})([\^]{0,1})/',$pos,$m)) {
		$this->Accidental=$m[1];
		$this->Position=$m[2];
		$this->Notehead=$m[3];
		$this->Tied=$m[4];
		}
	if (preg_match('/!([0-9]+)/',$pos,$m)) {
		$this->Color=$m[1];
		}
}
	
function GetAccidentalPitchOffset()
{
	if ($this->Accidental) return intval(nw_aafield(self::$nwcAccidentalOffsets,$this->Accidental,2));
	return 0;
}

function GetNoteName($clef)
{
	$n = 8*WholeNotesPerOctave + self::GetClefStdCenterTone($clef) + $this->Position;

	return nw_aafield(self::$NoteNames,intval($n % 7),"Z");
}

function ReconstructClipText()
{
	$s = $this->Accidental.$this->Position.$this->Notehead;
	if ($this->Tied) $s .= "^";
	if ($this->Color) $s .= "!".$this->Color;
	return $s;
}

// --------------------------
// Utility definitions statically contained in the NWC2NotePitchPos name space
static $NoteNames = array('C','D','E','F','G','A','B');
static $nwcAccidentals = array('v','b','n','#','x');
static $BaseNoteSemitones = array("C"=>0,"D"=>2,"E"=>4,"F"=>5,"G"=>7,"A"=>9,"B"=>11);
static $ClefCenterTones = array("Treble"=>34,"Bass"=>22,"Alto"=>28,"Tenor"=>26,"Drum"=>22);
//
// These are initialized below
static $NoteNamesKey;
static $nwcAccidentalOffsets;
//
static function GetClefStdCenterTone($clef) {return nw_aafield(self::$ClefCenterTones,$clef,self::$ClefCenterTones['Treble']);}

};

define("WholeNotesPerOctave",count(NWC2NotePitchPos::$NoteNames));
NWC2NotePitchPos::$NoteNamesKey = array_flip(NWC2NotePitchPos::$NoteNames);
NWC2NotePitchPos::$nwcAccidentalOffsets = array_flip(NWC2NotePitchPos::$nwcAccidentals);
array_walk(NWC2NotePitchPos::$nwcAccidentalOffsets,create_function('&$v,$k','$v-=2;'));


// -----------------------------------------------------------------------
class NWC2ClipItemWithPitchPos extends NWC2ClipItem {
// -----------------------------------------------------------------------
static function ObjTypeHasPitchPos($ObjType) {return (strpos("|Note|Chord|RestChord|","|$ObjType|") !== false);}

var $PitchPos = array("Pos" => array(), "Pos2" => array());

// Constructor
function NWC2ClipItemWithPitchPos($itemtext,$remapFakes=false)
{
	parent::__construct($itemtext,$remapFakes);

	if ($this->ObjTypeHasPitchPos($this->ObjType)) foreach (array("Pos","Pos2") as $pos_tag) {
		$notePosList = $this->GetTaggedOptAsArray($pos_tag,false);
		if (is_array($notePosList)) foreach ($notePosList as $np) $this->PitchPos[$pos_tag][] = new NWC2NotePitchPos($np);
		}
}	

function ReconstructPosOpts()
{
	if ($this->ObjTypeHasPitchPos($this->ObjType)) foreach (array("Pos","Pos2") as $pos_tag) {
		if (!isset($this->Opts[$pos_tag])) continue;
		$this->Opts[$pos_tag] = array();
		foreach ($this->PitchPos[$pos_tag] as $npp) $this->Opts[$pos_tag][] = $npp->ReconstructClipText();
		}
}

};
?>