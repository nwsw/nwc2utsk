<?php

// G L O B A L _ M O D . P H P
//
// Author: Copyright (c)2004 Andrew Purdam (ADP)
//
//
//	Date		Ver	Who	Comments
//	 9/11/04	1.0	ADP	1st release
//	17/11/04 1.0.1 ADP   Added help msg
//  2/12/04 1.0.2 ADP   Added message for empty selection
//  11/3/05 1.1 ADP Added a "negative" (using '!')
//  2006-08-11 1.2 nwsw Added support for escaping commas in the "<comparison>" section, small code clean up
//  2009-10-11 1.5 nwsw Clean up PHP5 warnings
//  2010-07-14 2.0 nwsw Support usage against entire file text 
//  2011-12-19 2.1 nwsw Support most nwctxt objects 
//  2015-09-15 2.75 nwsw Support User.<objtype> expression for <type>
// 
// For documentation, see help_msg_and_exit (down a few lines)

require_once("lib/nwc2clips.inc");

function abort($string)
{
	fputs(STDERR, $string) ;
	exit(NWC2RC_ERROR) ;
}

$final_exit_value = NWC2RC_SUCCESS ;
function report($string)
{
	global $final_exit_value ;
	fputs(STDERR, $string) ;
	$final_exit_value = NWC2RC_REPORT ;
}

function help_msg_and_exit()
{
echo <<<__EOHELPTEXT
Usage: php global_mod.php <type>,<comparison>,... <action>

Where:
<type> is eg Note, Chord, Clef, Key, TimeSig, Bar, RestChord, Rest, Dynamic, User, User:objtype etc
<comparison> is of form
	<opt>, meaning the named option is present or
	!<opt>, meaning the option is absent
	<opt>==<value>, meaning that <opt> is the given value,
				or value is present if opt is an array. or
	<opt><=<value>, meaning that the single value of opt is
				LTE value, ditto >, <, >= and !=
NB: If <opt> is <opts>.<subopt> it will deal with subopt as a subtype of opt.
eg Dur.Dotted or Opts.VertOffset>=0

<action> is either
	DELETE	- delete the object
	<opt>=<val>, set option to value
	<opt>+=<val>, add value to option, also -=, *=, /=
Again, opt can be of the form <opt>.<subopt>

If a comma is included in your desired comparison, it must be prefixed by a backslash character. For example, "MPC,Pt1==0\,72" will search for an MPC item with a first controller value of "0,72" (the backslash prevents the comma from being interpreted as the start of a new <opt>).

examples:
To adjust all whole rests up two
	php global_mod.php Rest,Dur==Whole Opts.VertOffset+=2

or to modify all tempos to slow by 10%
	php global_mod.php Tempo Tempo*=0.9

or to hide all tempo markings
	php global_mod.php Tempo Visibility=Never
	
or to move all GuitarChord.ms objects to a new position
	php global_mod.php User:GuitarChord.ms Pos=12
	
Known issues: altering positions might not work with accidentals.
__EOHELPTEXT;

exit(NWC2RC_REPORT) ;
}

function inform($string) { fputs(STDERR, $string) ; }

function gm_getTaggedOpt($tag, &$o)		// eg tag might be Dur.DblDotted or Opts.VertOffset
{
	$opts = $o->GetOpts() ;
	foreach (explode(".",$tag) as $t) {
		if (!isset($opts[$t])) return FALSE ;
		$opts = $opts[$t] ;
	}
	return $opts ;
}

function gm_setTaggedOpt($tag, &$o, $val)		//	eg gm_setTaggedOpt("Opts.VertOffset", $o, "3")
{
	$opts =& $o->GetOpts() ;
	foreach (explode(".",$tag) as $t) {
		if (!isset($opts[$t])) $opts[$t] = "" ;
		$opts =& $opts[$t] ;
	}
	$opts = $val ;
}

// allowed comparisons
$gm_comparisons = array("<=", "<", "==", ">=", ">", "!=") ;

class gm_match 
{
	var $maintype = "" ;		// string, eg Note, Chord, Tempo
	var $subtype = "" ;		// string, eg ChordPlay.nw, GuitarChord.ms, etc.
	var $present = array( ) ;	// optional array of Opt names, which must be present in the Clip object for Matches($o) to return true
	var $comparisons = array( ) ;	// comparison, string representation of comparison eg "Pos" => "=="
	var $comparesTo = array( ) ;	// values to compare to, eg "Pos" => 30, or "Dur" => "Whole"

	function gm_match ($matchtext)
	{
		global $gm_comparisons;
	
		$matchlist = preg_split('/(?<!\\\)\,/', $matchtext) ;
		$typeexpression = explode(':', array_shift($matchlist), 2) ;
		$this->maintype = array_shift($typeexpression) ;
		if ($typeexpression) $this->subtype = array_shift($typeexpression) ;

		foreach( $matchlist as $match )	// go through remainder of match criteria
		{
			$match = str_replace('\\,',',',$match);
			foreach ($gm_comparisons as $comp) if (strstr($match, $comp))
			{
				list($optName,$compValue) = explode($comp, $match, 2) ;
				$this->comparisons[$optName] = $comp ;
				$this->comparesTo[$optName] = $compValue ;
				$match = "" ;
				break ;	// Don't try and match any other comparisons
			}
			if ($match != "") $this->present[$match] = "" ;
			if (strstr($match,"=")) 
				inform("Single = (assignment) found in comparison \"$match\".\nIf you intend to compare equality, use \"==\"\nI'll continue anyway.\n") ;
		}
	}
	
	function Matches($o) 	// true if this gm_match instance Matches the supplied NWCClipObject
	// to do so, the main type must match, if an option was named, it must be present, and if 
	// an option was named with a comparison, it must compare true. Any failures return false. It's a hard world.
	{
		if ($o->GetObjType() != $this->maintype) return FALSE ;
		if (!empty($this->subtype) && ($this->subtype != $o->GetUserObjType())) return FALSE ;

		foreach ($this->present as $presence => $val)   // check for matching presence or absence
			if ($presence{0} == '!') { if (gm_getTaggedOpt(substr($presence,1),$o)!==FALSE) return FALSE ; }
			else if (gm_getTaggedOpt($presence,$o)===FALSE) return FALSE ;
		foreach ($this->comparisons as $opt => $comp) // eg "Pos" => "<" or "Dur" => "==". $comparesTo has the value to compare to
		{
			$optVal = gm_getTaggedOpt($opt,$o);

			if ($optVal === FALSE) return FALSE ;	// getTaggedOpt might return 0 or FALSE, need to equate to type as well

			if (is_array($optVal)) 
			{ 
				if (count($optVal) != 1) return FALSE ; 
				
				$optVal = array_keys($optVal); 
				$optVal = $optVal[0];
			}

			$optVal = rtrim($optVal) ;
			switch ($comp) 
			{
				case "<=" : if (!($optVal <= $this->comparesTo[$opt])) return FALSE ;	break ;
				case "<"  : if (!($optVal  < $this->comparesTo[$opt])) return FALSE ;	break ;
				case "==" : if (!($optVal == $this->comparesTo[$opt])) return FALSE ;	break ;
				case ">=" : if (!($optVal >= $this->comparesTo[$opt])) return FALSE ;	break ;
				case ">"  :	if (!($optVal  > $this->comparesTo[$opt])) return FALSE ;	break ;
				case "!=" :	if (!($optVal != $this->comparesTo[$opt])) return FALSE ;	break ;
			}
		}
		// well, it didn't disqualify itself, so I'd better return TRUE
		return TRUE ;
	}
}

// possible action types
define("GM_DELETE","DELETE");
define("GM_SET","SET");
define("GM_MOD","MOD");

// possible GM_MOD actions
$gm_modActions = array("+=", "-=", "*=", "/=") ;

class gm_action 
{
	var $ActionType ;		// type of action GM_DELETE, GM_SET, GM_MOD
	var $ActionTarget ;	// name of target of action
	var $ActionMod ;		// GM_ADD, GM_SUB, GM_MUL, GM_DIV
	var $ActionValue ;	// Either the value to set, or the value to modify with ;

	function gm_action ($actiontext)
	{	
		global $gm_modActions;
		if ($actiontext == "DELETE") { $this->ActionType = GM_DELETE; return ; }

		foreach ($gm_modActions as $mod) if (strstr($actiontext, $mod))	// these are *=, /=, +=, -=
		{
			$this->ActionType = GM_MOD ;
			list($ModName,$ModValue) = explode($mod, $actiontext, 2) ;
			$this->ActionMod = $mod ;
			$this->ActionValue = $ModValue ;
			$this->ActionTarget = $ModName ;
			return ;	// Don't try and match any other comparisons
		}
		if (strstr($actiontext, "="))		// none of the above. Action is "="
		{
			$this->ActionType = GM_SET ;
			list($this->ActionTarget, $this->ActionValue) = explode("=", $actiontext, 2) ;
			return ;
		}
		abort("Couldn't understand the action \"$actiontext\". Use \"help\" for more help.") ;
	}
	
	function GetAction() { return $this->ActionType ; }
	
	function actUpon(&$o)
	{
		switch ($this->GetAction()) 
		{
			case GM_DELETE: break ;	// do nothing, as the action is to NOT PRINT
			case GM_MOD:
				$opts = gm_getTaggedOpt($this->ActionTarget,$o) ;
				if ($opts === FALSE && !(in_array($this->ActionMod, array("+=", "-="))))	// if value doesn't exist, can't modify
				{
					inform("Couldn't perform ".$this->ActionTarget.$this->ActionMod.$this->ActionValue." in " . $o->ReconstructClipText()."\n") ;
					break ; 
				}
				if ($opts == FALSE) 	// but we make an exception for += and -=, since 0 is often a default value for these
					$opts = 0;

				switch ($this->ActionMod)			// now we can do the action
				{
					case "+=" :	$opts += $this->ActionValue ;	break ;
					case "-=" :	$opts -= $this->ActionValue ;	break ;
					case "*=" :	$opts *= $this->ActionValue ;	break ;
					case "/=" :	$opts /= $this->ActionValue ;	break ;
				}
				$opts = (string) round($opts) ; // convert numbers back to string for correct interpretation by ReconstructClipText 
				gm_setTaggedOpt($this->ActionTarget,$o,$opts) ;
				break ;
			case GM_SET:
				gm_setTaggedOpt($this->ActionTarget,$o,$this->ActionValue) ;
				break ;
		}
	}
}

class gm_match_action_pair
{
	var $mymatch ;
	var $myaction ;
	
	function gm_match_action_pair($matchtext, $actiontext)
	{
		$this->mymatch = new gm_match($matchtext) ;
		$this->myaction = new gm_action($actiontext) ;
	}
	
	function Matches($o) {	return $this->mymatch->Matches($o) ;	}
	function GetAction() {	return $this->myaction->GetAction() ;	}
	function ActUpon(&$o)	{ $this->myaction->ActUpon(&$o) ; }
}

$map_array = array ( ) ;
// THE MAIN ACTION STARTS HERE
array_shift($argv) ;		// get rid of the first arg (script name)
if (!count($argv)) abort("Hey! We need somethin' to do here, y'know! Try something of the form <type>,<subtype>,... <action>\nor use \"help\" for more details.") ;
if ($argv[0] && $argv[0] == "help") help_msg_and_exit() ;
if (count($argv) % 2) abort("Parameters need to be a multiple of two. Each pair being <type>,<subtype>,... <action>.\nUse \"help\" for more help.") ;
while (count($argv))
{
	$Matches = array_shift($argv) ;
	$action = array_shift($argv) ;

	array_push($map_array, new gm_match_action_pair($Matches, $action)) ;
}

$nothingToDo = TRUE ;
$FileMode = FALSE ;

$zin = gzopen('php://stdin','rb');
$zout = gzopen('php://stdout','wb');

// should have at least one match, action pair
while (!gzeof($zin))
{
	$item = gzgets($zin, 32000);

	if (!preg_match('/^\|/',$item)) {
		gzwrite($zout,$item);
		if (preg_match('/^\!NoteWorthyComposer\(([0-9\.]+)/',$item,$m)) $FileMode = $m[1];
		continue;
		}

	if (preg_match('/^\|(AddStaff|Lyric)/',$item)) {
		// Structural objects are skipped
		gzwrite($zout,$item);
		continue;
		}

	$nothingToDo = FALSE ;
	$o = new NWC2ClipItem($item);
	$toDelete = 0 ;
	foreach ($map_array as $MatchActionPair) if ($MatchActionPair->Matches($o)) switch ($MatchActionPair->GetAction())
	{
		case GM_DELETE: $toDelete = 1 ;  break ;
		case GM_SET: 
		case GM_MOD:	$MatchActionPair->ActUpon(&$o) ;
	}
	if (!$toDelete) gzwrite($zout,$o->ReconstructClipText()."\n") ;
	unset($o);
}

gzclose($zout);
gzclose($zin);

if ($nothingToDo) {
	if ($FileMode) abort("Nothing changed.");
	else abort("This tool requires a selection. Please select a section of your staff before invoking global_mods.") ;
	}

exit ($final_exit_value) ;
?>
