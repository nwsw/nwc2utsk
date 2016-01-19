<?php
require_once("lib/nwc2clips.inc");

$clip = new NWC2Clip('php://stdin');

foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item);

	if ($o->IsContextInfo()) continue;

	$origLine = trim($item);
	$newLine = $o->ReconstructClipText();

	if ($newLine != $origLine) echo "Ref: $origLine\nNew: $newLine\n\n";
	}

$usermsg = <<<__EOMSG
See the standard output file for a comparison of the library 
output to the input clip.
__EOMSG;
//
fputs(STDERR,$usermsg);

exit(NWC2RC_REPORT);
?>