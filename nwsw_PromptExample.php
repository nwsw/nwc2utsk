<?php
/*******************************************************************************
nwsw_PromptExample.php Version 1.01

This script demonstrates the prompting mechanism in NWC2 User Tools.

Copyright © 2006 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2006-05-12] Version 1.01: Code format cleanup
[2004-12-12] Version 1.00: Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

$clip = new NWC2Clip('php://stdin');

$raw_argv = print_r($argv,true);

echo <<<___EOTEXT
This script demonstrates one method for passing command line
arguments into a user tool. This demonstration accepts three 
options, each of which are prompted on the scripts behalf 
by NWC2.

The built-in argc and argv variables are set as follows:
\$argc = $argc
\$argv = $raw_argv

The result of the example processing of the options is 
shown below. To see how this was done, simply look at
the source for this script.
___EOTEXT;

// Below is an example of one technique for evaluating the arguments passed 
// to the script
$opts = array();
foreach ($argv as $k => $v) {
	if (!$k) continue;

	if (preg_match('/^\/(opt[1-3])\=(.*)$/',$v,$m)) {
		$optname = $m[1];
		$optvalue = $m[2];
		$opts[$optname] = $optvalue;
		}
	}

if (isset($opts["opt1"])) {
	echo "\nOption 1 is set with the following data: $opts[opt1]\n";
	}

if (isset($opts["opt2"])) {
	echo "\nOption 2 is set with the following data: $opts[opt2]\n";
	}

if (isset($opts["opt3"])) {
	echo "\nOption 3 is set with the following data: $opts[opt3]\n";
	}

exit(NWC2RC_REPORT);
?>