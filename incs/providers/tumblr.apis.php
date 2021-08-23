<?php
/**
 * This file contains all Tumblr Api services and oauth 1.0
 *
 * @author Eratags
 * @link eratags.com
 * @since 1.0
 * @package Scoail Coda => ( Scoda )
 * @subpackage incs/providers/tumblr
 *
 * ------------------------------------------------------------------------
 * Table of Methods
 * ------------------------------------------------------------------------
 *
 */

if ( !class_exists( 'ScodaTumblr' ) ) {

	class ScodaTumblr extends EratagsUtil {
		
		 /**
		 * @var string $social_name 
		 * The social network name 
		 */
        public $social_name;

		/**
		 * @var string $host
		 * the api host
		 */
        private $host;

		/**
		 * @var array $endpoint
		 * list of endpoints with slashes 
		 */
        private $endpoint;

		/**
		 * @var string $redirect_url
		 * the redirect uri after authorization 
		 */
        private $redirect_url;

		/**
		 * @var string $version
		 * the version of open graph or current api 
		 */
        private $version;

		/**
		 * @var array $slug
		 * contains all slugs of apis
		 */
		private $slug;

        /**
		 * @var string $consumer_key 
		 * The api application key 
		 */
        private $consumer_key;

		/**
		 * @var string $consumer_secret_key 
		 * The secret api application key 
		 */
        private $consumer_secret_key;

		/**
		 * @var string $consumer_access_token 
		 * access token of app 
		 */
        private $consumer_access_token;

		/**
		 * @var string $consumer_secret_access_token 
		 * secret access token of app 
		 */
        private $consumer_secret_access_token;  

		/**
		 * 
		 * @var $instance Store values
		 */
		private static $instance;

		/** 
		 * @uses Restricts the instantiation of a class to one "single" instance
		 * 
		 * @return ScodaTumblr  
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
				self::$instance = new self; 
			}

			return self::$instance;

		}

		/**
		 * @uses store all attributes once instance tumblr class  
		 */
		public function __construct() {
			
			// Needed givens 
			$this->social_name = 'tumblr';
			$this->host = 'https://api.tumblr.com';
			$this->endpoint = array(
				'oauth_token'    => '/oauth/request_token',
				'authorization'	 => '/oauth/authorize',
				'access_tokens'	 => '/oauth/access_token', // not working from provider
				'followers'	     => '/followers',
				'info'			 => '/info',
				'get_posts'		 => '/posts',
				'create_post'	 => '/post',
				'edit_post'		 => '/post/edit',
				'delete_post'	 => '/post/delete'
			);
			$this->redirect_url                 = $this->get_option( 'redirect_url', '' );
			$this->version 						= '/v2';
			$this->slug 						= ( object ) array( 'blog' => '/blog', 'user' => '/user' );

			// database options 
			$options                            = $this->get_option( $this->social_name );
			
			// Load keys from database 
			$this->consumer_key                 = isset( $options['consumer_key'] )? $options['consumer_key']: '';
            $this->consumer_secret_key          = isset( $options['consumer_secret'] )? $options['consumer_secret']: '';
			
			// Get generated access tokens
			$this->consumer_access_token        = isset( $options['access_token'] )? $options['access_token']: '';
            $this->consumer_secret_access_token = isset( $options['secret_access_token'] )? $options['secret_access_token']: '';
		
		}

		/**
		 * Load all credentials from database 
		 * 
		 * @return array contain all keys 
		 */
		private function credentials() {

			$credentials = array(
				'secret_access_token'  => $this->consumer_secret_access_token,
				'access_token'         => $this->consumer_access_token,
				'consumer_key'         => $this->consumer_key,
				'secret_consumer_key'  => $this->consumer_secret_key
			);
  
			return $credentials ;
  
		}

		/**
         * Geneate OAuth Token 
		 * 
         * @api /oauth/request_token
         * 
         * @return string
         */
		public function generate_tokens() {

			// should return array contains all tokens  
			$return = array();

			// Build url
			$basic_url = sprintf(
				'https://www.%1$s.com',
				$this->social_name
			);
			$api_url = $basic_url . $this->endpoint['oauth_token'];
			  
			// Load Needed Creadentials
			$credentials = $this->credentials();
			
			// Add new oauth fields
			$oauth = array( 'oauth_callback' => $this->redirect_url );

			// Build oAuth Signature
			$build = $this->oauth_signature(
				$api_url,
				"POST",
				$credentials,
				array(),
				$oauth
			);
			
			if ( !isset( $build['header'] ) ) {
				return $return;
			}

			// Set Authorization string header  and send http request
			$auth_header = array( 'Authorization' => $build['header'] );
			
			// send http request to get keys 
			$response    = $this->request( $api_url, array( 'headers' => $auth_header ), "POST" );

			if ( ! $response->error ) {

				$data_array = explode( '&', $response->data );

				// Extract Tokens
				$token_array 			=  explode( '=', $data_array[0] );
				$secret_token_array 	=  explode( '=', $data_array[1] );

				$return['oauth_token'] 			= trim( $token_array[1] );
				$return['oauth_secret_token'] 	= trim( $secret_token_array [1] );

			} 

			return $return;
		}

		/**
		 * Generate Authorization Url
		 * 
		 * @api /oauth/authorize
		 * 
		 * @return string
		 */
		public function authorization_url() {

			// Generate New Tokens
			$token = $this->generate_tokens();

			if ( !isset( $token['oauth_token'] ) ) {
				return '';
			}

			// Build Basic Url 
			$basic_url = sprintf(
				'https://www.%1$s.com',
				$this->social_name
			);
			$api_url = $basic_url . $this->endpoint['authorization'];


			// build Auth url
			$api_url = add_query_arg(
				array( 'oauth_token' => $token['oauth_token'] ),
				$api_url
			);

			return esc_url_raw( $api_url );
		}

		
		/**
		 * Get Follower Counts and user data
		 * 
		 * @api /:slug/:blogName/followers
		 * @api /:slug/:blogName/:info
		 * 
		 * @param string $blog_name => name.tumblr.com
		 * 
		 * @return array contains user info and follower counts
		 */
		public function get_blog_info( $blog_name ) {

			$error = new WP_Error();
			
			/**
			 * Get Follower Counts
			 */
			$api_url = $this->host . $this->version . $this->slug->blog . '/' . $blog_name . $this->endpoint['followers'];
			$credentials = $this->credentials();
		 
			// Build oAuth Signature
			$build = $this->oauth_signature(
                $api_url,
                "GET",
                $credentials
            );

			if ( !isset( $build['header'] ) ) {
                $build['header'] = '';
            }
			
			// Build request arguments
			$args = array(
                'headers' => array(
                    'Authorization' => $build['header']
                )
            );

			// http request
			$follower_request = $this->request( $api_url, $args );

			// Handling errors
			if ( $follower_request->error ) {
                $error->add( 'follower_counts',  $follower_request->message );
            }

			/**
			 * Get blog information
			 */
			$api_url = $this->host . $this->version . $this->slug->blog . '/' . $blog_name . $this->endpoint['info'];
			
			// Build request arguments
			$args = array( 'api_key' => $this->consumer_key );
			$api_url = add_query_arg( $args , $api_url );

			$info_request = $this->request( $api_url );
			if ( $info_request->error ) {
				$error->add( 'blog_info', $info_request->message  );
			}

			if ( count( $error->get_error_codes() ) ) {

				return ( object ) array(
					'error' => true,
					'message' => $error->get_error_message()
				);

			}

			// Filter
			return apply_filters( 'eratags/scoda/tumplr/blog_information', $info_request->data, $follower_request->data );

		}
		
		/**
		 * Get Posts of Blog
		 * 
		 * @api /:slug/:blogName/posts => GET
		 * @param string $blog_name => name.tumblr.com
		 * @param string $params => it can be : id, tag, limit, offset, reblog_info, notes_info, filter, before, npf
		 * @param $type => default( all )  text, quote, link, answer, video, audio, photo, chat
		 *
		 * @return array contains all blog feeds 
		 */
		public function get_blog_posts( $blog_name, $type = null, $params = array(), $pagging = array() ) {

			// Get type of data response
			if ( is_null( $type ) ) {
				$type = '';
			} else {
				$type = '/' . $type;
			}

			// Build api url
			$raw_url = $this->host . $this->version . $this->slug->blog . '/' . $blog_name .  $this->endpoint['get_posts'] . $type;

			$collected = array(
				'api_key' => $this->consumer_key
			);

			// Build Pagination data for load more 
			if ( count( $pagging ) ) {
				foreach ( $pagging as $key => $value) {
					$collected[$key] = $value;
				}
			}

			$params  = wp_parse_args( $collected, $params );
			$api_url = add_query_arg( $params, $raw_url );

			// Send HTTP Request
			$request = $this->request( $api_url, $collected );

			if ( $request->error ) {
				return $request;
			}

			$response = (object) array();

			$response->error 	   = $request->error;
			$response->message 	   = $request->message;
			$response->status_code = $request->status_code;

			$response->data = ( object ) array();
			
			// General information 
			$response->data->counts = isset(  $request->data->response['total_posts'] )?  $request->data->response['total_posts']: 0;

			// Getting feeds 
			$response->data->feeds  = apply_filters( 'eratags/scoda/tumplr/blog_feeds', $request->data );

			// Handling Pagination 
			$response->data->paging = array();
			
			if ( isset( $request->data->response['_links'] ) ) {
				if ( isset( $request->data->response['_links']['next'] ) ) {
					$response->data->paging = isset( $request->data->response['_links']['next']['query_params'] ) ? $request->data->response['_links']['next']['query_params']: array();
				}
			}

			return $response;

		}

		/**
		 * Create/Edit new blog post
		 * 
		 * @uses to use update api just add the id field to fields array and the same fields in creation api
		 * 
		 * @param string $blog_name
		 * @param array $fields
		 * 
		 * @return 
		 */
		public function create_post( $blog_name, $fields = array() ) {

			
			$proccess = $this->endpoint['create_post'];
			
			// That's mean we have an update proccess 
			if ( isset( $fields['id'] ) ) {
				$proccess = $this->endpoint['edit_post'];
			}
			
			// Build Basic URL 
			$api_url = $this->host . $this->version .  $this->slug->blog . '/' . $blog_name . $proccess;

            // Load Needed Creadentials and fields
            $credentials = $this->credentials(); 
            
			// create oauth post fields 
			$data_fields = array(
				'type' => 1,
				'data' => $fields
			);

			// create oauth fields 
			$body_fields = $this->oauth_signature( $api_url, "POST", $credentials, $data_fields, array(), true );
			 
			// Build post fields
			$api_fields = array(
				'headers' => array(  
					'Content-Type' => 'application/x-www-form-urlencoded'
				),
				'body' 	  => $body_fields 
			);

			// send http request 
			$request = $this->request( $api_url, $api_fields, "POST" );

			/**
			 * note: the next code show you the success message with 201 and 200 as a status 
			 * code ! that's happend from api provider so we have to check one for 200 and one for 201  
			 * to prvent our error detection
			 */

			// Check if it created with 201 
			if ( $request->status_code === 201 ) {
				
				$request->error = false; 
				$request->status_code = 200;
				$request->data = $request->message;
				$request->message = __('Success');

				if ( isset( $request->data['meta'] ) && isset( $request->data['response'] ) ) {
					$request->data = wp_parse_args( $request->data['meta'], $request->data['response']  );
				}
			} 
			
			// Check if it created with 200 
			if ( $request->status_code === 200 ){
				$request->data = $request->data->response;
			}
			
			return $request;
		}

		/**
		 * Delete blog post
		 * 
		 * @param string $blog_name
		 * @param string $id 
		 * 
		 * @return mixed
		 */
		public function delete_post( $blog_name, $id ) {
			
			// Build api url 
			$api_url = $this->host . $this->version .  $this->slug->blog . '/' . $blog_name . $this->endpoint['delete_post'];

			// Load Needed Creadentials and fields
            $credentials = $this->credentials(); 
            
			// create oauth post fields 
			$data_fields = array(
				'type' => 1,
				'data' => array( 'id' => $id )
			);

			// create oauth fields 
			$body_fields = $this->oauth_signature( $api_url, "POST", $credentials, $data_fields, array(), true );
			 
			// Build post fields
			$api_fields = array(
				'headers' => array(  
					'Content-Type' => 'application/x-www-form-urlencoded'
				),
				'body' 	  => $body_fields 
			);

			// send http request 
			$request = $this->request( $api_url, $api_fields, "POST" );

			if ( ! $request->error ) {
				$request->data = $request->data->response;
			}

			return $request;
		}

		/**
		 * Tubmlr Share Button Generator ( url only )
		 * 
		 * @param string $type => url ( FOR NOW ONLY SITE OR POST URL )
		 */
		public function share_url( $url, $type = 'url') {
			
			$custom_string = '';

			// Case the share data is link only  
			if ( $type === 'url' ) {
				$custom_string = esc_url_raw(sprintf(
					'http://www.tumblr.com/share/link?url=%1$s',
					urlencode_deep( $url )
				));
			}

			return $custom_string;
			
		}

	}

}