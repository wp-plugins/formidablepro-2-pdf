<?php
/**
 * Plugin Name: Formidable PRO2PDF
 * Version: 1.6.0.16
 * Description: This plugin allows to export data from Formidable Pro forms to PDF
 * Author: Alexandre S.
 * Plugin URI: http://www.formidablepro2pdf.com/
 * Author URI: http://www.formidablepro2pdf.com/
 */

if ( !defined('ABSPATH') )
  exit;



@ini_set('display_errors', 'off');

$upload_dir = wp_upload_dir();
define('FPROPDF_FORMS_DIR', $upload_dir['basedir'] . '/fpropdf-forms/');

if ( ! file_exists( FPROPDF_FORMS_DIR ) )
{
  // Create forms folder in wp-content/uploads
  @mkdir(FPROPDF_FORMS_DIR);

  // Move old forms to new folder
  $old_forms = __DIR__ . '/forms/';

  if ( file_exists( $old_forms ) )
    if ($handle = opendir( $old_forms )) 
    {
      while (false !== ($entry = readdir($handle)))
      {
        if ( $entry == '.' ) continue;
        if ( $entry == '..' ) continue;
        @rename( $old_forms . $entry, FPROPDF_FORMS_DIR . $entry );
      }
    }
}

// Plugin settings link in Plugins list
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'fpropdf_add_action_links' );

function fpropdf_add_action_links ( $links ) {
  $mylinks = array(
    '<a href="' . admin_url( 'admin.php?page=fpdf' ) . '">Settings</a>',
  );
  return array_merge( $links, $mylinks );
}

@mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
@mysql_select_db(DB_NAME);
 
function fpropdf_myplugin_activate() {
  global $wpdb;
  $wpdb->query('CREATE TABLE IF NOT EXISTS `wp_fxlayouts` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(64) NOT NULL,
    `file` varchar(64) NOT NULL,
    `data` varchar(8192) NOT NULL,
    `visible` tinyint(1) NOT NULL,
    `form` int(11) NOT NULL,
    `dname` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    `formats` text,
    PRIMARY KEY (`ID`)
  )');
  $wpdb->query("ALTER TABLE wp_fxlayouts ADD COLUMN formats TEXT");
  $wpdb->query("ALTER TABLE wp_fxlayouts ADD COLUMN passwd VARCHAR(255)");
  $wpdb->query("ALTER TABLE wp_fxlayouts ADD COLUMN add_att INT(3) UNSIGNED NOT NULL DEFAULT '0'");

  if ( ! get_option('fpropdf_licence') )
    update_option( 'fpropdf_licence', 'TRIAL' . strtoupper(FPROPDF_SALT) );

}
register_activation_hook( __FILE__, 'fpropdf_myplugin_activate' );

include 'class.php';

// Define some consts
$wpfx_idd = 'fpdf';
$wpfx_dsc = 'Formidable PRO2PDF';

// Plugin base url
$wpfx_url = trailingslashit( WP_PLUGIN_URL. '/' .dirname( plugin_basename(__FILE__) ) );

// Generate file
function wpfx_output($form, $content)
{
  $form = FPROPDF_FORMS_DIR . $form;
  $temp = tempnam(sys_get_temp_dir(), 'fdf');
  $file = fopen  ($temp, 'w');

  if($file)
  {
    $output = tempnam(sys_get_temp_dir(), 'pdf');

    fwrite($file, $content);
    fclose($file);

    return $temp;
  } 
  else 
    die("Can not open a temporary file for writing, verify the permissions.");
}

function wpfx_download($content)
{
  $temp = tempnam(sys_get_temp_dir(), 'fdf');
  $file = fopen  ($temp, 'w');

  if ( $file )
  {
    fwrite($file, $content);
    fclose($file);

    return $temp;
  } 
  else 
    die("Can not open a temporary file for writing, verify the permissions.");
}

// Field mapping is performed here
function wpfx_extract($layout, $id, $custom = false)
{
  global $wpdb;

  $id = intval( $id ); // Filter IDs

  $data   = array();
  $array  = array();
  $query  = "SELECT `field_id` as id, `meta_value` as value FROM `".$wpdb->prefix."frm_item_metas` WHERE `item_id` IN( $id ";

  // handle rental quotes form which is preceding inflatable
  if($layout == 1)
    $query .= ", ".($id - 1).")";
  else $query .= " )";

  $result = mysql_query($query);

  $rows = array();
  while($row = mysql_fetch_array($result))
    $rows[] = $row;

  $entry = FrmEntry::getOne($id, true);
  $fields = FrmField::get_all_for_form( $entry->form_id, '', 'include' );


  foreach ( $rows as $index => $row )
  {
    $query  = "SELECT `type` FROM `".$wpdb->prefix."frm_fields` WHERE `id` = " . intval( $row['id'] );
    $data = @mysql_fetch_array( @mysql_query( $query ) );
    if ( !$data ) continue;
    if ( ( $data['type'] == 'data' ) or ( $data['type'] == 'checkbox' ) )
    {
      foreach ( $fields as $field )
      {
        if ( $field->id != $row['id'] ) continue;
        $embedded_field_id = ( $entry->form_id != $field->form_id ) ? 'form' . $field->form_id : 0;
        $atts = array(
          'type' => $field->type, 'post_id' => $entry->post_id,
          'show_filename' => true, 'show_icon' => true, 'entry_id' => $entry->id,
          'embedded_field_id' => $embedded_field_id,
        );

        //if ( isset( $_GET['testing'] ) ) { print_r($entry); print_r($field); exit; }
        //$rows[ $index ]['value'] = FrmEntriesHelper::prepare_display_value($entry, $field, $atts);
        $rows[ $index ]['value'] = $entry->metas[ $field->id ];
      }

      //$query  = "SELECT `meta_value` FROM `".$wpdb->prefix."frm_item_metas` WHERE `item_id` = ".intval( $row['value'] ) . ' ORDER BY id DESC LIMIT 1';
      //$data2 = @mysql_fetch_array( @mysql_query( $query ) );
      //if ( !$data2 ) continue;
      //$rows[ $index ]['value'] = $data2['meta_value'];
    }
  }

  // get data
  foreach ( $rows as $row )
  {
    $key = $row['id'];
    $val = $row['value'];

    $found = false;
    foreach ( $data as $dataKey => $values )
      if ( $values[ 0 ] == $key )
      {
        $found = true;
        $data[ $dataKey ][ 1 ] = $val;
      }
    if ( !$found )
      $data[] = array( $key, $val );
  }

  //print_r($data); exit;

  switch($layout)
  {
  case 1: // inflatable app
    $array = array(1135 => 50, 1139 => 73, 1131 => 60, 1140 => 72, 1163 => 74, 1150 => 53, 1125 => 78, 1125 => 79,
      1124 => 82, 1130 => 56, 1127 => 57, 1128 => 59, 1363 => 'List', 1168 => 393, 1147 => 125,
      1148 => 216, 1462 => 151, 1462 => 31); // last one is date filling
    break;

  case 2: // business quote
    $array = array(845 => 71, 848 => 349, 826 => 378, 923 => 389, 876 => 491, 828 => 492, 847 => 489, 830 => 50,
      837 => 102, 928 => 60, 1052 => 346, 844 => 73, 927 => 53, 925 => 74, 932 => 72, 853 => 75,
      854 => 78, 840 => 79, 856 => 80, 855 => 82, 881 => 56, 882 => 57, 883 => 58, 884 => 59,
      857 => 91, 859 => 92, 858 => 93, 860 => 95);
    break;

  case 3: // use custom layout
    $array = $custom;
    break;
  }

  //print_r($data);
  //print_r($array);
  //exit;

  // Prepare list for fdf forming in case of missing fields
  $awesome = array();
  if(is_array($array)) 
    foreach($array as $datakey => $fdfKey)
    {
      $found = false;
      foreach ( $data as $values )
        if ( $values[0] == $fdfKey[0] )
        {
          $awesome[] = array( $fdfKey[ 1 ], $values[ 1 ] );
          $found = true;
        }
      if ( ! $found )
        $awesome[] = array( $fdfKey[ 1 ], '');
    }

  //print_r($awesome);
  return $awesome;
}

function fpropdf_bash_replace($string)
{
  return $string;
}

function fpropdf_custom_command_exist($cmd) {
  $returnVal = @shell_exec("which $cmd");
  return ( empty($returnVal) ? false : true );
}

function fpropdf_is_activated()
{
  if ( defined('FPROPDF_IS_MASTER') )
    return true;
  $code = get_option('fpropdf_licence');
  return $code;
}

function fpropdf_is_trial()
{
  $code = get_option('fpropdf_licence');
  return ( $code and preg_match( '/^TRIAL/', $code) );
}

function fpropdf_check_code($code, $update=0)
{
  if ( ! function_exists('curl_init') )
    throw new Exception('Curl extension is not enabled on this server.');
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => FPROPDF_SERVER . 'licence/check.php',
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => array(
      'salt'   => FPROPDF_SALT,
      'code'   => $code,
      'update' => $update,
    )
  ));
  $result = curl_exec($curl);
  if ( ! $result )
    throw new Exception('Server did not return any results. Please try again later.');
  $result = json_decode($result);
  if ( $result->activated )
  {
    if ( $update )
      update_option('fpropdf_licence', $code);
    return true;
  }
  update_option( 'fpropdf_licence', 'TRIAL' . strtoupper(FPROPDF_SALT) );
  throw new Exception('This licence code is not valid.');
  return false;
}


define('FPROPDF_SERVER', 'http://www.idealchoiceinsurance.com/wp-content/plugins/fpropdf/');
global $wpdb;
define('FPROPDF_SALT', md5( NONCE_SALT . $wpdb->prefix) );

// Admin Options page
function wpfx_admin()
{
  global $wpfx_url, $wpfx_idd, $wpfx_dsc;


  $wpfx_fdf = new FDFMaker();

  echo "<div class = 'parent formidable-pro-fpdf'>";

  echo "<div class = '_first _left'>";
  echo "<h1>$wpfx_dsc</h1>";

  if ( version_compare(PHP_VERSION, '5.3.0', '<') )
  {
    echo '<div class="error"><p>This plugin requires PHP version 5.3 or higher. Your version is '.PHP_VERSION.'. Please upgrade your PHP installation.</p></div>';
    exit;
  }

  if ( isset($_GET['action']) and ( $_GET['action'] == 'deactivatekey' ) )
  {
    update_option( 'fpropdf_licence', 'TRIAL' . strtoupper(FPROPDF_SALT) );
    echo "<div class='updated'><p>The licence key has been deactivated.</p></div>";
  }

  // Start activating

  if ( isset( $_POST['action'] ) and ( $_POST['action'] == 'activate-fpropdf' ) )
  {
    try
    {
      $code = trim( $_POST['activation-code'] );
      if ( ! $code )
        throw new Exception('Please paste the activation code into the text field.');
      fpropdf_check_code($code, 1);
      echo '<div class="updated" style="margin-left: 0;"><p>Thanks for activating Formidable PRO2PDF! You are now using the full version of the plugin.</p></div>';
    }
    catch ( Exception $e )
    {
      echo '<div class="error" style="margin-left: 0;"><p>'.$e->getMessage().' <a href="#" class="fpropdf-activate">Click here</a> to retry.</p></div>';
    }
  }

  // start checking for errors

  $errors = array();

  try
  {
    if ( ! file_exists( $tmp = FPROPDF_FORMS_DIR ) )
      throw new Exception("Folder $tmp could not be created. Please create it using FTP, and set its permissions to 777.");
    if ( ! is_writable( $tmp = FPROPDF_FORMS_DIR ) )
      throw new Exception("Folder $tmp should be writable. Please change its permissions to 777.");
  }
  catch ( Exception $e ) 
  { 
    $errors[] = $e->getMessage(); 
  }

  try
  {
    if ( ! is_writable( $tmp = __DIR__ . '/fields' ) )
      throw new Exception("Folder $tmp should be writable. Please change its permissions to 777.");
  }
  catch ( Exception $e ) 
  { 
    $errors[] = $e->getMessage(); 
  }

  try
  {
    if ( ! file_exists( __DIR__ . '/../formidable/formidable.php') )
      throw new Exception("Formidable PRO2PDF requires Formidable Forms plugin installed and activated. Please <a target='_blank' href='plugin-install.php?tab=search&s=formidable'>install it</a>.");
    if ( ! class_exists('FrmAppHelper') ) // Check if Formidable class exists
      throw new Exception("Formidable PRO2PDF requires Formidable Forms plugin installed and activated. Please <a href='plugins.php'>activate it</a>.");
  }
  catch ( Exception $e ) 
  { 
    $errors[] = $e->getMessage(); 
  }

  try
  {
    $msg = "You can generate only 1 PDF forms, because ";
    if ( ini_get('safe_mode') )
      throw new Exception("PHP safe mode is turned on. Unless you <a href='#' class='fpropdf-activate'>activate this plugin</a>, it won't work with PHP safe mode.");
    $functions = explode(' ', 'exec passthru system shell_exec');
    foreach ( $functions as $function )
    {
      $d = ini_get('disable_functions');
      $s = ini_get('suhosin.executor.func.blacklist');
      if ("$d$s") {
        $array = preg_split('/,\s*/', "$d,$s");
        if (in_array($function, $array) and !fpropdf_is_activated()) {
          throw new Exception("your server has to have PHP <code>".$function."()</code> command enabled for PDF generation.");
        }
      }
    }
    if ( ! function_exists('curl_init') )
      throw new Exception("your server has to have <code>Curl</code> extension installed and enabled for PDF generation.");
    if ( ! function_exists('mb_convert_encoding') and ! function_exists('iconv') )
      throw new Exception("your server has to have PHP <code>MB</code> or <code>iconv</code> extension installed and enabled for PDF generation.");
    if ( ! fpropdf_is_activated() or fpropdf_is_trial() )
    {
      if ( ! fpropdf_custom_command_exist('ls') )
        throw new Exception("your server has to have PHP <code>shell_exec()</code> command enabled for PDF generation.");
      if ( ! fpropdf_custom_command_exist('pdftk') )
        throw new Exception("your server has to have <code>pdftk</code> installed for PDF generation. Please <a href='https://www.pdflabs.com/docs/install-pdftk-on-redhat-or-centos/' target='_blank'>install it</a>.");
    }
  }
  catch ( Exception $e ) 
  { 
    $errors[] = $msg . $e->getMessage() . " Alternatively, you can <a href='#' class='fpropdf-activate'>activate Formidable PRO2PDF</a> if you want to use more forms."; 
  }

  try
  {
    if ( ! fpropdf_is_activated() )
      throw new Exception("You are using a free version of the plugin. To unlock additional functions (no need of installing pdftk, pretty field selection and many others), please <a href='#' class='fpropdf-activate'>activate Formidable PRO2PDF</a>.");
  }
  catch ( Exception $e ) { $errors[] = $e->getMessage(); }

  foreach ( $errors as $error )
    echo '<div class="error" style="margin-left: 0;"><p>'.$error.'</p></div>';

  // end checking for errors

  if ( isset( $_SESSION['new_layout'] ) and $_SESSION['new_layout'] )
  {
    unset( $_SESSION['new_layout'] );
    echo '<div class="updated" style="margin-left: 0;"><p>Layout has been added. You can now use it.</p></div>';
  }

  if ( isset($_POST['action']) and ( $_POST['action'] == 'upload-pdf-file' ) )
  {
    try
    {
      if ( !isset( $_FILES['upload-pdf'] ) or !$_FILES['upload-pdf'] )
        throw new Exception('Please select a PDF file');
      $file = $_FILES['upload-pdf'];
      $fname = $file['name'];
      $tmp = $file['tmp_name'];
      if ( ! preg_match('/\.pdf$/i', $fname) )
        throw new Exception('The file should be a PDF file and have .pdf file extension. Please <a href="#" class="upl-new-pdf">upload another file</a>.');
      $fname = preg_replace('/\.pdf$/i', '.pdf', $fname);
      @move_uploaded_file( $file['tmp_name'], FPROPDF_FORMS_DIR . $fname );
      echo '<div class="updated" style="margin-left: 0;"><p><b>'.$fname.'</b> has been uploaded. You can now use it in your layouts.</p></div>';
    }
    catch (Exception $e)
    {
      echo '<div class="error" style="margin-left: 0;"><p>'.$e->getMessage().'</p></div>';
    }
  }

  // Handle user input
  if ( isset($_POST["wpfx_submit"]) and $_POST["wpfx_submit"] )
  {
    echo "<div align = 'center'>";
    echo "<form method = 'POST' action = '$wpfx_url"."generate.php' target='_blank' id = 'dform' >";

    $filename = '';
    $filledfm = '';

    $layout   = wpfx_readlayout(intval($_POST['wpfx_layout']) - 9);
    global $currentLayout;
    $currentLayout = $layout;

    // Generate pdf
    switch($_POST['wpfx_layout'])
    {
    case 1:
      $filename = wpfx_download($wpfx_fdf->makeInflatablesApp(wpfx_extract(1, $_POST['wpfx_dataset']), FPROPDF_FORMS_DIR.'InflatableApp.pdf') );
      $filledfm = 'InflatableApp.pdf';
      break;

    case 2:
      $filename = wpfx_download($wpfx_fdf->makeBusinessQuote(wpfx_extract(2, $_POST['wpfx_dataset']), FPROPDF_FORMS_DIR.'BusinessQuote.pdf') );
      $filledfm = 'BusinessQuote.pdf';
      break;

    default:
      $pdf      = FPROPDF_FORMS_DIR.$layout['file'];
      $filename = wpfx_download($wpfx_fdf->makeFDF(wpfx_extract(3, $_POST['wpfx_dataset'], $layout['data']), $pdf) );
      $filledfm = $layout['file'];
      break;
    }

    $filledfm = FPROPDF_FORMS_DIR.fpropdf_bash_replace($filledfm);

    echo "<input type = 'hidden' name = 'desired' value = '$filledfm' />";
    echo "<input type = 'hidden' name = 'actual'  value = '$filename' />";
    echo "<input type = 'hidden' name = 'lock' value = '".$layout['visible']."' />";
    echo "<input type = 'hidden' name = 'passwd' value = \"".htmlspecialchars($layout['passwd'])."\" />";
    echo "<input type = 'hidden' name = 'filename' value = '".esc_attr($layout['name'])."' />";
    echo "<input type = 'submit' value = 'Download' name = 'download' id = 'hideme' />";
    echo "</form>";
    echo "</div>";
    //exit;

    unset ($_POST);
  } 
  else if( isset($_POST['wpfx_savecl']) and $_POST['wpfx_savecl'] ) // Save a custom layout here
  {
    $layout = array();

    $formats = array();

    foreach($_POST['clfrom'] as $index => $value)
    {
      $to = $_POST['clto'][$index];

      $formats[] = array( $to, $_POST['format'][ $index ], fpropdf_stripslashes( $_POST['repeatable_field'][ $index ] ), fpropdf_stripslashes( $_POST['checkbox_field'][ $index ] ) );


      if( strlen(trim($value)) && strlen(trim($to)) )
        $layout[] = array( $value, $to );
    }

    // Get desired dataset name
    // "clname" can be anything and does not need to be filtered
    list(, $index) = explode("_", $_POST['clname']);

    $index = intval($index);

    $add_att = esc_sql( $_POST['wpfx_add_att'] );
    $passwd = esc_sql( $_POST['wpfx_password'] );

    if(isset ($_POST['update']) && ($_POST['update'] == 'update'))
      $r = wpfx_updatelayout(intval($_POST['wpfx_layout']) - 9, esc_sql( $_POST['wpfx_clname'] ), base64_decode(urldecode($_POST['wpfx_clfile'])), intval($_POST['wpfx_layout_visibility']), esc_sql( $_POST['wpfx_clform'] ), $index, $layout, $formats, $add_att, $passwd);
    else 
      $r = wpfx_writelayout( esc_sql( $_POST['wpfx_clname'] ), base64_decode(urldecode($_POST['wpfx_clfile'])), intval($_POST['wpfx_layout_visibility']), esc_sql( $_POST['wpfx_clform'] ), $index, $layout, $formats, $add_att, $passwd);

    if ( $r )
      echo '<div class="updated" style="margin-left: 0;"><p>Layout has been saved!</p></div>';
    else 
      echo '<div class="error" style="margin-left: 0;"><p>Failed to save this custom layout :(</p></div>';

    echo '<script>window.location.href="?page=fpdf";</script>'; 
    exit;

  }

  if ( ! is_plugin_active('formidable/formidable.php') )
  {
    echo '</div></div>';
    return;
  }

  $forms  = wpfx_getforms();

  $has_forms = false;
  foreach($forms as $key => $data)
  {
    if(($key == '9wfy4z') or ($key == '218da3') or (strtotime($data[1]) > strtotime("01 March 2013")))
    {
      $has_forms = true;
    }
  }
  if ( ! $has_forms )
  {
    echo '<div class="error" style="margin-left: 0;"><p>You have no Formidable Forms. Please <a href="admin.php?page=formidable&frm_action=new" class="button-primary">Add a Formidable Form</a></p></div>';
    echo '</div></div>';
    return;
  }


  $currentTab = $_GET['tab'];
  if ( ! in_array( $currentTab, array('general', 'forms') ) )
    $currentTab = "general";

  if ( fpropdf_is_activated() and !defined('FPROPDF_IS_MASTER') )
  {
    $tabs = array( 'general' => 'Export', 'forms' => 'Activated Forms' );
    echo '<div id="icon-themes" class="icon32"><br></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $name ){
      $class = ( $tab == $currentTab ) ? ' nav-tab-active' : '';
      echo "<a class='nav-tab$class' href='?page=fpdf&tab=$tab'>$name</a>";

    }
    echo '</h2>';
  }

  if ( $currentTab == 'forms' )
  {

    $code = get_option('fpropdf_licence');
    if ( $code and !fpropdf_is_trial() )
    {
      try
      {
        fpropdf_check_code( $code, 1 );
      }
      catch (Exception $e)
      {

      }
    }

    $this_site = new stdClass();
    $this_site->url = site_url('/');
    $this_site->site_salt = FPROPDF_SALT;
    $this_site->title = get_bloginfo('name');
    $this_site->not_active = true;
    $this_site->ip = $_SERVER['SERVER_ADDR'];

    if ( $_GET['action'] == 'site_activate' )
    {
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => FPROPDF_SERVER . 'licence/licence-change.php',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => array(
          'salt'   => FPROPDF_SALT,
          'code'   => $code,
          'action' => 'activate_site',
          'title'  => $this_site->title,
          'url'    => $this_site->url,
        )
      ));
      $result = curl_exec($curl);
      $result = json_decode($result);
      if ( $result->success )
        echo "<div class='updated'><p>The site has been activated.</p></div>";
      elseif ( $result->error )
        echo "<div class='error'><p>".$result->error."</p></div>";
      else
        echo "<div class='error'><p>Unknown error. Please try again later.</p></div>";
    }

    if ( $_GET['action'] == 'form_activate' )
    {
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => FPROPDF_SERVER . 'licence/licence-change.php',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => array(
          'salt'   => FPROPDF_SALT,
          'code'   => $code,
          'action' => 'activate_form',
          'site_id'    => $_GET['site'], // No need to filter this
          'form_id'    => $_GET['form'], // No need to filter this
          'title'      => $_GET['title'], // No need to filter this
        )
      ));
      $result = curl_exec($curl);
      $result = json_decode($result);
      //print_r($result);
      if ( $result->success )
        echo "<div class='updated'><p>The form has been activated.</p></div>";
      elseif ( $result->error )
        echo "<div class='error'><p>".$result->error."</p></div>";
      else
        echo "<div class='error'><p>Unknown error. Please try again later.</p></div>";
    }

    if ( $_GET['action'] == 'form_deactivate' )
    {
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => FPROPDF_SERVER . 'licence/licence-change.php',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => array(
          'salt'   => FPROPDF_SALT,
          'code'   => $code,
          'action' => 'deactivate_form',
          'site_id'    => $_GET['site'], // No need to filter this
          'form_id'    => $_GET['form'], // No need to filter this
        )
      ));
      $result = curl_exec($curl);
      //print_r($result);
      $result = json_decode($result);
      if ( $result->success )
        echo "<div class='updated'><p>The form has been deactivated.</p></div>";
      elseif ( $result->error )
        echo "<div class='error'><p>".$result->error."</p></div>";
      else
        echo "<div class='error'><p>Unknown error. Please try again later.</p></div>";
    }

    if ( $_GET['action'] == 'site_deactivate' )
    {
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => FPROPDF_SERVER . 'licence/licence-change.php',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => array(
          'salt'   => FPROPDF_SALT,
          'code'   => $code,
          'action' => 'deactivate_site',
          'site_id'    => $_GET['site'], // No need to filter this
        )
      ));
      $result = curl_exec($curl);
      //print_r($result);
      $result = json_decode($result);
      if ( $result->success )
        echo "<div class='updated'><p>The site has been deactivated.</p></div>";
      elseif ( $result->error )
        echo "<div class='error'><p>".$result->error."</p></div>";
      else
        echo "<div class='error'><p>Unknown error. Please try again later.</p></div>";
    }


    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => FPROPDF_SERVER . 'licence/info.php',
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => array(
        'salt'   => FPROPDF_SALT,
        'code'   => $code,
      )
    ));
    $result = curl_exec($curl);
    $result = json_decode($result);

    // Output activated forms

    $found = false;
    foreach ( $result->sites as $site )
      if ( $site->site_salt == $this_site->site_salt )
        $found = true;
    if ( ! $found )
      array_unshift( $result->sites, $this_site );

    $this_forms = array();
    foreach ( $forms as $key => $form )
    {
      if(($key == '9wfy4z') or ($key == '218da3') or (strtotime($form[1]) > strtotime("01 March 2013")))
      {
        $this_form = new stdClass();
        $this_form->form_id = $key;
        $this_form->not_active = 1;
        $this_form->title = $form[0];
        $this_forms[] = $this_form;
      }
    }

    foreach ( $result->sites as $site )
      if ( $site->site_salt == $this_site->site_salt )
      {
        foreach ( $this_forms as $form )
        {
          $found = false;
          foreach ( $site->forms as $site_form )
            if ( $site_form->form_id == $form->form_id )
            {
              $found = true;
              $site_form->title = $form->title;
            }
          if ( ! $found )
            $site->forms[] = $form;
        }
      }

    if ( fpropdf_is_trial() )
      echo "<div class='updated'><p>You can activate only 1 form on this website. Please <a href='#' class='button-primary fpropdf-activate'>upgrade</a> if you want to use more forms.</p></div>";
    else
      echo "<div class='updated'><p>Your licence key is <strong>".$code."</strong>. <br /> It is valid until ".date('m/d/Y', strtotime( $result->licence->expires_on ))." <br />With this activation code, you can register up to <strong>".$result->licence->sites."</strong> site".($result->licence->sites == 1 ? '' : 's')." and up to <strong>".$result->licence->forms."</strong> form".($result->licence->forms == 1 ? '' : 's').". <a href='?page=fpdf&action=deactivatekey'>Click here to deactivate this key.</a> </p><p>You have <strong>".$result->sites_left."</strong> site".($result->licence->sites_left == 1 ? '' : 's')." and <strong>".$result->forms_left."</strong> form".($result->licence->forms_left == 1 ? '' : 's')." left.</p></div>";

    echo '<ol class="fpropdf-sites">';
    if ( ! count($result->sites) )
    {
      echo '<li><i>you do not have any active sites</i></li>';
    }
    else
    {
      foreach ( $result->sites as $site )
      {
        echo '<li class="opt-'.($site->not_active ? 'inactive' : 'active').'">';
        echo $site->url.' ('.$site->title.')';

        if ( $site->not_active )
        {

          echo ' - not active. <a class="" href="?page=fpdf&tab=forms&action=site_activate" style="opacity: 1;">Activate this website</a>';
          echo '</li>';
          continue;
        }

        echo ' - active. <a class="" href="?page=fpdf&action=site_deactivate&tab=forms&site='.$site->site_id.'">Deactivate this website</a>';
        if ( !count( $site->forms ) )
          echo '<ul><li><i>no activated forms</i></li></ul>';
        else
        {
          echo '<ul>';
          foreach ( $site->forms as $form )
          {
            echo '<li class="opt-'.($form->not_active ? 'inactive' : 'active').'">';
            echo $form->title;
            if ( $form->not_active )
              echo ' - not active. <a class="" href="?page=fpdf&action=form_activate&tab=forms&site='.urlencode($site->site_id).'&form='.urlencode($form->form_id).'&title='.urlencode($form->title).'">Activate</a>';
            else
              echo ' - active. <a class="" href="?page=fpdf&action=form_deactivate&tab=forms&site='.urlencode($site->site_id).'&form='.urlencode($form->form_id).'">Deactivate</a>';
            echo '</li>';
          }
          echo '</ul>';
        }
        echo '</li>';
      }
    }

    echo '</ol>';

    echo '</div>';
    return;
  }

  if ( function_exists('add_thickbox') )
    add_thickbox();

  echo "<form method = 'POST' id='frm-bg' data-activated='".intval(!fpropdf_is_trial())."'>";
  echo "<table>";
  echo "<tr>";
  echo "<td width='300'>Select the form to export data from:</td>";
  echo "<td colspan = '2'><select id = 'wpfx_form' name = 'wpfx_form'>";

  $actual = array();

  // hardcode inflatable apps, business quote and new forms
  foreach($forms as $key => $data)
  {
    if(($key == '9wfy4z') or ($key == '218da3') or (strtotime($data[1]) > strtotime("01 March 2013")))
    {
      echo "<option value = '$key'>".$data[0]."</option>";
      $actual[ $key ] = $data;
    }
  }

  echo "</select> &nbsp; ";
  echo "<a class='button' target = 'blank' href = 'admin-ajax.php?action=frm_forms_preview' id = 'wpfx_preview'>Preview</a></td></tr>";

  echo "<tr><td>Select the dataset to export:</td>";
  echo "<td><select id = 'wpfx_dataset' name = 'wpfx_dataset'>";

  // Datasets will be filled by AJAX

  echo "</select></td>";
  echo "<td></td></tr>";

  // Manage layouts
  echo "<tr><td>Field Map to use:</td>";
  echo "<td colspan = '2'><select id = 'wpfx_layout' name = 'wpfx_layout'>";
  echo "<option value = '3'>New Field Map</option>";

  // Populate with custom saved layouts
  foreach(wpfx_getlayouts() as $key => $name)
    echo "<option value = '$key'>$name</option>";

  echo "</select></td></tr>";


  if ( fpropdf_is_activated() and !fpropdf_is_trial() )
  {
    echo "<tr><td></td><td> <label> <input type='checkbox' id='use-second-layout' /> Add a second dataset</label> </td></tr>";


    echo "<tr class='hidden-use-second'>";
    echo "<td>Select the second form to export data from:</td>";
    echo "<td colspan = '2'><select id = 'wpfx_form2' name = 'wpfx_form2'>";

    $actual = array();

    foreach($forms as $key => $data)
    {
      if(($key == '9wfy4z') or ($key == '218da3') or (strtotime($data[1]) > strtotime("01 March 2013")))
      {
        echo "<option value = '$key'>".$data[0]."</option>";
        $actual[ $key ] = $data;
      }
    }

    echo "</select> &nbsp; ";
    echo "<a class='button' target = 'blank' href = 'admin-ajax.php?action=frm_forms_preview' id = 'wpfx_preview2'>Preview</a></td></tr>";

    echo "<tr class='hidden-use-second'><td>Select the second dataset to export:</td>";
    echo "<td><select id = 'wpfx_dataset2' name = 'wpfx_dataset2'>";

    // Will be filled by AJAX

    echo "</select></td>";
    echo "<td></td></tr>";

    // Manage layouts
    echo "<tr class='hidden-use-second'><td>Second Field Map to use:</td>";
    echo "<td colspan = '2'><select id = 'wpfx_layout2' name = 'wpfx_layout2'>";

    // Populate with custom saved layouts
    foreach(wpfx_getlayouts() as $key => $name)
      echo "<option value = '$key'>$name</option>";

    echo "</select></td></tr>";


  }

  echo "<tr><td colspan = '3' align = 'center'><hr /><a href='#' target='_blank' id='main-export-btn' class='button-primary'>Export</a></td></tr>";
  echo "</table>";
  echo "</form>";

  echo "</div>";
  echo "<div class = '_second _left'><div id = 'loader'><img src = '".$wpfx_url."res/loader.gif' /> Loading layout... Please wait...</div><div class = 'layout_builder' style='width: auto;'><h2>Field Map Designer</h2>";
  echo "<form method = 'POST' id = 'wpfx_layout_form'>";

  echo "<table>";
  echo "<tr><td>Name of Field Map (will be used as default filename):</td><td><input name = 'wpfx_clname' id = 'wpfx_clname' /></td></tr>";
  echo "<tr><td>Select PDF file to work with:</td><td><select name = 'wpfx_clfile' id = 'wpfx_clfile'>";

  // Print existing PDF files
  if ($handle = opendir( FPROPDF_FORMS_DIR ))
  {
    while (false !== ($file = readdir($handle)))
    {
      if ($file != "." && $file != ".." && strtolower(substr($file, strrpos($file, '.') + 1)) == 'pdf')
      {
        echo "<option value = '".base64_encode($file)."' >$file</option>";
      }
    }
    closedir($handle);
  } 
  else 
    echo "<option>Error: can not list directory</option>";

  echo "</select></td></tr>
    <tr><td></td><td>
    <a href='#' class='upl-new-pdf button-primary' style='margin: 1px;'>Upload a PDF file</a>
    <input type='button' class='remove-pdf button' style='margin: 1px;' value='Remove this PDF file' />

    </td></tr>";

  echo "<tr><td>Select Form to work with:</td><td><select name = 'wpfx_clform' id = 'wpfx_clform'>";

  $forms  = wpfx_getforms();
  $actual = array();

  foreach($forms as $key => $data)
  {
    if(($key == '9wfy4z') or ($key == '218da3') or (strtotime($data[1]) > strtotime("01 March 2013")))
    {
      echo "<option value = '$key'>".$data[0]."</option>";
      $actual[ $key ] = $data;
    }
  }

  echo "<tr><td>Flatten PDF form</td>";
  if ( fpropdf_is_activated() and !fpropdf_is_trial() )
    echo "<td><select id = 'wpfx_layoutvis'><option value = '1'>Yes</option><option value = '0'>No</option></select></td></tr>";
  else
    echo "<td><select id = 'wpfx_layoutvis' disabled='disabled'><option value = '0'>No</option></select></td></tr>";

  echo "<tr><td>Attach PDF to Email notifications</td>";
  if ( fpropdf_is_activated() and !fpropdf_is_trial() )
    echo "<td><select id = 'wpfx_add_att' name='wpfx_add_att'><option value = '1'>Yes</option><option value = '0'>No</option></select></td></tr>";
  else
    echo "<td><select id = 'wpfx_add_att' name='wpfx_add_att' disabled='disabled'><option value = '0'>No</option></select></td></tr>";

  echo "<tr><td>PDF password <i>(leave empty if password shouldn't be set)</i>:</td><td><input name = 'wpfx_password' id = 'wpfx_password' /></td></tr>";

  // now create dynamic list
  echo "<tr><td colspan = '2'><table class = 'cltable'>";
  echo "<thead><tr>";
  echo "<th>Use as <br />Dataset<br />Name?</th><th>Webform Data Field ID</th><th>Maps<br />to...</th><th>PDF Form Field Name</th>";
  if ( fpropdf_is_activated() and !fpropdf_is_trial() )
    echo "<th>Format</th>";
  echo "<th>&nbsp;</th>";
  echo "</thead></tr><tbody id='clbody' data-activated='".intval(!fpropdf_is_trial())."'>";

  // table body will be populated by AJAX

  echo "</tbody></table>";
  echo "<br />";
  echo "</td></tr><tr><td colspan = '2'><table  width = '100%'><tr>";

  // Control buttons here
  echo "<td align = 'left'><input type = 'button' id = 'clnewmap' value = 'Map Another Field' class='button' />";
  echo "<input type = 'reset' value = 'Reset' class='button' /></td>";
  echo "<td align = 'center'><input type = 'submit' value = 'Save Field Map' class='button-primary' name = 'wpfx_savecl' id = 'savecl'/></td>";
  echo "<td align = 'right'>
    <input type = 'button' value = 'Duplicate this Field Map' class='button' id = 'dupcl'/>
    <input type = 'button' value = 'Delete Entire Field Map' class='button' id = 'remvcl'/>
  </td></tr></table>";
  echo "</td></tr></table></form>";
  echo "</div></div>";
  echo "</div>";
}

// Get all Formidable forms available
function wpfx_getforms()
{
  global $wpdb;

  $query = "SELECT `form_key`, `name`, `created_at` FROM `".$wpdb->prefix."frm_forms` WHERE `status` = 'published' ORDER BY UNIX_TIMESTAMP(`created_at`) DESC";
  $array = array();

  $result = mysql_query($query);

  while($row = mysql_fetch_array($result))
    $array[ $row['form_key'] ] = array( stripslashes($row['name']), $row['created_at'] );

  return $array;
}

// Get all custom created layouts
function wpfx_getlayouts()
{
  global $wpdb;

  $array  = array();
  $query  = "SELECT `ID`, `name` FROM `wp_fxlayouts` WHERE 1 ORDER BY `created_at` DESC";
  $result = mysql_query($query);

  while ( $row = mysql_fetch_array($result) )
    $array[ $row['ID'] + 9 ] = stripslashes($row['name']); // adding 9 not to mess up with our hardcoded layouts

  return $array;
}

function wpfx_readlayout($id)
{
  global $wpdb;

  //$query  = "SELECT w.`name`, w.`data`, w.`file`, w.`visible`, w.`dname`, f.`form_key` as `form`, w.`formats`, w.`add_att` FROM `wp_fxlayouts` w, `".$wpdb->prefix."frm_forms` f WHERE w.`ID` = $id AND f.`id` = w.`form`";
  $query  = "SELECT w.*, f.`form_key` as `form` FROM `wp_fxlayouts` w, `".$wpdb->prefix."frm_forms` f WHERE w.`ID` = $id AND f.`id` = w.`form`";
  $result = mysql_query($query);
  $result = mysql_fetch_array($result);

  $formats = @unserialize($result['formats']);
  if ( ! is_array($formats) )
    $formats = array();

  $data = unserialize($result['data']);

  $vals = array_values( $data );
  if ( count($vals) )
  if ( !is_array($vals[0]) )
  {
    $_data = array();
    foreach ( $data as $k => $v )
      $_data[] = array( $k, $v );
    $data = $_data;
  }

  $vals = array_values( $formats );
  if ( count($vals) )
  if ( !is_array($vals[0]) )
  {
    $_data = array();
    foreach ( $formats as $k => $v )
      $_data[] = array( $k, $v );
    $formats = $_data;
  }

  return array(
    'name' => $result['name'], 
    'passwd' => stripslashes($result['passwd']),
    'file' => $result['file'], 
    'visible' => $result['visible'], 
    'form' => $result['form'], 
    'index' => $result['dname'], 
    'add_att' => $result['add_att'], 
    'data' => $data, 
    'formats' => $formats
  );
}


function wpfx_writelayout($name, $file, $visible, $form, $index, $data, $formats, $add_att, $passwd)
{
  global $wpdb;

  //$name = mysql_real_escape_string($name);
  //$file = mysql_real_escape_string($file);
  //$passwd = mysql_real_escape_string($passwd);
  $data = serialize($data);
  $formats = mysql_real_escape_string( serialize($formats) );

  $query = "SELECT `id` FROM `".$wpdb->prefix."frm_forms` WHERE `form_key` = '$form'";

  $form = mysql_fetch_array(mysql_query($query));

  $form = $form['id'];

  mysql_query("ALTER TABLE wp_fxlayouts ADD COLUMN formats TEXT");
  mysql_query("ALTER TABLE wp_fxlayouts ADD COLUMN passwd VARCHAR(255)");
  mysql_query("ALTER TABLE wp_fxlayouts ADD COLUMN add_att INT(3) UNSIGNED NOT NULL DEFAULT '0'");

  $query = "INSERT INTO `wp_fxlayouts` (`name`, `file`, `visible`, `form`, `data`, `dname`, `created_at`, `formats`, `add_att`, `passwd`)
    VALUES ('$name', '$file', $visible, $form, '$data', $index, NOW(), '$formats', '$add_att', '$passwd')";

  $_SESSION['new_layout'] = 1;

  $res = mysql_query($query);


  return $res;
}

@session_start();

function wpfx_updatelayout($id, $name, $file, $visible, $form, $index, $data, $formats, $add_att, $passwd)
{
  global $wpdb;

  //$name = mysql_real_escape_string($name);
  //$file = mysql_real_escape_string($file);
  //$passwd = mysql_real_escape_string($passwd);
  $data = serialize($data);
  $formats = mysql_real_escape_string( serialize($formats) );

  mysql_query("ALTER TABLE wp_fxlayouts ADD COLUMN formats TEXT");
  mysql_query("ALTER TABLE wp_fxlayouts ADD COLUMN passwd VARCHAR(255)");
  mysql_query("ALTER TABLE wp_fxlayouts ADD COLUMN add_att INT(3) UNSIGNED NOT NULL DEFAULT '0'");

  $query = "UPDATE `wp_fxlayouts` SET `name` = '$name',
    `file` = '$file', `data` = '$data', `visible` = $visible,
    `form` = (SELECT `id` FROM `".$wpdb->prefix."frm_forms` WHERE `form_key` = '$form'),
             `dname` = $index,
             `formats` = '$formats',
             `add_att` = '$add_att',
             `passwd` = '$passwd',
             `created_at` = NOW() WHERE `ID` = $id";

  //echo $query; mysql_query($query); exit;

  return mysql_query($query);
}


// Get all datasets for specified form
function wpfx_getdataset()
{
  global $wpdb;

  // Form key can be any string
  $key   = esc_sql( $_POST['wpfx_form_key'] );

  $array = array();

  $query = "SELECT  `id` FROM    `".$wpdb->prefix."frm_forms` WHERE  `form_key` =  '$key'";
  $fid   = mysql_fetch_array(mysql_query($query));
  $fid   = $fid['id'];


  $query = "SELECT `id`, `name`, `item_key`, `created_at` FROM  `".$wpdb->prefix."frm_items`
    WHERE  `form_id` = $fid ORDER BY UNIX_TIMESTAMP(`created_at`) DESC";

  $result = mysql_query($query);

  if ( is_resource($result) AND mysql_num_rows($result) > 0 )
  {
    while ( $row = mysql_fetch_array($result) )
    {
      //print_r($row);
      $query  = "SELECT `data`, `dname` FROM `wp_fxlayouts` WHERE `form` = $fid";
      $layoutQuery = mysql_query($query);

      $name = '';

      $layouts = array();
      if ( $numLayout = mysql_num_rows($layoutQuery) )
      {
        while ( $layout = mysql_fetch_array( $layoutQuery ) )
          $layouts[] = $layout;
      }

      if ( $numLayout )
      {

        foreach ( $layouts as $layout )
        {

          //$layout = mysql_fetch_array($layoutQuery);
          //$name .= print_r($layout, true);

          $count = 0;
          $found = false;

          foreach ( unserialize($layout['data'] ) as $values )
          {
            $key = $values[0];
            $value = $values[1];
            if($count == $layout['dname'])
            {
              $count = $key;
              $found = true;
              break;
            }
            $count ++;
          }

          if ( $found )
          {
            // Old code
            //$query = "SELECT `meta_value` as value FROM `".$wpdb->prefix."frm_item_metas` WHERE `item_id` = ".$row['id']." AND `field_id` = $count";
            //$_name  = mysql_fetch_array(mysql_query($query));
            //if ( $_name )
            //{
              //$name  = stripslashes($_name['value']);
              //break;
            //}

            //print_r($row); print_r($count); exit;

            $entry = FrmEntry::getOne($row['id'], true);
            $fields = FrmField::get_all_for_form( $entry->form_id, '', 'include' );

            $found2 = false;
            foreach ( $fields as $field )
            {
              if ( $field->id != $count ) continue;
              $embedded_field_id = ( $entry->form_id != $field->form_id ) ? 'form' . $field->form_id : 0;
              $atts = array(
                'type' => $field->type, 'post_id' => $entry->post_id,
                'show_filename' => true, 'show_icon' => true, 'entry_id' => $entry->id,
                'embedded_field_id' => $embedded_field_id,
              );
              $name = FrmEntriesHelper::prepare_display_value($entry, $field, $atts);



              if ( $name )
                $found2 = true;
              break;
            }

            if ( $found2 ) continue;


            $query = "SELECT `meta_value` as value FROM `".$wpdb->prefix."frm_item_metas` WHERE `item_id` = ".$row['id']." AND `field_id` = $count";
            $_name  = mysql_fetch_array(mysql_query($query));
            if ( $_name )
            {
              $name  = stripslashes($_name['value']);
              break;
            }

          }

          if ( ! $name )
            $name = "[empty]";
        } 

        if ( ! $name )
          $name = "Add matching field first";
      }     
      else 
        $name = "Add matching layout first";

      $array[] = array('id' => $row['id'],
        'date' => $name." — ".date("m-d-Y", strtotime($row['created_at'])));
    }
  } 
  else 
    $array = array(
      array(
        'id' => -3, 
        'date' => 'This form does not contain datasets'
      ) 
    );

  echo json_encode($array);

  die();
}

function wpfx_peeklayout()
{
  global $wpdb, $currentFile;

  // Convert into integer for security reasons
  $id = intval($_POST['wpfx_layout']) - 9;

  $layout = wpfx_readlayout($id);

  $file = FPROPDF_FORMS_DIR . $layout['file'];
  if ( defined('FPROPDF_IS_DATA_SUBMITTING') )
    $file = $currentFile;

  $form_key = $layout['form'];

  $layout['file'] = base64_encode($layout['file']);

  ob_start();

  $layout['imagesBase'] = plugins_url( '', __FILE__ );
  $layout['images'] = array();
  $layout['checkboxes'] = array();

  try
  {
    if ( !file_exists($file) )
      throw new Exception('PDF file not found');
    $fields_data = shell_exec("pdftk '$file' dump_data_fields_utf8 2> /dev/null");
    $fields = array();
    if ( !preg_match_all('/FieldName: (.*)/', $fields_data, $m) )
      throw new Exception('PDFTK returned no fields.');
    $fields = $m[1];
    $layout['fields2'] = $fields;

    $data2 = explode('---', $fields_data);
    foreach ($data2 as $_row)
    {
      $_id = false;
      $options = array();
      $_row = explode("\n", $_row);
      foreach ( $_row as $_line )
      {
        //if ( isset( $_GET['testing'] ) ) { echo $_line; }
        if ( preg_match('/FieldName: (.*)$/', $_line, $m) )
          $id = $m[ 1 ];
        if ( $id )
          if ( preg_match('/FieldStateOption: (.*)$/', $_line, $m) )
            $options[] = $m[ 1 ];
      }
      if ( $id and count( $options ) )
        $layout['checkboxes'][ $id ] = json_encode( $options );
    }
    //if ( isset( $_GET['testing'] ) ) {echo 'ok'; exit; }
  }
  catch (Exception $e)
  {
    $layout['error2'] = $e->getMessage();
  }

  try
  {
    if ( !file_exists($file) )
      throw new Exception('PDF file not found');

    $fields = array();

    global $wpdb;

    $results = $wpdb->get_results($q = "SELECT fields.* FROM {$wpdb->prefix}frm_fields fields INNER JOIN {$wpdb->prefix}frm_forms forms ON ( forms.id = fields.form_id AND forms.form_key = '$form_key') ORDER BY fields.field_order ASC ");

    foreach ( $results as $row )
    {
      $name = $row->name;
      $name = str_replace('&nbsp;', ' ', $name);
      $name = trim($name);
      if ( $name == 'Section' ) continue;
      if ( $name == 'End Section' ) continue;
      if ( $row->type == 'checkbox' )
      {
        $checkboxes = array();
        $_opts = @unserialize( $row->options );
        if ( $_opts and is_array( $_opts ) )
          foreach ( $_opts as $_opt )
          {
            //print_r($_opt); exit;
            if ( is_array( $_opt ) )
              $_opt = $_opt['value'];
            $checkboxes[] = $_opt;
          }
        //$layout['checkboxes'][ $row->id ] = $checkboxes;
      }
      if ( $row->type == 'divider' )
      {
        $data = $row->field_options;
        $data = @unserialize( $data );
        if ( ! $data['repeat'] )
          continue;
      }
      if ( $row->type == 'html' ) continue;
      //$fields[ $row->id ] = "[" . $row->id . "] " . $name;
      $fields[] = array( $row->id, "[" . $row->id . "] " . $name );
    }

    if ( !count($fields) )
      throw new Exception('Could not get web form IDs');
    $layout['fields1'] = $fields;
  }
  catch (Exception $e)
  {
    $layout['error1'] = $e->getMessage();
  }

  // Try to get data from master server
  // Master server processes the submitted PDF file and returns user-friendly field names and IDs using Java program

  if ( fpropdf_is_activated() and !defined('FPROPDF_IS_MASTER') )
  {

    try
    {

      if ( !file_exists($file) or !is_file($file) )
        throw new Exception('PDF file not found');

      $post = array(
        'salt'   => FPROPDF_SALT,
        'code'   => get_option('fpropdf_licence'),
        'form'   => $layout['form'],
      );
      $post['pdf_file'] = '@' . realpath( $file );
      $post['pdf_file_string'] = base64_encode( @file_get_contents( realpath( $file ) ) );

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => FPROPDF_SERVER . 'licence/data.php',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_TIMEOUT => 30,
      ));
      $data = curl_exec($curl);
      if ( !$data )
        throw new Exception('Server returned no data');
      $tmp = $data;
      $data = json_decode($data);
      if ( !$data )
        throw new Exception('Server unknown data: ' . $tmp);
      $keys = explode(' ', 'fields fields2');
      foreach ($keys as $key) 
      {
        if ( isset( $data->{$key} ) and $data->{$key} )
          $layout[ $key ] = $data->{$key};
      }

    } 
    catch ( Exception $e )
    {
      $layout['error_server'] = $e->getMessage();
    }
  }

  if ( defined('FPROPDF_IS_DATA_SUBMITTING') )
    @unlink( $currentFile );

  // End try to get data

  ob_get_clean();

  echo json_encode($layout);

  die();
}

function wpfx_killlayout()
{
  global $wpdb;

  // Convert to integers for security reasons
  $id = intval($_POST['wpfx_layout']) - 9;

  $query = "DELETE FROM `wp_fxlayouts` WHERE `ID` = $id";
  mysql_query($query);
  die();
}

function wpfx_duplayout()
{
  global $wpdb;

  // Convert to integers for security reasons
  $id = intval($_POST['wpfx_layout']) - 9;

  $layout = wpfx_readlayout( $id );
  extract($layout);
  $name .= ' (copy)';
  wpfx_writelayout($name, $file, $visible, $form, $index, $data, $formats, $add_att, $passwd);

  die();
}

// Enqueue admin styles and scripts
function wpfx_init()
{

  if ( $_GET['page'] != 'fpdf' )
    return;

  wp_register_script( 'wpfx-script', plugins_url('/res/script.js', __FILE__), array(), @filemtime( __DIR__ . '/res/script.js' ) );
  wp_register_style ( 'wpfx-style',  plugins_url('/res/style.css', __FILE__) );

  wp_enqueue_style ( 'wpfx-style'  );
  wp_enqueue_script( 'wpfx-script' );
}


// Add menu button
function wpfx_menu()
{
  global $wpfx_idd, $wpfx_url, $wpfx_dsc;
  add_menu_page($wpfx_dsc, 'Formidable PRO2PDF', 'administrator', $wpfx_idd, 'wpfx_admin', $wpfx_url.'/res/icon.png');
}


// Get layout visibility
function wpfx_getlayoutvisibility()
{
  global $wpdb;

  // Convert to integers for security reasons
  $id = intval( $_POST['wpfx_layout'] ) - 9;

  $query = "SELECT `visible`, `form` FROM `wp_fxlayouts` WHERE `ID` = '$id'";
  $result = mysql_query($query);
  $result = mysql_fetch_array($result);

  die(json_encode(array('visible' => $result['visible'], 'form' => $result['form'])));
}

// Change layout visibility
function wpfx_setlayoutvisibility()
{
  global $wpdb;

  // Convert to integers for security reasons
  $id = intval( $_POST['wpfx_layout'] ) - 9;
  $vs = intval( $_POST['wpfx_layout_visibility'] );

  $query = "UPDATE `wp_fxlayouts` SET `visible` = $vs WHERE `ID` = '$id'";
  die(mysql_query($query));
}

// Formidable forms are required for this
function wpfx_validate_formidable()
{
  global $frm_entry, $frm_form, $frm_field, $frmpro_is_installed;

  $errors = $frm_form->validate($_POST);
  $id = (int)FrmAppHelper::get_param('id');

  if( count($errors) > 0 )
  {
    $hide_preview = true;
    $frm_field_selection = FrmFieldsHelper::field_selection();
    $record = $frm_form->getOne( $id );
    $fields = $frm_field->getAll(array('fi.form_id' => $id), 'field_order');
    $values = FrmAppHelper::setup_edit_vars($record, 'forms', $fields, true);
    die($errors);
  }
}

function wpfx_fpropdf_remove_pdf()
{
  $file = $_POST['file'];
  $file = base64_decode( $file );
  // Check if filename does not contain slashes
  if ( preg_match('/\//', $file) )
    die('Wrong filename');
  @unlink( FPROPDF_FORMS_DIR . $file );
  die();
}

// Add admin init action
add_action( 'admin_init', 'wpfx_init');

// Register menu
add_action( 'admin_menu', 'wpfx_menu');

// Register AJAX requests
add_action('wp_ajax_wpfx_get_dataset', 'wpfx_getdataset');
add_action('wp_ajax_wpfx_get_layout',  'wpfx_peeklayout');
add_action('wp_ajax_wpfx_del_layout',  'wpfx_killlayout');
add_action('wp_ajax_wpfx_dup_layout',  'wpfx_duplayout');
add_action('wp_ajax_wpfx_validate_fd', 'wpfx_validate_formidable');
add_action('wp_ajax_fpropdf_remove_pdf', 'wpfx_fpropdf_remove_pdf');

// Form Visibility
add_action('wp_ajax_wpfx_getlayoutvis',  'wpfx_getlayoutvisibility');
add_action('wp_ajax_wpfx_setlayoutvis',  'wpfx_setlayoutvisibility');

// Generate PDF
add_action('wp_ajax_wpfx_generate',  'wpfx_generate_pdf');
add_action('wp_ajax_nopriv_wpfx_generate',  'wpfx_generate_pdf');

function wpfx_generate_pdf()
{
  include __DIR__ . '/download.php';
  exit;
}

// Generate Previews
add_action('wp_ajax_wpfx_preview_pdf',  'wpfx_preview_pdf');
add_action('wp_ajax_nopriv_wpfx_preview_pdf',  'wpfx_preview_pdf');

function wpfx_preview_pdf()
{
  if ( isset( $_GET['TB_iframe'] ) and $_GET['TB_iframe'] )
  {
    unset( $_GET['TB_iframe'] );
    $src = '?' . http_build_query( $_GET );
    echo '<img src="'.$src.'" />';
    exit;
  }
  include __DIR__ . '/preview.php';
  exit;
}

// Email Notifications

add_filter('frm_notification_attachment', 'add_my_attachment', 10, 3);
function add_my_attachment($attachments, $form, $args)
{
  $form_id = $form->id;
  $form_key = $form->form_key;
  $res = mysql_query('SELECT * FROM wp_fxlayouts WHERE form = \''.$form_id.'\' AND add_att = 1');
  if ( ! mysql_num_rows($res) ) return $attachments;

  $layout = mysql_fetch_array( $res );

  $layout = $layout['ID'];
  $dataset = $args['entry']->id;

  define('FPROPDF_NO_HEADERS', true);

  $__POST = $_POST;
  $__GET = $_GET;
  $__REQUEST = $_REQUEST;

  $_GET['form'] = $form_key;
  $_GET['layout'] = $layout + 9;
  $_GET['dataset'] = $dataset;



  if ( !defined( 'FPROPDF_CONTENT' ) )
  {
    ob_start();
    include __DIR__ . '/download.php';
    ob_get_clean();
  }

  $data = FPROPDF_CONTENT;
  //var_dump($data); exit;

  $_POST = $__POST;
  $_GET = $__GET;
  $_REQUEST = $__REQUEST;

  $filename = FPROPDF_FILENAME;

  if ( defined('FPROPDF_GEN_ERROR') )
  {
    $filename = "error.txt";
    if ( FPROPDF_GEN_ERROR )
      $data = FPROPDF_GEN_ERROR;
  }

  $tmp = __DIR__ . '/fields/' . $filename;
  file_put_contents( $tmp, $data );

  //echo $tmp; exit;

  $attachments[] = $tmp;
  return $attachments;
}

// Shortcode
include_once __DIR__ . '/formidable-shortcode.php';

function fpropdf_stripslashes($str)
{
        return stripslashes($str);
        return get_magic_quotes_gpc() ? stripslashes($str) : $str;
}
