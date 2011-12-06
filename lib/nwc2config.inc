<?php
/*******************************************************************************
This is a support API for interfacing to the NWC2 system config. It requires
that COM support be available from within PHP.

The following constants are created by this module:

NWC2CONFIG_AppFolder      Contains the application folder containing the NWC2 program files
NWC2CONFIG_UserRegistry   This is the name of the top level regsitry are used by NWC2
NWC2CONFIG_SongFolder     Specifies the main user's Song Files folder
NWC2CONFIG_ConfigFolder   Contains the folder used by NWC2 to store user config data
	
The following functions are available to retrieve config data:

function NWC2_GetUserOption(<option-name>)
	This retrieves an option value from the NWC2 registry. Some example <option-name>
	items include: "InstrumentTree", "LastToolRun", and "PianoConfig"


Copyright © 2011 by NoteWorthy Software, Inc.
All Rights Reserved

HISTORY:
================================================================================
[2010-05-08] Initial release
*******************************************************************************/

if (!class_exists("COM")) die("The nwc2config module requires COM support");

define('NWC2CONFIG_UserRegistry','HKEY_CURRENT_USER\Software\NoteWorthy Software\NoteWorthy Composer 2');

try {
	$shellObj = new COM("WScript.Shell");

	define('NWC2CONFIG_AppFolder',$shellObj->RegRead('HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\NoteWorthy Composer 2\InstallLocation'));
	define('NWC2CONFIG_SongFolder',NWC2_GetSongFilesFolder($shellObj));
	define('NWC2CONFIG_ConfigFolder',NWC2_GetConfigFolder($shellObj));

	unset($shellObj);
	} 
catch (Exception $e) {
	echo 'NWC2CONFIG module exception: ',  $e->getMessage(), "\n";
}


function NWC2WIN_GetLocalAppDataFolder()
{
	// The LocalAppData (aka CSIDL_LOCAL_APPDATA) is difficult to retrieve from PHP. Ideally, we would use an interface 
	// to SHGetSpecialFolderLocation, but there is not an easy built-in way to do this in PHP for all Windows versions. 
	// In Vista and later, we will use the LOCALAPPDATA environment variable, otherwise we will use the Windows XP default.
	// See also: http://technet.microsoft.com/en-us/library/cc766489(WS.10).aspx
	//
	if (!empty($_SERVER["LOCALAPPDATA"])) return $_SERVER["LOCALAPPDATA"];

	if (empty($_SERVER["USERPROFILE"])) return "";

	return $_SERVER["USERPROFILE"].'\Local Settings\Application Data';
}


function NWC2_GetConfigFolder($shellObj)
{
	$folder = "";

	try {
		$folder = $shellObj->RegRead(NWC2CONFIG_UserRegistry.'\Folders\Config');
		}
	catch (Exception $e) {
		// the NWC2 user has not set the Config folder location, so use the default location
		$folder = NWC2WIN_GetLocalAppDataFolder();
		
		if ($folder) $folder .= '\Noteworthy Software\nwc2';
		else $folder = NWC2CONFIG_AppFolder.'\Config';
		}

	return $folder;
}


function NWC2WIN_GetMyDocumentsFolder($shellObj)
{
	$folder = "";

	try {
		$folder  = $shellObj->SpecialFolders("MyDocuments");
		}
	catch (Exception $e) {
		trigger_error("MyDocuments Special Folder not found: ".$e->getMessage(),E_USER_WARNING);
		}

	return $folder;
}


function NWC2_GetSongFilesFolder($shellObj)
{
	$folder = "";

	try {
		$folder = $shellObj->RegRead(NWC2CONFIG_UserRegistry.'\Folders\Songs');
		}
	catch (Exception $e) {
		}

	if (!$folder) {
		// the NWC2 user has not set the Song Files folder location, so use the default location
		$folder = NWC2WIN_GetMyDocumentsFolder($shellObj);
		$nwcfolder = $folder.'\NoteWorthy Composer';
		if (file_exists($nwcfolder)) $folder = $nwcfolder;
		}

	return $folder;
}


function NWC2_GetUserOption($opt)
{
	$v = false;

	try {
		$shellObj = new COM("WScript.Shell");
		$v = $shellObj->RegRead(NWC2CONFIG_UserRegistry.'\Options\\'.$opt);
		unset($shellObj);
		}
	catch (Exception $e) {}

	return $v;
}

?>