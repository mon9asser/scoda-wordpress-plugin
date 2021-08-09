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
* Description:       SocialCoda let you see social counters and social feeds also automatically post all your content to several different social networks in one time as you like.
* Version:           1.0.0
* Requires at least: 4.6
* Requires PHP:      5.6
* Author:            Eratags
* Author URI:        http://eratags.com
* Text Domain:       social-coda
* License:           
* License URI:        
*/


// Exit if accessed directly
/*
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}
*/
    

// Define Constants 
define( 'SCODA_VERSION', '1.0' );
define( 'SCODA_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCODA_URL', plugin_dir_url( __FILE__ ) );


// Filters and Actions 
require_once SCODA_PATH . 'incs/functions/filters.php';

// Our Framework Files 
require_once SCODA_PATH . 'incs/framework/tags.filters.php'; 
require_once SCODA_PATH . 'incs/framework/tags.class.helper.php';

// Social Network Providers 
require_once SCODA_PATH . 'incs/providers/facebook.apis.php';
require_once SCODA_PATH . 'incs/providers/twitter.apis.php';

add_filter( 'eratags/option_key', function(){
    return 'eratags_scoda_options';
});

add_action( 'init', function() {
    

    $instnce = new Scoda_Facebook();

    ?>

    <a href="<?php echo $instnce->authorize_url();?>">Authorize</a>
    <br />
    <a href="<?php echo home_url('?revoke=true'); ?>">Revok</a>

    <?php
    
    $code    = isset( $_REQUEST['code'] ) ?$_REQUEST['code']: false;
    
    $revoke   = isset( $_REQUEST['revoke'] ) ? true: false;
    
    if ( $code ) {
        $instnce->authentication( $code );
    }

    if (  $revoke  ) { 
        $instnce->revoke();
    }
	 /*
	$is_update = $instnce->update_feed( '2913117239003314', array(
		'message' => 'Stack Overflow Website ! For You',
		'link'	  => 'https://stackoverflow.com/' 
	));
	 */
	 $is_update = $instnce->delete_feed( '2913117239003314_2920734364908268' );
	// '684617564898246_5010832442276715'
    echo "<pre>";
    print_r( $is_update  );
    echo "</pre>";

    $options = $instnce->tags_get_option();
 
    echo "<pre>";
    print_r( $options  );
    echo "</pre>";

});