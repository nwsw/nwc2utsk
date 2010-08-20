<?php
require_once("lib/nwc2clips.inc");

$do_warning = false;
$numchanges = 0;

$zin = gzopen('php://stdin','rb');
$zout = gzopen('php://stdout','wb');
while (!gzeof($zin)) {
	$l = gzgets($zin, 32000);

	if (preg_match('/^\|Spacer/',$l)) {
		$spacer = new NWC2ClipItem($l);
		if (!empty($spacer->Opts["Width"])) {
			if ($spacer->Opts["Width"] > 60) $do_warning = true;
			$spacer->Opts["Width"] *= 25;
			$l = $spacer->ReconstructClipText()."\r\n";
			$numchanges++;
			}
		}

	gzwrite($zout,$l);
	}
gzclose($zout);
gzclose($zin);

if (!$numchanges) {
	fputs(STDERR,"No spacers were found.");
	exit(NWC2RC_ERROR);
	}

if ($do_warning) {
	fputs(STDERR, "A spacer larger than 15 note head widths was encountered in this file. This indicates that this file has already been upgraded to ".
		"the new spacer width resolution and you should not apply the upgrade conversion again.\n\n".
		"Is is recommneded that you press the Cancel button now.");
	}

exit(NWC2RC_SUCCESS);
?>