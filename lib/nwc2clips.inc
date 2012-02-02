<?php
function NWC2ClipLibVersion() {return "2.5";}
/*******************************************************************************
This is a support API for interfacing to NWC2 clip text. A forum dedicated to 
NWC2 user tools can be found here:

NoteWorthy Composer > User Tools
http://my.noteworthysoftware.com/?board=7.0

Copyright © 2011 by NoteWorthy Software, Inc.
All Rights Reserved
*******************************************************************************/

if (!function_exists('nw_aafield')) {
function nw_aafield($a,$key,$fv="") {if (isset($a[$key])) return $a[$key]; return $fv;}
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
define("NWC2_STARTFILETXT","!NoteWorthyComposer");
define("NWC2_ENDFILETXT","!NoteWorthyComposer-End");
//
// Return codes
define("NWC2RC_SUCCESS",0); // Standard output is processed..contents in standard error is presented as a warning
define("NWC2RC_ERROR",1);   // Standard error is shown to the user
define("NWC2RC_REPORT",99); // Standard output is shown to the user

// Cmd flags set in the User Tool command line. Defiend here mostly for documentation purposes.
define("UTOOL_SEND_CLIPTXT",0);    // Selected notation clip text sent in STDIN (the default)
define("UTOOL_SEND_FILETXT",1);    // File nwctxt sent in STDIN
define("UTOOL_ACCEPTS_GZIP",2);    // The nwctxt/clip text in STDIN can be (optionally) gzip deflated
define("UTOOL_RETURNS_FILETXT",4); // Script sends a nwctxt file to STDOUT that replaces current file
define("UTOOL_LONG_TASK",8);       // Task may require a long time to finish
define("UTOOL_USES_PROMPTS",16);   // Prompts the user for input from within the script

// ----------------------------------------------------------------
//
define("NWCTXTLTYP_ERROR",0);
define("NWCTXTLTYP_FORMATHEADER",1);
define("NWCTXTLTYP_COMMENT",2);
define("NWCTXTLTYP_OBJECT",3);
//
function NWC2ClassifyLine($ln)
{
	switch ($ln[0]) {
		case '|': return NWCTXTLTYP_OBJECT;
		case '!': return NWCTXTLTYP_FORMATHEADER;
		case '#': return NWCTXTLTYP_COMMENT;
		}

	// Treat empty lines as comments
	if (!trim($ln)) return NWCTXTLTYP_COMMENT;

	return NWCTXTLTYP_ERROR;
}

// ----------------------------------------------------------------
//
function NWC2GetObjType($ln)	{return ($ln[0] == '|') ? substr($ln,1,strcspn($ln,"\r\n|\t",1)) : '';}

define("NWC2OBJTYP_ERROR",0);
define("NWC2OBJTYP_FILEPROPERTY",1);
define("NWC2OBJTYP_STAFFPROPERTY",2);
define("NWC2OBJTYP_STAFFLYRIC",3);
define("NWC2OBJTYP_STAFFNOTATION",4);
//
function NWC2ClassifyObjType($ObjType)
{
	if (!$ObjType) return NWC2OBJTYP_ERROR;
	$boundedOTyp = "|$ObjType|";
	// Speed optimization: Check for most common notation types first using strpos
	if (strpos("_|Note|Chord|Bar|Rest|Text|Dynamic|Clef|Key|TimeSig|",$boundedOTyp)) return NWC2OBJTYP_STAFFNOTATION;
	if (strpos("_|Locale|Editor|SongInfo|PgSetup|Font|PgMargins|",$boundedOTyp)) return NWC2OBJTYP_FILEPROPERTY;
	if (strpos("_|AddStaff|StaffProperties|StaffInstrument|",$boundedOTyp)) return NWC2OBJTYP_STAFFPROPERTY;
	if (substr($ObjType,0,5) == "Lyric") return NWC2OBJTYP_STAFFLYRIC;
	return NWC2OBJTYP_STAFFNOTATION;
}

// ----------------------------------------------------------------
//
define("NWC2OPT_RAW",0);
define("NWC2OPT_TEXT",1);
define("NWC2OPT_LIST",2);
define("NWC2OPT_ASSOCIATIVE",3);
//
function NWC2ClassifyOptTag($ObjType,$Tag)
{
	// For speed, classify all possible note tags first
	$b_Tag = "|$Tag|";

	if (strpos("_|Opts|Dur|Dur2|Endings|",$b_Tag)) return NWC2OPT_ASSOCIATIVE;

	if (strpos("_|Pos|Pos2|",$b_Tag)) {
		if (($ObjType == "Chord") || ($ObjType == "RestChord")) return NWC2OPT_LIST;
		return NWC2OPT_RAW;
		}

	if (strpos("_|Visibility|Color|",$b_Tag)) return NWC2OPT_RAW;

	if (strpos("_|Text|Name|Label|Group|Typeface|",$b_Tag) || ($ObjType == "SongInfo")) return NWC2OPT_TEXT;

	if ($Tag == "DynVel") return NWC2OPT_LIST;

	if (strpos("_|WithNextStaff|WhenHidden|",$b_Tag)) return NWC2OPT_LIST;

	if (($Tag == "Signature") && ($ObjType == "Key")) return NWC2OPT_ASSOCIATIVE;
	if (($Tag == "Bar") && ($ObjType == "Context")) return NWC2OPT_LIST;
	return NWC2OPT_RAW;
}

// ----------------------------------------------------------------
//
include("obj_NWC2Clip.inc");
include("obj_NWC2ClipItem.inc");
include("obj_NWC2NotePitchPos.inc");
include("obj_NWC2PlayContext.inc");
?>