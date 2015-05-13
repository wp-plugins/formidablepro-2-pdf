<?php
/**
 *	Signature to Image: A supplemental script for Signature Pad that
 *	generates an image of the signature’s JSON output server-side using PHP.
 *	
 *	@project	ca.thomasjbradley.applications.signaturetoimage
 *	@author		Thomas J Bradley <hey@thomasjbradley.ca>
 *	@link		http://thomasjbradley.ca/lab/signature-to-image
 *	@link		http://github.com/thomasjbradley/signature-to-image
 *	@copyright	Copyright MMXI–, Thomas J Bradley
 *	@license	New BSD License
 *	@version	1.0.1
 */

/**
 *	Accepts a signature created by signature pad in Json format
 *	Converts it to an image resource
 *	The image resource can then be changed into png, jpg whatever PHP GD supports
 *
 *	To create a nicely anti-aliased graphic the signature is drawn 12 times it's original size then shrunken
 *
 *	@param	string|array	$json
 *	@param	array	$options	OPTIONAL; the options for image creation
 *		imageSize => array(width, height)
 *		bgColour => array(red, green, blue)
 *		penWidth => int
 *		penColour => array(red, green, blue)
 *
 *	@return	object
 */

function fpropdf_sigJsonToImage($json, $options = array())
{
	$defaultOptions = array(
		'imageSize' => array(300, 200)
		,'bgColour' => array(0x15, 0xff, 0xff)
		,'penWidth' => 4
		,'penColour' => array(0x00, 0x00, 0x00)
		,'drawMultiplier'=> 2
	);
	
	$options = array_merge($defaultOptions, $options);
	
	$img = imagecreatetruecolor($options['imageSize'][0] * $options['drawMultiplier'], $options['imageSize'][1] * $options['drawMultiplier']);
        imagealphablending($image, false);
        $bg = imagecolorallocatealpha($img, $options['bgColour'][0], $options['bgColour'][1], $options['bgColour'][2], 127);
        imagesavealpha($img, true);
        imagealphablending($image, true);
        //imagecolortransparent($img, $bg);
	$pen = imagecolorallocatealpha($img, $options['penColour'][0], $options['penColour'][1], $options['penColour'][2], 0);
        imagefill($img, 0, 0, $bg);
	
        //imagepng($img); exit;

        if(is_string($json))
          $json = json_decode(stripslashes($json));

        //var_dump($json); exit;
        //imagepng($img); exit;

	foreach($json as $v)
		fpropdf_drawThickLine($img, $v->lx * $options['drawMultiplier'], $v->ly * $options['drawMultiplier'], $v->mx * $options['drawMultiplier'], $v->my * $options['drawMultiplier'], $pen, $options['penWidth'] * ($options['drawMultiplier'] / 2));
	
        //imagepng($img); exit;

        $imgDest = imagecreatetruecolor($options['imageSize'][0], $options['imageSize'][1]);
        imagesavealpha($imgDest, true);
        imagealphablending($imgDest, false);
        //imagecolortransparent($imgDest, $bg);
        imagecopyresampled($imgDest, $img, 0, 0, 0, 0, $options['imageSize'][0], $options['imageSize'][0], $options['imageSize'][0] * $options['drawMultiplier'], $options['imageSize'][0] * $options['drawMultiplier']);
        
        imagedestroy($img);

        //imagepng($imgDest); exit;
	
	return $imgDest;
}

/**
 *	Draws a thick line
 *	Changing the thickness of a line using imagesetthickness doesn't produce as nice of result
 *
 *	@param	object	$img
 *	@param	int		$startX
 *	@param	int		$startY
 *	@param	int		$endX
 *	@param	int		$endY
 *	@param	object	$colour
 *	@param	int		$thickness
 *
 *	@return	void
 */
function fpropdf_drawThickLine($img, $startX, $startY, $endX, $endY, $colour, $thickness) 
{
	$angle = (atan2(($startY - $endY), ($endX - $startX))); 

	$dist_x = $thickness * (sin($angle));
	$dist_y = $thickness * (cos($angle));
	
	$p1x = ceil(($startX + $dist_x));
	$p1y = ceil(($startY + $dist_y));
	$p2x = ceil(($endX + $dist_x));
	$p2y = ceil(($endY + $dist_y));
	$p3x = ceil(($endX - $dist_x));
	$p3y = ceil(($endY - $dist_y));
	$p4x = ceil(($startX - $dist_x));
	$p4y = ceil(($startY - $dist_y));
	
        $array = array(0=>$p1x, $p1y, $p2x, $p2y, $p3x, $p3y, $p4x, $p4y);
	imagefilledpolygon($img, $array, (count($array)/2), $colour);
}
