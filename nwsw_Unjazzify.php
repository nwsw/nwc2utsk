<?php
/*******************************************************************************
nwsw_Unjazzify Version 1.11

This script will find note pairs where the first note is a dotted note, 
and the second is half the notehead duration of the first and is not dotted.
It will convert the pair into two notes of equal duration.

Copyright © 2006 by NoteWorthy Software, Inc.
All Rights Reserved

History:
[2006-05-12] Version 1.11 - Include in starter kit
[2006-05-09] Version 1.10 - Process rests, unbeamed notes, and quarter notes
[2006-05-09] Version 1.00 - Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

$clip = new NWC2Clip('php://stdin');

// 
$validPairs = array(
	"4th"  => "8th", 
	"8th"  => "16th",
	"16th" => "32nd",
	"32nd" => "64th"
	);
//
// Track the number of conversions
$numConvertedPairs = 0;
//
// Track the previous note if it is eligible for conversion
$priorNoteObj = false;
$targetEndingDuration = false;
//
// Track any items that fall between the two notes in the group
$NonNoteQ = "";
//
function FlushTheGroupingQ()
{
	global $NonNoteQ,$priorNoteObj;

	if ($priorNoteObj) echo $priorNoteObj->ReconstructClipText()."\n";
	if ($NonNoteQ) echo $NonNoteQ;

	$NonNoteQ = "";
	$priorNoteObj = false;
}

echo $clip->GetClipHeader()."\n";
//
foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item);
	//
	$is_note = in_array($o->GetObjType(), array("Chord","Note","Rest"));
	$is_rest = ($o->GetObjType() == "Rest");
	$is_grace = isset($o->Opts["Dur"]["Grace"]);
	$is_dotted = isset($o->Opts["Dur"]["Dotted"]);
	$is_dbldotted = isset($o->Opts["Dur"]["DblDotted"]);
	$is_beamed = isset($o->Opts["Opts"]["Beam"]);
	$is_beamstart = $is_beamed && ($o->Opts["Opts"]["Beam"] == "First");

	if ($is_note) {
		if (!$priorNoteObj) {
			$starterDuration = array_intersect(array_keys($o->Opts["Dur"]),array_keys($validPairs));
			if (count($starterDuration)) $starterDuration = array_shift($starterDuration);
			else $starterDuration = false;
			//
			if ($is_dotted && !$is_grace && $starterDuration && (!$is_beamed || $is_beamstart)) {
				$targetEndingDuration = $validPairs[$starterDuration];
				$priorNoteObj = $o;
				continue;
				}
			//
			echo $item;
			}
		else if (in_array($targetEndingDuration, array_keys($o->Opts["Dur"])) && !$is_beamstart && !$is_dotted && !$is_dbldotted) {
			$numConvertedPairs++;
			unset($priorNoteObj->Opts["Dur"]["Dotted"]);
			unset($o->Opts["Dur"][$targetEndingDuration]);
			$o->Opts["Dur"][$starterDuration] = "";
			FlushTheGroupingQ();
			echo $o->ReconstructClipText()."\n";
			}
		else {
			FlushTheGroupingQ();
			echo $item;
			}

		continue;
		}

	if (in_array($o->GetObjType(),array("Bar","TimeSig"))) {
		FlushTheGroupingQ();
		echo $item;
		continue;
		}
	
	if ($priorNoteObj) $NonNoteQ .= $item;
	else echo $item;
	}

FlushTheGroupingQ();
echo NWC2_ENDCLIP."\n";

if (!$numConvertedPairs) {
	fputs(STDERR,"No valid note pairs were found within the selection");
	exit(NWC2RC_ERROR);
	}

exit(NWC2RC_SUCCESS);
?>