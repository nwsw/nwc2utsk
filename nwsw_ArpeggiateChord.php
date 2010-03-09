<?php
/*******************************************************************************
nwswArpeggiateChord.php Version 1.01

This script will arpeggiate selected chords by adding grace notes that tie into the chord.

Copyright © 2006 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2006-05-12] Version 1.01: Code format cleanup
[2004-10-28] Version 1.00: Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

$clip = new NWC2Clip('php://stdin');

if (count($clip->Items) < 1) trigger_error("Please select at least one chord",E_USER_ERROR);

echo $clip->GetClipHeader()."\n";

$priorNoteObj = false;
foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item);
	//
	// If this is a non-grace chord and is not preceded by a grace note,
	// and it is larger than an 8th note in duration, then add a grace note arpeggio
	if (
		($o->GetObjType() == "Chord") && 
		!isset($o->Opts["Dur"]["Grace"]) &&
		(count(array_intersect(array_keys($o->Opts["Dur"]),array("Whole","Half","4th"))) > 0) &&
		!isset($priorNoteObj->Opts["Dur"]["Grace"])
		) 
	{
		$chordnotes = $o->GetTaggedOpt("Pos");
		$chorddur = $o->GetTaggedOpt("Dur");

		$graceDur = "8th";

		$numchordnotes = count($chordnotes);
		$i = 0;
		if ($numchordnotes > 1) foreach ($chordnotes as $notepitchtxt)
		{
			$notepitchObj = new NWC2NotePitchPos($notepitchtxt);
			$notepitchObj->Tied = '^';

			$new_notepitchtxt = $notepitchObj->ReconstructClipText();
			//
			$beamOpt = ($i ? "" : "=First");
			$i++;
			if ($i == $numchordnotes) $beamOpt = "=End";
			//
			$o = new NWC2ClipItem($item);
			echo "|Note|Dur:$graceDur,Grace|Opts:Stem=Up,Beam$beamOpt|Pos:$new_notepitchtxt\n";
		}
	}
	//
	echo $item;

	if (in_array($o->GetObjType(),array('Note','Chord','Rest','RestChord'))) $priorNoteObj = $o;
	else if ($o->GetObjType() == "Bar") unset($priorNoteObj);
	unset($o);
	}

echo NWC2_ENDCLIP."\n";

exit(NWC2RC_SUCCESS);
?>