<?php
require_once("lib/nwc2clips.inc");
require_once("lib/nwc2gui.inc");

class nwcut_MainWindow extends wxDialog
{
	function nwcut_MainWindow()
	{
		parent::__construct(null,-1,"Basic Sample Interface",wxDefaultPosition,wxDefaultPosition);
	
		$wxID = wxID_HIGHEST;

		$MainSizer = new wxBoxSizer(wxVERTICAL);
		$this->SetSizer($MainSizer);
		
		$ControlPanel = new wxStaticBoxSizer(wxVERTICAL, $this);
		$MainSizer->Add($ControlPanel,0,wxGROW|wxALL,20);

		$text = new wxTextCtrl($this, ++$wxID,"Sample Message",wxDefaultPosition,new wxSize(400,450),wxTE_MULTILINE|wxTE_DONTWRAP|wxTE_NOHIDESEL|wxTE_READONLY);
		$ControlPanel->Add($text,1,wxGROW);

		$ButtonPanel = new wxStaticBoxSizer(wxVERTICAL, $this);
		$MainSizer->Add($ButtonPanel,0,wxGROW|wxALL,0);
		
		$box = new wxBoxSizer(wxHORIZONTAL);
		$ButtonPanel->Add($box,0,wxALIGN_RIGHT|wxALL,0);

		$btn_test = new wxButton($this, ++$wxID, "Test");
		$box->Add($btn_test,0,wxGROW);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onTest"));

		$box->AddSpacer(20);

		$btn_cancel = new wxButton($this, wxID_CANCEL);
		$box->Add($btn_cancel,0,wxGROW);
		$this->Connect(wxID_CANCEL,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onQuit"));

		$MainSizer->Fit($this);
	}

	function onTest()
	{
		$redirs = array(array("file", "php://stdin", "r"),
				array("file", "php://stdout", "w"),
				array("file", "php://stderr", "w"));
		//$redirs = array();

		$bypshell = array("bypass_shell" => true);
		//$bypshell = array();


		$process = proc_open('X:\Dev-bc\BIN\php\php.exe Scripts\nwsw_About.php', $redirs, $sink, null, null, $bypshell);
		$exitcode = proc_close($process);
	}

	function onQuit()
	{
		$this->Destroy();
	}
}

class nwcut_MainApp extends wxApp 
{
	function OnInit()
	{
		$Frame = new nwcut_MainWindow();
		$Frame->Show();

		return 0;
	}
	
	function OnExit() {return 0;}
}

function nwcut_InitWX()
{
	$App = new nwcut_MainApp();
	wxApp::SetInstance($App);
	wxEntry();
}

nwcut_InitWX();

exit(NWC2RC_REPORT);
?>
