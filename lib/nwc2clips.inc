<?php
function NWC2ClipLibVersion() {return 1.50;}
/*******************************************************************************
This is a support API for interfacing to NWC2 clip text. A forum dedicated to 
NWC2 user tools can be found here:

NoteWorthy Composer > User Tools
http://my.noteworthysoftware.com/?board=7.0

Copyright © 2009 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2009-10-02] Version 1.50: Migrate to php5 and add note name script, support for web scripts, and NWC2PlayContext class
[2007-01-03] Version 1.22: Fixed bug in function ReconstructClipText
[2006-09-05] Version 1.21: Library classes moved into own files
[2006-05-12] Version 1.20: Clean up source formatting
[2005-09-12] Version 1.12: Fix ReconstructClipText for Instrument names containing quotes
[2004-12-01] Version 1.10: Enhancements to allow for empty clips
[2004-10-27] Version 1.00: Initial release
*******************************************************************************/

if (!function_exists('nw_aafield')) {
function nw_aafield(&$a,$key,$fv="") {if (isset($a[$key])) return $a[$key]; return $fv;}
}

function NWC2ErrorHandler($errno, $errstr, $errfile, $errline)
{
	$errtags = array(E_USER_ERROR => "Error",E_USER_WARNING => "Warning");
	$errtag = nw_aafield($errtags,$errno,"Note");

	fputs(STDERR,"$errtag: [in $errfile, at line $errline]\n");
	if ($errstr) fputs(STDERR,"--> $errstr\n");

	if ($errno == E_USER_ERROR) exit(1);
}

// Set an error handler that directs most run-time errors to STDERR. If "html_errors" is enabled, 
// then it is assumed that the web developer is handling errors via their htaccess (Apache) file
// or other similar web server function
if (!ini_get("html_errors")) set_error_handler("NWC2ErrorHandler");
//
// Require PHP 5.2 as a base line for all scripts
if (phpversion() < "5.2") trigger_error("The nwc2clips library requires PHP version 5.2 or later. You are using version ".phpversion()."\n",E_USER_ERROR);

define("NWC2_STARTCLIP","!NoteWorthyComposerClip");
define("NWC2_ENDCLIP","!NoteWorthyComposerClip-End");
//
// Return codes
define("NWC2RC_SUCCESS",0); // Standard output is processed..contents in standard error is presented as a warning
define("NWC2RC_ERROR",1);   // Standard error is shown to the user
define("NWC2RC_REPORT",99); // Standard output is shown to the user

// ----------------------------------------------------------------
//
define("NWC2OPT_RAW",0);
define("NWC2OPT_TEXT",1);
define("NWC2OPT_LIST",2);
define("NWC2OPT_ASSOCIATIVE",3);
//
function NWC2ClassifyOptTag($ObjType,$Tag)
{
	if (in_array($Tag, array("Opts","Dur","Dur2","Endings"))) return NWC2OPT_ASSOCIATIVE;
	if (($Tag == "Signature") && ($ObjType == "Key")) return NWC2OPT_ASSOCIATIVE;
	if (in_array($Tag,array("Text","Name"))) return NWC2OPT_TEXT;
	if ($Tag == "DynVel") return NWC2OPT_LIST;
	if (in_array($ObjType,array("Chord","RestChord")) && in_array($Tag,array("Pos","Pos2"))) return NWC2OPT_LIST;
	return NWC2OPT_RAW;
}

include("obj_NWC2Clip.inc");
include("obj_NWC2ClipItem.inc");
include("obj_NWC2NotePitchPos.inc");
include("obj_NWC2PlayContext.inc");
?>