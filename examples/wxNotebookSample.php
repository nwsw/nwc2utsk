<?php
require_once("lib/nwc2clips.inc");
require_once("lib/nwc2gui.inc");

class nwcut_NotebookPage extends wxPanel
{
	private $PanelNum = -1;
	private $Parent;

	function nwcut_NotebookPage($parent,$panelnum)
	{
		parent::__construct($parent);

		$this->Parent = $parent;
		$this->PanelNum = $panelnum;

		$panelSizer = new wxBoxSizer(wxVERTICAL);
		$this->SetSizer($panelSizer);
		$panelSizer->SetMinSize(300,200);
		$wxID = wxID_HIGHEST;

		$newrow = new wxBoxSizer(wxHORIZONTAL);
		$panelSizer->Add($newrow,0,wxGROW|wxALL,10);

		$statictext = new wxStaticText($this, ++$wxID, "This is panel $panelnum");
		$newrow->Add($statictext,0,wxGROW);
		//
		$newrow->AddSpacer(15);
		//
		$btn = new wxButton($this,++$wxID,"About Panel $panelnum");
		$newrow->Add($btn,0,wxGROW);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onAbout"));

		$newrow = new wxStaticBoxSizer(wxVERTICAL,$this);
		$panelSizer->Add($newrow,0,wxGROW|wxALL,10);
		//
		for($i=0;$i<$panelnum;$i++) {
			$wxID++;

			if (($i%6) == 0) {
				if ($i) $newrow->AddSpacer(10);
				$newbox = new wxBoxSizer(wxHORIZONTAL);
				$newrow->Add($newbox,0,wxGROW);
				}
			else {
				$newbox->AddSpacer(10);
				}

			$btn = new wxButton($this,$wxID,"Test $wxID",wxDefaultPosition, wxDefaultSize);
			$newbox->Add($btn,0,wxGROW);
			$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"doTestBtn"));
			}

		$panelSizer->Fit($this);
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
}

class nwcut_NotebookFrame extends wxDialog
{
	private $NotebookPanel = false;
	private $NotebookSizer = false;
	private $CurrentPanel = false;
	private $PanelNum = 0;

	function nwcut_NotebookFrame()
	{
		parent::__construct(null,-1,"Sample Notebook Interface",wxDefaultPosition,new wxSize(690,400));
	
		$wxID = wxID_HIGHEST;

		$this->NotebookSizer = new wxBoxSizer(wxVERTICAL);
		$this->SetSizer($this->NotebookSizer);

		$newrow = new wxBoxSizer(wxHORIZONTAL);
		$this->NotebookSizer->Add($newrow,0,wxGROW);
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

		$this->NotebookPanel = new wxNotebook($this,++$wxID,new wxPoint(0,0),wxDefaultSize,wxNB_TOP);
		$this->AddPanel(1);
		$this->AddPanel(9);
		$this->AddPanel(22);
		$this->AddPanel(32);
		$this->AddPanel(64);
		$newrow->Add($this->NotebookPanel,0,wxGROW);

		$ButtonPanel = new wxStaticBoxSizer(wxVERTICAL, $this);
		$this->NotebookSizer->Add($ButtonPanel,0,wxGROW|wxALL,0);
		
		$box = new wxBoxSizer(wxHORIZONTAL);
		$ButtonPanel->Add($box,0,wxALIGN_RIGHT|wxALL,5);

		$btn_cancel = new wxButton($this, wxID_CANCEL);
		$box->Add($btn_cancel,0,wxGROW|wxRIGHT,40);
		$this->Connect(wxID_CANCEL,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onQuit"));

		$btn_back = new wxButton($this, ++$wxID, "<- Back");
		$box->Add($btn_back,0,wxGROW|wxRIGHT,6);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onPriorPanel"));

		$btn_next = new wxButton($this, ++$wxID, "Next ->");
		$box->Add($btn_next,0,wxGROW);
		$this->Connect($wxID,wxEVT_COMMAND_BUTTON_CLICKED,array($this,"onNextPanel"));

		$this->NotebookSizer->Fit($this);
	}

	function AddPanel($i)
	{
		$NewPage = new nwcut_NotebookPage($this->NotebookPanel,$i);
		$this->NotebookPanel->AddPage($NewPage,"$i Test Control".(($i == 1) ? '' : "s"));
	}

	function onPriorPanel()
	{
		$n = $this->NotebookPanel->GetSelection();
		$lastPage = $this->NotebookPanel->GetPageCount() - 1;

		$n--;
		if ($n < 0) $n = $lastPage;

		$this->NotebookPanel->SetSelection($n);
	}

	function onNextPanel()
	{
		$n = $this->NotebookPanel->GetSelection();
		$lastPage = $this->NotebookPanel->GetPageCount() - 1;

		$n++;
		if ($n > $lastPage) $n = 0;

		$this->NotebookPanel->SetSelection($n);
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
		$this->AppFrame = new nwcut_NotebookFrame();
		$this->AppFrame->Show();

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
