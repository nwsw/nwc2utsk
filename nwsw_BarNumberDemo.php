<?
/*******************************************************************************
nwsw_BarNumberDemo.php Version 1.0

This script demonstrates how to work with bar numbers from within a user tool.
It adds a hidden text instruction to the start of every measure in a staff of
the form: "Bar #"

Copyright © 2010 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2010-02-27] Version 1.0: Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

$abortMsg = false;
//
$clip = new NWC2Clip('php://stdin');

if ($clip->Mode != "Single") trigger_error("Clip mode of {$clip->Mode} is not supported",E_USER_ERROR);
if ($clip->Version < "2.5") trigger_error("The notation clip uses an unknown format version ({$clip->Version}), so it may not be processed correctly",E_USER_NOTICE);

function IsPossibleBarNumber(&$o)
{
	return ($o->GetObjType() == "Text") && preg_match('/^Bar [0-9]+$/',$o->GetTaggedOpt('Text',""));
}

$pendingOutput = array();
$priorBarIndex = -1;
//
echo $clip->GetClipHeader()."\n";

$PlayContext = new NWC2PlayContext();
foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item,true);

	if ($o->IsContextInfo()) {
		$PlayContext->UpdateContext($o);
		continue;
		}

	$flushPending = false;

	if ($o->GetObjType() == "Bar") {
		if ($o->GetTaggedOpt('XBarCnt','N') == 'Y') $flushPending = true;
		else $priorBarIndex = count($pendingOutput);
		}
	else if (IsPossibleBarNumber($o)) {
	  if (count($pendingOutput) && ($priorBarIndex == (count($pendingOutput)-1))) {
		  // Remove this existing bar number. If a new one is needed, it will be added later.
			continue;
			}
		}
	else if (in_array($o->GetObjType(),array('Note','Chord','RestChord','Rest'))) {
		if (count($pendingOutput)) {
			if ($priorBarIndex >= 0) {
				$pendingOutput[$priorBarIndex] .= "|Text|Text:\"Bar ".$PlayContext->NextBarNum."\"|Font:StaffBold|Pos:6|Wide:Y|Placement:AsStaffSignature|Visibility:Never\n";
				}
			}

		$flushPending = true;
		}

	$PlayContext->UpdateContext($o);

	if ($flushPending) {
		if (count($pendingOutput)) {
			echo implode("",$pendingOutput);
			$pendingOutput = array();
			}
		$priorBarIndex = -1;
		echo $item;
		}
	else {
		$pendingOutput[] = $item;
		}
	}

if (count($pendingOutput)) echo implode("",$pendingOutput);
echo $clip->GetClipFooter()."\n";
exit(NWC2RC_SUCCESS);
?>