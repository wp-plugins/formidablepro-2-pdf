<?php

if ( !defined('ABSPATH') )
  exit;

// Request parameters are escaped already in fpropdf.php file

$params = $_GET;

$_POST['wpfx_submit'] = 1;
$_POST['wpfx_dataset'] = -3;
if ( $params['dataset'] )
  $_POST['wpfx_dataset'] = $params['dataset'];
$_POST['wpfx_layout'] = $params['layout'];
$_POST['wpfx_form'] = $params['form'];

ob_start();
wpfx_admin();
$html = ob_get_clean();

if ( $params['form2'] )
{
  $_POST['wpfx_submit'] = 1;
  $_POST['wpfx_dataset'] = -3;
  if ( $params['dataset'] )
    $_POST['wpfx_dataset'] = $params['dataset2'];
  $_POST['wpfx_layout'] = $params['layout2'];
  $_POST['wpfx_form'] = $params['form2'];
 
  ob_start();
  wpfx_admin();
  $html2 = ob_get_clean();
}

$_POST = array();

if ( preg_match("/<input type = 'hidden' name = 'desired' value = '([^']+)' /", $html, $m) )
  $_POST['desired'] = $m[1];

if ( preg_match("/<input type = 'hidden' name = 'actual'  value = '([^']+)' /", $html, $m) )
  $_POST['actual'] = $m[1];

if ( preg_match("/<input type = 'hidden' name = 'lock' value = '([^']+)' /", $html, $m) )
  $_POST['lock'] = $m[1];

if ( preg_match("/<input type = 'hidden' name = 'passwd' value = \"([^\"]+)\" /", $html, $m) )
  $_POST['passwd'] = htmlspecialchars_decode( $m[1] );

if ( preg_match("/<input type = 'hidden' name = 'filename' value = '([^']+)' /", $html, $m) )
  $_POST['filename'] = $m[1];

///

if ( preg_match("/<input type = 'hidden' name = 'desired' value = '([^']+)' /", $html2, $m) )
  $_POST['desired2'] = $m[1];

if ( preg_match("/<input type = 'hidden' name = 'actual'  value = '([^']+)' /", $html2, $m) )
  $_POST['actual2'] = $m[1];


if ( preg_match("/<input type = 'hidden' name = 'lock' value = '([^']+)' /", $html2, $m) )
  $_POST['lock2'] = $m[1];

$_POST['download'] = 'Download';

include __DIR__ . '/generate-pdf.php';
