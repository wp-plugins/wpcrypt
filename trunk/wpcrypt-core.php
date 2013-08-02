<?php	
class WpCryptCore
{
	public static $WpCrypt_Encryption;
	public static $WpCrypt_Methods;
	public static $WPCRYPTKEY_ = '_wpCryptKey';
	public $_WPCRYPTKEY = '<?php_12d69db805c1ho3ra19c3720699';
	

	public function init() {
		self::setEncryption(0);
		update_user_meta( $user_id, 'wpcrypt_method', '0' );
		
		// define wpCryptKey
		if( !get_option( self::$WPCRYPTKEY_ )) :
			add_option( self::$WPCRYPTKEY_, _WPCRYPTKEY, '', 'yes' );
		endif;
	}

	
	
	public function add_extra_profile_fields() {
		global $wpdb;
		
		// define wpCryptKey
		if( !get_option( self::$WPCRYPTKEY_ )) :
			add_option( self::$WPCRYPTKEY_, _WPCRYPTKEY, '', 'yes' );
		endif;


		echo "<a name='crypt'></a><h3 id='wordpress-seo'>WpCrypt Options</h3>";

		$selectMethod  = '<table class="form-table"><tbody><tr><th><label for="wpcrypt_method">1. Choose your password encryption method:</label></th>';
		$selectMethod .= '<td><select name="wpcrypt_method" id="wpcrypt_method" title="Please Select ...">';
			$selectMethod .= '<option value="">Please Select ... </option>';
			$selectMethod .= '<option value="0">Default</option>';
			$selectMethod .= '<option value="1">SHA1</option>';
			$selectMethod .= '<option value="2">SHA2</option>';
			$selectMethod .= '<option value="3">AES128</option>';
			$selectMethod .= '<option value="4">AES256</option>';
			$selectMethod .= '<option value="5">* AES Fuzzy Hash</option>';
		$selectMethod .= '</select>';

		echo $selectMethod;
		echo '</td>';
		echo '</tr></tbody></table>';

		$pass  = '<table class="form-table"><tbody><tr><th><label for="cryptPass">2. Type your password:</label></th>';
		$pass .= '<td><input type="text" name="cryptPass" id="cryptPass" size="20" />';
		
		$userID = get_current_user_id();
		$query = "SELECT user_pass FROM $wpdb->users WHERE ID = %d";
		$currentHash = $wpdb->get_results( $wpdb->prepare($query,$userID) );
		echo $pass;


		echo '<br /> You need to type your password to change the hash stored in the DB (Be careful).<br>';
		echo '<br /> * AES Fuzzy Hash: Mix two AES methods in one "fuzzy" password hash.<br>';
	
		echo '<br /> Current method: '. self::getCurrentMethod();
		echo '<br /> Current hash: '. $currentHash[0]->user_pass;
		echo '</td>';

		echo "<td>";
		echo '';
		echo "</td>";
		echo '</tr></tbody></table>';

		$selectMethod  = '<table class="form-table"><tbody><tr><th><label for="AES_key"><strong>Advanced</strong> - AES security key:</label></th>';
		$selectMethod .= '<td><input type="text" value="'.get_option(self::$WPCRYPTKEY_).'" name="AES_key" id="AES_key" maxlength="32" />';
		echo $selectMethod;
		echo '</td>';
		echo '</tr></tbody></table>';
	}

	function save_extra_user_profile_fields( $user_id ) {
		global $wpdb;

		if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }
		


		if($_POST['wpcrypt_method'] != "") {
			WpCryptCore::setEncryption($_POST['wpcrypt_method']);
			update_user_meta( $user_id, 'wpcrypt_method', $_POST['wpcrypt_method'] );
		}

		if($_POST['cryptPass'] != "") {
			$hash = WpCryptCore::handleHash($_POST['cryptPass']);
			$wpdb->update($wpdb->users, array('user_pass' => $hash), array('ID' => $user_id) );

			wp_cache_delete($user_id, 'users');
		}


		if($_POST['AES_key'] != "") {

			update_option(self::$WPCRYPTKEY_,$_POST['AES_key']);

		}
	}


	public function handleHash($password) {
		// wpCrypt
		$method = WpCryptCore::getCurrentMethod();
		if($method == 'SHA1') :
			$hash = sha1($password);
		elseif($method == 'SHA256'):
			$hash = hash('sha256', $password);
		elseif($method == 'AES128') :
			$hash = AESCrypt::encrypt128($password);
		elseif($method == 'AES256') :
			$hash = AESCrypt::encrypt256($password);
		elseif($method == 'AES FuzzyHash') :
			$hash = AESCrypt::getFuzzyHash($password);
		else:
			$hash = wp_hash_password($password);
		endif;

		return $hash;
	}


	public function getCurrentMethod() {
		$WpCrypt_Methods = array(
			0 => 'Default',
			1 => 'SHA1',
			2 => 'SHA256',
			3 => 'AES128',
			4 => 'AES256',
			5 => 'AES FuzzyHash'
		);
		$currentMethod = get_user_meta(get_current_user_id(), 'wpcrypt_method', true);
		return $WpCrypt_Methods[$currentMethod];
	}

	public function setEncryption($method) {
		$WpCrypt_Encryption = $WpCrypt_Methods[$method];
	}

	/* is_bcrypt METHOD - Validate BCrypt
		- Return true or false
	 */
	public function is_bcrypt($str) {
		return (bool) preg_match('/^\$[0-9a-z]{2}\$[0-9]{2}\$[A-Za-z0-9\.\/]{53}$/i', $str);
	}

	 /* is_sha1 METHOD - Validate SHA1
		- Return true or false
	 */
	public function is_sha1($str) {
	    return (bool) preg_match('/^[0-9a-f]{40}$/i', $str);
	}

	/* is_sha256 METHOD - Validate SHA256
		- Return true or false
	 */
	public function is_sha256($str) {
	    return (bool) preg_match('/^[0-9a-f]{64}$/i', $str);
	}

	 /* is_md5 METHOD - Validate md5
		- Return true or false
	 */
	public function is_md5($str) {
		return (bool) preg_match('/^[0-9a-f]{32}$/i', $str);
	}


	public function is_AES($str) {
		return (bool) preg_match('/[a-z0-9\/\+]+.+[=]$/i', $str);
	}

	public function is_fuzzyAES($str) {
		return (bool) preg_match('/[a-z0-9\/\+]+.+[$]$/i', $str);
		
	}



	 /* CUSTOM PASSWORD METHOD - Filter for wordpress check_password 
		@return user
	 */
	public function custom_check_password($crypt, $password, $hash) {
		global $wp_hasher;

		// Hash is AES FuzzyHash...
		if ( self::is_fuzzyAES($hash) ) {
			$AES = new AESCrypt(get_option(self::$WPCRYPTKEY_));
			$check = ( $hash == AESCrypt::getFuzzyHash($password) );
			if ( $check && $user_id ) {
				// Rehash using new hash.
				wp_set_password($password, $user_id);
				$hash = wp_hash_password($password);
			}

			return apply_filters('checkme', $check, $password, $hash, $user_id);
		}

		// Hash is AES...
		if ( self::is_AES($hash) ) {
			$AES = new AESCrypt(get_option(self::$WPCRYPTKEY_));
			$check = ( $hash == AESCrypt::encrypt128($password) || $hash == AESCrypt::encrypt256($password) );
			if ( $check && $user_id ) {
				// Rehash using new hash.
				wp_set_password($password, $user_id);
				$hash = wp_hash_password($password);
			}

			return apply_filters('checkme', $check, $password, $hash, $user_id);
		}

		// Hash is sha256...
		if ( self::is_sha256($hash) ) {
			$check = ( $hash == hash('sha256', $password) );
			if ( $check && $user_id ) {
				// Rehash using new hash.
				wp_set_password($password, $user_id);
				$hash = wp_hash_password($password);
			}

			return apply_filters('checkme', $check, $password, $hash, $user_id);
		}

		// Hash is sha1...
		if ( self::is_sha1($hash) ) {
			$check = ( $hash == sha1($password) );
			if ( $check && $user_id ) {
				// Rehash using new hash.
				wp_set_password($password, $user_id);
				$hash = wp_hash_password($password);
			}

			return apply_filters('checkme', $check, $password, $hash, $user_id);
		}
		
		// Hash is md5...
		if ( self::is_md5($hash) ) {
			$check = ( $hash == md5($password) );
			if ( $check && $user_id ) {
				// Rehash using new hash.
				wp_set_password($password, $user_id);
				$hash = wp_hash_password($password);
			}

			return apply_filters('checkme', $check, $password, $hash, $user_id);
		}

		/* If the stored hash is longer than an MD5, presume the
		// new style phpass portable hash.
		
		*/
		if ( empty($wp_hasher) ) {
			require_once( ABSPATH . 'wp-includes/class-phpass.php');
			// By default, use the portable hash from phpass
			$wp_hasher = new PasswordHash(8, TRUE);
		}

		$check = $wp_hasher->CheckPassword($password, $hash);

		return apply_filters('checkme', $check, $password, $hash, $user_id);

	}

	public function admin_notice() {
		
		$notice = '<div class="clear"></div><div class="updated" id="message">';
		$notice .= '<p><strong>WpCrypt is running! Please, change your encryption method <a href="./profile.php#crypt">here</a>.</strong></p>';
		$notice .= '</div>';
		echo $notice;
	}



} // end class
?>