<?php

if ( !defined('ABSPATH') )
  exit;

function fpropdf_readfile($file)
{
  global $_currentFile, $currentFile;
  if ( $_currentFile )
    @unlink( $_currentFile );
  if ( $currentFile )
    @unlink( $currentFile );
  header('Content-Length: '.filesize($file));
  readfile($file);
  exit;
}

$file = $_GET['file'];
$file = base64_decode( $file );
if ( preg_match('/[\/\\]/', $file) )
  die("File cannot contain slashes");
$file = __DIR__ . '/forms/' . $file;

global $currentFile;
if ( $currentFile )
  $file = $currentFile;


$fieldId = $_REQUEST['field'];

header('Content-Type: image/png');

$file_key = md5( file_get_contents($file) );

$folder = "/fields/" . $file_key . "/";



if ( !file_exists(__DIR__ . $folder) )
  mkdir(__DIR__ . $folder);

try
{

  $testFile = $testFileOrig = __DIR__ . $folder . md5( $fieldId ) . ".png";
  if ( file_exists( $testFile ) )
  {
    fpropdf_readfile( $testFile );
  }
  $testFile .= '.done';
  if ( file_exists( $testFile ) )
    throw new Exception('Already processed, but no data.');
 
  if ( !file_exists($file) )
    throw new Exception('PDF file not found');

  $the_file = $file;
  $the_folder = $folder;
  $file = $the_file;
  $folder = $the_folder;

  if ( !defined('FPROPDF_IS_MASTER') and fpropdf_is_activated() )
  {
    
    $post = array(
      'salt'   => FPROPDF_SALT,
      'code'   => get_option('fpropdf_licence'),
      'field'  => $fieldId,
      'form'   => $_GET['form'],
    );
    $post['pdf_file'] = '@' . realpath( $file );

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => FPROPDF_SERVER . 'licence/preview.php',
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => $post,
      CURLOPT_TIMEOUT => 30,
    ));
    $data = curl_exec($curl);
    if ( $data )
    {
      @touch($testFile);
      file_put_contents($testFileOrig, $data);
      fpropdf_readfile( $testFileOrig );
    }

    throw new Exception('Image could not be get from the server');
  }

  if ( !defined('FPROPDF_IS_MASTER') )
    throw new Exception('Previews cannot be generated on this server.');



}
catch (Exception $e)
{
  fpropdf_readfile( __DIR__ . '/res/blank.png' );
}
