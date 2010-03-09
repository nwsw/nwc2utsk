<?

// C O M P L E X _ A U T O B E A M . P H P
//
// Author: Copyright (c)2004 Andrew Purdam (ADP)
//
// 
// Beams complex time sigs according to user-specified template
//
// Called as:
// php complex_autobeam.php <timesig>=g1,g2,... [<timesig2>=h1,h2,...]...
// 
// where <timesig> is the timesignature to use this autobeam on eg 7/8, 13/8, 11/8, 
// and the groupings are given by a comma-separated list eg.
// php complex_autobeam.php 7/8=3,2,2   13/8=2,2,2,3,2,2   18/8=3,2,2,2,2,3,2,2
//
// Only unbeamed beamable notes which satisfy this grouping will be beamed.
//
//	Date		Version	Who	Comments	
//	11/11/04	1.0		ADP	Remembrance Day release
//	11/11/04	1.0.1		ADP	Need to ignore grace notes
// 25/11/04 1.0.2    ADP   Fixed bug that beamed notes that were already beamed. Thx Gennaro
//  2/12/04 1.0.3    ADP   Now needs to abort on empty input, so that an empty stream is not returned.
// 12/12/04 1.1      ADP   Improve treatment when notes are larger than their groupings. Thx Ertugrul
//                         
// 
// Scans a NoteworthyComposerClip stream to transpose rests up or down

require_once("lib/nwc2clips.inc");

function abort($string)
{
	fputs(STDERR, $string) ;
	exit(NWC2RC_ERROR) ;
}

$exitVal = NWC2RC_SUCCESS ;
function report($string)
{
	fputs(STDERR, $string) ;
	$exitVal = NWC2RC_REPORT ;
}

function help_msg_and_exit()
{
	echo "Called as:
php complex_autobeam.php <timesig>=g1,g2,... [<timesig2>=h1,h2,...]...

where <timesig> is the timesignature to use this autobeam on eg 7/8, 13/8, 11/8,
and the groupings are given by a comma-separated list eg.
php complex_autobeam.php 7/8=3,2,2   13/8=2,2,2,3,2,2   18/8=3,2,2,2,2,3,2,2

Only unbeamed beamable notes which satisfy this grouping will be beamed." ;
exit(NWC2RC_REPORT) ;
}

// The reason I use 12 is so that I can get a common factor of 2/3 and 7/4 and remain integral for accuracy (I don't want rounding errors)
$durArray = array ( "Whole" => 64*12, "Half" => 32*12,	"4th" => 16*12,	"8th" => 8*12,	"16th" => 4*12,	"32nd" => 2*12,	"64th" => 12 ) ;
$durMods = array ( "Triplet" => 2/3, "Dotted" => 3/2, "DblDotted" => 7/4, "Grace" => 0 ) ;

function timeOfDurOrDur2($da)
{
	global $durArray, $durMods ;
	if (!$da || !is_array($da)) return 0 ;
	foreach ($durArray as $key => $val) if (isset($da[$key])) $t = $val ;	// get base value
	foreach ($durMods as $key => $val) if (isset($da[$key])) $t *= $val ;	// and modify accordingly
	return round($t) ;
}

function timeTaken($o)	// of a note, chord or rest
{
	$opts = $o->GetOpts() ;
	$t1 = timeOfDurOrDur2($opts["Dur"]) ;
	if (!isset($opts["Dur2"])) return $t1 ;
	return min($t1, timeOfDurOrDur2($opts["Dur2"])) ;
}

function smallestBaseTime($o)	// of a note, chord, rest or restchord (but only used for notes and chords)
{
	global $durArray ; 
	$opts = $o->GetOpts() ;
	$da = $opts["Dur"] ;
	if (isset($da["Grace"])) return 0 ;
	foreach ($durArray as $key => $val) if (isset($da[$key])) $t1 = $val ;	// get base value
	if (isset($opts["Dur2"])) 
	{ 
		$da = $opts["Dur2"] ;
		foreach ($durArray as $key => $val) if (isset($da[$key])) $t2 = $val ;	// get base value
	}
	if (!isset($t2) || !($t2)) return $t1 ;
	else return min($t1, $t2) ;
}

function sign($i)		// returns the sign of a number. -1 if <0, 0 if 0, +1 if >0
{
	if ($i < 0) return -1 ;
	else if ($i == 0) return 0 ;
	else return 1 ;
}

function tryToBeam(&$cG)	// try and beam all beamable notes and chords cG is an array of clip items
{
	global $durArray ;
	$numObjs = 0 ;
	$bn = array ( ) ;	// array of beamable notes
	$majPos = 0 ;		// majority position is sum of (-1 for below middle line, +1 for above or on middle line). Used if notes only
	// first of all, which way to beam (and find first and last beamable note)
	foreach ($cG as $key => $o) if (in_array($o->GetObjType(), array("Note", "Chord")) && ($sBT = smallestBaseTime($o)) && $sBT <=$durArray["8th"])
	{	// we have a beamable note. NB: we ignore notes > 8ths, and also grace notes
	   $numObjs++ ;
		$opts = $o->GetOpts() ;
		if (isset($opts["Opts"]) && isset($opts["Opts"]["Beam"])) return ; // return because already beamed
		if ($o->GetObjType() == "Chord") 
		{
			if (isset($opts["Opts"]["Pos2"])) // we have notes of different lengths in the chord, must have a forced stem direction (shortest note)
			{
				if (isset($forcedStem) && $forcedStem != $opts["Opts"]["Stem"]) return ;	// Can't do it, chords are facing different directions
				$forcedStem = $opts["Opts"]["Stem"] ;
			}
			else 	// plain chord with notes all the same length, we can deal with this like a normal note
			{		// default stemming of plain chords is based on which note is farthest from the middle, or majority of notes if the extremes are the same
				$max = max($opts["Pos"]) ;
				$min = min($opts["Pos"]) ;
				if (abs($max) == abs($min)) $furthest = array_sum($opts["Pos"]) ;
				else $furthest = abs($max)>abs($min) ? $max : $min ;
				$majPos += sign($furthest) ;	// if below middle line, take it from majority position, else increment maj pos
			}
		}
		else $majPos += sign($opts["Pos"]) ;	
		$bn[] =& $cG[$key] ;
		if (!isset($firstObj)) $firstObj =& $cG[$key] ;
		$lastObj =& $cG[$key] ;
	}

	if ($numObjs < 2) return ;	// nothing to beam
	// if no chords (deduced from forcedStem), use majority position to get stems position
	if (!isset($forcedStem)) $forcedStem = $majPos < 0 ? "Up" : "Down" ;
	foreach ($bn as $key => $val) 
	{
		$opts =& $bn[$key]->GetOpts() ;
		$opts["Opts"]["Stem"] = $forcedStem ;
		$opts["Opts"]["Beam"] = "" ;
	}
	$opts =& $firstObj->GetOpts() ;
	$opts["Opts"]["Beam"] = "First" ;
	$opts =& $lastObj->GetOpts() ;
	$opts["Opts"]["Beam"] = "End" ;
}

// THE MAIN ACTION STARTS HERE
// 1. Parse the parameters
array_shift($argv) ;		// get rid of the first arg (script name)
foreach ($argv as $arg) // expecting a regexp of form timesig=grouplist
{
	if ($arg == "help") help_msg_and_exit() ;
	if (!preg_match("/^(\d+\/\d+)=(\d+(,\d+)*)$/", $arg, $m)) abort("\"$arg\" is misformed.\nEach parameter needs to be of the form <upper>/<lower>=<grp1>,<grp2>,<grp3>...\n<upper>,<lower> and <grp> are all integers.") ;
	$timesig = $m[1] ;	$groupings = explode(",", $m[2]) ;
	list($numer,$denom) = explode("/", $timesig) ;
	if (!preg_match("/^(2|4|8|16|32|64)$/",  $denom)) abort("Time signature \"$timesig\" must have a lower number equal to 2,4,8,16,32 or 64.\n(Otherwise I can't do the maths)") ;
	$total = 0 ;
	foreach ($groupings as $i) $total += $i ;
	if ($numer != $total) abort("Groupings for \"$arg\" don't add up to the upper number of a time signature (\"$numer\"). Added to $total.") ;
	// repopulate array with equivalent TimeTaken values, making them cumulative
	$last = 0 ;
	foreach ($groupings as $key => $val) $last = ($groupings[$key] = $val * 64 * $durArray["64th"] / $denom + $last) ;
	$groupingsTable[$timesig] = $groupings ;	// build up a table eg "7/8=3,2,2" gives $groupingsTable["7/8"] = (288,480,672) ;
}
if (!isset($groupingsTable)) abort("Must provide at least one parameter of the form <upper>/<lower>=group1,group2,...") ;

$currentGroup = array ( ) ;	$bar = 0 ;  $nothingToDo = TRUE ;
// 2. Load and scan the stream... output is group by group
$clip = new NWC2Clip('php://stdin');
echo $clip->GetClipHeader()."\n";
foreach ($clip->Items as $item) 
{
	$nothingToDo = FALSE ;
	$o = new NWC2ClipItem($item);
	$oType = $o->GetObjType();
	if ($oType == "TimeSig") 		// we've found a time signature.
	{
		// better flush anything in the buffer
		while ($go = array_shift($currentGroup)) echo $go->ReconstructClipText()."\n" ;	// now flush the current group
		$timeSig = $o->GetTaggedOpt("Signature") ;
		if (isset($groupingsTable[$timeSig])) $currentGroupTimes = $groupingsTable[$timeSig] ;	// this is one we need to group
		else unset($currentGroupTimes) ;																			// this isn't
		$groupNum = 0 ;
		$placeInTime = 0 ;
	}
	if ($oType == "Bar" && !$o->GetTaggedOpt("Color"))	// found a barline that is not coloured (assume colour means dotted - the best we can do for dotted barlines)
	{
		$bar ++ ;
		while ($go = array_shift($currentGroup)) echo $go->ReconstructClipText()."\n" ;	// now flush the current group
		$groupNum = 0 ;
		$placeInTime = 0 ;
	}
	if (!isset($currentGroupTimes)) echo $item ;		// if this current music has no timesig to convert, just pass it thru unaltered
	else 
	{
		array_push($currentGroup,$o) ;	// save the item
		if (in_array($oType, array("Note", "Chord", "Rest", "RestChord"))) $placeInTime += timeTaken($o) ;
		if (!isset($currentGroupTimes[$groupNum])) report("Bar is too long at bar $bar from start\n") ;
		elseif ($placeInTime >= $currentGroupTimes[$groupNum])		// if we're at the end of a group
		{
			tryToBeam(&$currentGroup) ;
			while ($go = array_shift($currentGroup)) echo $go->ReconstructClipText()."\n" ;	// now flush the current group
			$groupNum ++ ;	// and increment our group number
			// following is a test for when notes or chords exceed groupings
			if ($placeInTime > $currentGroupTimes[$groupNum-1] && isset($currentGroupTimes[$groupNum]))
			{
				while ($placeInTime >= $currentGroupTimes[$groupNum] && isset($currentGroupTimes[$groupNum+1]))
					$groupNum++ ;
			}
		}
	}
	unset($o);
}
while ($go = array_shift($currentGroup)) echo $go->ReconstructClipText()."\n" ;	// flush any incomplete final group

echo NWC2_ENDCLIP."\n";
if ($nothingToDo) abort("You didn't give me anything to beam!") ;

exit (NWC2RC_SUCCESS) ;
?>
