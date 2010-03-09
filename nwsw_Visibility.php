<?php
/*******************************************************************************
nwsw_Visibility Version 1.01

This script enables fast alteration of the visibility property for selected
items.

Copyright © 2007 by NoteWorthy Software, Inc.
All Rights Reserved

History:
[2007-01-03] Version 1.01 - Inclusion in starter kit
[2006-07-11] Version 1.00 - Initial release
*******************************************************************************/
//
//AdvisoryInvocation:"/visibility=<PROMPT:Set Visibility to:=|Default|Always|TopStaff|Never|>" "/skip=<PROMPT:Skip:=|None|Bar|Bar,Text|Bar,Text,Flow,Ending|>"

require_once("lib/nwc2clips.inc");

$clip = new NWC2Clip('php://stdin');

$opts = array('visibility' => 'hide','skip' => 'all');
foreach ($argv as $k => $v) {
	if (!$k) continue;

	if (preg_match('/^\/([a-z]+)\=(.*)$/',$v,$m)) {
		$optname = $m[1];
		$optvalue = $m[2];
		$opts[strtolower($optname)] = $optvalue;
		}
	}

$opts['skip'] = strtolower($opts['skip']);
$skiplist = explode(',',$opts['skip']);

echo $clip->GetClipHeader()."\n";
//
foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item);
	//
	$skipit = false;
	//
	if ($opts['skip'] == 'all') $skipit = true;
	else if ($opts['skip'] != 'none') $skipit = in_array(strtolower($o->GetObjType()), $skiplist);
	//
	if ($skipit) {
		echo $item;
		continue;
		}

	$o->Opts['Visibility'] = $opts['visibility'];
	echo $o->ReconstructClipText()."\n";
	}

echo NWC2_ENDCLIP."\n";
exit(NWC2RC_SUCCESS);
?>