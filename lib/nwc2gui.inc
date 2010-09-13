<?php
dl('php_wxWidgets.dll');

if (!defined("NO_wxInitAllImageHandlers")) wxInitAllImageHandlers();

function nwc2gui_wxArray($a)
{
	if (!is_array($a)) trigger_error("Invalid argument; sequential array required",E_USER_ERROR);

	$wxArrayString = new wxArrayString();

	foreach ($a as $v) $wxArrayString->Add($v);

	return $wxArrayString;
}

class nwc2gui_MemoryInStream extends wxMemoryInputStream
{
	// We need the buffer to remain allocated, so it must be a member of the class
	var $buffer;

	function __construct($data,$opts=array("base64","gz"))
	{
		if (in_array('base64',$opts)) $data = base64_decode($data);
		if (in_array('gz',$opts)) $data = gzuncompress($data);

		$this->buffer = $data;

		parent::__construct($this->buffer,strlen($this->buffer));
	}
}

// Embedded file code created by nwswEncodeFile.php
define('MSTREAM_WXPHP_ICO',
	'eJztlUtuAyEMQJ1pFlUVKQknAFY5Qdez6jG67jFytDkAKtfoMgdAohjbfKbppIpStZXqyRjsB8YYRgFYwR3sdjsAWMNhBfCYelqT/TYAPCffOJL98gDw'.
	'tAY4JAs9Or2r5Ec5QpUdvyAE4fFIDT7oSmqapuSJ+QcxUoMPulCxbGOW08ZlmT62IXjnp41Pwm1yUetdYzvm3M7iyDpwIzlwjcamTjB8X51m9RI5XWGja'.
	'xtPub3GxiQo5y3zU3m/YuM87Mv85XxdJ+dsH1x0PoYYQ7XRFRruXIg8Pnofwkcbh6Pt2Y5i5/A4rLN9YBvXTnZeMtmUCObuOZ/l/Gf7vdX9vJXgPR+Brm+'.
	'55/eVt/f8FejOXvNeFLsoMCxz/Re45l3kFpUu2/ojnM6p4XJwlcOcw4yLu3SGdn3dc8mh5pfiddxSSj1v878t13OuW96kVTcy9PxCfYY5HwpfkJ/nZr8g6'.
	'rfwRmPapG3hqtF7K8NM5bZqHpajCLctz8sonN5wU3TyyvRPOAagPnMOVriSfuVKNAWwnBFxe4abjpu0ZtLC1Sec68jZC1e5FKrj5hyXc1A9t5UrCd9zUwo'.
	'u4dUZbtra1/wVlbryGqnn+8JLJOS0WaloiSTnpzpujWQv52+zTfcN59riML/h+7jwfV/6f/6Xf1mSdwOx4SU=');
//
// The nwc2gui_IconBundle class handles the construction of a wxIconBundle using a memory stream.
// This is complicated by wx version 2.8, as the wxIconBundle does not accept stream construction.
class nwc2gui_IconBundle extends wxIconBundle
{
	function __construct($iconData=false,$imgtype=wxBITMAP_TYPE_ICO)
	{
		if (is_string($iconData)) parent::__construct($iconData,$imgtype);
		else {
			parent::__construct();

			if (is_object($iconData)) {
				$mstream = $iconData;
				}
			else {
				if (!is_array($iconData)) $iconData = array("data" => MSTREAM_WXPHP_ICO, 'opts' => array('gz','base64'));
				$mstream = new nwc2gui_MemoryInStream($iconData['data'],$iconData['opts']);
				}

			$img = new wxImage();
			$n = $img->GetImageCount($mstream,$imgtype);
			while ($n--) {
				$img->LoadFile($mstream, $imgtype, $n);
				$ico = new wxIcon();
				$ico->CopyFromBitmap(new wxBitmap($img));
				$this->AddIcon($ico);
				}
			}
	}
}

?>