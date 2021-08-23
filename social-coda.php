<?php 
/**
* Social Coda
*
* @package           SocialCoda
* @author            Eratags
* @copyright         2021 Eratags
* @license           GPL-2.0-or-later
*
* @wordpress-plugin
* Plugin Name:       SocialCoda
* Plugin URI:        http://plugins.eratags.com/social-coda
* Description:       SocialCoda let you see social counters and social feeds also automatically post all your content to several different social networks in one time and social share buttons, share to Facebook, WhatsApp, Messenger, Twitter, Instagram, Tumblr and much more.
* Version:           1.0.0
* Requires at least: 4.6
* Requires PHP:      5.6
* Author:            Eratags
* Author URI:        http://eratags.com
* Text Domain:       SOCIAL-CODA
* License:           
* License URI:        
*/


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit; 
}

// Define Constants 
define( 'SCODA_VERSION', '1.0' );
define( 'SCODA_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCODA_URL', plugin_dir_url( __FILE__ ) );

// Load Admin files 
require_once SCODA_PATH . 'admin/admin-load.php';

// Social Network Providers ( We didn't use any liberary we are created all of apis from scratch )
require_once SCODA_PATH . 'incs/providers/facebook.apis.php';
require_once SCODA_PATH . 'incs/providers/instagram.apis.php';
require_once SCODA_PATH . 'incs/providers/twitter.apis.php';
require_once SCODA_PATH . 'incs/providers/tumblr.apis.php';

// Actions & Filters Callbacks
require_once SCODA_PATH . 'incs/functions/filters.php';
require_once SCODA_PATH . 'incs/functions/actions.php';



/**
 * 
 * @uses Set the activation hook for a plugin.
 * 
 * @link https://developer.wordpress.org/reference/functions/register_activation_hook/
 * 
 * @param $file
 * @param $callback 
 * 
 */
if ( !function_exists( 'scoda_setup_plugin_callback' ) ) {

	function scoda_setup_plugin_callback() {
		
		global $wp_version;

		/**
		 * 
		 * PHP Software up to v5.4 is required 
		 * Version 5.4 is required for our scoda plugin 
		 * 
		 * SCODA_PATH . 'incs/functions/disable-php.php';
		 */ 
		if ( version_compare( phpversion(), '5.4', '<' ) ) {
			
			$message = sprintf( __( 'The SocialCoda plugin requires at least php version 5.4, the current version of your php is %1$s', 'SOCIAL-SCODA' ), phpversion() );
			wp_die( $message );

		}

		/**
		 * 
		 * Wordpress  Framework Version Detecter
		 * Version 4.7 is required for our scoda plugin 
		 * 
		 * SCODA_PATH . 'incs/functions/disable.wp.framework.php';
		 */ 
		if ( version_compare( $wp_version, '4.7', '<' ) ) {

			$message = sprintf( __( 'The SocialCoda plugin requires at least wordpress software 4.7, the current version of your wordpress software is %1$s', 'SOCIAL-SCODA' ), $wp_version );
			wp_die( $message ); 

		}

		/**
		 * 
		 * Setup Networks once the plugin activated
		 */ 
		ScodaAdminUI::get_instance()->setup_networks();

	}

	register_activation_hook( __FILE__, 'scoda_setup_plugin_callback' );

}
