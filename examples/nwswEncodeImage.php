<?php
define("APPNAME","nwsw Image Embed Encoder");
define("APPVERSION","1.0");
define("APPDESC",
<<<___EODESC
This tool shows you how to embed a *.png image into your script.
___EODESC
);

require_once("lib/nwc2clips.inc");
require_once("lib/nwc2gui.inc");

function BuildEncodedDataString($pngConv,$maxl)
{
	$a = str_split($pngConv,$maxl);
	$s = "";
	// Use a loop so that we can addslashes, in case the choice of encoding includes quotes
	foreach ($a as $ln) {
		if ($s) $s .= "'.".PHP_EOL."\t'";
		$s .= addslashes($ln);
		}

	return "\t'".$s."';";
}

function BuildConvScript($pngConv,$maxl)
{
	return
		'// The following code can be used to embed this image'.PHP_EOL.PHP_EOL.
		'//Image embed code created by nwswEncodeImage.php'.PHP_EOL.
		'$tmpfile_PNG = tempnam("", "nwcut");'.PHP_EOL.
		'$pngData ='.PHP_EOL.BuildEncodedDataString($pngConv,max($maxl,16)).PHP_EOL.
		'file_put_contents($tmpfile_PNG,base64_decode($pngData));'.PHP_EOL.
		'$my_bitmap = new wxBitmap($tmpfile_PNG,wxBITMAP_TYPE_PNG);'.PHP_EOL.
		'unlink($tmpfile_PNG);'.PHP_EOL;
}

class nwcut_MainWindow extends wxDialog
{
	var $ctrl_ImgEmbedText = false;
	var $ctrl_MaxLineLength = false;

	function nwcut_MainWindow()
	{
		parent::__construct(null,-1,APPNAME,wxDefaultPosition,wxDefaultPosition);
	
		$wxID = wxID_HIGHEST;

		$MainSizer = new wxBoxSizer(wxVERTICAL);
		$this->SetSizer($MainSizer);
		
		$ControlPanel = new wxBoxSizer(wxVERTICAL);
		$MainSizer->Add($ControlPanel,0,wxGROW|wxALL,20);

		$btn = new wxButton($this, ++$wxID, "Select Image");
		$ControlPanel->Add($btn);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onSelectImage"));

		$ControlPanel->AddSpacer(5);

		$newrow = new wxBoxSizer(wxHORIZONTAL);
		$ControlPanel->Add($newrow);
		//
		$label = new wxStaticText($this, ++$wxID, "Maximum Encode Line Length:");
		$newrow->Add($label);
		$newrow->AddSpacer(5);
		//
		$this->ctrl_MaxLineLength = new wxSpinCtrl($this,++$wxID,"",wxDefaultPosition,wxDefaultSize,wxSP_ARROW_KEYS,32,32000,512);
		$newrow->Add($this->ctrl_MaxLineLength);

		$ControlPanel->AddSpacer(5);

		$text = new wxTextCtrl($this, ++$wxID,"Select an image, then a code snippet for embedding it will be shown here",wxDefaultPosition,new wxSize(400,250),wxTE_MULTILINE|wxTE_DONTWRAP|wxTE_NOHIDESEL|wxTE_READONLY);
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

	function onSelectImage()
	{
		$dlg = new wxFileDialog($this,"Choose an image file",dirname(__FILE__),"","PNG Image File (*.png)|*.png");
		if ($dlg->ShowModal() == wxID_OK) {
			$pngConv = base64_encode(file_get_contents($dlg->GetPath()));
			$script = BuildConvScript($pngConv,intval($this->ctrl_MaxLineLength->GetValue()));
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