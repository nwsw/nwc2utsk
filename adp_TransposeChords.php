<?
//	T R A S N P O S E _ C H O R D S . P H P

//	Author(s):		Copyright (c)2004 Andrew Purdam	(ADP)
//	
//	Date		Version	Who	Comments	
//	 5/11/04	1.0		ADP	1st go
//	 8/11/04	1.0.1		ADP	add ability to change bass notes of form eg Cm7b5/Eb
//  2/12/04 1.0.2    ADP   added check for null input. Required due to NWC2e not handling null output
//
//	Scans a NoteworthyComposerClip stream of NWC2 items and transposes "text chords"
// For usage, see help_msg_and_exit below

// All the different names for chords in a given key, roughly. NB: These are not NOTE names
// You need to think, "if a chord is written in this key, what chord name would I use?"
// Many chords you'd probably see only rarely in a particular key
// I haven't decided what to do about relative minors yet
$chords = array ("C" => array("C", "Db", "D", "Eb", "E", "F", "F#", "G", "Ab", "A", "Bb", "B"), 
					"C#" => array("C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"),
					"Db" => array("C", "Db", "D", "Eb", "E", "F", "Gb", "G", "Ab", "A", "Bb", "B"),
					"D" => array("C", "C#", "D", "Eb", "E", "F", "F#", "G", "G#", "A", "Bb", "B"), 
					"D#" => array("C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"),	// not a common key sig
					"Eb" => array("C", "Db", "D", "Eb", "E", "F", "Gb", "G", "Ab", "A", "Bb", "B"),
					"E" => array("C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"),
					"F" => array("C", "Db", "D", "Eb", "E", "F", "Gb", "G", "Ab", "A", "Bb", "B"),
					"F#" => array("C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"),
					"Gb" => array("C", "Db", "D", "Eb", "E", "F", "Gb", "G", "Ab", "A", "Bb", "B"),
					"G" => array("C", "C#", "D", "D#", "E", "F", "F#", "G", "Ab", "A", "Bb", "B"),
					"G#" => array("C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"), 
					"Ab" => array("C", "Db", "D", "Eb", "E", "F", "Gb", "G", "Ab", "A", "Bb", "B"),
					"A" => array("C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "Bb", "B"),
					"A#" => array("C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"),	// not a common key sig
					"Bb" => array("C", "Db", "D", "Eb", "E", "F", "Gb", "G", "Ab", "A", "Bb", "B"),
					"B" => array("C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B") ) ;
// I guess I have decided what to do with relative minors
$chords["Am"] = $chords["C"] ;	$chords["Am"][8] = "G#" ;
$chords["A#m"] = $chords["C#"] ;	
$chords["Bbm"] = $chords["Db"] ;
$chords["Bm"] = $chords["D"] ;	$chords["Bm"][10] = "A#" ;
$chords["Cm"] = $chords["Eb"] ;
$chords["C#m"] = $chords["E"] ;
$chords["Dbm"] = $chords["F"] ;	// surely no one ever writes in Gb minor?
$chords["Dm"] = $chords["F"] ;	$chords["Dm"][1] = "C#" ;
$chords["D#m"] = $chords["F#"] ;	
$chords["Ebm"] = $chords["Gb"] ;
$chords["Em"] = $chords["G"] ;	$chords["Em"][8] = "G#" ;	$chords["Em"][10] = "A#" ;
$chords["Fm"] = $chords["Ab"] ;
$chords["F#m"] = $chords["A"] ;
$chords["Gbm"] = $chords["Gb"] ;	// surely no one ever writes in Gb minor?
$chords["Gm"] = $chords["Bb"] ;	$chords["Gm"][1] = "C#" ;	$chords["Gm"][7] = "F#" ;
$chords["G#m"] = $chords["B"] ;
$chords["Abm"] = $chords["Ab"] ;

// got B#, Cb, E# and Fb for completeness and easier parsing of a chord. Never expect to use them
$chordPosition = array("B#"=>0, "C"=>0, "C#"=>1, "Db"=>1, "D"=>2, "D#"=>3, "Eb"=>3, "E"=>4, "Fb"=>4, "E#"=>5, "F"=>5, "F#"=>6, "Gb"=>6, 
								"G"=>7, "G#"=>8, "Ab"=>8, "A"=>9, "A#"=>10, "Bb"=>10, "B"=>11, "Cb"=>11) ;

$simpleTranspose = array("Db"=>"C#", "C#"=>"Db", "Eb"=>"D#", "D#"=>"Eb", "Gb"=>"F#", "F#"=>"Gb", "Ab"=>"G#", "G#"=>"Ab", "Bb"=>"A#", "A#"=>"Bb") ;
$fullEnharmonicSwap = $simpleTranspose ;
$fullEnharmonicSwap["C"] = "B#" ;	$fullEnharmonicSwap["B"] = "Cb" ;	$fullEnharmonicSwap["F"] = "E#" ;	$fullEnharmonicSwap["E"] = "Fb" ;
$strict = FALSE ;

// Functions
function abort($string)	// print the string on STDERR and exit(1)
{
	fputs(STDERR, $string) ;
	exit(1) ;
}

function help_msg_and_exit()
{  echo 'Usage: php transpose_chords.php <range> [<hint>] ["strict"] ["font=<fontname>"]

where <range> is an integer, -11 to +11, and is the transposition in semitones
optional <hint> is the key of the tune, of the form [A-G]([#b])(m|M|min|maj)
optional keyword "strict" uses a stricter policy on what is a chord
optional "font="<fontname> means only transpose chords displayed in font

eg php transpose_chords.php -4 C# font=StaffBold

If range is 0, and no hint is given, convert all enharmonic spellings
If range is not 0, and no hint is given, transpose according to "C"
A "text chord" is a piece of text of the regular-expression form
\s*[A-G][#b]?   (leading space, uppercase A-G, optional # or b)

Trying to match the dim, dom, maj, aug, sus4, +9, add11, etc was considered fruitless
but a keyword "strict" is available to try and limit what we call a chord
\s*[A-G][#b]? ?(M|maj|m|min|dim|dom|sus|aug|add|7|9|11|13|b5|b11|b13)?
(as above, but with optional trailing space followed by optional chord modifiers).
' ;
exit (NWC2RC_REPORT) ;
}

function rootChordValue($t)	// given text $t, return the chord at the start of it
{
	global $strict ;
	// fist test strict conditions if we have $strict set
	if ($strict && preg_match("/^\s*([A-G][#b]?) ?($|M|maj|m|min|dim|dom|sus|aug|add|7|9|11|13|b5|b11|b13|\+4)/", $t, $m))
		return $m[1] ;
	if ($strict) return false ;
	if (!preg_match("/^\s*([A-G][#b]?)/", $t, $m))
		return false;	// couldn't find a chord
	else return $m[1] ;
}

function baseNote($t)	// given text $t, return a root note if there is one. Root notes are defined in chords of the form
//	chord/root  eg A7/C#, C/G, 
{
	if (!preg_match("/\/([A-G][#b]?)$/", $t, $m))
		return false ; 	// couldn't find a base note
	else return $m[1] ;
}

function transpose(&$t)	// t the object's Opts["Text"] entry, passed by reference
{
	global $transposeKey, $transposeValue, $chords, $simpleTranspose, $chordPosition, $fullEnharmonicSwap ;
	
	if (!($bcv = rootChordValue($t)))
		return ;	// couldn't find a chord
	// okay, if we are doing a simple transpose (trans 0, no transposeKey set) and is enharmonic
	if ($transposeValue == 0 && !isset($transposeKey))
	{
		if (isset($simpleTranspose[$bcv])) 
			$t = preg_replace("/^(\s*)([A-G][#b]?)/", "\$1".$simpleTranspose[$bcv], $t, 1) ;
		if (($b = baseNote($t)) && isset($simpleTranspose[$b]))	// and transpose base note if it is there and needs it
			$t = preg_replace("/\/([A-G][#b]?)$/", "/".$simpleTranspose[$b], $t, 1) ;
		return ;
	}
	// okay, we're doing a transposition according to $transposeKey
	$semitones = ($chordPosition[$bcv] + $transposeValue + 12) % 12 ;
	$newC = $chords[$transposeKey][$semitones] ;
	$t = preg_replace("/^(\s*)([A-G][#b]?)/", "\$1".$newC, $t, 1) ; 
	if ($b = baseNote($t))
	{
		$semitones = ($chordPosition[$b] + $transposeValue + 12) % 12 ;
		$newB = $chords[$transposeKey][$semitones] ;		// work out the new base, and try and match chord for # and b if necessary
		if ((preg_match("/#/", $newC) && !preg_match("/#/", $newB)) || (preg_match("/b/", $newC) && !preg_match("/b/", $newB)))
			if (isset($fullEnharmonicSwap[$newB]))
				$newB = $fullEnharmonicSwap[$newB] ;
		$t = preg_replace("/\/([A-G][#b]?)$/", "/".$newB, $t, 1) ;
	}
	return ;
}

require_once("lib/nwc2clips.inc");

// THE MAIN ACTION STARTS HERE
array_shift($argv) ;	// don't need the program name
foreach ($argv as $arg) {
	if ($arg == "help") help_msg_and_exit() ;
	elseif (preg_match("/^-?[0-9]$/", $arg)) $transposeValue = $arg ;
	elseif (preg_match("/^[A-G][#b]?m?$/", $arg)) $transposeKey = $arg ;
	elseif ($arg == "strict") $strict = TRUE ;
	elseif (preg_match("/^font=(\w+)/", $arg, $m)) $font = $m[1] ;
	else abort("Unknown parameter \"$arg\". Use \"help\" for more details.") ;
}

if (!isset($transposeValue) || $transposeValue < -11 || $transposeValue > 11) abort("Must provide an integer between -11 and 11 as transposition") ;
if ($transposeValue != 0 && !isset($transposeKey)) $transposeKey = "C" ;

$clip = new NWC2Clip('php://stdin');

// 1. Scan each line
echo $clip->GetClipHeader()."\n";

$nothingToDo = TRUE ;
foreach ($clip->Items as $item) 
{
	$nothingToDo = FALSE ;
	$o = new NWC2ClipItem($item);
	if ($o->GetObjType() == "Text" && ($t =& $o->GetTaggedOpt("Text")) && rootChordValue($t)
			&& (isset($font) ? $o->GetTaggedOpt("Font") == $font : true))
	{
		transpose($t) ;
		echo $o->ReconstructClipText()."\n" ;
	}
  else echo $item ;
  unset($o);
}
  
echo NWC2_ENDCLIP."\n";
if ($nothingToDo) abort("This tool requires a selection.
Please select something on the staff before invoking transpose_chords.") ;

exit (NWC2RC_SUCCESS) ;
?>
