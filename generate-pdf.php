<?php

if ( !defined('ABSPATH') )
  exit;

if ( ! function_exists('fpropdf_header') )
{
  function fpropdf_header( $h )
  {
    if ( ! defined('FPROPDF_NO_HEADERS') )
      @header( $h );
  }
}

global $fpropdfSignatures;



$error = 0;

if ( isset($_POST['desired']) and isset($_POST['actual']) )
{

  if ( !file_exists($_POST['desired']) or !is_file($_POST['desired']) )
  {
    $_POST['actual'] = __DIR__ . '/blank.pdf';
    $is_blank = true;
  }

  if ( !file_exists($_POST['actual']) or !is_file($_POST['actual']) )
  {
    $_POST['actual'] = __DIR__ . '/blank.pdf';
    $is_blank = true;
  }

  $desired = escapeshellarg( $_POST['desired'] );
  $actual  = escapeshellarg( $_POST['actual'] );
  $actual2  = escapeshellarg( $_POST['actual2'] );
  $flatten = intval($_POST['lock']) ? 'flatten' : '';

  if ( isset($_REQUEST['flattenOverride']) )
  {
    if ( $_REQUEST['flattenOverride'] == 'yes' )
      $flatten = 'flatten';
    if ( $_REQUEST['flattenOverride'] == 'no' )
      $flatten = '';
  }

  if ( isset($_POST['passwd']) and $_POST['passwd'] )
  {
    $pass = escapeshellarg( stripslashes($_POST['passwd']) );
    $flatten .= ' encrypt_40bit user_pw ' . $pass;
  }

  $generated_filename = $_POST['filename'];
  $generated_filename = preg_replace('/[^a-zA-Z0-9\_\.\- ]+/', '_', $generated_filename);
  $generated_filename = preg_replace('/ +/', ' ', $generated_filename);
  $generated_filename = preg_replace('/\_+/', '_', $generated_filename);
  $generated_filename = trim($generated_filename);
  $generated_filename = trim($generated_filename, '_');
  if ( ! $generated_filename )
    $generated_filename = "Form";
  if ( !preg_match('/\.pdf$/i', $generated_filename) )
    $generated_filename .= '.pdf';

  $old_post = $_POST;
  unset($_POST);
  $cont = true;

  if($cont)
  {

    fpropdf_header('Content-type: application/pdf');
    fpropdf_header("Content-Disposition: attachment; filename='".$generated_filename."'");

    if ( isset($_GET['inline']) )
      fpropdf_header("Content-Disposition: inline; filename='".$generated_filename."'");

    define('FPROPDF_FILENAME', $generated_filename);

    ob_start();

    if ( $is_blank )
    {
      readfile( __DIR__ . '/blank.pdf' );
    }
    elseif ( $actual2 )
    {
      $tmp = escapeshellarg(tempnam( sys_get_temp_dir(), 'output' ) . '.pdf');
      $command = "pdftk $desired fill_form $actual output $tmp 2>&1";
      shell_exec($command);
      $command = "pdftk $tmp fill_form $actual2 output - $flatten";
      passthru($command);
      @unlink($tmp);
    }
    else
    {
      $command = "pdftk $desired fill_form $actual output - $flatten";
      passthru($command);
    }



    $data = ob_get_clean();

    if ( ( !defined('FPROPDF_IS_MASTER') and !$data and ! fpropdf_custom_command_exist('pdftk') and fpropdf_is_activated() ) or isset( $_GET['licence_test'] ) )
    {
      $data = ob_get_clean();

      $pdftk = '';

      $post = array(
        'salt'   => FPROPDF_SALT,
        'form'   => $_GET['form'],
        'passwd' => stripslashes( $_POST['passwd'] ),
        'flatten' => $_POST['flatten'],
        'flattenOverride' => $_REQUEST['flattenOverride'],
        'site_url'   => site_url('/'),
        'site_title' => get_bloginfo('name'),
        'site_ip' => $_SERVER['SERVER_ADDR'],
        'filename' => $generated_filename,
        'code'   => get_option('fpropdf_licence'),
        'fpropdfSignatures' => @serialize($fpropdfSignatures),
      );
      $post = array_merge( $post, $old_post );
      //print_r($post); exit;

      $keys = explode(' ', 'actual actual2 desired');
      foreach ( $keys as $key )
        if ( isset( $post[$key] ) and $post[$key] )
        {
          $post[$key] = '@' . realpath( $post[$key] );
        }

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => FPROPDF_SERVER . 'licence/pdftk.php',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_TIMEOUT => 30,
      ));
      $data = curl_exec($curl);

      if ( preg_match('/^\{.*\}$/', $data) )
      {
        $tmp = json_decode($data);
        $data = false;
        $command = false;
        $error = $tmp->error;
      }

    }

    // There was an error.
    // Either system commands do not work or the master server returned an error.
    if ( ! $data )
    {
      ob_start();
      fpropdf_header('Content-Type: text/html; charset=utf-8');
      fpropdf_header("Content-Disposition: inline; filename='error.txt'");
      $debug = shell_exec("$command 2>&1");
      if ( preg_match('/java\.lang\.NullPointerException/', $debug) )
        $debug = "The form could not be filled in.\n\n$debug";
      if ( $error )
        $debug = $error;
      if ( preg_match('/has not been activated/', $debug) )
        $debug .= ' <a href="admin.php?page=fpdf&tab=forms" target="_blank">Click here</a> to manage your activated forms.';
      echo "<pre>There was an error generating the PDF file.";
      if ( $command )
        echo "\nThe command was: $command";
      echo "\n$debug</pre>";
      $data = ob_get_clean();
      if ( ! defined('FPROPDF_GEN_ERROR') )
        define( 'FPROPDF_GEN_ERROR', $data );
    }

    fpropdf_header("Content-length: ".strlen($data));
    if ( ! defined( 'FPROPDF_NO_HEADERS' ) )
      echo $data;
    elseif ( ! defined( 'FPROPDF_CONTENT' ) )
      define( 'FPROPDF_CONTENT', $data);

  }
  else 
    die('can not open '.$actual);


} 
else
{
  die('Wrong post params');
}


