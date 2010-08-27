<?php
define("APPNAME","nwsw PHP Class Explorer");
define("APPVERSION","0.1");
define("APPDESC",
<<<___EODESC
This tool shows all of the available PHP classes. You can click
on any class name in order to see its methods and variables.
___EODESC
);

require_once("lib/nwc2clips.inc");
require_once("lib/nwc2gui.inc");

class nwcut_MainApp extends wxApp 
{
	function OnInit()
	{
		$AppFrame = new nwcut_MainWindow();
		$AppFrame->Show();

		return 0;
	}
	
	function OnExit() {return 0;}
}

class nwcut_MainWindow extends wxDialog
{
	public $ClassList = array();
	public $ctrl_LineList = false;
	public $ctrl_Desc = false;

	function nwcut_MainWindow()
	{
		global $ClassList;
		parent::__construct(null,-1,APPNAME,wxDefaultPosition,new wxSize(620,500));

		$this->ClassList = get_declared_classes();
		//
		// Remove our GUI classes
		while (strstr(end($this->ClassList),'nwcut_')) array_pop($this->ClassList);
		//
		sort($this->ClassList);
	
		$wxID = wxID_HIGHEST;

		$MainSizer = new wxBoxSizer(wxVERTICAL);
		$this->SetSizer($MainSizer);

		$newrow = new wxBoxSizer(wxHORIZONTAL);
		$MainSizer->Add($newrow, 0, wxEXPAND|wxALL,10);

		$newcol = new wxBoxSizer(wxVERTICAL);
		$newrow->Add($newcol, 0, wxEXPAND);

		$label = new wxStaticText($this, ++$wxID, "PHP Class List:");
		$newcol->Add($label);

		$lbox = new wxListBox($this,++$wxID,wxDefaultPosition,new wxSize(200,450),nwc2gui_wxArray($this->ClassList),wxLB_SINGLE|wxLB_HSCROLL);
		$this->Connect($wxID, wxEVT_COMMAND_LISTBOX_SELECTED, array($this, "doShowClassDesc"));
		$this->ctrl_LineList = $lbox;
		$newcol->Add($lbox);

		$newrow->AddSpacer(10);

		$newcol = new wxStaticBoxSizer(wxVERTICAL,$this,"Class Description");
		$newrow->Add($newcol, 1, wxGROW);
		$text = new wxTextCtrl($this, ++$wxID,"Select a class at the left to have it described here",wxDefaultPosition,new wxSize(400,450),wxTE_MULTILINE|wxTE_DONTWRAP|wxTE_NOHIDESEL|wxTE_READONLY);
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

	function doShowClassDesc($evt)
	{
		$this->ShowDesc($evt->GetSelection());
	}

	function ShowDesc($linenum)
	{
		$nm = nw_aafield($this->ClassList,$linenum);

		if (!$nm) {
			$this->ctrl_Desc->SetValue("Select a class at the left to have it described here");
			return;
			}

		$cm = get_class_methods($nm);
		$cv = get_class_vars($nm);

		Sort($cm);

		$d = "Class: $nm\n\nClass Methods: ".print_r($cm,true);
		if ($cv) {
			Sort($cv);
			$d .= "\n\nClass Variables: ".print_r($cv,true);
			}

		$this->ctrl_Desc->SetValue($d);
	}

	function onAbout()
	{
		$dlg = new wxMessageDialog($this,
			APPNAME." (Version ".APPVERSION.")\n\n".
			APPDESC."\n",
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
	// This prevents some scope problems that can arise during Destroy.
	$App = new nwcut_MainApp();
	wxApp::SetInstance($App);
	wxEntry();
}

//--------------------------------

nwcut_InitWX();

exit(NWC2RC_SUCCESS);
?>