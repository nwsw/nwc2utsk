<?php

// --------------------------
class NWC2PlayContext {
// --------------------------

var $Clef="Treble";
var $ClefOctave="None";
var $Transposition=0;
var $Key=array('C'=>0,'D'=>0,'E'=>0,'F'=>0,'G'=>0,'A'=>0,'B'=>0);
var $RunKey=array('C'=>0,'D'=>0,'E'=>0,'F'=>0,'G'=>0,'A'=>0,'B'=>0);
var $KeyTonic='C';
var $NextBarNum=1;
var $PendingBarIncrement=true;
var $Ties=array();
var $Slur=false;

var $SeenFirstEnding=false;
var $Ending1Context=false;

function NWC2PlayContext() { }

function SaveRestoreContext($o)
{
	$varlist = array("Clef","ClefOctave","Transposition","Key","RunKey","KeyTonic","Ties","Slur");
	if ($o->GetObjType() == "Ending") {
		$endings = $o->GetTaggedOpt('Endings',array());
		if (isset($endings[1]) && !$this->SeenFirstEnding) {
			$this->SeenFirstEnding = true;
			$this->Ending1Context = array();
			foreach($varlist as $v) $this->Ending1Context[$v] = $this->$v;
			}
		else if (!empty($endings) && $this->SeenFirstEnding && !isset($endings[1])) {
			foreach($varlist as $v) $this->$v = $this->Ending1Context[$v];
			}
		}
}

function GetOctaveShift()
{
	switch ($this->ClefOctave) {
		case "Octave Up":   return 1;
		case "Octave Down": return -1;
		}

	return 0;
}

function GetPlayPitchShift() { return (12 * $this->GetOctaveShift()) + $this->Transposition; }

function GetNotePitchName($notepitchObj) { return $notepitchObj->GetNoteName($this->Clef); }

static function GetTiePosFromStr($s)
{
	return substr($s,1);
}

function GetNotePitchAccidental($notepitchObj)
{
	$a = $notepitchObj->Accidental;
	if ($a) return $a;

	foreach ($this->Ties as $tiekey => $tie) {
		if (NWC2PlayContext::GetTiePosFromStr($tie) == $notepitchObj->Position) return substr($tie,0,1);
		}
		
	$n = $notepitchObj->GetNoteName($this->Clef);
	return NWC2NotePitchPos::$nwcAccidentals[$this->RunKey[$n]+2];
}

function GetScientificPitchOctave($notepitchObj)
{
	return intval((NWC2NotePitchPos::GetClefStdCenterTone($this->Clef) + $notepitchObj->Position) / WholeNotesPerOctave) + $this->GetOctaveShift();
}

function GetNoteMidiPitch($notepitchObj)
{
	$d = 12 * ($this->GetScientificPitchOctave($notepitchObj)+1);
	$d += nw_aafield(NWC2NotePitchPos::$BaseNoteSemitones,$this->GetNotePitchName($notepitchObj),0);
	$d += nw_aafield(NWC2NotePitchPos::$nwcAccidentalOffsets,$this->GetNotePitchAccidental($notepitchObj),0);
	$d += $this->Transposition;

	// Midify any beyond boundary values
	if ($d < 0) $d = 12 - (abs($d) % 12);
	else if ($d > 127) $d = 115 + (($d - 115) % 12);

	return $d;
}

function IsTieReceiver($notepitchObj) 
{
	return array_search($this->GetNotePitchAccidental($notepitchObj).$notepitchObj->Position,$this->Ties) !== false;
}

function UpdateContext($o)
{
	if (in_array($o->GetObjType(),array('Note','Chord','Rest','RestChord'))) {
		$notes = $o->GetTaggedOptAsArray("Pos",array());
		$notes2 = $o->GetTaggedOptAsArray("Pos2",array());
		//
		if (empty($notes)) $notes = $notes2;
		else if (!empty($notes2)) {
			// Merge the stem down notes with the stem up notes
			if (isset($o->Opts["Stem"]) && ($o->Opts["Stem"] == "Up")) $notes = array_merge($notes2,$notes);
			else $notes = array_merge($notes,$notes2);
			}

		$RunKey_Changes = array();
		foreach ($notes as $notepitchTxt) {
			$notepitchObj = new NWC2NotePitchPos($notepitchTxt);

			$notename = $notepitchObj->GetNoteName($this->Clef);
			$noteacc = $this->GetNotePitchAccidental($notepitchObj);
			if (!$noteacc) $noteacc = NWC2NotePitchPos::$nwcAccidentals[$this->RunKey[$notename]+2];
			$tieKey = $noteacc.$notepitchObj->Position;

			$runningTieKey = array_search($tieKey,$this->Ties);
			if ($runningTieKey !== false) { if (!$notepitchObj->Tied) unset($this->Ties[$runningTieKey]); }
			else if ($notepitchObj->Tied) { $this->Ties[] = $tieKey; }

			if ($notepitchObj->Accidental) $RunKey_Changes[$notename] = $notepitchObj->GetAccidentalPitchOffset();
			}

		foreach ($RunKey_Changes as $notename => $newAccidentalOffset) $this->RunKey[$notename] = $newAccidentalOffset;
		//
		if (!isset($o->Opts["Dur"]["Grace"])) $this->Slur = isset($o->Opts["Dur"]["Slur"]);

		if ($this->PendingBarIncrement) {
			$this->NextBarNum++;
			$this->PendingBarIncrement = false;
			}
		}
	else switch ($o->GetObjType()) {
		case "Context":
			$this->NextBarNum = intval($o->Opts["Bar"][0]);
			$this->PendingBarIncrement = in_array("AtStart",$o->Opts["Bar"]);
			if (!$this->PendingBarIncrement) $this->NextBarNum++;
			break;

		case "Bar":
			$this->RunKey = $this->Key;
			if ($o->GetTaggedOpt("Style","") == "MasterRepeatOpen") $this->SeenFirstEnding = false;
			$this->PendingBarIncrement = ($o->GetTaggedOpt("XBarCnt","N") != "Y");
			break;

		case "RestMultiBar":
			if ($this->PendingBarIncrement) $this->NextBarNum++;
			$this->PendingBarIncrement = false;
			$this->NextBarNum += nw_aafield($o->Opts,"NumBars",1)-1;
			break;

		case "Clef":
			$this->Clef = $o->GetTaggedOpt("Type","Treble");
			$this->ClefOctave = $o->GetTaggedOpt("OctaveShift","None");
			break;

		case "Key":
			$k = $o->GetTaggedOpt('Signature',array());
			foreach (NWC2NotePitchPos::$NoteNames as $notename) {
				$a = 0;
				if (isset($k[$notename."b"])) $a--;
				else if (isset($k[$notename."#"])) $a++;
				//				
				$this->Key[$notename] = $a;
				}

			$this->RunKey = $this->Key;
			$this->KeyTonic = $o->GetTaggedOpt("Tonic","C");
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