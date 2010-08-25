<?php
define("APPNAME","nwctxt File Explorer");
define("APPVERSION","0.1");
define("APPDESC",
<<<___EODESC
This tool shows all of the "File Text (nwctxt)" that is sent from 
the NoteWorthy Composer User Tool mechanism and the standard
objects that can be created when using the starter kit library.
___EODESC
);
define("USETREECONTROL",true);

require_once("lib/nwc2clips.inc");
require_once("lib/nwc2gui.inc");

$nwctxtLines = gzfile('php://stdin');

class nwcut_MainApp extends wxApp 
{
	function OnInit()
	{
		$MainFrame = new nwcut_MainWindow();
		$MainFrame->Show();

		return 0;
	}

	function OnExit()	{return 0;}
}

class nwcut_MainWindow extends wxDialog
{
	public $ctrl_LineList = false;
	public $ctrl_Desc = false;
	public $staffIndexes = array();

	function nwcut_MainWindow()
	{
		global $nwctxtLines;
		parent::__construct(null,-1,APPNAME,wxDefaultPosition,new wxSize(620,500));
	
		$wxID = wxID_HIGHEST;

		$MainSizer = new wxBoxSizer(wxVERTICAL);
		$this->SetSizer($MainSizer);

		$newrow = new wxBoxSizer(wxHORIZONTAL);
		$MainSizer->Add($newrow, 0, wxEXPAND|wxALL,10);

		$newcol = new wxBoxSizer(wxVERTICAL);
		$newrow->Add($newcol, 0, wxEXPAND);

		if (USETREECONTROL) {
			$tree = new wxTreeCtrl($this,++$wxID,wxDefaultPosition,new wxSize(200,500),wxTR_HAS_BUTTONS);
			$this->Connect($wxID, wxEVT_COMMAND_TREE_SEL_CHANGED, array($this, "doTreeSelect"));
			$this->ctrl_LineList = $tree;
			$treeRoot = $tree->AddRoot("nwctxt Input");
			$treeBranch[] = $treeRoot;
			foreach ($nwctxtLines as $index => $l) {
				$d = "--Error--";
				$targetLevel = count($treeBranch);
				$makeNewLevel = 0;

				switch(NWC2ClassifyLine($l)) {
					case NWCTXTLTYP_FORMATHEADER:
						$d = trim($l);
						$targetLevel = 1;
						if (!strstr($l,"-End")) {
							$makeNewLevel = 1;
							$this->staffIndexes[] = $index + 1;
							}
						break;
					case NWCTXTLTYP_OBJECT:
						$d = NWC2GetObjType($l);
						if ($d == "AddStaff") {
							$this->staffIndexes[] = $index;
							$targetLevel = 2;
							$makeNewLevel = 1;
							}
						break;
					case NWCTXTLTYP_COMMENT:
						$d = "#Comment";
						break;
					default:
						break;
					}

				while (count($treeBranch) > $targetLevel) array_pop($treeBranch);
				$newnode = $tree->AppendItem(end($treeBranch),"$d  ^".($index+1)."");
				if ($makeNewLevel > 0) $treeBranch[] = $newnode;
				}
			$tree->Expand($treeRoot);
			$newcol->Add($tree,1,wxEXPAND);
			}
		else {
			$label = new wxStaticText($this, ++$wxID, "nwctxt Input Lines:");
			$newcol->Add($label);

			$lb_list = new wxArrayString();
			foreach ($nwctxtLines as $index => $l) {
				$d = "--Error--";
				switch(NWC2ClassifyLine($l)) {
					case NWCTXTLTYP_OBJECT:
						$d = NWC2GetObjType($l);
						if ($d == "AddStaff") $this->staffIndexes[] = $index;
						break;
					case NWCTXTLTYP_FORMATHEADER:
						$d = trim($l);
						break;
					case NWCTXTLTYP_COMMENT:
						$d = "#Comment";
						break;
					}

				$lb_list->Add($d);
				}

			$lbox = new wxListBox($this,++$wxID,wxDefaultPosition,new wxSize(200,450),$lb_list,wxLB_SINGLE|wxLB_HSCROLL);
			$this->Connect($wxID, wxEVT_COMMAND_LISTBOX_SELECTED, array($this, "doShowLineDesc"));
			$this->ctrl_LineList = $lbox;
			$newcol->Add($lbox);
			}

		$newrow->AddSpacer(10);

		$newcol = new wxStaticBoxSizer(wxVERTICAL,$this,"Description of input text line");
		$newrow->Add($newcol, 1, wxGROW);
		$text = new wxTextCtrl($this, ++$wxID,"Select a line at the left to have it described here",wxDefaultPosition,new wxSize(400,450),wxTE_MULTILINE|wxTE_DONTWRAP|wxTE_NOHIDESEL|wxTE_READONLY);
		$newcol->Add($text,1,wxEXPAND);
		$this->ctrl_Desc = $text;

		$btnrow = new wxStaticBoxSizer(wxVERTICAL,$this);
		$MainSizer->Add($btnrow,0,wxEXPAND);

		$box = new wxBoxSizer(wxHORIZONTAL);
		$btnrow->Add($box,0,wxALIGN_RIGHT|wxALL,5);

		$btn = new wxButton($this,++$wxID,"&About...");
		$box->Add($btn);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onAbout"));

		$box->AddSpacer(15);

		$btn = new wxButton($this,wxID_CANCEL,"Close");
		$box->Add($btn);

		$this->Connect(wxID_CANCEL,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onQuit"));

		$MainSizer->Fit($this);
	}

	function doTreeSelect($evt)
	{
		$linenum = -1;
		$lbl = $this->ctrl_LineList->GetItemText($evt->GetItem());
		$indexStart = strrpos($lbl,'^');
		if ($indexStart !== false) {
			$linenum = intval(substr($lbl,$indexStart+1)) - 1;
			}

		$this->ShowDesc($linenum);
	}

	function doShowLineDesc($evt)
	{
		$this->ShowDesc($evt->GetSelection());
	}

	function ShowDesc($linenum)
	{
		static $lnTypes = array(NWCTXTLTYP_OBJECT=>"NWCTXTLTYP_OBJECT",NWCTXTLTYP_FORMATHEADER=>"NWCTXTLTYP_FORMATHEADER",NWCTXTLTYP_COMMENT=>"NWCTXTLTYP_COMMENT");
		static $ntnTypes = array(
			NWC2OBJTYP_FILEPROPERTY=>"NWC2OBJTYP_FILEPROPERTY",NWC2OBJTYP_STAFFPROPERTY=>"NWC2OBJTYP_STAFFPROPERTY",NWC2OBJTYP_STAFFLYRIC=>"NWC2OBJTYP_STAFFLYRIC",
			NWC2OBJTYP_STAFFNOTATION=>"NWC2OBJTYP_STAFFNOTATION");

		global $nwctxtLines;

		if (!isset($nwctxtLines[$linenum])) {
			$this->ctrl_Desc->SetValue("Select a line at the left to have it described here");
			return;
			}

		$ln = trim($nwctxtLines[$linenum]);

		$lnTypeID = NWC2ClassifyLine($ln);
		$lnType = nw_aafield($lnTypes,$lnTypeID,"NWCTXTLTYP_ERROR");

		$d = "Line $linenum (Type $lnTypeID, $lnType)\n\n$ln\n";

		if ($lnTypeID == NWCTXTLTYP_OBJECT) {
			$ObjType = NWC2GetObjType($ln);
			$ObjClassificationID = NWC2ClassifyObjType($ObjType);
			$ClipObj = (NWC2ClipItemWithPitchPos::ObjTypeHasPitchPos($ObjType) ? new NWC2ClipItemWithPitchPos($ln) : new NWC2ClipItem($ln));

			$d .= "\n".
				"$ObjType Object (Type $ObjClassificationID, ".nw_aafield($ntnTypes,$ObjClassificationID,"NWC2OBJTYP_ERROR").")\n".
				"\n".print_r($ClipObj,true)."\n";

			if (($ObjClassificationID == NWC2OBJTYP_STAFFNOTATION) && $this->staffIndexes) {
				end($this->staffIndexes);
				while (current($this->staffIndexes) > $linenum) prev($this->staffIndexes);
				$playIndex = current($this->staffIndexes);
				$PlayContext = new NWC2PlayContext();
				while ($playIndex < $linenum) {
					$o = new NWC2ClipItemWithPitchPos($nwctxtLines[$playIndex++]);
					$PlayContext->UpdateContext($o);
					}

				$d .= "\n".print_r($PlayContext,true)."\n";
				}
			}

		$this->ctrl_Desc->SetValue($d);
	}

	function onAbout()
	{
		$dlg = new wxMessageDialog($this,
			APPNAME." (Version ".APPVERSION.")\n\n".
			APPDESC."\n\n".
			"It is currently using a ".(USETREECONTROL ? "tree" : "listbox")." control to show nwctxt lines.",
			"About",wxICON_INFORMATION);
		$dlg->ShowModal();
	}

	function onQuit()
	{
		$this->Destroy();
	}
}

function nwcut_InitWX()
{
	// This init function is designed to protect the $App variable from the global scope.
	// This prevents some problems that can arise during Destroy.
	$App = new nwcut_MainApp();
	wxApp::SetInstance($App);
	wxEntry();
}

//--------------------------------

nwcut_InitWX();

// As long as no warning or errors, NWC should just return back to the editor
exit(NWC2RC_SUCCESS);
?>