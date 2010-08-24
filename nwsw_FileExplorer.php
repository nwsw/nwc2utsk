<?php
/*******************************************************************************
nwsw_FileExplorer.php Version 0.1
*******************************************************************************/
define("APPNAME","nwxtxt File Explorer");
define("APPVERSION","0.1");
define("USETREECONTROL",false);

require_once("lib/nwc2clips.inc");
require_once("lib/nwc2gui.inc");

$nwctxtLines = gzfile('php://stdin');

class nwcut_MainApp extends wxApp 
{
	public $AppFrame = false;

	function OnInit()
	{
		$this->AppFrame = new nwcut_MainWindow();
		$this->AppFrame->Show();

		return 0;
	}
	
	function OnExit()
	{
		return 0;
	}
}

class nwcut_MainWindow extends wxDialog
{
	public $ctrl_LineList = false;
	public $ctrl_Desc = false;

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
							}
						break;
					case NWCTXTLTYP_OBJECT:
						$d = NWC2GetObjType($l);
						if ($d == "AddStaff") {
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
				else if ($makeNewLevel < 0) array_pop($treeBranch);
				}
			$tree->Expand($treeRoot);
			$newcol->Add($tree,1,wxEXPAND);
			}
		else {
			$label = new wxStaticText($this, ++$wxID, "nwctxt Input Lines:");
			$newcol->Add($label);

			$lb_list = new wxArrayString();
			foreach ($nwctxtLines as $l) {
				$d = "--Error--";
				switch(NWC2ClassifyLine($l)) {
					case NWCTXTLTYP_OBJECT:
						$d = NWC2GetObjType($l);
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
		$text = new wxTextCtrl($this, ++$wxID,"Select a line at the left to have it described here",wxDefaultPosition,new wxSize(400,450),wxTE_MULTILINE|wxTE_DONTWRAP|wxTE_NOHIDESEL|wxTE_RICH);
		$newcol->Add($text,1,wxEXPAND);
		$this->ctrl_Desc = $text;

		$btnrow = new wxStaticBoxSizer(wxVERTICAL,$this);
		$MainSizer->Add($btnrow,0,wxEXPAND);

		$box = new wxBoxSizer(wxHORIZONTAL);
		$btnrow->Add($box,0,wxALIGN_CENTER|wxALL,10);

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

			$d .= "\n".
				"$ObjType Object (Type $ObjClassificationID, ".nw_aafield($ntnTypes,$ObjClassificationID,"NWC2OBJTYP_ERROR").")\n".
				"\n".print_r(new NWC2ClipItem($ln),true)."\n";
			}

		$this->ctrl_Desc->SetValue($d);
	}

	function onQuit()
	{
		$this->Destroy();
	}
}

function nwcut_InitWX()
{
	// This init function is designed to protect the $App variable from the global scope, which prevents problems during Destroy
	$App = new nwcut_MainApp();
	wxApp::SetInstance($App);
	wxEntry();
}

//--------------------------------

nwcut_InitWX();

// As long as no warning or errors, NWC should just return back to the editor
exit(NWC2RC_SUCCESS);
?>