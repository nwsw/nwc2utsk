<?php
/*******************************************************************************
nwsw_About.php Version 1.5

This script shows the version of the Starter Kit that is installed. 
This is useful when checking for possible updates.

Copyright © 2009 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2009-10-02] Version 1.5: Added PHP version and phpinfo
[2006-05-12] Version 1.01: Code format cleanup
[2005-09-12] Version 1.00: Initial release
*******************************************************************************/
require_once("lib/nwc2clips.inc");

$libver = NWC2ClipLibVersion();
$phpver = phpversion();

$usermsg = <<<__EOMSG
Clip Library Version: $libver
PHP Version: $phpver

NWC2 User Tool Starter Kit is a free software package that includes a set of PHP scripts for use with the User Tool command in NoteWorthy Composer 2. Additional details about the starter kit can be found at our web site:

http://noteworthysoftware.com

You are currently using version $libver of the NWC2 User Tool Starter Kit.

__EOMSG;
//
fputs(STDOUT,$usermsg);
phpinfo(INFO_CONFIGURATION+INFO_MODULE+INFO_ENVIRONMENT+INFO_VARIABLES);

exit(NWC2RC_REPORT);
?>