<?php 

/**
 * @uses Prints admin screen notices.
 * Display notices with required wordpress framework version 
 * 
 * @return string render html markup ! 
 */
if ( !function_exists( 'scoda_admin_notices_wp_version_callback' ) ) {
	
	function scoda_admin_notices_wp_version_callback() {
		
		global $wp_version;

		$error_version = array(
			'<div class=\'notice notice-error\'>',
				'<p>',
					sprintf( __( 'The SocialCoda plugin requires at least wordpress software 4.7, the current version of your wordpress software is %1$s', 'SOCIAL-SCODA' ), $wp_version ),
				'</p>',
			'</div>'
		);

		echo implode( "\n", $error_version );

	}

	add_action( 'admin_notices', 'scoda_admin_notices_wp_version_callback' );

}
