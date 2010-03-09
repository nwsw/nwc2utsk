<?php

// --------------------------
class NWC2PlayContext {
// --------------------------

var $Clef="Treble";
var $ClefOctave="None";
var $Transposition=0;
var $Key=array('A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0,'F'=>0,'G'=>0);
var $RunKey=array('A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0,'F'=>0,'G'=>0);
var $Ties=array();
var $Slur=false;

var $SeenFirstEnding=false;
var $Ending1Context=false;

function NWC2PlayContext() { }

function SaveRestoreContext(&$o)
{
	$varlist = array("Clef","ClefOctave","Transposition","Key","RunKey","Ties","Slur");
	if ($o->GetObjType() == "Ending") {
		$endings = $o->GetTaggedOpt('Endings',array());
		if (isset($endings[1]) && !$this->SeenFirstEnding) {
			$this->SeenFirstEnding = true;
			$Ending1Context = array();
			foreach($varlist as $v) $this->Ending1Context[$v] = $this->$v;
			}
		else if ($this->SeenFirstEnding && !isset($endings[1])) {
			foreach($varlist as $v) $this->$v = $this->Ending1Context[$v];
			}
		}
}

function GetNotePitchName(&$notepitchObj) { return $notepitchObj->GetNoteName($this->Clef); }

function GetNotePitchAccidental(&$notepitchObj)
{
	$n = $notepitchObj->GetNoteName($this->Clef);
	$a = $notepitchObj->Accidental;
	if (!$a) $a = $GLOBALS['nwcAccidentals'][$this->RunKey[$n]+2];
	return $a;
}

function IsTieReceiver(&$notepitchObj) 
{
	return array_search($this->GetNotePitchAccidental($notepitchObj).$notepitchObj->Position,$this->Ties) !== false;
}

function UpdateContext(&$o)
{
	if (in_array($o->GetObjType(),array('Note','Chord','Rest','RestChord'))) {
		$notes = $o->GetTaggedOptAsArray("Pos",array());
		$notes2 = $o->GetTaggedOptAsArray("Pos2",array());
		//
		if (empty($notes)) $notes = $notes2;
		else if (!empty($notes2)) {
			// Merge the stem down notes with the stem up notes
			if (isset($o->Opts["Stem"]) && ($o->Opts["Stem"] == "Up")) if ($start2->Position < $start1->Position) $notes = array_merge($notes2,$notes);
			else $notes = array_merge($notes,$notes2);
			}

		$RunKey_Changes = array();
		foreach ($notes as $notepitchTxt) {
			$notepitchObj = new NWC2NotePitchPos($notepitchTxt);

			$notename = $notepitchObj->GetNoteName($this->Clef);
			$noteacc = $notepitchObj->Accidental;
			if (!$noteacc) $noteacc = $GLOBALS['nwcAccidentals'][$this->RunKey[$notename]+2];
			$tieKey = $noteacc.$notepitchObj->Position;

			$runningTieKey = array_search($tieKey,$this->Ties);
			if ($runningTieKey !== false) { if (!$notepitchObj->Tied) unset($this->Ties[$runningTieKey]); }
			else if ($notepitchObj->Tied) { $this->Ties[] = $tieKey; }

			if ($notepitchObj->Accidental) $RunKey_Changes[$notename] = $notepitchObj->GetAccidentalPitchOffset();
			}

		foreach ($RunKey_Changes as $notename => $newAccidentalOffset) $this->RunKey[$notename] = $newAccidentalOffset;
		//
		if (!isset($o->Opts["Dur"]["Grace"])) $this->Slur = isset($o->Opts["Dur"]["Slur"]);
		}
	else switch ($o->GetObjType()) {
		case "Bar":
			$this->RunKey = $this->Key;
			if ($o->GetTaggedOpt("Style","") == "MasterRepeatOpen") $this->SeenFirstEnding = false;
			break;

		case "Clef":
			$this->Clef = $o->GetTaggedOpt("Type","Treble");
			$this->ClefOctave = $o->GetTaggedOpt("OctaveShift","None");
			break;

		case "Key":
			$k = $o->GetTaggedOpt('Signature',array());
			foreach ($GLOBALS['nwcNoteNames'] as $notename) {
				$a = 0;
				if (isset($k[$notename."b"])) $a--;
				else if (isset($k[$notename."#"])) $a++;
				//				
				$this->Key[$notename] = $a;
				}

			$this->RunKey = $this->Key;
			break;

		case "Instrument":
			$this->Transposition = intval($o->GetTaggedOpt("Trans",0));
			break;

		case "Ending":
			$this->SaveRestoreContext($o);
			break;
		}
}
};

?>