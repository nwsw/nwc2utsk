<?php
// These defines can be made quickly using this regular expression:
// /#define +([^ ]+) +([0-9]+)/define('\1',\2);/
define('wxSTC_LEX_CONTAINER',0);
define('wxSTC_LEX_NULL',1);
define('wxSTC_LEX_PYTHON',2);
define('wxSTC_LEX_CPP',3);
define('wxSTC_LEX_HTML',4);
define('wxSTC_LEX_XML',5);
define('wxSTC_LEX_PERL',6);
define('wxSTC_LEX_SQL',7);
define('wxSTC_LEX_VB',8);
define('wxSTC_LEX_PROPERTIES',9);
define('wxSTC_LEX_ERRORLIST',10);
define('wxSTC_LEX_MAKEFILE',11);
define('wxSTC_LEX_BATCH',12);
define('wxSTC_LEX_XCODE',13);
define('wxSTC_LEX_DIFF',16);
define('wxSTC_LEX_PASCAL',18);
define('wxSTC_LEX_TCL',25);
define('wxSTC_LEX_VBSCRIPT',28);
define('wxSTC_LEX_ASM',34);
define('wxSTC_LEX_CPPNOCASE',35);
define('wxSTC_LEX_CSS',38);
define('wxSTC_LEX_ESCRIPT',41);
define('wxSTC_LEX_PS',42);
define('wxSTC_LEX_NSIS',43);
define('wxSTC_LEX_PHPSCRIPT',69);

define('wxSTC_STYLE_DEFAULT',32);
define('wxSTC_STYLE_LINENUMBER',33);
define('wxSTC_STYLE_BRACELIGHT',34);
define('wxSTC_STYLE_BRACEBAD',35);
define('wxSTC_STYLE_CONTROLCHAR',36);
define('wxSTC_STYLE_INDENTGUIDE',37);
define('wxSTC_STYLE_CALLTIP',38);
define('wxSTC_STYLE_LASTPREDEFINED',39);
define('wxSTC_STYLE_MAX',127);

// Lexical states for SCLEX_HTML, SCLEX_XML
define('wxSTC_H_DEFAULT',0);
define('wxSTC_H_TAG',1);
define('wxSTC_H_TAGUNKNOWN',2);
define('wxSTC_H_ATTRIBUTE',3);
define('wxSTC_H_ATTRIBUTEUNKNOWN',4);
define('wxSTC_H_NUMBER',5);
define('wxSTC_H_DOUBLESTRING',6);
define('wxSTC_H_SINGLESTRING',7);
define('wxSTC_H_OTHER',8);
define('wxSTC_H_COMMENT',9);
define('wxSTC_H_ENTITY',10);
define('wxSTC_H_TAGEND',11);
define('wxSTC_H_XMLSTART',12);
define('wxSTC_H_XMLEND',13);
define('wxSTC_H_SCRIPT',14);
define('wxSTC_H_ASP',15);
define('wxSTC_H_ASPAT',16);
define('wxSTC_H_CDATA',17);
define('wxSTC_H_QUESTION',18);
define('wxSTC_H_VALUE',19);

define('wxSTC_HPHP_COMPLEX_VARIABLE',104);
define('wxSTC_HPHP_DEFAULT',118);
define('wxSTC_HPHP_HSTRING',119);
define('wxSTC_HPHP_SIMPLESTRING',120);
define('wxSTC_HPHP_WORD',121);
define('wxSTC_HPHP_NUMBER',122);
define('wxSTC_HPHP_VARIABLE',123);
define('wxSTC_HPHP_COMMENT',124);
define('wxSTC_HPHP_COMMENTLINE',125);
define('wxSTC_HPHP_HSTRING_VARIABLE',126);
define('wxSTC_HPHP_OPERATOR',127);

define('SC_MOD_INSERTTEXT',0x1);
define('SC_MOD_DELETETEXT',0x2);
define('SC_MOD_CHANGESTYLE',0x4);
define('SC_MOD_CHANGEFOLD',0x8);
define('SC_PERFORMED_USER',0x10);
define('SC_PERFORMED_UNDO',0x20);
define('SC_PERFORMED_REDO',0x40);
define('SC_MULTISTEPUNDOREDO',0x80);
define('SC_LASTSTEPINUNDOREDO',0x100);
define('SC_MOD_CHANGEMARKER',0x200);
define('SC_MOD_BEFOREINSERT',0x400);
define('SC_MOD_BEFOREDELETE',0x800);
define('SC_MULTILINEUNDOREDO',0x1000);
define('SC_MODEVENTMASKALL',0x1FFF);

function wxphp_stc_BuildPHPKeyWords()
{
	$w = explode(' ',
		'array bool break case char class continue default do double else elseif endfor endforeach endif endswitch '.
		'endwhile extends false float for foreach function global if include int integer long mixed new object php '.
		'real require require_once return static string switch true var void while'
		);
	//
	$classes = get_declared_classes();
	$functions = get_defined_functions();
	//
	$w = array_merge($w, $classes, $functions['internal']);
	sort($w);

	return implode(' ',$w);
}

class wxphp_StyledTextCtrl extends wxStyledTextCtrl
{
	var $LineMarginMaxChars = -1;

	function __construct($parent,$id=wxID_ANY,$pos=wxDefaultPosition,$size=wxDefaultSize,$style=0)
	{
		parent::__construct($parent,$id,$pos,$size,$style);

		$this->Connect(wxEVT_STC_MODIFIED,array($this,"onTextModified"));
	}

	function SetStyleFromSpecArray($specArray)
	{
		$this->StyleResetDefault();

		$n = 0;
		foreach ($specArray as $id => $spec)
		{
			list($fc,$bg,$decor) = $spec;

			if ($fc !== false) $this->StyleSetForeground($id, new wxColour($fc));
			if ($bg !== false) $this->StyleSetBackground($id, new wxColour($bg));
			if ($decor) foreach(explode(',',$decor) as $decopt) switch ($decopt) 
			{
				case "Italic": $this->StyleSetItalic($id,true); break;
				case "Bold": $this->StyleSetBold($id,true); break;
				case "Underline": $this->StyleSetUnderline($id,true); break;
				default:
					if (preg_match('/^([0-9]+)pt$/',$decopt,$m)) $this->StyleSetSize($id,$m[1]);
					else $this->StyleSetFaceName($id,$decopt);
					break;
			}

			if (++$n == 1) $this->StyleClearAll();
		}
	}

	function SetDefaultStyle()
	{
		$this->SetLexer(wxSTC_LEX_NULL);
		$this->SetStyleFromSpecArray(array(
			wxSTC_STYLE_DEFAULT => array("Black","White","Courier New,10pt"),
			wxSTC_STYLE_LINENUMBER => array("#AAAAAA","#EEEEEE",false),
			wxSTC_STYLE_INDENTGUIDE => array("#AAAAAA",false,false)
			));
	}

	function SetPHPStyle()
	{
		static $keywords = false;
		$this->SetLexer(wxSTC_LEX_HTML);
		$this->SetStyleBits(7);

		$this->SetStyleFromSpecArray(array(
			wxSTC_STYLE_DEFAULT => array("Black","White","Courier New,10pt"),
			wxSTC_STYLE_LINENUMBER => array("#AAAAAA","#EEEEEE",false),
			wxSTC_STYLE_INDENTGUIDE => array("#AAAAAA",false,false),
			wxSTC_HPHP_DEFAULT => array("#660000",false,false),
			wxSTC_HPHP_COMMENT => array("#00A000",false,false),
			wxSTC_HPHP_COMMENTLINE => array("#00A000",false,"Italic"),
			wxSTC_HPHP_HSTRING => array("#CC0044",false,false),
			wxSTC_HPHP_SIMPLESTRING => array("#CC0044",false,false),
			wxSTC_HPHP_COMPLEX_VARIABLE => array("#AA0000",false,false),
			wxSTC_HPHP_WORD => array("#0000AA",false,false), // From the keyword list
			wxSTC_HPHP_NUMBER => array("#000088",false,false),
			wxSTC_HPHP_VARIABLE => array("#006666",false,false),
			wxSTC_HPHP_HSTRING_VARIABLE => array("#006600",false,false),
			wxSTC_HPHP_OPERATOR => array("BLACK",false,false),
			));

		$this->SetWhitespaceForeground(1,new wxColour("#CCCCCC"));

		if (!$keywords) $keywords = wxphp_stc_BuildPHPKeyWords();
		$this->SetKeyWords(4,$keywords);
	}

	function SaveFile($fname)
	{
		$b = false;
		if ($fname && parent::SaveFile($fname)) {
			$this->SetSavePoint();
			$b = true;
			}

		return $b;
	}

	function LoadFile($fname)
	{
		$doPHPStyling = true;

		if (!$fname) $this->SetText("");
		else {
			if (!parent::LoadFile($fname)) return false;

			$doPHPStyling = in_array(strtolower(pathinfo($fname,PATHINFO_EXTENSION)),array("php","wxphp","inc"));
			}

		if ($doPHPStyling ) $this->SetPHPStyle();
		else $this->SetDefaultStyle();

		$this->SetTabWidth(4);
		$this->SetIndent(4);
		$this->SetUseTabs(1);
		$this->SetTabIndents(1);

		$this->SetMarginLeft(4);
		$this->SetMarginWidth(1,0);
		$this->SetSavePoint();
		$this->SetAnchor(0);
		$this->SetCurrentPos(0);

		return true;
	}

	function EnableLineNumbers($v)
	{
		$this->LineMarginMaxChars = ($v ? 0 : -1);
		$this->UpdateLineMargin();
	}

	function UpdateLineMargin()
	{
		if ($this->LineMarginMaxChars < 0) {
			$this->SetMarginWidth(0,0);
			return;
			}

		$n_len = intval(abs(ceil(log10($this->GetLineCount()+1))));
		if (($n_len != $this->LineMarginMaxChars) && ($n_len < 10)) {
			$this->LineMarginMaxChars = $n_len;
			$sampleLineNum = str_repeat('9',$n_len+1);
			$this->SetMarginWidth(0,$this->TextWidth(wxSTC_STYLE_LINENUMBER,$sampleLineNum));
			}
	}

	function onTextModified($evt)
	{
		$mt = $evt->GetModificationType();

		if (($mt & SC_PERFORMED_USER|SC_MOD_INSERTTEXT) && ($this->LineMarginMaxChars >= 0) && $evt->GetLinesAdded()) {
			$this->UpdateLineMargin();
			}
	}
}
?>
