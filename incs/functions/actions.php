<?php 




/**
 * Storing Facebook Application Keys and Sanitization
 *
 * @package Facebook Apis 
 *
 * @param string $app_key
 * @param string $key_secret 
 *
 */
if ( !function_exists( 'scoda_facebook_store_app_keys' ) ) {
	
	function scoda_facebook_store_app_keys( $app_key, $key_secret ) {

		$facebook = ScodaFacebook::get_instance();
		$stored = $facebook->set_option( $facebook->social_name, array(
			'app_id' 	=> sanitize_text_field( $app_key ),
			'secret_id' => sanitize_text_field( $key_secret )
		));

		//$facebook->set_notification( $stored );

	}

	add_action( 'eratags/scoda/facebook/store_app_keys', 'scoda_facebook_store_app_keys', 10, 2 );

}

/**
 * Storing Twitter Application Keys and Sanitization
 *
 * @package Twitter Apis 
 *
 * @param string $app_key
 * @param string $key_secret 
 *
 */
if ( !function_exists( 'scoda_twitter_store_app_keys' ) ) {
	
	function scoda_twitter_store_app_keys( $app_key, $key_secret ) {

		$twitter = ScodaTwitter::get_instance();
		$twitter->set_option( $twitter->social_name, array(
			'consumer_key' 		  => sanitize_text_field( $app_key ),
			'secret_consumer_key' => sanitize_text_field( $key_secret )
		));

	}

	add_action( 'eratags/scoda/twitter/store_app_keys', 'scoda_twitter_store_app_keys', 10, 2 );

}

/**
 * Storing Tumblr Application Keys and Sanitization
 *
 * @package Tumblr Apis 
 *
 * @param string $app_key
 * @param string $key_secret 
 * @param string $token
 * @param string $token_secret
 *
 */
if ( !function_exists( 'scoda_tumblr_store_app_keys' ) ) {
	
	function scoda_tumblr_store_app_keys( $app_key, $key_secret, $token, $token_secret ) {

		$tumblr = ScodaTumblr::get_instance();
		$tumblr->set_option( $tumblr->social_name, array(
			'consumer_key' 		  => sanitize_text_field( $app_key ),
			'consumer_secret' 	  => sanitize_text_field( $key_secret ),
			'access_token' 		  => sanitize_text_field( $token ),
			'secret_access_token' => sanitize_text_field( $token_secret )
		));
		
	}

	add_action( 'eratags/scoda/tumblr/store_app_keys', 'scoda_tumblr_store_app_keys', 10, 4 );

}


