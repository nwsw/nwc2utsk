<?
/*******************************************************************************
nwsw_ShapeNotes.php Version 1.02

This script will will automatically convert all notes in the file, or selection,
to use shape notes. It relies on the proper tonic to be set for each key signature
change.

Recommended command line options:
nwsw_ShapeNotes.php "/mode=<PROMPT:Mode:=|7-shape|4-shape>"

Recommended NWC Options: 
	[X] File Text
	[X] Compress Input

Also Supports:
	[X] Clip Text

Optional command line arguments:
"/skip=<PROMPT:Skip:=*xXzy>"
	Defines the existing note heads that should be ignored during shape assignment


Copyright © 2010 by NoteWorthy Software, Inc.
All Rights Reserved

*******************************************************************************/
require_once("lib/nwc2clips.inc");

if (NWC2ClipLibVersion() < "2.491") trigger_error("This tool requires the latest release of the starter kit library.",E_USER_ERROR);

$SHAPE_MODE = "7-shape";
$NOTEHEAD_IGNORE_LIST = "xXzy";
//
foreach ($argv as $k => $v) {
	if (preg_match('/^\/mode\=(.*)$/i',$v,$m)) $SHAPE_MODE = $m[1];
	if (preg_match('/^\/skip\=(.*)$/i',$v,$m)) $NOTEHEAD_IGNORE_LIST = $m[1];
	}

$AllShapeNotes = array("4-shape" => "dfgdfgc", "7-shape" => "abcdfgh");
$ObjectsWithNotes = array('Note','Chord','RestChord');
$AllNotePosTags = array("Pos","Pos2");
define("STEM_LEFT",0);
define("STEM_RIGHT",1);

$zin = gzopen('php://stdin','rb');
$zout = gzopen('php://stdout','wb');

$PlayContext = new NWC2PlayContext();
$FileMode = false;
$HiddenStaff = false;

while (!gzeof($zin)) {
	$l = gzgets($zin, 32000);

	if (!preg_match('/^\|/',$l)) {
		gzwrite($zout,$l);
		if (preg_match('/^\!NoteWorthyComposer\(([0-9\.]+)/',$l,$m)) $FileMode = $m[1];
		continue;
		}
		
	if (preg_match('/^\|AddStaff/',$l)) {
		unset($PlayContext);
		$PlayContext = new NWC2PlayContext();
		$HiddenStaff = false;
		gzwrite($zout,$l);
		continue;
		}

	if (preg_match('/^\|StaffProperties\|/',$l) && preg_match('/\|Visible:\s*([Y,N])/',$l,$m)) $HiddenStaff = ($m[1] == "N");
	if ($HiddenStaff) {
		gzwrite($zout,$l);
		continue;
		}

	$o = new NWC2ClipItemWithPitchPos($l,!$FileMode);

	if (!$FileMode && $o->IsContextInfo()) {
		$PlayContext->UpdateContext($o);
		continue;
		}

	if (in_array($o->GetObjType(),$ObjectsWithNotes)) {
		$stemDir = CalcMainStemDir($o);
		$ischanged = false;

		foreach ($AllNotePosTags as $pos_tag) {
			if (empty($o->PitchPos[$pos_tag])) continue;

			$PitchPos = &$o->PitchPos[$pos_tag];

			$tagStemDir = ($pos_tag == "Pos") ? $stemDir : (($stemDir+1)%2);

			$weakSideStemPositions = array();
			$numNotes = count($PitchPos);
			$priornpp = false;
			if ($tagStemDir == STEM_LEFT) end($PitchPos);
			else reset($PitchPos);
			while ($numNotes--) {
				if ($priornpp) {
					if (abs($priornpp->Position - current($PitchPos)->Position) == 1) $weakSideStemPositions[] = current($PitchPos)->Position;
					}
			  $priornpp = current($PitchPos);
				if ($tagStemDir == STEM_LEFT) prev($PitchPos);
				else next($PitchPos);
				}

			foreach ($PitchPos as &$npp) {
				if (in_array($npp->Position,$weakSideStemPositions)) $tagStemDir++;
				if (AssignShapeNote(($tagStemDir % 2),$npp)) $ischanged = true;
				}
			}

		if ($ischanged) {
			$o->ReconstructPosOpts();
			$l = $o->ReconstructClipText()."\n";
			}
		}

	$PlayContext->UpdateContext($o);
	gzwrite($zout,$l);
	}

gzclose($zout);
gzclose($zin);

exit(NWC2RC_SUCCESS);

// --------------------------------------
function AssignShapeNote($stemSide, &$notepitchObj)
{
	global $PlayContext,$AllShapeNotes,$SHAPE_MODE,$NOTEHEAD_IGNORE_LIST;

	if ($notepitchObj->Notehead && strstr($NOTEHEAD_IGNORE_LIST,$notepitchObj->Notehead)) return false;

	$pitchName = $PlayContext->GetNotePitchName($notepitchObj);
	$shapeMap = $AllShapeNotes[$SHAPE_MODE];
	$tonicNoteNum = NWC2NotePitchPos::$NoteNamesKey[$PlayContext->KeyTonic];
	$pitchNum = NWC2NotePitchPos::$NoteNamesKey[$pitchName];

	$shape = substr($shapeMap,($pitchNum+7-$tonicNoteNum)%7,1);
	if (($shape == 'd') && ($stemSide == STEM_LEFT)) $shape = 'e';

	$changed = ($notepitchObj->Notehead != $shape);
	$notepitchObj->Notehead = $shape;
	return $changed;
}

function CalcMainStemDir($o)
{
	$objOpts = $o->GetTaggedOptAsArray('Opts',array());
	if (!empty($o->Opts['Opts']['Stem'])) return ($o->Opts['Opts']['Stem'] == "Down") ? STEM_LEFT : STEM_RIGHT;

	// Calculate the default stem direction
	$pplist = $o->PitchPos['Pos'];
	if (!count($pplist)) return 0;

	$staffpos1 = $pplist[0]->Position;
	$staffpos2 = end($pplist)->Position;
	$staffpos = max($staffpos1,$staffpos2) + min($staffpos1,$staffpos2);

	return ($staffpos >= 0) ? STEM_LEFT : STEM_RIGHT;
}

?>