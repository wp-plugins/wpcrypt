<?php
/*
Plugin Name: WpCrypt
Plugin URI: http://emancipa.net
Description: Allow users to change password encryption method to SHA1, SHA2, AES Rijndael and more...
Version: 0.1
Author: Emancipa | Comunicação Digital
Author Email: dev@emancipa.net
License: GPL

  Copyright 2011  (dev@emancipa.net)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/

if ( !defined( '_WPCRYPTKEY' ) )
	define('_WPCRYPTKEY','<?php_12d69db3ef38d99<?=');
if ( !defined( 'WPCRYPT_URL' ) )
	define( 'WPCRYPT_URL', plugin_dir_url( __FILE__ ) );
if ( !defined( 'WPCRYPT_PATH' ) )
	define( 'WPCRYPT_PATH', plugin_dir_path( __FILE__ ) );
if ( !defined( 'WPCRYPT_BASENAME' ) )
	define( 'WPCRYPT_BASENAME', plugin_basename( __FILE__ ) );

class WpCrypt {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'WpCrypt';
	const slug = 'wpcrypt';
	
	/**
	 * Constructor
	 */
	function __construct() {
		//register an activation hook for the plugin
		register_activation_hook( __FILE__, array( &$this, 'install_wpcrypt' ) );

		//Hook up to the init action
		add_action( 'init', array( &$this, 'init_wpcrypt' ) );
	}
  
	/**
	 * Runs when the plugin is activated
	 */  
	function install_wpcrypt() {
		// do not generate any output here
		WpCryptCore::init();
	}
  
	/**
	 * Runs when the plugin is initialized
	 */
	function init_wpcrypt() {
		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		// Load JavaScript and stylesheets
		$this->register_scripts_and_styles();

	
		if ( is_admin() ) {
			//this will run when in the WordPress admin
			//echo '<div id="wp_crypt_msg">WpCrypt is running! Please choose your encryption method <a href="#" id="choose_method_here">here</a>.</div>';
		}

		/*
		 * TODO: Define custom functionality for your plugin here
		 *
		 * For more information: 
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		
		add_action( 'admin_notices', array('WpCryptCore', 'admin_notice') );

		add_action('show_user_profile', array('WpCryptCore', 'add_extra_profile_fields'));
		add_action('edit_user_profile', array('WpCryptCore', 'add_extra_profile_fields'));

		add_action( 'personal_options_update', array('WpCryptCore', 'save_extra_user_profile_fields') );
		add_action( 'edit_user_profile_update', array('WpCryptCore', 'save_extra_user_profile_fields') );

		
		add_filter('hash_password', array('WpCryptCore', 'custom_hash_password'), 10, 4);		
		add_filter('check_password', array('WpCryptCore', 'custom_check_password'), 10, 4);

	}

	function action_callback_method_name() {
		// TODO define your action method here
	}

	function filter_callback_method_name() {
		// TODO define your filter method here
	}
  
	/**
	 * Registers and enqueues stylesheets for the administration panel and the
	 * public facing site.
	 */
	private function register_scripts_and_styles() {
		if ( is_admin() ) {
			$this->load_file( self::slug . '-admin-script', '/js/admin.js', true );
			$this->load_file( self::slug . '-admin-style', '/css/admin.css' );
		} else {

		} // end if/else
	} // end register_scripts_and_styles
	
	/**
	 * Helper function for registering and enqueueing scripts and styles.
	 *
	 * @name	The 	ID to register with WordPress
	 * @file_path		The path to the actual file
	 * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
	 */
	private function load_file( $name, $file_path, $is_script = false ) {

		$url = plugins_url($file_path, __FILE__);
		$file = plugin_dir_path(__FILE__) . $file_path;

		if( file_exists( $file ) ) {
			if( $is_script ) {
				wp_register_script( $name, $url, array('jquery') ); //depends on jquery
				wp_enqueue_script( $name );
			} else {
				wp_register_style( $name, $url );
				wp_enqueue_style( $name );
			} // end if
		} // end if

	} // end load_file
  
} // end class
new WpCrypt();


require WPCRYPT_PATH.'wpcrypt-core.php';
require WPCRYPT_PATH.'wpcrypt-aes.php';
?>