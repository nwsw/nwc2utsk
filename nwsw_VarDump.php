<?php
/*******************************************************************************
nwswVarDump.php Version 1.5

This script outputs a report that shows the object breakdown when using 
the NWC2Clip and NWC2ClipItem objects to process NWC2 clip text. It uses
a special return code to signal NWC2 to display the results in STDOUT.

Copyright © 2006 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2010-02-27] Version 1.50: Add Fake item conversion for cleaner object report
[2006-05-12] Version 1.01: Code format cleanup
[2004-10-28] Version 1.00: Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

$clip = new NWC2Clip('php://stdin');

echo "A text representation of the NWC2ClipItem object for each ".
	"clip line is shown below. This was generated using ".
	"NWC2Clip Version ".NWC2ClipLibVersion().".\n\n";

foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item,true);
	echo preg_replace('/[\r\n]+/',"\n",print_r($o,true))."\n";
	unset($o);
	}

$usermsg = <<<__EOMSG
See the standard output file for a text representation of the notation 
clip after processing into the NWC2Clip and NWC2ClipItem objects.
__EOMSG;
//
fputs(STDERR,$usermsg);

exit(NWC2RC_REPORT);
?>