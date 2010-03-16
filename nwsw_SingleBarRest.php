<?php
require_once("lib/nwc2clips.inc");

$replacewith = <<<__EOTEXT
|Bar
|Text|Text:"1"|Font:User4|Pos:7|Justify:Center|Placement:AtNextNote
|Rest|Dur:Whole
|Bar
__EOTEXT;

$clip = file_get_contents('php://stdin');

echo preg_replace('/\|Bar[\r\n]+\|Rest\|Dur:Whole[\r\n]+\|Bar/',trim($replacewith),$clip);

exit(NWC2RC_SUCCESS);
?>
