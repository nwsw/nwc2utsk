<?php
/*******************************************************************************
nwswStatisticsReport.php Version 2.0

This script outputs a report showing statistics about the clip.
It counts notes, chords, bar lines, etc. and outputs the counts.

Copyright © 2011 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2011-12-19] Version 2.0: Add new NWC 2.5 objects
[2006-05-12] Version 1.01: Code format cleanup
[2004-10-28] Version 1.00: Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

$clip = new NWC2Clip('php://stdin');

$typeCounts = array(
'Clef' => 0,
'Key' => 0,
'TimeSig' => 0,
'Bar' => 0,
'RestMultiBar' => 0,
'Note' => 0,
'Chord' => 0,
'RestChord' => 0,
'Rest' => 0,
'Dynamic' => 0,
'Spacer' => 0,
'Boundary' => 0
);

foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item);

	$oType = $o->GetObjType();
	if (!isset($typeCounts[$oType])) $typeCounts[$oType] = 1;
	else $typeCounts[$oType]++;

	unset($o);
	}

echo "Statistics Report:\n\n";
echo sprintf("%5d Total Items\n",count($clip->Items));
foreach ($typeCounts as $k => $v) echo sprintf("%5d $k\n",$v);

fputs(STDERR,"The STDOUT file contains the report.");
exit(NWC2RC_REPORT);
?>