<?php

if ( !function_exists('fpropdf_custom_capitalize') )
{
  function fpropdf_custom_capitalize( $m )
  {
    $s = $m[1];
    $s = mb_strtoupper($s);
    return $s;
  }
}

class FDFMaker
{
  function makeInflatablesApp($data = false, $remote = false)
  {
    // let's fill default fields
    $defaults = array(
      109 => '/No', 105 => '/No', 104 => '/No', 103 => '/No', 3660 => '/', 3650 => '/Yes', 98 => '/',
      97 => '/', 96 => '/', 399 => '1', 392 => 'S', 391 => 'Rentals', 390 => '1', 88 => '100%', 87 => '0',
      85 => '/', 84 => '/Tenant', 83 => '/Inside', 383 => '5,000', 382 => '300,000', 381 => '1,000,000',
      380 => '1,000,000', 77 => '1', 76 => '1', 379 => '2,000,000', 378 => '2,000,000', 377 => '/Yes',
      376 => '/', 373 => '0', 372 => '/Yes', 371 => '0', 370 => '/Yes', 67 => '/', 66 => '/', 65 => '/',
      64 => '/', 63 => '/', 62 => '/', 61 => '/', 567 => '/', 566 => 'None', 565 => '/No', 564 => '/No',
      401 => 'U', 563 => '/No', 400 => 'Inflatables', 562 => '/No', 561 => '/No', 367 => 'None',
      560 => '/No', 366 => '/', 365 => '/', 559 => '/No', 558 => '/No', 557 => '/No', 556 => '/No',
      555 => '/No', 554 => '/No', 553 => '/No', 552 => '/No', 358 => '/', 551 => '/No', 357 => '/',
      550 => '/No', 350 => '/', 48 => 'Y', 45 => '/AgencyBill', 549 => '/No', 548 => '/No', 547 => '/No',
      546 => '/No', 349 => '/Yes', 34 => '/Quote', 33 => '/', 32 => '/', 31 => '/', 136 => '/Yes',
      'busops' => 'Inflatable Rentals with \(\) Inflatables', 292 => '/', 291 => '/', 25 => '/Yes',
      3690 => '/Yes', 22 => '/', 'Waiver' => 'Yes', 9 => '619-423-7172', 4 => 'Imperial Beach, CA 91932',
      3 => '1233 Palm Avenue', 2 => 'Ideal Choice Insurance Agency, Inc.', 3680 => '/', 10 => '619-374-2317',
      115 => 'None', 114 => '/No', 113 => '/No', 112 => '/No', 111 => '/No', 110 => '/No', 3670 => '/');

    if( !is_array($data) )
      $data = array();

    foreach ( $defaults as $k => $v )
    {
      $found = false;
      foreach ( $data as $key => $values )
        if ( ( $values[ 0 ] == $k ) and $values[1] )
          $found = true;
      if ( ! $found )
        $data[] = array( $k, $v );

    }

    // format filename
    $file = $remote ? $remote : 'InflatableApp.pdf';

    // create FDF
    return ($this->makeFDF($data, $file));
  }

  function makeBusinessQuote($data = false, $remote = false)
  {
    // defaults for 125&126
    $defaults = array(
      109 => '/No', 108 => '/No', 107 => '/No', 106 => '/No', 105 => '/No', 104 => '/No', 103 => '/No',
      3660 => '/', 3650 => '/Yes', 98 => '/', 97 => '/', 96 => '/', 399 => '1', 392 => 'S', 391 => 'Rentals',
      390 => '1', 88 => '100%', 87 => '0', 85 => '/', 84 => '/Tenant', 83 => '/Inside', 383 => '5,000',
      382 => '300,000', 381 => '1,000,000', 380 => '1,000,000', 77 => '1', 76 => '1', 379 => '2,000,000',
      378 => '2,000,000', 377 => '/Yes', 376 => '/', 373 => '0', 372 => '/Yes', 371 => '0', 370 => '/Yes',
      67 => '/', 66 => '/', 65 => '/', 64 => '/', 63 => '/', 62 => '/', 61 => '/', 567 => '/', 566 => 'None',
      565 => '/No', 564 => '/No', 401 => 'U', 563 => '/No', 400 => 'Inflatables', 562 => '/No', 561 => '/No',
      367 => 'None', 560 => '/No', 366 => '/', 365 => '/', 559 => '/No', 558 => '/No', 557 => '/No', 556 => '/No',
      555 => '/No', 554 => '/No', 553 => '/No', 552 => '/No', 358 => '/', 551 => '/No', 357 => '/', 550 => '/No',
      350 => '/', 48 => 'Y', 45 => '/AgencyBill', 549 => '/No', 548 => '/No', 547 => '/No', 546 => '/No', 349 => '/Yes',
      34 => '/Quote', 33 => '/', 32 => '/', 31 => '/', 136 => '/Yes', 292 => '/', 291 => '/', 25 => '/Yes',
      3690 => '/Yes', 22 => '/', 9 => '619-423-7172', 4 => 'Imperial Beach, CA 91932', 3 => '1233 Palm Avenue',
      2 => 'Ideal Choice Insurance Agency, Inc.', 3680 => '/', 10 => '619-374-2317', 115 => 'None', 114 => '/No',
      113 => '/No', 112 => '/No', 111 => '/No', 110 => '/No', 3670 => '/');


    if( !is_array($data) )
      $data = array();

    foreach ( $defaults as $k => $v )
    {
      $found = false;
      foreach ( $data as $key => $values )
        if ( ( $values[ 0 ] == $k ) and $values[1] )
          $found = true;
      if ( ! $found )
        $data[] = array( $k, $v );

    }

    // format filename
    $file = $remote ? $remote : 'BusinessQuote.pdf';

    // create FDF
    return ($this->makeFDF($defaults, $file));
  }

  // create FDF from array
  function makeFDF($data, $file)
  {
    $cr   = chr(hexdec('0a')); // use carriage return explicitly

    // make header
    $fdf  = '%FDF-1.2'.$cr.'%'.chr(hexdec('e2')).chr(hexdec('e3')).chr(hexdec('cf')).chr(hexdec('d3')).$cr;
    $fdf .= '1 0 obj '.$cr.'<<'.$cr.'/FDF '.$cr.'<<'.$cr.'/Fields [';

    //if ( isset( $_GET['testing'] ) ) { print_r($data); exit; }

    global $currentLayout;
    if ( $currentLayout )
    {

      $formats = $currentLayout['formats'];
      //print_r($formats); exit;
      foreach ( $formats as $_format )
      {

        $key = $_format[ 0 ];
        $format = $_format[ 1 ];
        //print_r($formats); exit;

        //$foundFormat = false;

        foreach ( $data as $dataKey => $values )
        {

          if ( $values[ 0 ] != $key )
            continue;

          $v = $values[ 1 ];

          switch ( $format )
          {

            case 'signature';
              global $fpropdfSignatures;
              if ( !$fpropdfSignatures )
                $fpropdfSignatures = array();
              $fpropdfSignatures[] = array(
                'data' => $v,
                'field' => $values[ 0 ],
              );
              $v = '';
              break;

            case 'curDate':
              $v = date('m/d/y');
              break;

            case 'curDate2':
              $v = date('d/m/Y');
              break;

            case 'curDate3':
              $v = date('m/d/Y');
              break;

            case 'curDate4':
              $v = date('Y/m/d');
              break;

            case 'repeatable':
              $v = @unserialize($v);
              $vals = array();
              try
              {
                if ( !$v or !is_array($v) )
                  throw new Exception('Not an array');

                foreach ( $v as $id )
                {

                  $string = $_format[ 2 ];

                  global $wpdb;
                  $query  = "SELECT * FROM `".$wpdb->prefix."frm_item_metas` WHERE `item_id` = " . intval( $id );
                  $result = mysql_query($query);
                  if ( ! $query ) 
                    die( 'Mysql error: ' . $query . ' : ' . mysql_error() );
                  while($row = mysql_fetch_array($result))
                  {
                    //$data [ $row['id'] ] = $row['value'];
                    $key = $row['field_id'];
                    $val = $row['meta_value'];
                    $string = str_replace('['.$key.']', $val, $string);
                  }

                  $vals[] = $string;
                }
              }
              catch (Exception $e)                
              {
              }
              $v = implode('', $vals);
              break;

            case 'tel':
              $v2 = preg_replace('/[^0-9]+/', '', $v);
              $v2 = intval($v2);
              $v2 = sprintf("%010d", $v2);
              if ( preg_match( '/(\d{3})(\d{3})(\d{4})$/', $v2,  $matches ) )
                $v = $matches[1] . '-' .$matches[2] . '-' . $matches[3];
              break;

            case 'date':
              if ( preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $v, $m) )
                $v = $m[2] . '/' . $m[3] . '/' . substr($m[1], 2, 4);
              break;

            case 'date2':
              if ( preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $v, $m) )
                $v = $m[3] . '/' . $m[2] . '/' . $m[1];
              break;

            case 'date3':
              if ( preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $v, $m) )
                $v = $m[2] . '/' . $m[3] . '/' . $m[1];
              break;


            case 'date4':
              if ( preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $v, $m) )
                $v = $m[1] . '/' . $m[2] . '/' . $m[3];
              break;


            case 'returnToComma':
              $v = str_replace("\n", ", ", $v);
              $v = preg_replace('/ +/', ' ', $v);
              $v = preg_replace('/\, +$/', '', $v);
              break;

            case 'capitalize':
              $v = preg_replace_callback('/(^[a-z]| [a-z])/u', 'fpropdf_custom_capitalize', $v);
              break;


            default:

              if ( is_array($v) )
              {
                $_opts = @json_decode( $_format[ 3 ] );
                if ( $_opts and is_array( $_opts ) and count($_opts) )
                {
                  $data[ $dataKey ][ 1 ] = true;
                  foreach( $v as $_k => $_v )
                    if ( !in_array( $_v, $_opts ) )
                      unset( $v[ $_k ] );
                }
              }
              else
              {
                $v = str_replace("\r", "", $v);
              }
              break;
   
          }

          $data[ $dataKey ][ 1 ] = $v;

        }

        //if ( ! $formatFound )
          //if ( $format == 'curDate' )


      }

      $currentLayout = false;
    }

    // generate fields
    foreach($data as $values)
    {
      $index = $values[ 0 ];
      $value = $values[ 1 ];

      if ( !is_array($value) )
        $_values = array( $value );
      else
      {
        if ( $values[ 2 ] )
          $_values = $value;
        else
          $_values = array( implode(', ', $value) );
      }

      foreach ( $_values as $value )
      {

        $value = stripslashes( $value );
        if ( function_exists('mb_convert_encoding') )
          $value = mb_convert_encoding( $value, 'UTF-16BE' );
        elseif ( function_exists('iconv') )
          $value = iconv( 'UTF-8', 'UTF-16BE', $value );
        $value = chr(0xfe) . chr(0xff) . str_replace(array('\\', '(', ')'), array('\\\\', '\(', '\)'), $value);

        $fdf .= $cr.'<<';
        $fdf .= $cr.'/V ';

        if($value[0] == '/')
          $fdf .= $value;
        else $fdf .= '('.$value.')';

        $fdf .= $cr.'/T ('.$index.')';
        $fdf .= $cr.'>> ';

      }

    }

    // make footer
    $fdf .= ']'.$cr.'/ID [ <'.md5(time()).'>'.$cr.'] >> '.$cr.'>> '.$cr.' endobj '.$cr.'trailer'.$cr.$cr.'<<'.$cr.'/Root 1 0 R'.$cr.'>>'.$cr.'%%EOF'.$cr;
    //echo $fdf; exit;

    return $fdf;
  }
}


