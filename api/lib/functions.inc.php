<?php
function exit_error( $err ){
	exit( json_encode( array(
						'rsm'=>null,
						'errno'=>-1,
						'err'=>$err
					)));
}

function exit_success( $info ){
	exit( json_encode( array(
						'rsm'=>$info,
						'errno'=>1,
						'err'=>null
					)));
}


function return_error( $err ){
	return json_encode( array( 'error'=>$err ) );
}
/**
 * 生成密码种子
 * 
 * @param  integer
 * @return string
 */
function fetch_salt($length = 4)
{
	$salt = '';
	for ($i = 0; $i < $length; $i++)
	{
		$salt .= chr(rand(97, 122));
	}
	
	return $salt;
}

/**
 * 根据 salt 混淆密码
 *
 * @param  string
 * @param  string
 * @return string
 */
function compile_password($password, $salt)
{
	// md5 password...
	if (strlen($password) == 32)
	{
		return md5($password . $salt);
	}
	
	$password = md5(md5($password) . $salt);
	
	return $password;
}
