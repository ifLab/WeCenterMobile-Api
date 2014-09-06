<?php
include "./lib/config.php";
include "./lib/class.MySQL.php";
include "./lib/functions.inc.php";


$data = file_get_contents('../static/js/areas.js');
exit($data);

//if( !is_array( $ret ) ) exit( return_error( 'Select Failed' ) );
//exit( json_encode( array( 'error'=>'', 'data'=>$ret ) ) );
