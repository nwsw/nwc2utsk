<?php
wxInitAllImageHandlers();

class wxphp_MemoryInputStream extends wxMemoryInputStream
{
	// We need the buffer to remain allocated, so it must be a member of the class
	var $buffer;

	function __construct($data,$opts=array("base64","gz"))
	{
		if (is_string($opts)) $opts = explode(',',$opts);
		if (in_array('base64',$opts)) $data = base64_decode($data);
		if (in_array('gz',$opts)) $data = gzuncompress($data);

		$this->buffer = $data;

		parent::__construct($this->buffer,strlen($this->buffer));
	}
}

// Embedded file code created by nwswEncodeFile.php
define('MSTREAM_WXPHP_ICO','
	eJztlUtuAyEMQJ1pFlUVKQknAFY5Qdez6jG67jFytDkAKtfoMgdAohjbfKbppIpStZXqyRjsB8YYRgFYwR3sdjsAWMNhBfCYelqT/TYAPCffOJL98gDw
	tAY4JAs9Or2r5Ec5QpUdvyAE4fFIDT7oSmqapuSJ+QcxUoMPulCxbGOW08ZlmT62IXjnp41Pwm1yUetdYzvm3M7iyDpwIzlwjcamTjB8X51m9RI5XWGja
	xtPub3GxiQo5y3zU3m/YuM87Mv85XxdJ+dsH1x0PoYYQ7XRFRruXIg8Pnofwkcbh6Pt2Y5i5/A4rLN9YBvXTnZeMtmUCObuOZ/l/Gf7vdX9vJXgPR+Brm+
	55/eVt/f8FejOXvNeFLsoMCxz/Re45l3kFpUu2/ojnM6p4XJwlcOcw4yLu3SGdn3dc8mh5pfiddxSSj1v878t13OuW96kVTcy9PxCfYY5HwpfkJ/nZr8g6
	rfwRmPapG3hqtF7K8NM5bZqHpajCLctz8sonN5wU3TyyvRPOAagPnMOVriSfuVKNAWwnBFxe4abjpu0ZtLC1Sec68jZC1e5FKrj5hyXc1A9t5UrCd9zUwo
	u4dUZbtra1/wVlbryGqnn+8JLJOS0WaloiSTnpzpujWQv52+zTfcN59riML/h+7jwfV/6f/6Xf1mSdwOx4SU=
	');
//
// The wxphp_IconBundle class handles the construction of a wxIconBundle using a memory stream.
// This is complicated by wx version 2.8, as the wxIconBundle does not accept stream construction.
class wxphp_IconBundle extends wxIconBundle
{
	// You can construct an icon bundle by passing one of four different $iconData types:
	// 1) the name of an image file
	// 2) a wxphp_MemoryInputStream that contains an image file
	// 3) an associative array that looks like array("data" => MSTREAM_DATA, "opts" => "base64")
	// 4) a string, or simple list, containing "base64" encoded images
	function __construct($iconData=false,$imgtype=wxBITMAP_TYPE_ICO)
	{
		if (is_string($iconData)) {
			if (file_exists($iconData)) {
				parent::__construct($iconData,$imgtype);
				return;
				}

			$iconData = array(0 => $iconData);
			}

		parent::__construct();

		if (is_array($iconData) && !isset($iconData['data'])) {
			foreach ($iconData as $imgdata) {
				$this->AddImage(new wxImage(new wxphp_MemoryInputStream($imgdata,"base64"),$imgtype));
				}

			return;
			}

		if (is_object($iconData)) {
			$mstream = $iconData;
			}
		else {
			if (!is_array($iconData)) $iconData = array("data" => MSTREAM_WXPHP_ICO, 'opts' => array('gz','base64'));
			$mstream = new wxphp_MemoryInputStream($iconData['data'],$iconData['opts']);
			}

		$img = new wxImage();
		$n = $img->GetImageCount($mstream,$imgtype);
		while ($n--) {
			$img->LoadFile($mstream, $imgtype, $n);
			$this->AddImage($img);
			}
	}

	function AddImage($img)
	{
		$ico = new wxIcon();
		$ico->CopyFromBitmap(new wxBitmap($img));
		$this->AddIcon($ico);
	}
}

?>
