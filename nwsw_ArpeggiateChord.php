<?php
/*******************************************************************************
nwsw_ArpeggiateChord.php Version 2

This script will arpeggiate selected chords using the Arpeggio.ms object.

Prompting can be added with the following command line:
"/duration=<PROMPT:Select a duration:=|8th|16th|32nd>" "/sequence=<PROMPT:Select an arpeggio order:=|up|down>"

Copyright © 2016 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2016-01-25] Version 2:    Switch from grace notes to use of Arpeggio.ms object
[2007-06-19] Version 1.21: Support prompting
[2007-06-16] Version 1.20: Add grace note duration constant, sequencing scheme, and ignore last note in chord sequence
[2006-05-12] Version 1.01: Code format cleanup
[2004-10-28] Version 1.00: Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

// ARPEGGIO_DURATION can be one of 8th, 16th, 32nd, 64th
$ARPEGGIO_DURATION = "8th";
$ARPDURConvert = array('8th'=>8,'16th'=>16,'32nd'=>32,'64th'=>64);
//
// Const_ARPEGGIO_SEQUENCE can be one of up or down
$ARPEGGIO_SEQUENCE = "up";

foreach ($argv as $k => $v) {
	if (preg_match('/^\/duration\=(.*)$/i',$v,$m)) $ARPEGGIO_DURATION = $m[1];
	else if (preg_match('/^\/sequence\=(.*)$/i',$v,$m)) $ARPEGGIO_SEQUENCE = $m[1];
	}

$clip = new NWC2Clip('php://stdin');

if (count($clip->Items) < 1) trigger_error("Please select at least one chord",E_USER_ERROR);

echo $clip->GetClipHeader()."\n";

$ArpeggioAllowedHere = true;
foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item);
	//
	// If this is a non-grace chord and is not preceded by a grace note,
	// and it is larger than an 8th note in duration, then add an arpeggio
	if (($o->GetObjType() == "Chord") && 
		!isset($o->Opts["Dur"]["Grace"]) &&
		(count(array_intersect(array_keys($o->Opts["Dur"]),array("Whole","Half","4th"))) > 0) &&
		$ArpeggioAllowedHere)
		{
		// Create the Arpeggio.ms object, then update its properties as needed

		$userObj = new NWC2ClipItem('|User|Arpeggio.ms|Pos:-4|Class:Standard|Dir:up|Rate:16|Color:0|Visibility:Default');

		$chordnotes = $o->GetTaggedOpt("Pos");
		$userObj->Opts['Pos'] = $chordnotes[0]['Position'];
		
		foreach (array('Color','Visibility') as $baseprop) {
			if (!empty($o->Opts[$baseprop])) $userObj->Opts[$baseprop] = $o->Opts[$baseprop];
			}
		
		if ($ARPEGGIO_SEQUENCE == "down") $userObj->Opts['Dir'] = 'down';
		
		$userObj->Opts['Rate'] = nw_aafield($ARPDURConvert,$ARPEGGIO_DURATION,16);

		echo $userObj->ReconstructClipText()."\n";

		// Now mute the chord
		if (empty($o->Opts['Opts'])) $o->Opts['Opts'] = array();
		$o->Opts['Opts']['Muted'] = '';
		echo $o->ReconstructClipText()."\n";
		}
	else {
		echo $item;
		}

	if ($o->GetUserObjType() == 'Arpeggio.ms')
		$ArpeggioAllowedHere = false;
	else if (in_array($o->GetObjType(),array('Note','Chord','Rest','RestChord')))
		$ArpeggioAllowedHere = !isset($o->Opts["Dur"]["Grace"]);
	else if ($o->GetObjType() == "Bar")
		$ArpeggioAllowedHere = true;
	}

echo NWC2_ENDCLIP."\n";

exit(NWC2RC_SUCCESS);
?>