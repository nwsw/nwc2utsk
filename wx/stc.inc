<?php
// These defines can be made quickly using this regular expression:
// /#define +([^ ]+) +([0-9]+)/define('\1',\2);/
define('wxSTC_LEX_HTML',4);
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

function wxphp_stc_BuildPHPKeyWords()
{
	$w = explode(' ',
		'array bool break case char class continue default do double else elseif endfor endforeach endif endswitch endwhile extends false float for foreach function global if include int integer '.
		'long mixed new object php real require require_once return static string switch true var void while __FILE__ __LINE__ E_ERROR E_NOTICE E_PARSE E_WARNING'
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
	var $FileName = false;

	function __construct($parent,$id=wxID_ANY,$pos=wxDefaultPosition,$size=wxDefaultSize,$style=0)
	{
		parent::__construct($parent,$id,$pos,$size,$style);
	}

	function SetStyleFromSpecArray($specArray)
	{
		$this->StyleResetDefault();
		$this->StyleSetFaceName(wxSTC_STYLE_DEFAULT,"Courier New");
		$this->StyleSetSize(wxSTC_STYLE_DEFAULT,10);
		$this->StyleClearAll();

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
				default: $this->StyleSetFaceName($id,$decopt);
			}
		}
	}

	function SetPHPStyle()
	{
		static $keywords = false;
		//$this->SetLexer(wxSTC_LEX_PHPSCRIPT);
		$this->SetLexer(wxSTC_LEX_HTML);
		$this->SetStyleBits(7);

		$this->SetStyleFromSpecArray(array(
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

		$this->SetTabWidth(4);

		if (!$keywords) $keywords = wxphp_stc_BuildPHPKeyWords();

		$this->SetKeyWords(4,$keywords);
	}
}
?>
