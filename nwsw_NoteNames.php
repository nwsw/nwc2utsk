<?
/*******************************************************************************
nwsw_NoteNames.php Version 2.00

This script will will automatically create text that includes all of the note 
names for the notes in the staff.

Note: In lyric mode, you should always select the entire staff so that the 
text is suitable for inclusion as a lyric.

Prompting for staff mode can be added with the following command line:
"/mode=inline" "/font=<PROMPT:Select Font:=|User 1|User 2|User 3|User 4|User 5|User 6>" "/staffpos=<PROMPT:Position:=#[-32,32]>"

Prompting for staff versus lyric mode can be added with the following command line:
"/mode=<PROMPT:Mode:=|inline|lyric>"

Copyright © 2010 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2010-07-05] Version 2.00: Support for NWC 2.5
[2010-02-27] Version 1.51: Enhanced to support new NWC 2.1 features
[2009-10-02] Version 1.50: Adapted for inclusion in the NWC2 Starter Kit Version 1.5
[2008-02-01] Version 1.00: Initial release for the web site
*******************************************************************************/
require_once("lib/nwc2clips.inc");

// NOTENAME_MODE can be inline or lyric
$NOTENAME_MODE = "inline";
//
// NOTENAME_FONT can be User [1-6]
$NOTENAME_FONT = "User 1";
//
// NOTENAME_POSITION can be a value [-30..30]
$NOTENAME_POSITION = 10;

foreach ($argv as $k => $v) {
	if (preg_match('/^\/mode\=(.*)$/i',$v,$m)) $NOTENAME_MODE = $m[1];
	else if (preg_match('/^\/font\=(.*)$/i',$v,$m)) $NOTENAME_FONT = $m[1];
	else if (preg_match('/^\/staffpos\=(.*)$/i',$v,$m)) $NOTENAME_POSITION = $m[1];
	}

$accmap = array("v"=>"bb","b"=>"b","n"=>"","#"=>"#","x"=>"x");

$useLyric = ($NOTENAME_MODE == "lyric");
$txtFont = str_replace(' ','',$NOTENAME_FONT);
$staffPos = @ intval($NOTENAME_POSITION);

$abortMsg = false;
$txtout = "";
//
$clip = new NWC2Clip('php://stdin');

if (!$useLyric) $txtout .= $clip->GetClipHeader()."\n";

if ($clip->Mode != "Single") trigger_error("Clip mode of {$clip->Mode} is not supported",E_USER_ERROR);

$barnotecnt = 0;

$PlayContext = new NWC2PlayContext();
foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item,true);

	if ($o->IsContextInfo()) {
		$PlayContext->UpdateContext($o);
		continue;
		}

	if (in_array($o->GetObjType(),array('Note','Chord','RestChord','Rest'))) {
		$notenameText = "";
		$lyricOpt = isset($o->Opts["Opts"]["Lyric"]) ? $o->Opts["Opts"]["Lyric"] : "Default";
		$isGrace = isset($o->Opts["Dur"]["Grace"]);
		$isInTie = false;
		$isAllInTie = true;

		for ($loop=0;$loop<2;$loop++) {
			$notes = $o->GetTaggedOptAsArray(($loop > 0) ? "Pos2" : "Pos",array());

			foreach ($notes as $i => $notepitchTxt) {
				$notepitchObj = new NWC2NotePitchPos($notepitchTxt);

				if (!$i && $notenameText) $notenameText .= ",";

				if ($PlayContext->IsTieReceiver($notepitchObj)) $isInTie = true;
				else $isAllInTie = false;

				$notename = $PlayContext->GetNotePitchName($notepitchObj);
				$noteacc = $PlayContext->GetNotePitchAccidental($notepitchObj);
				$noteacc = $accmap[$noteacc];

				$notenameText .= $notename.$noteacc;
				}
			}

		if ($useLyric) {
			$hasLyrics = !($isGrace || $isInTie || $PlayContext->Slur || ($o->GetObjType()=="Rest"));
			if ($lyricOpt == "Always") $hasLyrics = true;
			else if ($lyricOpt == "Never") $hasLyrics = false;

			if ($hasLyrics) {
				if ($barnotecnt) $txtout .= " ";
				$txtout .= $notenameText ? $notenameText : '-';
				//
				$barnotecnt++;
				}
			}
		else if ($notenameText && !$isAllInTie) {
			$txtout .= "|Text|Text:\"$notenameText\"|Font:$txtFont|Pos:$staffPos|Wide:Y|Justify:Center|Placement:AtNextNote\n";
			}
		}
	else if ($o->GetObjType() == "Bar") {
		if ($barnotecnt && $useLyric) $txtout .= "\n";
		$barnotecnt = 0;
		}

	if (!$useLyric) $txtout .= $item;
	$PlayContext->UpdateContext($o);
	}

if ($useLyric) {
	echo <<<___EOTEXT
LYRIC TEXT:
$txtout


INSTRUCTIONS:
The lyric text above is suitable for copying and pasting into a lyric line on this staff. 

Note: Note names for grace, tied, or slurred notes will not appear in the lyric text unless they have 
been configured to receive lyric text from Edit, Properties.

Instructions:
- Select and copy all of the text after the LYRIC TEXT label at the top of this box
- Press OK, then open the lyric editor and paste the contents into a new lyric line
___EOTEXT;

	exit(NWC2RC_REPORT);
	}

echo $txtout.NWC2_ENDCLIP."\n";
exit(NWC2RC_SUCCESS);
?>