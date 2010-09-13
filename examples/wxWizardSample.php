<?php
require_once("lib/nwc2clips.inc");
require_once("lib/nwc2gui.inc");

class nwcut_WizardPanel extends wxScrolledWindow
{
	private $PanelNum = -1;
	private $Parent;
	private $NumberBox;

	function nwcut_WizardPanel($parent,$panelnum,$sz)
	{
		parent::__construct($parent,-1,wxDefaultPosition,$sz);

		$this->Parent = $parent;
		$this->PanelNum = $panelnum;
		$this->SetScrollRate(1,1);

		$panelSizer = new wxBoxSizer(wxVERTICAL);
		$this->SetSizer($panelSizer);
		$wxID = wxID_HIGHEST;

		$newrow = new wxBoxSizer(wxHORIZONTAL);
		$panelSizer->Add($newrow,0,wxGROW|wxALL,10);

		$statictext = new wxStaticText($this, ++$wxID, "This is panel $panelnum");
		$newrow->Add($statictext);
		//
		$newrow->AddSpacer(15);
		//
		$btn = new wxButton($this,++$wxID,"About Panel $panelnum");
		$newrow->Add($btn);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onAbout"));

		$newrow->AddSpacer(10);

		$this->NumberBox = new wxTextCtrl($this,++$wxID,"100");
		$newrow->Add($this->NumberBox,0,wxGROW);
		$newrow->AddSpacer(3);
		$btn = new wxSpinButton($this,++$wxID,wxDefaultPosition,new wxSize(-1,10));
		$newrow->Add($btn,0,wxGROW);
		$this->Connect($wxID,wxEVT_SCROLL_LINEUP,array($this,"onSpinUp"));
		$this->Connect($wxID,wxEVT_SCROLL_LINEDOWN,array($this,"onSpinDown"));

		$newrow = new wxStaticBoxSizer(wxVERTICAL,$this);
		$panelSizer->Add($newrow,0,wxGROW|wxALL,10);
		//
		for($i=0;$i<$panelnum;$i++) {
			$wxID++;

			if (($i%10) == 0) {
				if ($i) $newrow->AddSpacer(10);
				$newbox = new wxBoxSizer(wxHORIZONTAL);
				$newrow->Add($newbox,0,wxGROW);
				}
			else {
				$newbox->AddSpacer(10);
				}

			$btn = new wxButton($this,$wxID,"Test $wxID",wxDefaultPosition, wxDefaultSize);
			$newbox->Add($btn);
			$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"doTestBtn"));
			}

		$panelSizer->FitInside($this);
	}

	function doTestBtn($event)
	{
		$dlg = new wxMessageDialog($this->Parent,"Test button id ".$event->GetId()." pressed","Test",wxICON_INFORMATION);
		$dlg->ShowModal();
	}

	function onAbout()
	{
		$dlg = new wxMessageDialog($this->Parent,"Panel {$this->PanelNum} About Box","About box...",wxICON_INFORMATION);
		$dlg->ShowModal();
	}

	function onSpinDown($event)
	{
		$this->NumberBox->SetValue(intval($this->NumberBox->GetValue()) - 1);
	}

	function onSpinUp($event)
	{
		$this->NumberBox->SetValue(intval($this->NumberBox->GetValue()) + 1);
	}
}

class nwcut_WizardFrame extends wxDialog
{
	private $WizardPanel = false;
	private $WizardSizer = false;
	private $CurrentPanel = false;
	private $PanelNum = 0;

	function nwcut_WizardFrame()
	{
		parent::__construct(null,-1,"Sample Wizard Interface",wxDefaultPosition,new wxSize(690,400));
	
		$this->SetIcons(new nwc2gui_IconBundle);

		$wxID = wxID_HIGHEST;

		$this->WizardSizer = new wxBoxSizer(wxVERTICAL);
		$this->SetSizer($this->WizardSizer);

		$newrow = new wxBoxSizer(wxHORIZONTAL);
		$this->WizardSizer->Add($newrow, 0, wxGROW);
		//
		// Embedded file code created by nwswEncodeFile.php
		define('MSTREAM_SAMPLE_BMP',
			'eJztl8Fu1DAQhsf4kD1U9ZXDinDkEThULOLOHXFAeQSOvaWPFsSLGPECOaZSlGHs2OO/7QalUFQJ+a8cfbs7sX/bM0764eOXI0W9k/ZG2mdpn14QGfkLei2/'.
			'X7m1ydexcWjMVFVVVVVVVfXsajw17Fc+zWQz9/Kk7oeIhvmG2pUt80BOWW5euWEeya7svvNEJjEJ09rnK+pn4CXzkU7ArQzcAX9N7MRE5kZMII+Jg3uvPOpY'.
			'hkfjkx8xZAsvyb+7kRlz4oFOyqOYkJGD2llMpBeYdpGBF2XLs66oCTPO6j09kboNhgEMsAVugFtgMKf7LnLA/U9fQspYbiosvXQa4ul9DrmFXn5AL8Wlmwr3'.
			'XlkGUpaBlGWdC/MAPAEvwPwN+BZYfdrIyT+HJO40aMlzDEFDvpvjHdhTCdLZILfAFpiQW2DcC0LGvWuAca8xBzA3NvMHeY/29PPQzyFcLLADTnO8oHDsKOu6'.
			'XYayS+zkoNH6ZdbzxMVizFz8uBkY/vlwd/J9AmbkAXgCXjLH1MgfYv4ktRAkntMRci/oxOXcuB/kIchDkMaYMoAEjYV7NUQWQtx0NsTw+ZCTPxvSzGdDqPhK'.
			'Id3a4wIcJ+sTs1Z22N2x7GnZ66ficLSXvEI2mUMeWuCcn1G76gu52+D9ih7SGXWAfi4e8DruJXhz4fIv13MHi5+31Ci/zN4iF8/Hu9xtMGW+yuzu8rXyQKVO'.
			'vS08t1i/yFo5zQbjoW2h3A0wQWHGd6esE9SLgyLdocMD7uL1tzmQ6yuaTfxMOXAvH9QP5urFBh9gLvQnbB7JW+9LeA5svDvhM9pCGjxG/0G9X+e1irVvgc0O'.
			'pifha+gTeS5rZRZdqxvHQ5N4EE7+41Mw7XV8f4C9NrDXBp6WVVVVVVVVVX+jX2I+lOo=');
		$my_bitmap = new wxBitmap(new wxImage(new nwc2gui_MemoryInStream(MSTREAM_SAMPLE_BMP,array('gz','base64')),wxBITMAP_TYPE_BMP));
		//
		$imgControl = new wxStaticBitmap($this,++$wxID,$my_bitmap);
		$newrow->Add($imgControl, 0, wxGROW);

		$this->WizardPanel = new wxPanel($this,++$wxID,new wxPoint(0,0),new wxSize(600,300));
		$newrow->Add($this->WizardPanel);

		$ButtonPanel = new wxStaticBoxSizer(wxVERTICAL, $this);
		$this->WizardSizer->Add($ButtonPanel,0,wxGROW|wxALL,0);
		
		$box = new wxBoxSizer(wxHORIZONTAL);
		$ButtonPanel->Add($box,0,wxALIGN_RIGHT|wxALL,0);

		$btn_cancel = new wxButton($this, wxID_CANCEL);
		$box->Add($btn_cancel,0,wxGROW|wxRIGHT,40);
		$this->Connect(wxID_CANCEL,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onQuit"));

		$btn_back = new wxButton($this, ++$wxID, "<- Back");
		$box->Add($btn_back,0,wxGROW|wxRIGHT,6);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onPriorPanel"));

		$btn_next = new wxButton($this, ++$wxID, "Next ->");
		$box->Add($btn_next,0,wxGROW);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onNextPanel"));

		$this->WizardSizer->Fit($this);

		$this->CurrentPanel = new nwcut_WizardPanel($this->WizardPanel,1,new wxSize(600,300));
	}

	function ChangePanel($i)
	{
		if ($i < 0) $i = 99;
		else if ($i > 99) $i = 0;

		$this->PanelNum = $i;

		if ($this->CurrentPanel) {
			$this->CurrentPanel->Destroy();
			$this->CurrentPanel = false;
			}

		$this->CurrentPanel = new nwcut_WizardPanel($this->WizardPanel,$i+1,new wxSize(600,300));

		// In the absence of "wxScrolledWindow" you might need to resize the wizard here, depending on what you
		// are doing
		//$this->WizardSizer->Fit($this);
	}

	function onPriorPanel()
	{
		$this->ChangePanel($this->PanelNum - 1);
	}

	function onNextPanel()
	{
		$this->ChangePanel($this->PanelNum + 1);
	}

	function onQuit()
	{
		$this->Destroy();
	}
}

class nwcut_MainApp extends wxApp 
{
	public $AppFrame = false;

	function OnInit()
	{
		$this->AppFrame = new nwcut_WizardFrame();
		$this->AppFrame->Show();

		// In theory, if you want to initiate an action without the user pressing a button, you should probably 
		// call wxPostEvent here, but this may not be possible in wxphp version
		
		return 0;
	}
	
	function OnExit()
	{
		return 0;
	}
}

function nwcut_InitWX()
{
	$App = new nwcut_MainApp();
	wxApp::SetInstance($App);
	wxEntry();
}

nwcut_InitWX();

exit(NWC2RC_SUCCESS);
?>
