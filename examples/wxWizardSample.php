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
	
		//$this->SetIcon(new wxIcon(dirname(__FILE__).DIRECTORY_SEPARATOR.'wxIcon.ico',wxBITMAP_TYPE_ICO ));

		$wxID = wxID_HIGHEST;

		$this->WizardSizer = new wxBoxSizer(wxVERTICAL);
		$this->SetSizer($this->WizardSizer);

		$newrow = new wxBoxSizer(wxHORIZONTAL);
		$this->WizardSizer->Add($newrow, 0, wxGROW);
		//
		// Image embed code created by nwswEncodeImage.php
		$tmpfile_PNG = tempnam("", "nwcut");
		$pngData =
			'iVBORw0KGgoAAAANSUhEUgAAAFoAAAJYAQMAAADYMWNjAAAAB3RJTUUH2gkHDA0hH2tYVgAAAAlwSFlzAAAPYAAAD2ABenhFjQAAAAZQTFRFAAD/////e9yZLAAAAnNJREFUeNrtlkFu2zAQRSlwwSwCc9tFEF6jiwLKUXSELruTj6aiF1HRC2ipAIKYkWySj7ZlOEnRBCj/QsiDJ8OZ4XBIpYqKioqKior+A1UjwfeHP+wknzbAYuICdNZ3NsBewARQ1az0EXZKTapK8GMTvir1V6G6DTTDifn4RR8KywexqRj1/frNoDnA3Tk8vakXorTfg0Jsq1qCI1iCIWhC9QZQr4O7s1JVl2B3BiHQT9AH15si74MMGtYgg1fKDoB6ArQe4NErlfddBH0CaaeM98NlsCeQhpOdHKDXgE4BluMM+BZhdwIPJ9BsQir8lxyygWL6GPWna5ePmCHR2wXotyA79SGFNRwL0CHT+7XlErAG7T8r/ICxMcYUrJ9ScnZG'.
			'DczEgmSjtmb+hmeu4pnL7ey4add2W3Z63rSrB7qDneFZr3miPUCskplYTbQaLlo5j2VoJWMjDRFLq3w4cNQIIGdYCfQEloZbcvglwrJmH11PCSSFMR0z69mJjiC9eIS1lTNgX1+5KPMj09wGN+pGb5uxZVEbQvY+YPE1wRHYVdml4bb6TRMcAef00El9sppjPmu/NfTV0NdThGeu8hMwV4CR0AFmVM2OAO1Zz7YHWHgTO5a9/Q2wzwCdol79YRtlXfrjnrZ/6G9zs648AA3h/a38TjHOKk4B2QUdJ5KAiSPFTZgc8vZxcT7JA6OOA8bul5slgHTPbABxHjzKmkNI+0HWHBSgT2DE10D4HsDK+hGcRJZBE6CeY0UfVZsBZpVaKhDg13JNHaNen1/6COuBMwm6JfdjBSUa14XaelwZ8jRMHWLk/7N2KSoqKioqKiq6qBf9GY3z04RqJgAAAABJRU5ErkJggg==';
		file_put_contents($tmpfile_PNG,base64_decode($pngData));
		$my_bitmap = new wxBitmap($tmpfile_PNG,wxBITMAP_TYPE_PNG);
		unlink($tmpfile_PNG);
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
	// This init function is designed to protect the $App variable from the global scope, which prevents problems during Destroy
	$App = new nwcut_MainApp();
	wxApp::SetInstance($App);
	wxEntry();
}

nwcut_InitWX();

exit(NWC2RC_SUCCESS);
?>
