<?php
/*******************************************************************************
nwsw_ArpeggiateChord.php Version 1.21

This script will arpeggiate selected chords by adding grace notes that tie into the chord.

Prompting can be added with the following command line:
"/duration=<PROMPT:Select a grace note duration:=|8th|16th|32nd>" "/sequence=<PROMPT:Select an arpeggio order:=|up|down|random>"

Copyright © 2007 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2007-06-19] Version 1.21: Support prompting
[2007-06-16] Version 1.20: Add grace note duration constant, sequencing scheme, and ignore last note in chord sequence
[2006-05-12] Version 1.01: Code format cleanup
[2004-10-28] Version 1.00: Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

// ARPEGGIO_DURATION can be one of 8th, 16th, 32nd, 64th
$ARPEGGIO_DURATION = "8th";
//
// Const_ARPEGGIO_SEQUENCE can be one of up, down, or random
$ARPEGGIO_SEQUENCE = "up";

foreach ($argv as $k => $v) {
	if (preg_match('/^\/duration\=(.*)$/i',$v,$m)) $ARPEGGIO_DURATION = $m[1];
	else if (preg_match('/^\/sequence\=(.*)$/i',$v,$m)) $ARPEGGIO_SEQUENCE = $m[1];
	}

$clip = new NWC2Clip('php://stdin');

if (count($clip->Items) < 1) trigger_error("Please select at least one chord",E_USER_ERROR);

echo $clip->GetClipHeader()."\n";

$priorNoteObj = false;
foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item);
	//
	// If this is a non-grace chord and is not preceded by a grace note,
	// and it is larger than an 8th note in duration, then add a grace note arpeggio
	if (($o->GetObjType() == "Chord") && 
			!isset($o->Opts["Dur"]["Grace"]) &&
			(count(array_intersect(array_keys($o->Opts["Dur"]),array("Whole","Half","4th"))) > 0) &&
			!isset($priorNoteObj->Opts["Dur"]["Grace"])) {
		$chordnotes = $o->GetTaggedOpt("Pos");
		$chorddur = $o->GetTaggedOpt("Dur");

		switch ($ARPEGGIO_SEQUENCE) {
		case "up":
			// do nothing
			break; 

		case "down":
			$chordnotes = array_reverse($chordnotes);
			break;

		case "random":
			shuffle($chordnotes);
			break;
		}

		// The last item does not need a tied in grace note
		array_pop($chordnotes);

		foreach ($chordnotes as $i => $notepitchtxt) {
			$notepitchObj = new NWC2NotePitchPos($notepitchtxt);
			$notepitchObj->Tied = '^';

			$new_notepitchtxt = $notepitchObj->ReconstructClipText();
			//
			$beamOpt = ($i ? "" : "=First");
			if ($i == (count($chordnotes)-1)) $beamOpt = "=End";
			//
			$o = new NWC2ClipItem($item);
			echo "|Note|Dur:$ARPEGGIO_DURATION,Grace|Opts:Stem=Up,Beam$beamOpt|Pos:$new_notepitchtxt\n";
			}
		}

	echo $item;

	if (in_array($o->GetObjType(),array('Note','Chord','Rest','RestChord'))) $priorNoteObj = $o;
	else if ($o->GetObjType() == "Bar") unset($priorNoteObj);
	unset($o);
	}

echo NWC2_ENDCLIP."\n";

exit(NWC2RC_SUCCESS);
?>