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

	public function getFuzzyHash($str) {
			$AES = new AESCrypt(get_option(WpCryptCore::$WPCRYPTKEY_));
			$hash = $AES->encrypt256($str);
			$hash2 = $AES->encrypt128($str);	
			$fuzzy = $AES->encrypt256($hash.$hash2);
						
			$atan = atan(preg_replace("/\/|[=a-z+]/i", "", $hash.$hash2.$fuzzy));
			$ex = pow($atan, exp(pi()));
			$um = $atan.$ex;
			$umm = pow($um, exp($atan));
			$ummm = pow($umm, exp($ex));
		
			$tringParts = str_split($hash);
			$tringParts2 = str_split($hash2);
			$tringParts3 = str_split($fuzzy);

			$fuz = str_split($um);
			$fuzz = str_split($umm);
			$fuzzy = str_split($ummm);
			
			$xy = array();
			foreach($tringParts as $k => $v) {
			  $xy[] = array_shift($fuzzy);
			  $xy[] = array_shift($tringParts2);
			  $xy[] = array_shift($fuz);
			  $xy[] = array_shift($tringParts3);
			  $xy[] = array_shift($fuzz);
			  $xy[] = array_shift($tringParts); 
			}

			$result = implode('', $xy);

			return substr($result,0,62).'$$';
	}
}
?>