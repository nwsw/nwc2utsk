<?php

// P A R T S . P H P
//
// Author: Andrew Purdam (ADP)
// With copious thanks to a patient Richard Woodroffe for alpha testing.
//
// Separate polyphonic staves into separate voices.
// Takes standard input stream and filters out other notes, leaving single voice,
// echoed to output stream, or remove the single voice, leaving the rest
//
// Called as:
// php parts.php remove|retain    top|bottom|pos=<pos>    [nosingle]
// 
// where keywords remove and retain indicate what action to take
// top and bottom indicate which voice to act upon
//	nosingle means that if there is a single voice, replace with a rest
//
// Version History
// Date		Ver	Who	Comments
//	28/10/04	1.0	ADP	Initial version using middle
//   1/11/04	1.0.1	ADP	Fixed bug in calculating dots in duration. Thx Rich.
//	 3/11/04	1.0.2	ADP	Fixed bug in returning rests (missed the ":"). Thx Rich.
//	 5/11/04	1.0.3	ADP	Added functionality for DblDotted. Thx again, Rich.
//  12/11/04    1.1   ADP   Change to use Eric's library, and use remove|retain paradigm
//  18/11/04    1.1.1	ADP   Fix bug with two chord members on same extremity. Thx Rich.
//   4/12/04    1.1.2 ADP   Add message for empty input
//  30/05/05    1.2 ADP Add new specification pos=<position>
//  31/05/05    1.2.1   ADP Fix bug with ties
//   4/04/06    1.2.2   ADP Fix bug in removing from note-chord combination

// Basically, if we filter out a Pos2 or Pos part, and the Duration (Dur or Dur2)
// of the filtered note is longer, we need to "swallow" the following notes to make up
// the length of the note. (eg filtering out an eighth note that is chorded with a
// quarter note, we'd need to swallow the following eighth note, since we are now
// left with a note of length one quarter

require_once("lib/nwc2clips.inc");

// Global declarations

$single = 1 ;			// are we allowing single notes or not Optionally Specified on command line. Default is yes.
$gobbleTime = 0 ;		// used for chords with two times, to "eat" subsequent notes of another part
$passTime = 0 ;      // also for chords with two times, to "pass" on subsequent notes of another part
// say we have a lower 4th note and upper 8th note followed by an 8th note
// retain lower needs to remove the 1st eight note and gobble the second 8th note
// remove lower needs to remove the 4th note and remember to pass thru the second 8th note
// retain top needs to remove the bottom 4th note
// remove top needs to remove the first 4th note and remember to gobble the second


// Functions
function abort($string)	// print the string on STDERR and exit(1)
{
	fputs(STDERR, $string) ;
	exit(1) ;
}

function report($string)	// print the string on STDERR
{  fputs(STDERR, $string) ;  }

function help_msg_and_exit()
{
	echo "Called as:
php parts.php remove|retain    top|bottom|pos=<pos>     [nosingle]

where keywords remove and retain indicate what action to take
top and bottom and pos indicate which voice to act upon
<pos> is a position on the staff, including accidental
(accidentals are ## for double sharp, # for sharp, b for flat, bb for double)
0 is the centre line
eg remove pos=#0 will remove all sharp centre line notes or chord members
nosingle means that if there is a single voice, replace with a rest.

About accidentals:
It is possible to filter out an accidental which may then render a
subsequent note incorrect. If you notice that two parts share an accidental
in different parts of a bar, I suggest you do the following:
1) Tools | Force Accidentals
2) Separate the parts
3) Tools | Audit Accidentals

Version:    1.2.1   31/5/05
" ;
exit(NWC2RC_REPORT) ;
}

function positionness($pos)
// converts a position of the form "xz" "#z" "nz or z" "bz" "vz" (double sharp, sharp, natural, flat, double flat)
//into an integer value 5*z , +2 for x, +1 for #, -1 for b, -2 for v
{
	if (preg_match('/^([\#bnxv]{0,1})(\-{0,1}[0-9]+)([oxXz]{0,1})([\^]{0,1})/',$pos,$m))
	{	$Acc=$m[1]; $Pos=$m[2]; $Head=$m[3];   $Tied=$m[4];   }  // should always match
	switch ($Acc) {
		case "x":   return ($Pos * 5 + 2) ; // double sharp
		case "#":   return ($Pos * 5 + 1) ; // sharp
		case "n":   return ($Pos * 5 ) ;		// natural
		case "b":   return ($Pos * 5 - 1) ; // flat
		case "v":   return ($Pos * 5 - 2) ; // double flat
		default: 	return ($Pos * 5) ;			// else return 5n
		}
}  // end positionness

function findPos($PosOrPos2, $o, $func) // return the extreme of "Pos" or "Pos2" in clipobject $o. $func should be 'min' or 'max'
{
	if ($func == 'max') $extreme = -999 ; else $extreme = 999 ;
	if ($o->GetTaggedOpt($PosOrPos2) === FALSE) return $extreme ;
	foreach ($o->GetTaggedOpt($PosOrPos2) as $pos)
		if ($func == 'max' && positionness($pos) > positionness($extreme))
			{ $extreme = $pos ; }
		elseif ($func == 'min' && positionness($pos) < positionness($extreme))
			{ $extreme = $pos ; }
	return $extreme ;
}  // end findPos

// The reason I use 12 is so that I can get a common factor of 2/3 and 7/4 and remain integral for accuracy (I don't want rounding errors)
$durArray = array ( "Whole" => 64*12, "Half" => 32*12,	"4th" => 16*12,	"8th" => 8*12,
			"16th" => 4*12,	"32nd" => 2*12,	"64th" => 12 ) ;
$durMods = array ( "Triplet" => 2/3, "Dotted" => 3/2, "DblDotted" => 7/4, "Grace" => 0 ) ;

function durationness($da) // $da is a NWC2ClipObject's Opts Dur or Dur2 entry. An array
{
	global $durArray, $durMods ;
	if (!$da || !is_array($da)) return 0 ;
	foreach ($durArray as $key => $val) if (isset($da[$key])) $t = $val ;	// get base value
	foreach ($durMods as $key => $val) if (isset($da[$key])) $t *= $val ;	// and modify accordingly
	return (int) round($t) ;
}

function timeTaken($o)	// of a note, chord, rest or rest chord. Returns minimum duration if two are present
{
	$opts = $o->GetOpts() ;
	$t1 = durationness($opts["Dur"]) ;
	if (!isset($opts["Dur2"])) return $t1 ;
	return min($t1, durationness($opts["Dur2"])) ;
}  // end TimeTaken

function filter_out_position_value(&$arr, $match)
{
	foreach ($arr as $key => $val)
		if (positionness($val) == positionness($match)) unset ($arr[$key]) ;
	$arr = array_values($arr) ;   // and reindex just in case it is needed
}

// remove|retain top|bottom|pos from clip object o (Note, Chord or RestChord only)
function &filterPart($action, $part, &$o )
{
	global $single, $gobbleTime, $passTime ;

	// convert eg part="pos=bb-4" to part="pos" and filterPos="v-4" ;
	if (substr($part,0,3)=='pos')
	{   // convert our double sharps and flats to NWC's x and v notation
		$part=preg_replace('/##/', 'x', $part) ;
		$part=preg_replace('/bb/', 'v', $part) ;
		$filterPos = substr($part,4) ;  // strip off 'pos='
		$filterPosness = positionness($filterPos) ; // and convert to positionness units
		$part = 'pos' ;  //  for ease of reference below
	}

	$oType =& $o->GetObjType() ;  // May need to modify either of these
	$opts =& $o->GetOpts() ;      //
	if ($oType == "Note")	// only two ways of dealing with single notes, irrespective of $action
	{
		if (!$single)		// Single notes are not wanted, need to convert to a rest
		{
			$oType = "Rest" ;
			$opts = array( "Dur" => $opts["Dur"] ) ; // clear opts except for Dur
			return $o ;
		}
		elseif ($part != 'pos')
			return $o ;	// Return it as the default top or bottom part
		else
		{
			if ((positionness($opts["Pos"])==$filterPosness && $action=='retain') || (positionness($opts["Pos"])!= $filterPosness && $action=='remove'))
				return $o ;
			else
			{
				$oType = "Rest" ;
				$opts = array( "Dur" => $opts["Dur"]) ;   // clear opts except for Dur
				return $o ;
			}
		}
			
	}  // end $oType == Note

	// we need these so often, just get them once at the start
	$dur = $opts["Dur"] ;
	if (isset($opts["Dur2"])) $dur2 = $opts["Dur2"] ;  else $dur2 = NULL ;
	$topPos = findPos("Pos", $o, 'max') ;		// get both maxima
	$topPos2 = findPos("Pos2", $o, 'max') ;
	$botPos = findPos("Pos", $o, 'min') ;		// and both minima
	$botPos2 = findPos("Pos2", $o, 'min') ;
	if (isset($opts["Beam"])) $beam = $opts["Beam"] ; else $beam = NULL ;
		if (isset($opts["Color"])) $color = $opts["Color"] ; else $color = NULL ;
		if (isset($opts["Stem"])) $stem = $opts["Stem"] ; else $stem = NULL ;

	// Only chords and RestChords remaining
	if ($action == "retain")
	{
		if ($part == "top")			// get rid of the lower bits
		{
			if ($oType == "Chord")	// Chords have Pos and optional Pos2
			{
				unset($opts["Pos"], $opts["Pos2"], $opts["Dur"], $opts["Dur2"]) ;
				// the following tests the obvious, whether the highest note is Pos or Pos2,
				// but also, if it is a shared note, in which case the stem up position is retained
				if (positionness($topPos) > positionness($topPos2)
					|| (positionness($topPos) == positionness($topPos2) && $opts["Opts"]["Stem"] == "Up"))
				{	// Using Pos. If duration of Pos2 was shorter, ...
					$opts["Pos"] = $topPos ;	$opts["Dur"] = $dur ;
					if ($dur2 && (durationness($dur) > durationness($dur2))) // need to "swallow" following short notes
						$gobbleTime = durationness($dur) - durationness($dur2) ;
				}
				else
				{	// Using Pos2. If duration of Pos2 was shorter...
					$opts["Pos"] = $topPos2 ;	$opts["Dur"] = $dur2 ;
					if (durationness($dur) < durationness($dur2))
						$gobbleTime = durationness($dur2) - durationness($dur) ;	// need to "swallow" following short notes
				}
				// and of course it's not a chord anymore, just a note
				$oType = "Note" ;
				return $o ;
			}  // end retain, top, chord
			// Only RestChords left. If Stem is upwards, rest is on top. Get rid of Pos2 notes leaving a rest
			if ($opts["Opts"]["Stem"] == "Up")
			{
				$oType = "Rest" ;
				$opts["Dur"] = $dur ;   unset($opts["Dur2"],$opts["Pos2"]) ;
				return $o ;
			}
			// This restchord is stem down, meaning notes are on top. Return the top pos2 note.
			$oType = "Note" ; 
			$opts	 = array( "Dur" => $dur2, "Pos" => $topPos2 );
			// because we have removed the shorter duration rest, need to gobble the time difference
			$gobbleTime = durationness($dur2) - durationness($dur) ;
			return $o ;
		} // end retain top
		elseif ($part == "bottom")	// Retain bottom, get rid of the upper bits, same as above, but using minimums
		{
			if ($oType == "Chord")	// Chords might have Pos and Pos2
			{
				unset($opts["Pos"], $opts["Pos2"], $opts["Dur"], $opts["Dur2"]) ;
				// the following tests the obvious, whether the lowest note is Pos or Pos2,
				// but also, if it is a shared note, in which case the stem down position is retained
				if (positionness($botPos) < positionness($botPos2)
					|| (positionness($botPos) == positionness($botPos2) && $opts["Opts"]["Stem"] == "Down"))
				{	// using Pos, so if we had a Dur2, and it is shorter, we need to set gobbleTime
					$opts["Pos"] = $botPos ;	$opts["Dur"] = $dur ;
					if ($dur2 && (durationness($dur) > durationness($dur2)))
						$gobbleTime = durationness($dur) - durationness($dur2) ;
				}
				else
				{
					$opts["Pos"] = $botPos2 ;	$opts["Dur"] = $dur2 ;
					if (durationness($dur) < durationness($dur2))
						$gobbleTime = durationness($dur2) - durationness($dur) ;	// need to "swallow" following short notes
				}
				// and of course it's not a chord anymore, just a note
				$oType = "Note" ;
				return $o ;
			}  // end retain bottom chord
			// Only RestChords left If Stem is downwards, rest is on bottom. Get rid of Pos2 notes leaving a rest
			if ($opts["Opts"]["Stem"] == "Down")
			{
				$oType = "Rest" ;
				$opts["Dur"] = $dur ;   unset($opts["Dur2"],$opts["Pos2"]) ;
				return $o ;
			}
			// This restchord is stem up, meaning notes are on bottom. Return the bot pos2 note.
			$oType = "Note" ;
			$opts	 = array( "Dur" => $dur2, "Pos" => $botPos2 );
			// because we have removed the shorter duration rest, need to gobble the time difference
			$gobbleTime = durationness($dur2) - durationness($dur) ;
			return $o ;
		}	// end retain bottoms
		else    // retain pos
		{
			if ($oType=="Chord")
			{   // search for a match
				foreach($opts["Pos"] as $pos)
				{
					if (positionness($pos)==$filterPosness)
					{   // return a single note of duration Dur
						$oType = "Note" ;
						$opts = array( "Dur" => $opts["Dur"], "Pos" => $pos) ;
						if (isset($beam)) $opts["Beam"] = $beam ;
						if (isset($color)) $opts["Color"] = $color ;
						if (isset($stem)) $opts["Stem"] = $stem ;
						return $o ;
					}
				}
			if (isset($opts["Pos2"])) foreach($opts["Pos2"] as $pos)
				if (positionness($pos)==$filterPosness)
				{   // return a single note of duration Dur
					$oType = "Note" ;
					$opts = array( "Dur" => $opts["Dur2"], "Pos" => $pos) ;
					if (isset($beam)) $opts["Beam"] = $beam ;
					if (isset($color)) $opts["Color"] = $color ;
					if (isset($stem)) $opts["Stem"] = $stem ;
					if (durationness($dur) < durationness($dur2))
						$gobbleTime = durationness($dur2) - durationness($dur) ;
						return $o ;
				}
			// no matching pos to retain, so return a rest of the shortest duration (Dur)
			$oType = "Rest" ;
			$opts = array( "Dur" => $opts["Dur"]) ;
			return $o ;
			}   // end retain pos in chord
			else    // retain pos in restchord
			{
				foreach($opts["Pos2"] as $pos)
					if (positionness($pos)==$filterPosness)
					{   // return a restchord with only filterpos of duration Dur
						$opts["Pos"] = array($pos) ;
						return $o ;
					}
				// no matching pos to retain, so return a rest of the shortest duration (Dur)
				$oType = "Rest" ;
				$opts = array( "Dur" => $opts["Dur"]) ;
				return $o ;
			}   // end retain pos in restchord
		}	// end retain pos
	}       // and end retain

// that's it for RETAINing. Now for REMOVing a single part
// Just need to set gobbletime if removing the last of a shorter note
	if ($part == "top")			// get rid of the top part
	{
		if ($oType == "Chord" && isset($opts["Pos2"]))	// Chords have Pos and optional Pos2
		{
			if (positionness($topPos) > positionness($topPos2)
					|| (positionness($topPos) == positionness($topPos2) && $opts["Opts"]["Stem"] == "Up"))
			{  // remove the top pos.
				filter_out_position_value(&$opts["Pos"], $topPos) ;
				if (!count($opts["Pos"]))
				{
					$opts["Pos"] = $opts["Pos2"] ;   $opts["Dur"] = $opts["Dur2"] ;
					unset($opts["Pos2"], $opts["Dur2"]) ;
					if (durationness($dur) < durationness($dur2))
						$gobbleTime = durationness($dur2) - durationness($dur) ;	// need to "swallow" following short notes
				}
				if (durationness($dur) > durationness($dur2))
					$passTime = durationness($dur) - durationness($dur2) ;
					// if we're removing a note that is longer than other notes in the chord,
					// what we really want to do is let the next $passtime worth of notes through unfiltered.
					// Do this in the main loop with 'if "retain" and gobbling then echo'
				return $o ;
			}  // end remove top pos in chord
			// remove the top pos2
			filter_out_position_value(&$opts["Pos2"], $topPos2) ;
			if (!count($opts["Pos2"]))
			{
				unset($opts["Dur2"], $opts["Pos2"]) ;
				if (durationness($dur) > durationness($dur2))
					$gobbleTime = durationness($dur) - durationness($dur2) ;
			}
			if (durationness($dur) < durationness($dur2))   // I've removed the longer note
				$passTime = durationness($dur2) - durationness($dur) ;	// need to "swallow" 					following short notes
			return $o ; //
		}  // end remove top pos or pos2 in chord with both
		if ($oType == "Chord")     // plain chord
		{
			filter_out_position_value(&$opts["Pos"], $topPos) ;
			return $o ;
		}
		// Only RestChords left. If Stem is upwards, rest is on top. Get rid of it
		if ($opts["Opts"]["Stem"] == "Up")
		{
			$oType = "Chord" ;
			$opts["Dur"] = $dur2 ;   $opts["Pos"] = $opts["Pos2"] ;
			unset($opts["Dur2"],$opts["Pos2"]) ;
			// because we have removed the shorter duration rest, need to gobble the time difference
			$gobbleTime = durationness($dur2) - durationness($dur) ;
			return $o ;
		}
		// This restchord is stem down, meaning notes are on top. Remove the top pos2 note.
		filter_out_position_value(&$opts["Pos2"], $topPos2) ;
		// just need to check that we haven't removed the last note
		if (!sizeof($opts["Pos2"])) unset($opts["Pos2"]) ;
		return $o ;
	} // end remove top
	
	if ($part == "pos")
	{   // remove a part (from a chord or restchord) described by pos
		if ($oType == "Chord")
		{
			filter_out_position_value(&$opts["Pos"], $filterPos) ;
			if (isset($opts["Pos2"])) filter_out_position_value(&$opts["Pos2"], $filterPos) ;
			if (empty($opts["Pos"]))
			{
				if (empty($opts["Pos2"])) // nothing left
				{
					$oType = "Rest" ;
					$opts = array( "Dur" => $opts["Dur"]) ;
					return $o ;
				}
				else  //  no Pos left, but Pos2 is left, so gobble some time
				{
					$gobbleTime = durationness($dur2) - durationness($dur) ;
					$opts["Dur"] = $opts["Dur2"] ;  // move Dur2 to Dur
					$opts["Pos"] = $opts["Pos2"] ;  // and Pos2 to Pos
					unset($opts["Dur2"]) ;
					unset($opts["Pos2"]) ;
					return $o ;
				}
			}
			else    // $opts["Pos"] is not empty
			{
				if (empty($opts["Pos2"]))
				{
					unset($opts["Pos2"]) ;
					unset($opts["Dur2"]) ;
				}
			return $o ;
			}
		}
		// Wasn't a chord, must be a restchord. Notes are in pos2
		filter_out_position_value(&$opts["Pos2"], $filterPos) ;
		if (empty($opts["Pos2"]))
		{
			unset($opts["Pos2"]) ;
			unset($opts["Dur2"]) ;
			unset($opts["Stem"]) ;
			$oType = "Rest" ;
		}
		return $o ;
	}   // end remove pos
	// only removing of bottom part left to do
	if ($oType == "Chord" && isset($opts["Pos2"]))	// Chords have Pos and optional Pos2
	{
		if (positionness($botPos) < positionness($botPos2)
				|| (positionness($botPos) == positionness($botPos2) && $opts["Opts"]["Stem"] == "Down"))
		{  // remove the bot pos.
			filter_out_position_value(&$opts["Pos"], $botPos) ;
			if (!count($opts["Pos"]))
			{
				$opts["Pos"] = $opts["Pos2"] ;   $opts["Dur"] = $opts["Dur2"] ;
				unset($opts["Pos2"], $opts["Dur2"]) ;
				if (durationness($dur) < durationness($dur2))
					$gobbleTime = durationness($dur2) - durationness($dur) ;	// need to "swallow" following short notes
				elseif (durationness($dur) > durationness($dur2))
					$passTime = durationness($dur) - durationness($dur2) ;
			}
			return $o ;
		}  // end remove bot pos in chord
		// remove the bot pos2
		filter_out_position_value(&$opts["Pos2"], $botPos2) ;
		if (!count($opts["Pos2"]))
		{
			unset($opts["Pos2"], $opts["Dur2"]) ;
			if (durationness($dur2) > durationness($dur))
				$passTime = durationness($dur2) - durationness($dur) ;	// need to "swallow" following short notes
			elseif (durationness($dur2) > durationness($dur))
				$gobbleTime = durationness($dur2) - durationness($dur) ;
		}
		return $o ; // can return a chord with only
	}  // end remove bot pos or pos2 in chord with both
	if ($oType == "Chord")     // plain chord
	{
		filter_out_position_value(&$opts["Pos"], $botPos) ;
		return $o ;
	}
	// Only RestChords left. If Stem is downwards, rest is on bottom. Get rid of it
	if ($opts["Opts"]["Stem"] == "Down")
	{
		$oType = "Chord" ;
		$opts["Dur"] = $dur2 ;   $opts["Pos"] = $opts["Pos2"] ;
		unset($opts["Dur2"],$opts["Pos2"]) ;
		// because we have removed the shorter duration rest, need to gobble the time difference
		$gobbleTime = durationness($dur2) - durationness($dur) ;
		return $o ;
	}
	// This restchord is stem up, meaning notes are on bottom. Remove the bot pos2 note.
	filter_out_position_value(&$opts["Pos2"], $botPos2) ;  
	// just need to check that we haven't removed the last note
	if (!sizeof($opts["Pos2"])) unset($opts["Pos2"]) ;
	return $o ;
}

// Global action: $argv should be "parts.php" "remove|retain" "top|bottom" [nosingle]
array_shift($argv) ;
$action = array_shift($argv) ;
if ($action == "help") help_msg_and_exit() ;
if ($action == NULL || !in_array($action, array("remove", "retain")))
	abort("1st parameter must be \"remove\" or \"retain\". Use \"help\" for more help.") ;

$part = array_shift($argv) ;
if ($part == NULL || !preg_match('/(top|bottom|pos=((##|#|b|bb)?-?\d+))/', $part, $matches))
{
	if (substr($part,0,3) != 'pos')
		abort("2nd parameter must be top, bottom or pos=<pos>.\nYou used \"$part\"\nUse \"help\" as a first parameter for more help.") ;
	else abort("You have \"$part\" for the second parameter\n".'
When using "pos=<pos>", the keyword is "pos="
and the <pos> parameter must be of the form
<accidentals><sign><digits>, where

<accidentals> are an optional ##, #, b or bb
<sign> is an optional minus sign (for notes below the middle line)
and <digits> indicate how far from the centre line
eg
#0 means notes that are on the centre line but have a single sharp accidental
bb-1 means double flat notes in the space below the centre line
7 means notes an octave above the centre line') ;
}

// process other optional arguments, in no particular order
foreach ($argv as $arg)
	if (preg_match("/^nosingle$/", $arg)) { $single = 0 ; } 
	else { abort("Unrecognised parameter '$arg'") ; }

$clip = new NWC2Clip('php://stdin');
echo $clip->GetClipHeader()."\n";

$nothingToDo = TRUE ;
foreach ($clip->Items as $item)
{
	$nothingToDo = FALSE ;
	$o = new NWC2ClipItem($item);
	$oType = $o->GetObjType();

	if (!in_array($oType, array("Note","Chord","Rest","RestChord")))   // not an item of interest, pass it thru
		echo $item ;
	elseif ($gobbleTime > 0)        // run down the gobble clock, don't print the item
		$gobbleTime -= timeTaken($o) ;   
	elseif ($passTime > 0)          // run down the pass time clock, DO print item
	{
		$passTime -= timeTaken($o) ;
		echo $item ;
	}
	elseif ($oType == "Rest" || !timeTaken($o))        // don't filter on rests or grace notes
		echo $item ;
	else                             // else filter out the required parts and print modified item
	{
		$o =& filterPart($action, $part, $o) ;
		echo $o->ReconstructClipText()."\n" ;
	}
	unset($o) ;
}

echo NWC2_ENDCLIP."\n";
if ($nothingToDo) abort("This tool requires a selection.
Please select something in your staff before executing this tool.") ;

unset($clip) ;
exit (NWC2RC_SUCCESS) ;
?>
