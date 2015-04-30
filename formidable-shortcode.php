<?php

if ( !defined('ABSPATH') )
  exit;

// Add backend styles
function formidable_shortcode_wp_admin_style( $hook )
{
  if ( basename( $_SERVER['PHP_SELF'] ) != 'admin.php' )
    return;
  if ( $_GET['page'] != 'fpdf' )
    return;
  wp_register_style( 'formidable_shortcode_css', plugin_dir_url( __FILE__ ) . 'css/admin.css', false, filemtime( __DIR__ . '/css/admin.css' ) );
  wp_enqueue_style( 'formidable_shortcode_css' );
  wp_enqueue_script( 'formidable_shortcode_js', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), filemtime( __DIR__ . '/js/admin.js' ) );
}
add_action( 'admin_enqueue_scripts', 'formidable_shortcode_wp_admin_style' );

// Add frontend styles
function formidable_shortcode_name_scripts()
{
  wp_register_style( 'formidable_shortcode_css', plugin_dir_url( __FILE__ ) . 'css/style.css', false, filemtime( __DIR__ . '/css/style.css' ) );
  wp_enqueue_style( 'formidable_shortcode_css' );
}

add_action( 'wp_enqueue_scripts', 'formidable_shortcode_name_scripts' );

// Add download shortcode
function formidable_shortcode_download($atts = array())
{
  $text = $atts['title'];
  if ( ! $text )
    $text = 'Download'; 
  $class = $atts['class'];
  $class .= ' readmore formidable-download';
  if ( $atts['download'] )
    $class .= ' formidable-download-auto';
  $iframe = 'iframe' . time() . rand(0,1000000);
  $args = $atts;
  unset($args['class']); 
  unset($args['title']);
  $href = admin_url('admin-ajax.php') . '?action=wpfx_generate&' . http_build_query( $args );
  $html = '<a href="' . $href . '" class="' . $class . '" target="_blank">' . $text . '</a>';
  if ( $atts['download'] )
    $html .= '<iframe class="formidable-download-iframe" id="' . $iframe . '" src="' . $href . '"></iframe>';
  return do_shortcode($html);
}
add_shortcode('formidable-download', 'formidable_shortcode_download');

// Add template download shortcode
function formidable_shortcode_download_in_list($atts = array())
{
  $atts['class'] = 'icon-button download-icon';
  $atts['dataset'] = $_GET['entry'];
  $atts['title'] .= ' <span class="et-icon"></span>';
  return formidable_shortcode_download($atts);
}
add_shortcode('formidable-download-in-list', 'formidable_shortcode_download_in_list');

