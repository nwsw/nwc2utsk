<?php
require_once("lib/nwc2clips.inc");

$clip = new NWC2Clip('php://stdin');

foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item);
	echo "Ref: $item";
	echo "New: ".$o->ReconstructClipText()."\n";
	unset($o);
	}

$usermsg = <<<__EOMSG
See the standard output file for a comparison of the library 
output to the input clip.
__EOMSG;
//
fputs(STDERR,$usermsg);

exit(NWC2RC_REPORT);
?>