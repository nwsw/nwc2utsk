<?php
/*******************************************************************************
nwswRetrograde.php Version 1.11

This script will invert the selected notation for a rough retrograde of the music.

This is a very basic retrograde script. There are many more things that could be done, such as
more sophisticated handling of accents on the start versus the end of a note tie, some kind
of fancy inversion of repeat designations, and smart inversion of note accidentals.

Copyright © 2006 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2006-05-12] Version 1.11: Code format cleanup
[2004-10-28] Version 1.10: Integrated into NWC2 User Tool Starter Kit
[2004-10-16] Version 1.00: Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

// ----------------------------------------------------------------------------
// I N I T I A L I Z A T I O N
//
$clip = new NWC2Clip('php://stdin');

if (count($clip->Items) < 2) trigger_error("Please select more than one item to perform the retrograde",E_USER_ERROR);

if ($clip->Mode != "Single") trigger_error("Clip mode of {$clip->Mode} is not supported",E_USER_ERROR);

// ----------------------------------------------------------------------------
// G L O B A L S
//
// $slurActive keeps track of whether the previous note included the slur attribute
$slurActive = false;
//
// $runningTiedPositions tracks note ties by keeping an array of positions that are currently tied
$runningTiedPositions = array();
//
// During a retrograde, the staff signatures have to be applied backwards, which requires that their reversal 
// be deferred until a like kind signature change occurs. This array holds the pending signatures used after
// we encounter the next signature.
$pending_sigs = array(
'TimeSig' => false,
'Key'	=> false,
'Clef'	=> false
);
//
// The barflow_zones array manages collections of adjacent bar lines, special endings, and flow directions.
// These items are not retrograded, since their reversal would be nonsensical (backwards repeats, etc.)
$barFlowZones = array();
$currentBarFlowZone = 0;
//
// Expressions that affix themselves to the following note or bar should be queued and then
// injected after the note or bar is placed
$notebarDecorationQueue = array();
//
// $previousItemType keeps track of the type of the previous item that was processed
$previousItemType = "";

// ----------------------------------------------------------------------------
// F U N C T I O N S
//
function GetNtnItemType($s)	{return (preg_match('/^\|([^\|]+)/',$s,$m)) ? trim($m[1]) : false;}
function GetStaffPos($fullpos)	{return (preg_match('/[^0-9\-]*(\-{0,1}[0-9]+)/',$fullpos,$m)) ? intval($m[1]) : intval($fullpos);}

function RetrogradeDurElements($dur)
{
	global $slurActive;

	$hasSlur = false;
	$durList = explode(',',$dur);
	$k = array_search("Slur", $durList);
	if (is_integer($k)) {
		$hasSlur = true;
		if (!$slurActive) unset($durList[$k]);
		}
	else if ($slurActive) $durList[] = "Slur";
	//
	$slurActive = $hasSlur;

	return implode(',',$durList);
}

function RetrogradeNotePosElements($pos)
{
	global $runningTiedPositions;
	$thisTiedPositions = array();

	$posList = explode(',',$pos);
	$newposList = array();
	foreach ($posList as $n) {
		$staffpos = GetStaffPos($n);

		if (strpos($n,"^") !== FALSE) {
			$thisTiedPositions[] = $staffpos;
			$n = str_replace("^","",$n);
			}

		$k = array_search($staffpos, $runningTiedPositions, true);
		if (is_integer($k)) {
			$n .= "^";
			unset($runningTiedPositions[$k]);
			}
		
		$newposList[] = $n;
		}

	$runningTiedPositions = array_merge($runningTiedPositions,$thisTiedPositions);

	return implode(',',$newposList);
}

// ----------------------------------------------------------------------------
// P R O C E S S I N G
//
//We will now perform a fancy version of "array_reverse($clip->Items)" that accounts for some extra notational needs
$new_ntnclip = array();
foreach ($clip->Items as $c) {
	$itemType = GetNtnItemType($c);

	if ($itemType === "DynamicVariance") {
		$c = preg_replace('/(?<=\|Style:)\s*(Crescendo|Decrescendo|Diminuendo)/e','("$1" === "Crescendo") ? "Decrescendo" : "Crescendo"',$c);
		}
	else if ($itemType === "TempoVariance") {
		// swap around Accelerando and Ritardando...others are left as an exercise
		$c = preg_replace('/(?<=\|Style:)\s*(Accelerando|Ritardando)/e','("$1" === "Accelerando") ? "Ritardando" : "Accelerando"',$c);
		}
	else if (in_array($itemType,array('Note','Chord','Rest','RestChord'))) {
		// The boundary notes of a beam or triplet need to be reversed
		$c = preg_replace('/(?<=Beam=|Triplet=)(First|End)/e','("$1" === "End") ? "First" : "End"',$c);

		// Note ties need to be reversed...accidental assignments should be reversed too, but is comnplicated 
		// enough that a force of accidentals inside NWC2 is a better option
		$c = preg_replace('/(?<=\|Pos:|\|Pos2:)\s*([^\|\r\n]+)/e','RetrogradeNotePosElements("$1")',$c);

		// Slurs need to be reversed
		$c = preg_replace('/(?<=\|Dur:)\s*([^\|\r\n]+)/e','RetrogradeDurElements("$1")',$c);

		// Change hairpin direction around
		$c = preg_replace('/(Crescendo|Diminuendo)/e','("$1" === "Crescendo") ? "Diminuendo" : "Crescendo"',$c);
		}
	else if (in_array($itemType,array('Bar','Ending','Flow'))) {
		// There is no easy way to retrograde these items, so we queue them in barflow zones, and reinstate them later in the
		// retrograded work.
		$this_c = $c;
		$c = false;

		if ($previousItemType != "Bar") {
			$currentBarFlowZone++;
			$barFlowZones[$currentBarFlowZone] = array();
			$c = "|Bar\n";
			}

		array_push($barFlowZones[$currentBarFlowZone],$this_c);
		}

	if ($c) {
		// Recalc item type just in case it was changed in above
		$itemType = GetNtnItemType($c);
		//
		if (isset($pending_sigs[$itemType])) {
			if ($pending_sigs[$itemType]) array_unshift($new_ntnclip,$pending_sigs[$itemType]);
			$pending_sigs[$itemType] = $c;
			}
		else if (preg_match('/(?<!\\\)\|Placement\:AtNextNote/',$c)) $notebarDecorationQueue[] = $c;
		else array_unshift($new_ntnclip,$c);

		// Check to see if the notebarDecorationQueue needs to be flushed
		if (count($notebarDecorationQueue) && in_array($itemType,array('Bar','Note','Chord','Rest','RestChord'))) {
			foreach ($notebarDecorationQueue as $c_decoration) array_unshift($new_ntnclip,$c_decoration);
			$notebarDecorationQueue = array();
			}

		$previousItemType = $itemType;
		}
	}

foreach ($notebarDecorationQueue as $c_decoration) array_unshift($new_ntnclip,$c_decoration);
foreach ($pending_sigs as $c) if ($c) array_unshift($new_ntnclip,$c);

echo $clip->GetClipHeader()."\n";
//
$currentBarFlowZone = 1;
foreach ($new_ntnclip as $c) {
	if (trim($c) == "|Bar") echo implode('',$barFlowZones[$currentBarFlowZone++]);
	else echo $c;
	}
//
echo NWC2_ENDCLIP."\n";

// Uncomment this line for debugging the STDIN,STDOUT, and STDERR files in your Windows temp folder.
//fputs(STDERR,"Debug prompt: Check your temp folder files now.\n");

// A return code of 0 is required to signal success back to NWC2.
exit(NWC2RC_SUCCESS);

?>