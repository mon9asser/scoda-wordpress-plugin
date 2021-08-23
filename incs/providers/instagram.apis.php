<?php  
/**
 * This file contains all Instagram open graph services
 * 
 * @author Eratags
 * @link eratags.com
 * @since 1.0
 * @package Scoail Coda => ( Scoda )
 * @subpackage incs/providers/instagram 
 * 
 * @uses -> request permission from facebook open graph  
 * Open Graph Facebook Authorization with ig permissions 
 * 
 * @uses First step we have to do is get accounts from facebook open graph 
 * linked Bussiness accounts 
 * 
 * @link https://developers.facebook.com/docs/instagram-basic-display-api/getting-started#before-you-start 
 *  
 * you know instagrm is owned by facebook now so we have to do all steps in the above line in their og document
 * a - A Facebook Developer Account.
 * b - An Instagram account with media 
 * 
 * @api facebook og 
 * ------------------------------------------------------------------------
 * Table of Methods
 * ------------------------------------------------------------------------
 * @method credentials          : Get all credentials
 * @method authorize_url      	: Build Authorize URL
 * @method authentication       : User Accounts Authentication    
 * @method get_feeds          	: Get Feeds From Accounts ( FB Pages - FB Groups - FB Timeline )
 * @method update_feed 			: Post Feed On Accounts ( FB Pages - FB Groups )
 * @method delete_feed   		: Delete Post Feed On Accounts ( FB Pages ) 
 * @method get_follower_counts  : Getting User Member Counts | Follower Counts | Fan Page Counts
 * 
 */


 if ( !class_exists( 'ScodaInstagram' ) ) {
	 
	class ScodaInstagram extends ScodaFacebook {
		
		/*
        * General Options
        */
		private $version;
        private $host;
        public  $social_name;
        private $social_url;
        private $user_id; // Should be linked and integrated with facebook page 
        private $redirect_url;
        private $limit;   

		/**
		* Credentials
        */
        private $app_id;
        private $secret_id; 
		private $api;

		/**
        * Tokens
        */
        private $access_token;

		/**
        * List Of Endpoints and permissions
        */
        private $endpoint;
        private $permissions;

		/**
		 * 
		 * @var $instance Store values
		 */
		private static $instance;

		/** 
		 * @uses Restricts the instantiation of a class to one "single" instance
		 * 
		 * @return ScodaInstagram 
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
				self::$instance = new self; 
			}

			return self::$instance;

		}

		/**
         * @todo Store all data in attributes
        */
		public function __construct() {
			 

			// General Settings 
            $this->social_name = 'instagram'; 
            $this->social_url  = 'https://www.instagram.com';
			$this->api 		   = 'https://api.instagram.com';
            $this->host        = 'https://graph.instagram.com'; // facebook developers own instagram 

			$this->version     = (object) array(
				'old' 		=> '/v1',
				'og'		=> '/v11.0'
			);  
            $this->limit       = 25; // default 

			// Scopes and permissions | Endpoints  
			$this->permissions = array(
				
				// To Get Instagram Account ID
				'user_profile',
				'user_media'
			);

			$this->endpoint    = array(
				'authorize' 	=> '/oauth/authorize',
				'access_token' 	=> '/oauth/access_token',
				'long_access'	=> '/access_token'
  			);

			// Load Options  
            $options                = $this->get_option( $this->social_name );
             

			// IG Keys
			$this->redirect_url     = $this->get_option( 'redirect_url', '' );
            $this->user_id          = isset( $options['user_id'] )? $options['user_id']: '';
            $this->app_id           = isset( $options['app_id'] )? $options['app_id']: '';
            
			// IG Tokens 
			$this->secret_id        = isset( $options['secret_id'] )? $options['secret_id']: '';
            $this->access_token     = isset( $options['access_token'] )? $options['access_token']: '';
			 
		}

		
		/**
		 * Check if facebook already authorized or not 
		 */
		

		/**
         * @todo Get all credentials
         * 
		 * @uses collect all credentials in one array 
		 * 
         * @return array 
         */
        private function credentials( $includes = array() ) {
            
            $credentials = array(
                'app_id'          => $this->app_id,
                'secret_id'       => $this->secret_id, 
                'redirect_uri'    => $this->redirect_url,
                'client_id'       => $this->app_id,
                'client_secret'   => $this->secret_id
            );

            $requested_fields = array(); 

            // exclude uneeded fields
            if ( count( $includes ) ) {
                foreach ( $includes as $include ) {
                    if ( isset( $credentials[$include] ) ) {
                        $requested_fields[$include] = $credentials[$include];
                    }
                }
            }

            return  $requested_fields;

        }

		/**
         * @todo Build Authorize URL 
         * 
		 * @uses generate url to do authorization 
		 * 
         * @return string 
         */
        public function authorization_url() {
             
            // Build Args 
            $args = $this->credentials(array( 'client_id', 'redirect_uri' ));
            
            // Load Permissions 
            if ( count( $this->permissions ) ) {
                $args['scope'] = implode( ',', $this->permissions );
            }

			// Mark is as a response code 
			$args['response_type'] = 'code'; 
			
            // Build The Authorization URL  
            $url = esc_url_raw(add_query_arg( 
                $args,
                $this->api . $this->endpoint['authorize']  
            ));

            return $url;

        }

		/**
		 * Request an access token 
		 * 
		 * @api /oauth/access_token
		 * @api /access_token
		 * 
		 * @param string $code 
		 * 
		 * @return boolean 
		 */
		public function authentication( $code ) {

			// Build api url 
			$api_url = $this->api . $this->endpoint['access_token'];
			 
			// build body request 
			$body = array(
				'client_id'		 => $this->app_id,
				'client_secret'	 => $this->secret_id,
				'code'			 => $code,
				'grant_type' 	 => 'authorization_code',
				'redirect_uri' 	 => $this->redirect_url
			);

			// send http request 
			$request = $this->request( $api_url, array( 'body' => $body ), "POST" );

			if ( $request->error ) {
				return $request;
			}

			// to prevent error else 
			$this->user_id = $request->data->user_id;

			// Now its time to send another request for long live access token 
			$api_url = $this->host . $this->endpoint['long_access'];

			// Build argument of get request 
			$url_params = array(
				'grant_type' 	=> 'ig_exchange_token',
				'client_secret' => $this->secret_id,
				'access_token'	=> $request->data->access_token
			); 
			
			$api_url = add_query_arg( $url_params, $api_url );

			// Send get http request 
			$request = $this->request( $api_url );
			if ( $request->error ) {
				return $request;
			}
			
			// Store long live access token in our atts
			$this->access_token     = $request->data->access_token;
 
			// Store access token in our database 
			$is_stored = $this->set_option( $this->social_name, array(
				'access_token' => sanitize_text_field( $this->access_token ),
				'user_id'	   => sanitize_text_field( $this->user_id )
			));

	 
			if ( $is_stored ) {
				return true;
			}

			// Save
			return false;
		}

		/**
		 * @uses the next step is integrate with facebook og 
		 * @api /:instagram_account_id/:fields 
		 */


		/**
		 * Get Account Information and follower counts 
		 */
		public function get_follower_counts( $fields = array() ){

			// Build api url 
			$api_url = $this->host . '/' . $this->user_id;
			
			// default fields 
			$default = array(  
				'id',
				'username',
				'followers_count'
			);

			// collect all fields together 
			$fields = wp_parse_args( $default, $fields );

			// add access token in url params 
			$params = array(
				'access_token' => $this->access_token,
				'fields' 	   => implode(',', $fields )
			);
			
			// build final url 
			$url = $api_url = add_query_arg( $params, $api_url  );
			 
			// send http request with get methdo 
			$request = $this->request( $url );

			return $request;
		}

	}

 }
 