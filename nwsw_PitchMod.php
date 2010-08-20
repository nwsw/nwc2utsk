<?php
/*******************************************************************************
nwsw_PitchMod.php Version 1.0

Inspired by Andrew Purdam's Global Mod script, this script allows you to 
conditionally modify note pitch/position information.

Copyright © 2010 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2010-03-15] Version 1.0:  Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

define("PITCHMOD_VERSION","1.0");

if (($argc < 1) || strstr('help',strtolower($argv[1]))) doHelp();

array_shift($argv);
$x = implode(" ",$argv);
if (preg_match('/\s+-r\s*$/',$x,$m)) {
	define("DO_GEN_REPORT",1);
	$x = substr($x,0,-strlen($m[0]));
	}

$regexList = array();
while (preg_match('~^([^/]+)/([^ ]+)~',$x,$m)) {
	$regex = $m[1];
	$actions = explode(',',strtolower($m[2]));
	$x = trim(substr($x,strlen($m[0])));

	if (preg_match('/^\S+:/',$regex)) $regex = '/'.$regex.'/';
	else $regex = '/^'.$regex.'/';

	$eval_actions = array();
	foreach ($actions as $a) $eval_actions[] = BuildEvalAction($a);

	$regexList[$regex] = $eval_actions;
	doReport("$regex\t-> ".implode(';',$eval_actions)."\n");
	}

doReport("\nProcessing input\n");
$clip = new NWC2Clip('php://stdin');
echo $clip->GetClipHeader()."\n";
$PlayContext = new NWC2PlayContext();
foreach ($clip->Items as $item) {
	$o = new NWC2ClipItem($item,true);

	if ($o->IsContextInfo()) {
		$PlayContext->UpdateContext($o);
		continue;
		}

	$isChanged = false;

	if (in_array($o->GetObjType(),array('Note','Chord','RestChord'))) {
		$o_new = new NWC2ClipItem($item,true);

		for ($loop=0;$loop<2;$loop++) {
			$Opt_inc = $loop ? "2" : "";
			$OptName_Pos = "Pos".$Opt_inc;
			$OptName_Dur = "Dur".$Opt_inc;

			$pitchpos = &$o_new->GetTaggedOpt($OptName_Pos);
			if (is_string($pitchpos)) ProcessNote($pitchpos);
			else if (is_array($pitchpos)) foreach ($pitchpos as &$notepitchTxt) ProcessNote($notepitchTxt);
			}

		if ($isChanged) echo $o_new->ReconstructClipText()."\n";
		}

	if (!$isChanged) echo $item;

	$PlayContext->UpdateContext($o);
	}

echo NWC2_ENDCLIP."\n";
//exit(NWC2RC_REPORT);
//exit(NWC2RC_ERROR);
exit(NWC2RC_SUCCESS);

function ProcessNote(&$notepitchTxt)
{
	global $PlayContext,$o,$OptName_Pos,$OptName_Dur,$isChanged,$regexList;
	static $accmap = array("v"=>"bb","b"=>"b","n"=>"n","#"=>"#","x"=>"x");

	$notepitchObj = new NWC2NotePitchPos($notepitchTxt);

	$notename = $PlayContext->GetNotePitchName($notepitchObj);
	$noteacc = $accmap[$PlayContext->GetNotePitchAccidental($notepitchObj)];
	$noteoctave = $PlayContext->GetScientificPitchOctave($notepitchObj);
	$notemidipitch = $PlayContext->GetNoteMidiPitch($notepitchObj);
	$fullNotepitchTxt = $notepitchTxt;
	if (!strstr($notepitchTxt,'!')) $fullNotepitchTxt .= '!0';
	$durTxt = implode(',',array_keys($o->GetTaggedOptAsArray($OptName_Dur,array())));

	$notenameText = $notename.$noteacc.$noteoctave."\tt:".$fullNotepitchTxt."\tk:".$notemidipitch."\td:$durTxt,$OptName_Pos\to:".$o->GetObjType();

	foreach ($regexList as $regex => $cmdlist) {
		if (preg_match($regex,$notenameText)) {
			doReport("MATCH: ".$notenameText."\n");
			foreach ($cmdlist as $cmd) ProcessCmd($cmd,$notepitchObj);
			$notepitchTxt = $notepitchObj->ReconstructClipText();
			$isChanged = true;
			return true;
			}
		}

	doReport("NOMATCH: $notenameText\n");
	return false;
}

function BuildEvalAction($cmd)
{
	static $mapOptNames = array('acc' => 'Accidental','pos' => 'Position','head' => 'Notehead', 'col' => 'Color');
	if (!preg_match('/^([a-z]+)(\=|\-\=|\+\=)(.+)$/',$cmd,$m)) doHelp("Invalid action string: $cmd\n");

	list($ignore,$option,$op,$val) = $m;

	$fldName = nw_aafield($mapOptNames,$option,false);
	if (!$fldName) doHelp("Invalid option: $option\n");

	if (in_array($op,array('+=','-='))) {
		if (!in_array($option,array('pos','col'))) doHelp("Invalid cmd: $cmd\n");
		$val = intval($val);
		}
	else {
		if (in_array($option,array('pos','col'))) {
			$val = intval($val);
			}
		else {
			if ($option == "acc") {
				static $accmap = array("v"=>"v","bb"=>"v","b"=>"b","n"=>"n","#"=>"#","x"=>"x");
				$val = nw_aafield($accmap,$val,$val);
				}

			$val = "'$val'";
			}
		}

	return '$npObj->'.$fldName.$op.$val.';';
}

function ProcessCmd($cmd,&$npObj)
{
	eval($cmd);
}

function doHelp($s="")
{
	if ($s) echo $s."\n";

	echo "Pitch Mod, Version ".PITCHMOD_VERSION."\n\n";

	echo <<<__EOHELPTEXT
Usage: nwsw_PitchMod.php <note-pitch-change-expression> [-r]

Where:
A -r switch at the end of the expression will trigger a report that shows all of the note pitch matching text in the STDERR output. The expression will also be processed as usual if you press OK after reviewing the report.

<note-pitch-change-expression> is one or more pairs of <note-match>/<action-list>. Some examples are shown below (in these examples, a match report is generated because the -r switch is added):

Example 1: Cb/pos-=1,acc=n -r
(changes all Cb to B natural)

Example 2: A#/pos+=1,acc=b -r
(changes A# to Bb)

Example 3: C/col=0 D/col=1 E/col=2 F/col=3 G/col=4 A/col=5 B/col=6 -r
(assigns a unique color highlight to each note in the octave)

Example 4: d:.*Pos2/col=7 -r
(assigns "Highlight 7" to each passive voice notehead)


In the mod expression, <note-match> is a regaular expression that will match against a single line of note pitch text. Each notehead in the clip is expanded to a full description string, as described later in this help text.

<action-list> is one or more actions, separated by a comma, like <action>,<action>

<action> is either <opt>=<val>, <numopt>+=<val>, or <numopt>-=<val>

<opt>=<val> sets option to value
<numopt>+=<val> adds value to option
<numopt>-=<val> subtracts value from option

<numopt> can be "col" or "pos" (see below)

<opt> can be col, pos, acc, or head:
"col":  Sets the numeric highlight color for the notehead
"pos":  Used to change the note position (beware of chord conflicts)
"acc":  Sets the accidental to one of bb,b,n,#,x
"head": Sets the notehead to one of o,x,X,z

<val> cannot be negative

When creating a match expression for <note-pitch-match>, you can match against a variety of information elements for each notehead. Each notehead is expanded into a full line of descriptive text that you can match against. Each line is of the following form:

<NN><O>\tt:<POSTXT>\tk:<KEY>\td:<DUR>\to:<OBJTYP>

Where:
<NN> is a note name, like Cb, A#, or Abb
<O> is the MIDI octave for the note
<POSTXT> is the full notehead pos text, including !0 for default color and "o" for standard notehead
<KEY> is the midi note that will be played for this notehead
<DUR> is the duration spec that applies to this notehead, including "Pos" or "Pos2" to indicate split chord voice
<OBJTYP> is either Note, Chord, or RestChord
__EOHELPTEXT;

	exit(NWC2RC_REPORT);
}

function doReport($s)
{
	if (defined("DO_GEN_REPORT"))	fputs(STDERR, $s);
}

?>