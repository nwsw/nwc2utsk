<?php
/*******************************************************************************
nwsw_ArpeggiateChord.php Version 2

This script will arpeggiate selected chords using the Arpeggio.ms object.

Prompting can be added with the following command line:
"/duration=<PROMPT:Select a duration:=#[8,128]>" "/sequence=<PROMPT:Select an arpeggio order:=|up|down>"

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

function getBaseDur($o,$dur='Dur')
{
	$d = $o->GetTaggedOpt($dur);
	if (!$d || !is_array($d)) return false;

	foreach($d as $k=>$v) if (strpos('_|Whole|Half|4th|8th|16th|32nd|64th|',$k)) return $k;
	return false;
}

// ARPEGGIO_DURATION can be either a number, or one of 8th, 16th, or 32nd from legacy setups
$ARPEGGIO_DURATION = 32;
$ARPDURConvert = array('Whole'=>1,'Half'=>2,'4th'=>4,'8th'=>8,'16th'=>16,'32nd'=>32,'64th'=>64);
//
// Const_ARPEGGIO_SEQUENCE can be one of up or down
$ARPEGGIO_SEQUENCE = 'up';

foreach ($argv as $k => $v) {
	if (preg_match('/^\/duration\=([0-9]+)/i',$v,$m)) $ARPEGGIO_DURATION = intval($m[1]);
	else if (preg_match('/^\/sequence\=(.*)$/i',$v,$m)) $ARPEGGIO_SEQUENCE = $m[1];
	}

$clip = new NWC2Clip('php://stdin');

if (count($clip->Items) < 1) trigger_error("Please select at least one chord",E_USER_ERROR);

echo $clip->GetClipHeader()."\n";

$ArpeggioAllowedHere = true;
$WarnAboutInboundTie = false;
$PlayContext = new NWC2PlayContext();

foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item,true);

	if ($o->IsContextInfo()) {
		$PlayContext->UpdateContext($o);
		continue;
		}

	$baseDur = getBaseDur($o);

	//
	// If this is a non-grace chord and is not preceded by a grace note,
	// and it is larger than an 8th note in duration, then add an arpeggio
	if ($ArpeggioAllowedHere &&
		($o->GetObjType() == "Chord") && 
		!isset($o->Opts["Dur"]["Grace"]) &&
		strpos('_|Whole|Half|4th|8th|16th|',"|$baseDur|")
		) {
		// Create the Arpeggio.ms object, then update its properties as needed

		$userObj = new NWC2ClipItem('|User|Arpeggio.ms|Pos:0|Class:Standard|Dir:up|Rate:16|Color:0|Visibility:Default');

		$chordnotes = $o->GetTaggedOptAsArray("Pos",array());
		$chordnotes2 = $o->GetTaggedOptAsArray("Pos2");
		if ($chordnotes2) $chordnotes = (nw_aafield($o->Opts["Opts"],"Stem") == "Up") ? array_merge($chordnotes2,$chordnotes) : array_merge($chordnotes,$chordnotes2);
		foreach ($chordnotes as $i => $notepitchtxt) {
			$notepitchObj = new NWC2NotePitchPos($notepitchtxt);
			if ($i == 0) $userObj->Opts['Pos'] = $notepitchObj->Position;
			if ($PlayContext->IsTieReceiver($notepitchObj)) $WarnAboutInboundTie = true;
			}
		
		foreach (array('Color','Visibility') as $baseprop) {
			if (!empty($o->Opts[$baseprop])) $userObj->Opts[$baseprop] = $o->Opts[$baseprop];
			}
		
		if ($ARPEGGIO_SEQUENCE == "down") $userObj->Opts['Dir'] = 'down';
		
		$chordDurMinRate = nw_aafield($ARPDURConvert,$baseDur,32) * 4;
		$userObj->Opts['Rate'] = max($chordDurMinRate,$ARPEGGIO_DURATION);

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

	$PlayContext->UpdateContext($o);
	}

echo NWC2_ENDCLIP."\n";

if ($WarnAboutInboundTie)  fputs(STDERR,"Warning: Adding Arpeggio within tied notes is not recommended.\n\nPress OK to add the Arpeggio anyway.");

exit(NWC2RC_SUCCESS);
?>