<?
//	R A N G E S . P H P

//	Author(s):		Andrew Purdam	(ADP)
//	
//	Date	Version	Who	Comments	
//	18/10/04	1.0		ADP	1st release	(Perl version)
//	19/10/04	1.0.1	ADP	Added "separate" as qualifier If "separate" is specified in command line, 
//								will print range as two separate notes, otherwise as chord (Perl)
//	24/10/04	1.1	ADP	PHP version
//	30/10/04	1.2	ADP	Rewritten to use Eric's library - saved 10 lines!!!
//  2/12/04     1.3 ADP   Better handling of stupid input
//
///Scans a NoteworthyComposerClip stream of NWC2 notes to give maxima and minima
// NB: ignores clef changes and enharmonic spellings (eg will say Cb is higher than B#)
//         I think this is a reasonable limitation
// Output is the same stream with the range at the start

$printedRange = 0 ;
$maximum = -999 ;
$minimum = 999 ;
$writeasseparate = 0 ;

require_once("lib/nwc2clips.inc");

function abort($str)
{
	fputs(STDERR, $str) ;
	exit (NWC2RC_ERROR) ;
}

function print_help_and_exit()
{
	echo "Usage:
php ranges.php ['separate']

where 'separate' is an optional keyword saying to print the ranges as separate notes
NWC tool to print ranges of an NWC selection (or whole staff).
(Useful for evaluating ranges of vocal and instrumental parts,
esp. when composing or arranging).

Ignores clef changes and enharmonic spellings

Version: 1.3  2/12/04
" ;
exit(NWC2RC_REPORT);
}

function positionness($src)
// converts a position of the form "xz" "#z" "nz or z" "bz" "vz" (double sharp, sharp, natural, flat, double flat)
//into an integer value 5*z , +2 for x, +1 for #, -1 for b, -2 for v
{
	$AccMarkToValue = array ("x"=>2, "#"=>1, "n"=>0, "b"=>-1, "v"=>-2) ;	// Double sharp, sharp, nat, flat, double-flat
	$n = new NWC2NotePitchPos($src) ;
	$ret = $n->Position * 5 ;
	if ($n->Accidental) $ret += $AccMarkToValue[$n->Accidental] ;
	unset($n) ;
	return ($ret) ;						// 
}

function CheckExtremes($o)		// scans a Pos or Pos2 tagged opt and sets maximum and minimum accordingly
{							
	global $maximum, $minimum ;
	if ($o === FALSE) return ;	// This might happen is called for Pos2 and there is no Pos2
	if (!is_array($o)) $o = array($o) ;
	foreach ($o as $pos)	// 
	{
		if (positionness($pos) > positionness($maximum)) { $maximum = $pos ; } // see sub above
		if (positionness($pos) < positionness($minimum)) { $minimum = $pos ; } 
	}
}


// THE MAIN ACTION STARTS HERE
foreach ($argv as $arg)
{
	if ($arg == "help") print_help_and_exit() ;
	elseif ($arg == "separate") $writeasseparate = 1 ;
}


$clip = new NWC2Clip('php://stdin');

// 1. Scan each line
foreach ($clip->Items as $item) 
{
	$o = new NWC2ClipItem($item);
	$oType = $o->GetObjType();
	if ($oType == "Note") CheckExtremes($o->GetTaggedOpt("Pos")) ;
	elseif ($oType == "RestChord") CheckExtremes($o->GetTaggedOpt("Pos2")) ;
	elseif ($oType == "Chord") {CheckExtremes($o->GetTaggedOpt("Pos")) ;	CheckExtremes($o->GetTaggedOpt("Pos2")) ;  }
	unset($o);
}

// 2. Check if we scanned any notes
if ($maximum == -999)		// Bail out if no notes or chords or restchords
	abort("No notes found!") ;
	
if ($maximum == $minimum)
	abort("This selection has a range of a single note!") ;

// 3. Commence output...
echo $clip->GetClipHeader()."\n";
foreach ($clip->Items as $item)
{
	if (! $printedRange)		// have we printed our range yet? We wait until the first note, chord or restchord...
	{
		$o = new NWC2ClipItem($item);
		if (in_array($o->GetObjType(), array("Note", "Chord", "RestChord")))
		{
			$printedRange = 1 ;			// ...and then precede it with our range
			if ($writeasseparate) { echo "|Note|Dur:Whole|Pos:$minimum\n|Note|Dur:Whole|Pos:$maximum\n" ; } 
			else { echo "|Chord|Dur:Whole|Pos:$minimum,$maximum\n"  ; }
			echo "|Bar|Style:Double\n" ;
		}
		unset ($o) ;
	}
	echo $item ;
}
echo NWC2_ENDCLIP."\n";
exit (NWC2RC_SUCCESS) ;		// Phew! Glad that's over!
?>