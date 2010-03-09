<?php
function NWC2ClipLibVersion() {return 1.22;}
/*******************************************************************************
This is a support API for interfacing to NWC2 clip text.

Copyright © 2006 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2007-01-03] Version 1.22: Fixed bug in function ReconstructClipText
[2006-09-05] Version 1.21: Library classes moved into own files
[2006-05-12] Version 1.20: Clean up source formatting
[2005-09-12] Version 1.12: Fix ReconstructClipText for Instrument names containing quotes
[2004-12-01] Version 1.10: Enhancements to allow for empty clips
[2004-10-27] Version 1.00: Initial release
*******************************************************************************/

// Require PHP 4.3 as a base line for all scripts
if (phpversion() < "4.3") {
	fputs(STDERR,"Error: The nwc2phplib library requires PHP version 4.3 or later. You are using version ".phpversion()."\n");
	exit(1);
	}

// Set an error handler that directs most run-time errors via STDERR
set_error_handler("NWC2ErrorHandler");
//
function NWC2ErrorHandler($errno, $errstr, $errfile, $errline) 
{
	$errtags = array(E_USER_ERROR => "Error",E_USER_WARNING => "Warning");
	$errtag = isset($errtags[$errno]) ? $errtags[$errno] : "Note";

	fputs(STDERR,"$errtag: [in $errfile, at line $errline]\n");
	if ($errstr) fputs(STDERR,"--> $errstr\n");

	if ($errno == E_USER_ERROR) exit(1);
}

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
?>