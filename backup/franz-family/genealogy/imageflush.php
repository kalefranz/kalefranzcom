<?php
/**
 * Flush an image to the browser
 *
 * phpGedView: Genealogy Viewer
 * Copyright (C) 2002 to 2003  John Finlay and Others
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id: imageflush.php 2091 2007-10-30 16:19:34Z yalnifj $
 *
 * @package PhpGedView
 * @subpackage Charts
 */
require("config.php");

/**
 * display any message as a PNG image
 *
 * @param string $txt message to be displayed
 */
function ImageFlushError($txt) {
	Header('Content-Type: image/png');
	$image = ImageCreate (400, 40);
	$bg = ImageColorAllocate($image, 0xEE, 0xEE, 0xEE);
	$red = ImageColorAllocate($image, 0xFF, 0x00, 0x00);
	ImageString($image, 2, 10, 10, $txt, $red);
	ImagePng($image);
	return;
}

// get image_type
$image_type = @$_GET['image_type'];
if (empty($image_type)) $image_type = "png";
$image_type = strtolower($image_type);
if ($image_type=="jpg") $image_type = "jpeg";
// get name of SESSION variable containing an image file name
$tempVarName = @$_GET["image_name"];
if (empty($tempVarName)) $tempVarName = "graphFile";

// read image_data from SESSION variable or from file pointed to by SESSION variable
if (isset($_SESSION["image_data"])) {
	$image_data = @$_SESSION['image_data'];
	$image_data = @unserialize($image_data);
	unset($_SESSION['image_data']);
} else if (isset($_SESSION[$tempVarName])) {
	$image_data = file_get_contents($_SESSION[$tempVarName]);
	unlink($_SESSION[$tempVarName]);
	unset($_SESSION[$tempVarName]);
}
if (empty($image_data)) ImageFlushError("Error: \$_SESSION['image_data'] or \$_SESSION['".$tempVarName."'] is empty");

// send data to browser
Header("Content-Type: image/$image_type");
Header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
Header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
Header('Pragma: no-cache');
print $image_data;
?>