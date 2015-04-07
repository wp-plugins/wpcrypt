<?php
class AESCrypt
{
	private $key,$iv_size,$iv;
 
	function __construct(){
		if (! function_exists ("mcrypt_encrypt")) {
			die ("To use AES, you need to have the mcrypt php module installed.");
		}
		$this->iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$this->iv = mcrypt_create_iv($this->iv_size, MCRYPT_RAND);
	}
 
	public function encrypt128($string){
		$AES = new AESCrypt( get_option(WpCryptCore::$WPCRYPTKEY_) );
		$string=trim($string);
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, get_option(WpCryptCore::$WPCRYPTKEY_), $string, MCRYPT_MODE_ECB, $AES->iv));
	}

	public function encrypt256($string){
		$AES = new AESCrypt( get_option(WpCryptCore::$WPCRYPTKEY_) );
		$string=trim($string);
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,get_option(WpCryptCore::$WPCRYPTKEY_), $string, MCRYPT_MODE_ECB, $AES->iv));
	}
}
?>