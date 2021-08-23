<?php 

/**
 * @uses Prints admin screen notices.
 * Display notices with required php software version 
 * 
 * @return string render html markup ! 
 */
if ( !function_exists( 'scoda_admin_notices_callback' ) ) {
	
	function scoda_admin_notices_callback() {
		
		$error_version = array(
			'<div class=\'notice notice-error\'>',
				'<p>',
					sprintf( __( 'The SocialCoda plugin requires at least php version 5.4, the current version of your php is %1$s', 'SOCIAL-SCODA' ), phpversion() ),
				'</p>',
			'</div>'
		);

		echo implode( "\n", $error_version );

	}

	add_action( 'admin_notices', 'scoda_admin_notices_callback' );

}
