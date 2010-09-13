<?php
define("APPNAME","nwswEncodeFile");
define("APPVERSION","1.0");
define("APPDESC",
<<<___EODESC
This tool assists with inlining a file, such as an icon or other image, directly into your script.
___EODESC
);

require_once("lib/nwc2clips.inc");
require_once("lib/nwc2gui.inc");

function EncodeFile($fname)
{
	$name = preg_replace('/[^a-zA-Z0-9]+/','_',pathinfo($fname,PATHINFO_BASENAME|PATHINFO_EXTENSION));
	$data = file_get_contents($fname);
	$opts = array();

	$gzdata = gzcompress($data);
	if ((strlen($gzdata)+10) < strlen($data)) {
		$opts[] = "gz";
		$data = $gzdata;
		}

	$opts[] = "base64";

	return array(
		"data" => base64_encode($data),
		"opts" => $opts,
		"name" => $name,
		"id"   => 'MSTREAM_'.strtoupper($name)
		);
}

function BuildEncodedDataDefine($data,$maxl,$quoteChar="'",$newLinePrefix="\t")
{
	$a = str_split($data['data'],$maxl);
	$s = "";
	// Use a loop so that we can addslashes, in case the choice of encoding includes quotes
	foreach ($a as $ln) {
		if ($s) $s .= $quoteChar.".".PHP_EOL.$newLinePrefix.$quoteChar;
		$s .= addcslashes($ln,$quoteChar);
		}

	return "define('$data[id]','$s');";
}

function BuildConvScript($fname,$data,$maxl)
{
	return
		'// The following code can be used to embed this file ('.$fname.')'.PHP_EOL.PHP_EOL.
		'// Embedded file code created by nwswEncodeFile.php'.PHP_EOL.
		BuildEncodedDataDefine($data,max($maxl,16),"'","\t").PHP_EOL.
		"\$$data[name] = new nwc2gui_MemoryInStream($data[id],array('".implode("','",$data["opts"])."'));".PHP_EOL;
}

class nwcut_MainWindow extends wxDialog
{
	var $Data = false;
	var $ctrl_FileNameText = false;
	var $ctrl_ImgEmbedText = false;
	var $ctrl_MaxLineLength = false;

	function nwcut_MainWindow()
	{
		parent::__construct(null,-1,APPNAME,wxDefaultPosition,wxDefaultPosition);
	
		$this->SetIcons(new nwc2gui_IconBundle);

		$wxID = wxID_HIGHEST;

		$MainSizer = new wxBoxSizer(wxVERTICAL);
		$this->SetSizer($MainSizer);
		
		$ControlPanel = new wxBoxSizer(wxVERTICAL);
		$MainSizer->Add($ControlPanel,0,wxGROW|wxALL,20);

		$newrow = new wxBoxSizer(wxHORIZONTAL);
		$ControlPanel->Add($newrow);
		//
		$btn = new wxButton($this, ++$wxID, "Select File");
		$newrow->Add($btn,0,wxALIGN_CENTER);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onSelectFile"));
		$newrow->AddSpacer(5);
		$label = new wxStaticText($this, ++$wxID, "<filename>");
		$newrow->Add($label,0,wxALIGN_CENTER);
		$this->ctrl_FileNameText = $label;

		$ControlPanel->AddSpacer(5);

		$newrow = new wxBoxSizer(wxHORIZONTAL);
		$ControlPanel->Add($newrow);
		//
		$label = new wxStaticText($this, ++$wxID, "Maximum Encode Line Length:");
		$newrow->Add($label,0,wxALIGN_CENTER);
		$newrow->AddSpacer(5);
		//
		$this->ctrl_MaxLineLength = new wxSpinCtrl($this,++$wxID,"",wxDefaultPosition,wxDefaultSize,wxSP_ARROW_KEYS,32,32000,512);
		$newrow->Add($this->ctrl_MaxLineLength,0,wxALIGN_CENTER);
		$this->Connect($wxID,wxEVT_COMMAND_TEXT_UPDATED,array($this,"onUpdateCode"));

		$ControlPanel->AddSpacer(5);

		$text = new wxTextCtrl($this, ++$wxID,"Select a file, then a code snippet for inline streaming will be shown here",wxDefaultPosition,new wxSize(400,250),wxTE_MULTILINE|wxTE_DONTWRAP|wxTE_NOHIDESEL|wxTE_READONLY);
		$ControlPanel->Add($text,1,wxGROW);
		$this->ctrl_ImgEmbedText = $text;

		$ButtonPanel = new wxStaticBoxSizer(wxVERTICAL, $this);
		$MainSizer->Add($ButtonPanel,0,wxGROW|wxALL,0);
		
		$box = new wxBoxSizer(wxHORIZONTAL);
		$ButtonPanel->Add($box,0,wxALIGN_RIGHT|wxALL,0);

		$btn = new wxButton($this, ++$wxID, "About...");
		$box->Add($btn);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onAbout"));

		$box->AddSpacer(15);

		$btn_cancel = new wxButton($this, wxID_CANCEL, "Close");
		$box->Add($btn_cancel,0,wxGROW);
		$this->Connect(wxID_CANCEL,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onQuit"));

		$MainSizer->Fit($this);
	}

	function onSelectFile()
	{
		$dlg = new wxFileDialog($this,"Choose a File",dirname(__FILE__),"","Image Files|*.png;*.ico;*.bmp;*.jpg|All Files|*.*");
		if ($dlg->ShowModal() == wxID_OK) {
			$this->ctrl_FileNameText->SetLabel($dlg->GetPath());
			$this->Data = EncodeFile($dlg->GetPath());
			$this->onUpdateCode();
			}
	}

	function onUpdateCode()
	{
		if ($this->Data) {
			$script = BuildConvScript($this->ctrl_FileNameText->GetLabel(),$this->Data,intval($this->ctrl_MaxLineLength->GetValue()));
			$this->ctrl_ImgEmbedText->SetValue($script);
			}
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