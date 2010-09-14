<?php
// These defines can be made quickly using this regular expression:
// ~#define +([^ ]+) +([0-9]+)~define('\1',\2);
define('wxSTC_LEX_HTML',4);

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
?>
