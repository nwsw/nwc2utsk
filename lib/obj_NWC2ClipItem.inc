<?php

// -----------------
class NWC2ClipItem {
// -----------------

var $ObjType="";
var $Opts=array();

// Constructor
function NWC2ClipItem($itemtext,$remapFakes=false)
{
	$o = preg_split('/(?<!\\\)\|/',trim($itemtext),-1,PREG_SPLIT_NO_EMPTY);
	$this->ObjType = array_shift($o);
	$isFake = ($this->ObjType == "Fake");
	if ($remapFakes && $isFake) $this->ObjType = array_shift($o);
	foreach ($o as $v) {
		if (preg_match('/^([0-9a-z\-\_]+):(.*)/i',$v,$m)) {
			$tag = $m[1];
			$data = trim($m[2]);
			//
			switch (NWC2ClassifyOptTag($this->ObjType,$tag)) {
				case NWC2OPT_TEXT:
					if (preg_match('/^\"(.*)\"$/',$data,$m)) $data = $m[1];
					// For legacy files, we need to convert \\ into \], which is the new construct for the backslash.
					// This avoids double detection/replacement of \ characters adjacent to other matching characters.
					$data = str_replace(array('\\\\','\\\'','\\\"','\\r','\\n','\\t','\\}','\\|','\\]'), array('\\]','\'','\"',"\r","\n","\t","|","|",'\\'),$data);
					break;

				case NWC2OPT_LIST:
					$data = preg_split('/(?<!\\\)\,\s*/',$data);
					break;

				case NWC2OPT_ASSOCIATIVE:
					$ldata = preg_split('/(?<!\\\)\,\s*/',$data);
					$data = array();
					foreach ($ldata as $ldv) {
						if (preg_match('/^(.+)\=(.*)$/',$ldv,$m)) $data["$m[1]"] = $m[2];
						else $data[$ldv] = "";
						}
					break;
				}

			$this->Opts[$tag] = $data;
			}
		else {
			$this->Opts[$v] = "";
			}
		}

	if ($remapFakes && $isFake) $this->Opts["Fake"] = "";
}

function &GetObjType() {return $this->ObjType;}
function &GetOpts() {return $this->Opts;}
function &GetTaggedOpt($tag,$nonexistent=false) {
	if (isset($this->Opts[$tag])) return $this->Opts[$tag];
	return $nonexistent;
	}
function GetTaggedOptAsArray($tag,$nonexistent=false) {
	if (isset($this->Opts[$tag])) {
		if (is_array($this->Opts[$tag])) return $this->Opts[$tag];
		return array($this->Opts[$tag]);
		}
	return $nonexistent;
	}
function IsFake() {
	return ($this->ObjType == "Fake") || isset($this->Opts["Fake"]);
	}
function IsContextInfo() {
	return $this->IsFake() || ($this->ObjType == "Context");
	}

function ReconstructClipText()
{
	if ($this->IsContextInfo()) return "|Fake";

	$s = "|".$this->ObjType;
	foreach ($this->Opts as $k => $v) {
		$c = NWC2ClassifyOptTag($this->ObjType,$k);

		if ($v === "") {
			$s .= "|$k";
			continue;
			}

		$s .= "|$k:";
		$optCounter = 0;
		if (is_string($v)) {
			// First encode all but '|' as cslashes, then convert \ and | to special combinations
			$v2 = str_replace(array('\\\\','|'), array('\\]','\\}'),addcslashes($v,"\r\n\t\\\"\'"));
			if ($c == NWC2OPT_TEXT) $s .= "\"$v2\"";
			else $s .= $v2;
			}
		else if (is_numeric($v)) $s .= $v;
		else if (is_array($v)) foreach ($v as $k2 => $v2) {
			$s .= ($optCounter++ ? "," : "");
			//
			switch ($c) {
				case NWC2OPT_ASSOCIATIVE:
					$s .= $k2;
					if ($v2 !== "") $s .= "=$v2";
					break;

				default:
					$s .= $v2;
					break;
				}
			}
		}
	//
	return $s;
}
};

?>